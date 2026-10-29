#!/usr/bin/env python3
"""No WordPress, no network; run python3 -m unittest tests/test_r8_catalog_preflight.py."""
import csv
import hashlib
import importlib.util
import json
from pathlib import Path
import tempfile
import time
import unittest

SRC = Path(__file__).resolve().parents[1] / "scripts/catalog/r8_catalog_preflight.py"
spec = importlib.util.spec_from_file_location("r8_preflight", SRC)
pre = importlib.util.module_from_spec(spec)
spec.loader.exec_module(pre)
COLS = json.loads(SRC.with_name("r8_catalog_policy.json").read_text())["columns"]


def product(sku="IDM-0001", **kw):
    row = dict.fromkeys(COLS, "")
    row.update({
        "sku": sku, "row_type": "simple", "title": "Original Doll " + sku,
        "slug": "original-doll-" + sku.lower().replace("_", "-").replace(".", "-"),
        "category_slug": "full-body-dolls", "material": "tpe",
        "height_cm": "155", "weight_kg": "32",
        "fulfillment_mode": "factory_mto", "dispatch_region": "CN",
        "lead_days": "21", "price_usd": "999.00",
        "content_origin": "original", "source_ref": "SUP-" + sku,
        "product_status": "draft"
    })
    row.update(kw)
    return row


class PreflightTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.addCleanup(self.tmp.cleanup)
        self.path = Path(self.tmp.name) / "batch.csv"

    def csv(self, rows, cols=COLS):
        with self.path.open("w", encoding="utf-8", newline="") as f:
            writer = csv.DictWriter(f, fieldnames=cols, extrasaction="ignore")
            writer.writeheader()
            writer.writerows(rows)
        return self.path

    def run_rows(self, rows, **kwargs):
        return pre.preflight(self.csv(rows), **kwargs)

    def reasons(self, result):
        return "\n".join(e["message"] for e in result["errors"])

    def test_simple_draft_original_pass_and_no_mutation(self):
        f = self.csv([product()])
        content = f.read_bytes()
        r = pre.preflight(f)
        self.assertTrue(r["can_import"], self.reasons(r))
        self.assertTrue(r["readonly"])
        self.assertEqual(r["total_rows"], 1)
        self.assertEqual(r["source_sha256"], hashlib.sha256(content).hexdigest())
        self.assertEqual(content, f.read_bytes())

    def test_variation_group_and_material_distinct(self):
        parent = product("IDM-PARENT", row_type="variable", price_usd="", stock_quantity="")
        tpe = product("IDM-TPE", row_type="variation", parent_sku="IDM-PARENT",
                      title="", slug="", category_slug="", brand_slug="",
                      fulfillment_mode="", dispatch_region="", lead_days="",
                      height_cm="", weight_kg="", material="tpe", price_usd="999.00")
        sil = dict(tpe, sku="IDM-SIL", material="silicone", price_usd="1099.00",
                   source_ref="SUP-IDM-SIL")
        r = self.run_rows([parent, tpe, sil])
        self.assertTrue(r["can_import"], self.reasons(r))
        self.assertEqual(r["type_counts"]["variation"], 2)

    def test_duplicate_sku_rejected(self):
        p = product()
        r = self.run_rows([p, dict(p)])
        self.assertFalse(r["can_import"])
        self.assertIn("Duplicate SKU", self.reasons(r))

    def test_existing_sku_conflict(self):
        r = self.run_rows([product()], existing_skus={"IDM-0001"})
        self.assertIn("existing Woo inventory", self.reasons(r))

    def test_orphan_variation_rejected(self):
        row = product("IDM-CHILD", row_type="variation", parent_sku="BAD-PARENT",
                      title="", slug="", category_slug="", brand_slug="",
                      fulfillment_mode="", dispatch_region="", lead_days="",
                      height_cm="", weight_kg="")
        self.assertIn("parent missing", self.reasons(self.run_rows([row])))

    def test_duplicate_variation_material_and_size_rejected(self):
        parent = product("IDM-PARENT", row_type="variable", price_usd="", stock_quantity="")
        base = product("IDM-A", row_type="variation", parent_sku="IDM-PARENT",
                       title="", slug="", category_slug="",
                       fulfillment_mode="", dispatch_region="", lead_days="",
                       height_cm="", weight_kg="")
        other = dict(base, sku="IDM-B", source_ref="SUP-IDM-B")
        self.assertIn("Duplicate parent variation", self.reasons(self.run_rows([parent,base,other])))

    def test_price_and_local_stock(self):
        bad = product(price_usd="-1", fulfillment_mode="local_stock", stock_quantity="")
        r = self.run_rows([bad])
        self.assertIn("positive USD price", self.reasons(r))
        self.assertIn("Local-stock simple", self.reasons(r))
        okay = product(fulfillment_mode="local_stock", stock_quantity="9", price_usd="999.95")
        self.assertTrue(self.run_rows([okay])["can_import"])

    def test_factory_mto_does_not_invent_stock(self):
        self.assertIn("must be blank", self.reasons(self.run_rows([product(stock_quantity="5")])))

    def test_unapproved_category_publish_and_provenance(self):
        r = self.run_rows([product(category_slug="copied-category", product_status="publish",
                                   content_origin="unverified", source_ref="https://competitor.invalid")])
        self.assertIn("Category not in", self.reasons(r))
        self.assertIn("must remain draft", self.reasons(r))
        self.assertIn("content_origin", self.reasons(r))
        self.assertIn("source", self.reasons(r))

    def test_reference_url_only_no_image_fetch(self):
        url = "https://www.yourdoll.com/wp-content/uploads/2025/02/example.webp"
        r = self.run_rows([product(reference_image_url=url)])
        self.assertTrue(r["can_import"], self.reasons(r))
        self.assertGreater(r["warning_count"],0)
        for bad in [
            "https://evil.example/wp-content/uploads/a.png",
            "http://www.yourdoll.com/wp-content/uploads/a.webp",
            "https://www.yourdoll.com/wp-content/uploads/a.jpg?token=1",
            "https://www.yourdoll.com/wp-content/uploads/../secret.jpg",
            "https://www.yourdoll.com:8080/wp-content/uploads/a.jpg",
            "https://www.yourdoll.com/wp-content/uploads/a.svg",
        ]:
            with self.subTest(url=bad):
                self.assertFalse(pre.valid_reference_image(bad))

    def test_formula_like_title_rejected(self):
        self.assertIn("Unsafe or overlong field: title",
                      self.reasons(self.run_rows([product(title="=1+1")])))

    def test_header_schema_and_empty_batch(self):
        self.assertIn("schema mismatch", self.reasons(pre.preflight(self.csv([],cols=["sku"]))))
        self.assertFalse(self.run_rows([])["can_import"])

    def test_2000_rows_fast_and_bounded(self):
        rows=[product("IDM-%04d" % i) for i in range(1, 2001)]
        before=time.monotonic()
        r=self.run_rows(rows)
        elapsed=time.monotonic()-before
        self.assertTrue(r["can_import"], self.reasons(r))
        self.assertEqual(r["total_rows"], 2000)
        self.assertLessEqual(len(r["preview"]),12)
        self.assertLess(elapsed, 10.0, f"2,000 rows too slow: {elapsed:.2f}s")

    def test_csv_cli_report_no_site_mutation(self):
        f=self.csv([product()])
        out=Path(self.tmp.name)/"preview.json"
        self.assertEqual(0,pre.main([str(f),"--report",str(out)]))
        self.assertTrue(out.is_file())
        self.assertTrue(json.loads(out.read_text())["readonly"])


if __name__ == "__main__":
    unittest.main()
