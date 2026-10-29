#!/usr/bin/env python3
"""R8-C: isolated Python-only Preview/Confirm/Rollback contract tests."""
import csv
import hashlib
import importlib.util
import json
from pathlib import Path
import tempfile
import unittest

DIR = Path(__file__).resolve().parents[1] / "scripts/catalog"
import sys
sys.path.insert(0, str(DIR))
spec = importlib.util.spec_from_file_location("r8_batch", DIR / "r8_catalog_batch.py")
batch = importlib.util.module_from_spec(spec)
spec.loader.exec_module(batch)
COLS = json.loads((DIR / "r8_catalog_policy.json").read_text())["columns"]


def product(sku="IDM-R8-EX001", **kw):
    r = dict.fromkeys(COLS, "")
    r.update({"sku": sku, "row_type": "simple", "title": "Original Example Product",
              "slug": "original-" + sku.lower(), "category_slug": "full-body-dolls",
              "material": "tpe", "height_cm": "155", "weight_kg": "40",
              "fulfillment_mode": "factory_mto", "dispatch_region": "CN",
              "lead_days": "28", "price_usd": "999.00", "stock_quantity": "",
              "content_origin": "original", "source_ref": "SUP-IDM-001",
              "product_status": "draft"})
    r.update(kw)
    return r


def state():
    return {"site": "https://dev.idollmate.com", "blog_public": "0",
            "coming_soon": "yes",
            "products": [{"id": 13, "sku": "IDM-R7-QA-20261028",
                          "slug": "r7-qa-only", "type": "simple", "status": "draft",
                          "integrity_sha256": hashlib.sha256(b"frozen-r7-original").hexdigest()}],
            "categories": ["uncategorized", "full-body-dolls", "mini-dolls"],
            "brands": [], "attributes": {"pa_material": ["tpe", "silicone", "hybrid"],
                                         "pa_size_code": []}}


