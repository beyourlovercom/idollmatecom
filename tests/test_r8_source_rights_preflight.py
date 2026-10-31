#!/usr/bin/env python3
"""Isolated no-network source-evidence gate checks. No Woo import or site access."""
import csv
import hashlib
import json
from pathlib import Path
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts" / "catalog"))
from r8_source_rights_preflight import MANIFEST_FIELDS, validate

COLS = json.loads((ROOT / "scripts/catalog/r8_catalog_policy.json").read_text())["columns"]


class SourceIntakeTests(unittest.TestCase):
    def setUp(self):
        temp = tempfile.TemporaryDirectory()
        self.addCleanup(temp.cleanup)
        self.base = Path(temp.name)
        self.catalog = self.base / "products.csv"
        self.rights = self.base / "rights.csv"
        self.snapshot = self.base / "snapshot.json"
        self.item = dict.fromkeys(COLS, "")
        self.item.update({
            "sku": "IDM-ORIG-10001",
            "row_type": "simple",
            "title": "First Owned Source Sample",
            "slug": "owned-source-model-10001",
            "category_slug": "mini-dolls",
            "material": "tpe",
            "fulfillment_mode": "factory_mto",
            "dispatch_region": "CN",
            "lead_days": "21",
            "price_usd": "599.00",
            "content_origin": "original",
            "source_ref": "SRC-00001",
            "product_status": "draft"
        })
        self.evidence = {
            "source_ref": "SRC-00001", "content_origin": "original",
            "rights_basis": "original_owned",
            "evidence_record_id": "EVIDENCE-00001",
            "evidence_sha256": "a" * 64,
            "reviewed_by": "CatalogReviewer",
            "reviewed_date": "2026-10-25",
            "review_status": "approved"
        }
        self._csv(self.catalog, COLS, [self.item])
        self._csv(self.rights, MANIFEST_FIELDS, [self.evidence])
        self.snapshot.write_text(json.dumps({
            "site": "https://dev.idollmate.com", "blog_public": "0",
            "coming_soon": "yes",
            "products": [{
                "id": 13, "sku": "IDM-R7-QA-20261028",
                "slug": "legacy-draft",
                "status": "draft", "type": "simple",
                "integrity_sha256": hashlib.sha256(b"legacy").hexdigest()
            }],
            "categories": ["mini-dolls", "uncategorized"],
            "brands": [],
            "attributes": {"pa_material": ["tpe", "silicone"]}
        }))

    def _csv(self, path, fields, items):
        with path.open("w", newline="", encoding="utf-8") as f:
            w = csv.DictWriter(f, fieldnames=fields)
            w.writeheader()
            w.writerows(items)

    def check(self):
        return validate(self.catalog, self.rights, self.snapshot)

    def test_metadata_complete_only_owner_review_not_import_approval(self):
        result = self.check()
        self.assertTrue(result["ready_for_independent_owner_evidence_review"], result["issues"])
        self.assertFalse(result["legal_rights_independently_proven"])
        self.assertFalse(result["production_import_approved"])
        self.assertFalse(result["dev_write_performed"])

    def test_missing_rights_manifest_rejected(self):
        self.rights.unlink()
        result = self.check()
        self.assertFalse(result["ready_for_independent_owner_evidence_review"])
        self.assertIn("RIGHTS_MANIFEST_MISSING_OR_UNSAFE", [x["code"] for x in result["issues"]])

    def test_synthetic_qa_sku_rejected_even_with_forged_approval(self):
        self.item["sku"] = "IDM-R8-SYNTH-0001"
        self._csv(self.catalog, COLS, [self.item])
        result = self.check()
        self.assertFalse(result["ready_for_independent_owner_evidence_review"])
        self.assertIn("QA_OR_SYNTHETIC_RECORD_FORBIDDEN", [x["code"] for x in result["issues"]])

    def test_unreviewed_evidence_rejected(self):
        self.evidence["review_status"] = "pending"
        self._csv(self.rights, MANIFEST_FIELDS, [self.evidence])
        self.assertIn("RIGHTS_REVIEW_NOT_APPROVED", [x["code"] for x in self.check()["issues"]])

    def test_mismatch_source_origin_rejected(self):
        self.evidence["content_origin"] = "licensed"
        self._csv(self.rights, MANIFEST_FIELDS, [self.evidence])
        self.assertIn("CATALOG_RIGHTS_ORIGIN_MISMATCH", [x["code"] for x in self.check()["issues"]])

    def test_invalid_private_digest_rejected(self):
        self.evidence["evidence_sha256"] = "invalid"
        self._csv(self.rights, MANIFEST_FIELDS, [self.evidence])
        self.assertIn("EVIDENCE_DIGEST_MISSING", [x["code"] for x in self.check()["issues"]])

    def test_duplicate_manifest_refs_rejected(self):
        self._csv(self.rights, MANIFEST_FIELDS, [self.evidence, self.evidence])
        self.assertIn("DUPLICATE_OR_EMPTY_SOURCE_REF", [x["code"] for x in self.check()["issues"]])

    def test_real_existing_sku_conflict_blocks_readiness(self):
        self.item["sku"] = "IDM-R7-QA-20261028"
        self._csv(self.catalog, COLS, [self.item])
        r = self.check()
        self.assertFalse(r["ready_for_independent_owner_evidence_review"])
        self.assertIn("WOO_SKU_TAXONOMY_OR_CSV_PREVIEW_BLOCKED", [x["code"] for x in r["issues"]])

    def test_2000_synthetic_errors_do_not_hide_missing_evidence_blocker(self):
        self.rights.unlink()
        synthetic = []
        for i in range(2000):
            row = self.item.copy()
            row["sku"] = f"IDM-R8-SYNTH-{i:04d}"
            row["slug"] = f"qa-synthetic-model-{i:04d}"
            row["source_ref"] = f"QA-SYNTHETIC-{i:04d}"
            synthetic.append(row)
        self._csv(self.catalog, COLS, synthetic)
        r = self.check()
        self.assertFalse(r["ready_for_independent_owner_evidence_review"])
        self.assertEqual(r["issue_code_counts"]["QA_OR_SYNTHETIC_RECORD_FORBIDDEN"], 2000)
        self.assertEqual(r["issue_code_counts"]["RIGHTS_MANIFEST_MISSING_OR_UNSAFE"], 1)
        self.assertEqual(r["rights_metadata_issue_count"], sum(r["issue_code_counts"].values()))
        self.assertLessEqual(len(r["issues"]), 30)
        self.assertTrue(r["issues_truncated"])
        self.assertFalse(r["dev_write_performed"])


if __name__ == "__main__":
    unittest.main()