class R8BatchTests(unittest.TestCase):
    def setUp(self):
        t = tempfile.TemporaryDirectory()
        self.addCleanup(t.cleanup)
        self.base = Path(t.name)
        self.csv = self.base / "items.csv"
        self.planfile = self.base / "plan.json"
        self.ledger = self.base / "ledger.json"
        self.snapshotfile = self.base / "snapshot.json"
        self.state = state()
        self._csv([product()])
        self._snapshot()

    def _csv(self, rows):
        with self.csv.open("w", encoding="utf-8", newline="") as f:
            writer = csv.DictWriter(f, COLS)
            writer.writeheader()
            writer.writerows(rows)

    def _snapshot(self):
        self.snapshotfile.write_text(json.dumps(self.state))

    def preview(self):
        return batch.preview(self.csv, self.state)

    def test_preview_counts_exact_hash_no_writes(self):
        p = self.preview()
        self.assertTrue(p["ready_for_owner_review"], p["blockers"])
        self.assertEqual(p["insert_draft"], 1)
        self.assertEqual(p["update_existing"], 0)
        self.assertEqual(p["publish"], 0)
        self.assertFalse(p["dev_write_performed"])
        self.assertFalse(self.ledger.exists())

    def test_real_dev_uninstalled_categories_and_attrs_block(self):
        self.state["categories"] = ["uncategorized"]
        self.state["attributes"] = {}
        p = self.preview()
        self.assertFalse(p["ready_for_owner_review"])
        self.assertIn("CATEGORY_NOT_INSTALLED", [b["code"] for b in p["blockers"]])
        self.assertIn("ATTRIBUTE_TERM_MISSING", [b["code"] for b in p["blockers"]])

    def test_existing_qa_sku_conflict(self):
        self._csv([product("IDM-R7-QA-20261028")])
        p = self.preview()
        self.assertFalse(p["ready_for_owner_review"])
        self.assertGreater(p["error_count"], 0)

    def test_existing_slug_conflict(self):
        self._csv([product(slug="r7-qa-only")])
        self.assertIn("SLUG_CONFLICT", [b["code"] for b in self.preview()["blockers"]])

    def test_brand_term_not_installed(self):
        self._csv([product(brand_slug="original-maker")])
        self.assertIn("BRAND_NOT_INSTALLED", [b["code"] for b in self.preview()["blockers"]])

    def test_confirmation_rejects_wrong_phrase_and_stale_csv(self):
        p = self.preview()
        with self.assertRaisesRegex(ValueError, "phrase"):
            batch.confirm(self.csv, self.state, p, "sure", self.ledger)
        self.assertFalse(self.ledger.exists())
        self._csv([product(price_usd="1099.00")])
        with self.assertRaisesRegex(ValueError, "STALE_PLAN"):
            batch.confirm(self.csv, self.state, p, p["approval_phrase"], self.ledger)

    def test_confirmation_rejects_changed_snapshot(self):
        p = self.preview()
        self.state["products"].append({"id": 20, "sku": "NEW-IDM",
                                       "slug": "new", "type": "simple", "status": "draft",
                                       "integrity_sha256": hashlib.sha256(b"unrelated-added").hexdigest()})
        with self.assertRaisesRegex(ValueError, "STALE_PLAN"):
            batch.confirm(self.csv, self.state, p, p["approval_phrase"], self.ledger)

    def test_apply_idempotency_and_batch_scoped_rollback(self):
        p = self.preview()
        r = batch.confirm(self.csv, self.state, p, p["approval_phrase"], self.ledger)
        self.assertEqual(r["status"], "applied")
        self.assertEqual(r["created"], 1)
        self.assertFalse(r["dev_write_performed"])
        self.assertEqual(batch.confirm(self.csv, self.state, p, p["approval_phrase"],
                                       self.ledger)["status"], "already_applied")
        self.assertEqual(len(batch.ledger_from(self.ledger)["products"]), 1)
        with self.assertRaisesRegex(ValueError, "phrase"):
            batch.rollback(p, "wrong", self.ledger)
        rolled = batch.rollback(p, "R8 ROLLBACK " + p["plan_id"], self.ledger)
        self.assertEqual(rolled["removed"], 1)
        self.assertFalse(batch.ledger_from(self.ledger)["products"])
        self.assertEqual(batch.rollback(p, "R8 ROLLBACK " + p["plan_id"],
                                        self.ledger)["status"], "already_rolled_back")
        with self.assertRaisesRegex(ValueError, "Already rolled back"):
            batch.confirm(self.csv, self.state, p, p["approval_phrase"], self.ledger)

    def test_rollback_refuses_modified_product_and_no_partial_deletion(self):
        p = self.preview()
        batch.confirm(self.csv, self.state, p, p["approval_phrase"], self.ledger)
        data = batch.ledger_from(self.ledger)
        data["products"]["idm-r8-ex001"]["status"] = "publish"
        batch.save(self.ledger, data)
        with self.assertRaisesRegex(ValueError, "modified"):
            batch.rollback(p, "R8 ROLLBACK " + p["plan_id"], self.ledger)
        self.assertIn("idm-r8-ex001", batch.ledger_from(self.ledger)["products"])

    def test_rollback_unknown_batch_does_not_touch(self):
        with self.assertRaisesRegex(ValueError, "Unknown batch"):
            batch.rollback(self.preview(), "R8 ROLLBACK " + self.preview()["plan_id"],
                           self.ledger)

    def test_wrong_site_and_noindex_block(self):
        self.state["site"] = "https://idollmate.com"
        self._snapshot()
        with self.assertRaisesRegex(ValueError, "protected"):
            batch.snapshot(self.snapshotfile)
        self.state["site"] = "https://dev.idollmate.com"
        self.state["blog_public"] = "1"
        self._snapshot()
        with self.assertRaisesRegex(ValueError, "protected"):
            batch.snapshot(self.snapshotfile)

    def test_legacy_weak_snapshot_denied_before_approval(self):
        del self.state["products"][0]["integrity_sha256"]
        self._snapshot()
        with self.assertRaisesRegex(ValueError, "weak snapshot"):
            batch.snapshot(self.snapshotfile)
        with self.assertRaisesRegex(ValueError, "weak snapshot"):
            self.preview()

    def test_preview_cli_writes_local_only(self):
        self.assertEqual(0, batch.main(["preview", "--csv", str(self.csv),
                                        "--snapshot", str(self.snapshotfile),
                                        "--plan", str(self.planfile)]))
        self.assertTrue(self.planfile.exists())
        self.assertFalse(self.ledger.exists())

    def test_csv_with_2000_rows_bounded(self):
        self._csv([product(f"IDM-R8-{i:04d}", slug=f"idm-r8-model-{i:04d}")
                   for i in range(1, 2001)])
        p = self.preview()
        self.assertTrue(p["ready_for_owner_review"], p["blockers"][:3])
        self.assertEqual(p["insert_draft"], 2000)
        self.assertLessEqual(len(p["actions_preview"]), 25)

    def test_corrupt_ledger_refused(self):
        self.ledger.write_text('{"site":"https://idollmate.com","products":{},"batches":{}}')
        with self.assertRaisesRegex(ValueError, "simulator"):
            batch.ledger_from(self.ledger)


if __name__ == "__main__":
    unittest.main()
