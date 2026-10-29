<?php
/**
 * iDollmate R8-C source-only WP-CLI guarded Woo Draft import/rollback adapter.
 *
 * Approved QA Dev Canary initially attempted on 2026-10-29. Four QA products
 * were created, but a previously unscoped snapshot include overwrote the
 * batch ID, causing an in-progress journal. See scoped recovery script.
 * Never Production; exact Owner authorization and fresh live checks required.
 */
defined('ABSPATH') || exit;
if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('R8-C CLI only');
}
if (get_option('siteurl') !== 'https://dev.idollmate.com'
    || (string)get_option('blog_public') !== '0'
    || (string)get_option('woocommerce_coming_soon') !== 'yes'
    || !class_exists('WooCommerce')
    || get_woocommerce_currency() !== 'USD') {
    throw new RuntimeException('R8-C blocked: protected Dev WooCommerce only');
}
$action = getenv('R8_ACTION');
if (!in_array($action, array('apply', 'rollback'), true)) {
    throw new RuntimeException('R8-C action must be apply or rollback');
}
$plan_path = getenv('R8_PLAN_PATH');
$csv_path = getenv('R8_CSV_PATH');
$snapshot_path = getenv('R8_SNAPSHOT_PATH');
$script_dir = __DIR__;
foreach (array($plan_path,$snapshot_path) as $path) {
    if (!is_string($path) || !is_file($path) || is_link($path)) {
        throw new RuntimeException('R8-C missing or symlink input');
    }
}
$plan = json_decode(file_get_contents($plan_path), true);
$source_snapshot = json_decode(file_get_contents($snapshot_path), true);
if (!is_array($plan) || $plan['schema'] !== 'r8-c-v1'
    || $plan['site'] !== 'https://dev.idollmate.com'
    || $plan['ready_for_owner_review'] !== true
    || $plan['blocker_count'] !== 0
    || $plan['error_count'] !== 0 || $plan['simulation_only'] !== true
    || $plan['update_existing'] !== 0 || $plan['publish'] !== 0
    || $plan['delete_existing'] !== 0) {
    throw new RuntimeException('R8-C plan is blocked, stale format or not draft-only');
}
$id = (string)$plan['plan_id'];
if (!preg_match('/^[a-f0-9]{24}$/D', $id)) {
    throw new RuntimeException('R8-C invalid batch id');
}
$policy_file = __DIR__.'/r8_catalog_policy.json';
if (!is_file($policy_file)
    || !hash_equals((string)$plan['policy_sha256'], hash_file('sha256', $policy_file))) {
    throw new RuntimeException('R8-C approved policy changed; new Preview required');
}
$calculated_id = substr(hash('sha256',
    'r8-c-v1:'.$plan['source_sha256'].':'.$plan['snapshot_sha256'].':'.$plan['policy_sha256']),0,24);
if (!hash_equals($id, $calculated_id)) {
    throw new RuntimeException('R8-C plan checksum mismatch');
}
function idm_r8_canonical($value) {
    // Preserve JSON empty objects as {}, not []: Woo's attribute dictionary
    // is empty on the current Dev baseline and Python digest must match.
    if (is_object($value)) {
        $props = get_object_vars($value);
        ksort($props, SORT_STRING);
        $out = new stdClass();
        foreach ($props as $key=>$element) { $out->{$key} = idm_r8_canonical($element); }
        return $out;
    }
    if (!is_array($value)) { return $value; }
    foreach ($value as $key=>$element) { $value[$key] = idm_r8_canonical($element); }
    return $value;
}
if (getenv('R8_OWNER_DEV_WRITE_PASS') !== 'R8-OWNER-PASS-'.$id) {
    throw new RuntimeException('R8-C independent Owner Dev write gate NOT granted');
}
$required_phrase = $action === 'apply'
    ? 'R8 CONFIRM '.$id.' '.$plan['total_rows']
    : 'R8 ROLLBACK '.$id;
if (getenv('R8_CONFIRM_PHRASE') !== $required_phrase) {
    throw new RuntimeException('R8-C typed confirmation phrase mismatch');
}
if (!is_array($source_snapshot) || $source_snapshot['site'] !== 'https://dev.idollmate.com') {
    throw new RuntimeException('R8-C wrong snapshot');
}
$snapshot_object = json_decode(file_get_contents($snapshot_path), false);
$snapshot_canonical = wp_json_encode(idm_r8_canonical($snapshot_object),
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
if (!hash_equals((string)$plan['snapshot_sha256'], hash('sha256', $snapshot_canonical))) {
    throw new RuntimeException('R8-C snapshot digest mismatch');
}
if ($action === 'apply') {
    if (!is_string($csv_path) || !is_file($csv_path) || is_link($csv_path)
        || filesize($csv_path) > 20000000
        || !hash_equals($plan['source_sha256'], hash_file('sha256', $csv_path))) {
        throw new RuntimeException('R8-C source CSV changed/missing');
    }
    if ($plan['total_rows'] < 1 || $plan['total_rows'] > 25) {
        throw new RuntimeException('R8-C first-Dev canary limit: 1-25 rows only');
    }
}
$batch_key = 'idm_r8_batch_'.$id;
$lock_key = 'idm_r8_lock_'.$id;
if (!add_option($lock_key, (string)time(), '', false)) {
    throw new RuntimeException('R8-C batch already locked; manual recovery required');
}
$lock_acquired = true;
function idm_r8_rows($path) {
    $f = fopen($path, 'rb');
    if (!$f) { throw new RuntimeException('Cannot read CSV'); }
    $expected = array(
        'sku','row_type','parent_sku','title','slug','category_slug','brand_slug',
        'material','size_code','height_cm','weight_kg','fulfillment_mode',
        'dispatch_region','lead_days','price_usd','stock_quantity',
        'content_origin','source_ref','reference_image_url','product_status'
    );
    $header = fgetcsv($f);
    if ($header !== $expected) { fclose($f); throw new RuntimeException('R8-C CSV header mismatch'); }
    $out = array();
    while (($values = fgetcsv($f)) !== false) {
        if (count($values) !== count($expected)) {
            fclose($f); throw new RuntimeException('R8-C CSV row malformed');
        }
        $row = array_combine($expected, array_map('trim', $values));
        $out[] = $row;
        if (count($out) > 25) { fclose($f); throw new RuntimeException('R8-C too many rows'); }
    }
    fclose($f);
    return $out;
}
function idm_r8_term($taxonomy, $slug) {
    if (!taxonomy_exists($taxonomy)) {
        throw new RuntimeException('R8-C taxonomy not installed: '.$taxonomy);
    }
    $term = get_term_by('slug', $slug, $taxonomy);
    if (!$term || is_wp_error($term)) {
        throw new RuntimeException('R8-C term not installed: '.$taxonomy.':'.$slug);
    }
    return (int)$term->term_id;
}
function idm_r8_mark_and_save($p, $row, $batch_id) {
    $p->update_meta_data('_idm_r8_batch_id', $batch_id);
    $p->update_meta_data('_idm_r8_source_id', $row['source_ref']);
    if ($row['reference_image_url'] !== '') {
        $p->update_meta_data('_idm_reference_image_url', $row['reference_image_url']);
    }
    $id = $p->save();
    if (!$id) { throw new RuntimeException('R8-C Woo save returned no product ID'); }
    $fresh = wc_get_product($id);
    $fingerprint = idm_r8_fingerprint($fresh);
    update_post_meta($id, '_idm_r8_import_fingerprint', $fingerprint);
    if (get_post_meta($id, '_idm_r8_import_fingerprint', true) !== $fingerprint) {
        throw new RuntimeException('R8-C fingerprint not persisted; manual reconcile required');
    }
    return (int)$id;
}
function idm_r8_batch_ids($batch) {
    global $wpdb;
    return array_map('intval', $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s",
        '_idm_r8_batch_id', $batch
    )));
}
function idm_r8_assert_owned_products_unchanged($ids, $batch_id) {
    global $wpdb;
    foreach ($ids as $pid) {
        $p = wc_get_product($pid);
        $parent = $p && $p->get_type() === 'variation'
            ? wc_get_product($p->get_parent_id()) : null;
        if (!$p || (string)$p->get_meta('_idm_r8_batch_id', true) !== $batch_id
            || ($p->get_type() !== 'variation' && $p->get_status() !== 'draft')
            || ($p->get_type() === 'variation'
                && (!$parent || $parent->get_status() !== 'draft'))
            || get_post_meta($pid, '_idm_r8_import_fingerprint', true)
                !== idm_r8_fingerprint($p)) {
            throw new RuntimeException('R8-C owned product changed; action blocked');
        }
        $references = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta
                WHERE meta_key IN ('_product_id','_variation_id') AND meta_value=%s",
            (string)$pid
        ));
        if ((int)$references > 0) {
            throw new RuntimeException('R8-C owned product referenced in order; action blocked');
        }
    }
}
/* Durable in-progress marker: if PHP dies between save() and journal append,
 * preserve which SKU was being created, never blindly retry/delete it. */
function idm_r8_journal_pending($key, &$journal, $sku) {
    $journal['pending_sku'] = $sku;
    if (!update_option($key, $journal, false)
        || get_option($key) !== $journal) {
        throw new RuntimeException('R8-C pending SKU journal write not durable');
    }
}
function idm_r8_journal_record($key, &$journal, $created_id) {
    $journal['ids'][] = $created_id;
    $journal['created_count']++;
    unset($journal['pending_sku']);
    if (!update_option($key, $journal, false)
        || get_option($key) !== $journal) {
        throw new RuntimeException('R8-C created ID journal write not durable');
    }
}
try {
    /* The included snapshot uses $id as a loop variable. Include it only
     * within an isolated function, never this batch's outer variable scope. */
    require_once __DIR__.'/r8_woo_batch_guards.php';
    $live_snapshot = idm_r8_capture_snapshot_isolated(
        $script_dir.'/r8_woo_dev_snapshot.php'
    );
    if ($id !== (string)$plan['plan_id']) {
        throw new RuntimeException('R8-C batch ID changed during snapshot read');
    }
    /* Reject a weak legacy Preview before any possible import write. */
    idm_r8_assert_live_baseline(
        $source_snapshot, $source_snapshot, $id,
        static function($live_id) { return ''; }
    );
    $journal = get_option($batch_key);
    $tagged_ids = idm_r8_batch_ids($id);
    if ($action === 'apply') {
        if ($journal !== false || $tagged_ids) {
            if (!is_array($journal) || ($journal['site'] ?? null) !== 'https://dev.idollmate.com'
                || ($journal['csv_sha256'] ?? null) !== $plan['source_sha256']) {
                throw new RuntimeException('R8-C batch replay provenance mismatch');
            }
            $added = idm_r8_assert_live_baseline(
                $source_snapshot, $live_snapshot, $id,
                static function($live_id) {
                    $p = wc_get_product($live_id);
                    return $p ? (string)$p->get_meta('_idm_r8_batch_id', true) : '';
                }
            );
            idm_r8_assert_applied_batch_replay($journal, $tagged_ids, $added, (int)$plan['total_rows']);
            idm_r8_assert_owned_products_unchanged($tagged_ids, $id);
            echo 'R8_DEV_APPLY_ALREADY_COMPLETE batch='.$id.' created=0'.PHP_EOL;
            return;
        }
        if ($live_snapshot !== $source_snapshot) {
            throw new RuntimeException('R8-C LIVE SNAPSHOT CHANGED; obtain new Preview');
        }
        $rows = idm_r8_rows($csv_path);
        if (count($rows) !== (int)$plan['total_rows']) {
            throw new RuntimeException('R8-C CSV row count mismatch');
        }
        /* Perform full duplicate/term/option sanity precheck BEFORE first write. */
        $unique = array();
        $parent_rows = array();
        $children = array();
        foreach ($rows as $row) {
            $sku = $row['sku']; $k = strtolower($sku);
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$/D', $sku)
                || isset($unique[$k]) || wc_get_product_id_by_sku($sku)
                || $row['product_status'] !== 'draft'
                || !in_array($row['row_type'], array('simple','variable','variation'), true)
                || !in_array($row['content_origin'], array('original','licensed'), true)
                || $row['reference_image_url'] !== '') {
                /* First canary refuses ALL third-party URLs, including reference-only,
                 * to ensure no unauthorized customer asset can enter the Woo site. */
                throw new RuntimeException('R8-C unauthorized SKU/status/reference: '.$sku);
            }
            $unique[$k] = true;
            if ($row['row_type'] === 'variation') {
                $children[] = $row;
                if ($row['material']) { idm_r8_term('pa_material', $row['material']); }
                if ($row['size_code']) { idm_r8_term('pa_size_code', $row['size_code']); }
            } else {
                idm_r8_term('product_cat', $row['category_slug']);
                if ($row['brand_slug']) { idm_r8_term('product_brand', $row['brand_slug']); }
                if ($row['material']) { idm_r8_term('pa_material', $row['material']); }
                $parent_rows[] = $row;
            }
            if ($row['row_type'] !== 'variable'
                && (!is_numeric($row['price_usd']) || (float)$row['price_usd'] <= 0)) {
                throw new RuntimeException('R8-C missing/invalid sale price');
            }
        }
        $parent_skus = array();
        foreach ($parent_rows as $row) {
            $parent_skus[strtolower($row['sku'])] = $row;
        }
        foreach ($children as $row) {
            $parent = $parent_skus[strtolower($row['parent_sku'])] ?? null;
            if (!$parent || $parent['row_type'] !== 'variable') {
                throw new RuntimeException('R8-C orphan variation');
            }
        }
        $journal = array('state'=>'in_progress', 'site'=>'https://dev.idollmate.com',
                         'csv_sha256'=>$plan['source_sha256'], 'ids'=>array(),
                         'created_count'=>0);
        if (!add_option($batch_key, $journal, '', false)) {
            throw new RuntimeException('R8-C journal creation failed; no products written');
        }
        $parent_created = array();
        foreach ($parent_rows as $row) {
            $p = $row['row_type'] === 'variable' ? new WC_Product_Variable() : new WC_Product_Simple();
            $p->set_name($row['title']);
            $p->set_slug($row['slug']);
            $p->set_sku($row['sku']);
            $p->set_status('draft');
            $p->set_catalog_visibility('hidden');
            $p->set_category_ids(array(idm_r8_term('product_cat', $row['category_slug'])));
            if ($row['row_type'] === 'simple') { $p->set_regular_price($row['price_usd']); }
            if ($row['fulfillment_mode'] === 'local_stock' && $row['row_type'] === 'simple') {
                $p->set_manage_stock(true);
                $p->set_stock_quantity((int)$row['stock_quantity']);
            }
            $p->update_meta_data('_idm_fulfillment_mode', $row['fulfillment_mode']);
            $p->update_meta_data('_idm_dispatch_region', $row['dispatch_region']);
            if ($row['lead_days'] !== '') {
                $p->update_meta_data('_idm_estimated_dispatch_days', (int)$row['lead_days']);
            }
            if ($row['height_cm'] !== '') { $p->update_meta_data('_idm_height_cm', $row['height_cm']); }
            if ($row['weight_kg'] !== '') { $p->update_meta_data('_idm_weight_kg', $row['weight_kg']); }
            $attrs = array();
            foreach (array('material'=>'pa_material', 'size_code'=>'pa_size_code') as $key=>$tax) {
                $slugs = array();
                if ($row[$key] !== '') { $slugs[] = $row[$key]; }
                if ($row['row_type'] === 'variable') {
                    foreach ($children as $child) {
                        if ($child['parent_sku'] === $row['sku'] && $child[$key] !== '') {
                            $slugs[] = $child[$key];
                        }
                    }
                }
                if ($slugs) {
                    $a = new WC_Product_Attribute();
                    $a->set_id(wc_attribute_taxonomy_id_by_name($tax));
                    $a->set_name($tax);
                    $a->set_options(array_map(static function($v) use ($tax) {
                        return idm_r8_term($tax, $v);
                    }, array_unique($slugs)));
                    $a->set_visible(true);
                    $a->set_variation($row['row_type'] === 'variable');
                    $attrs[$tax] = $a;
                }
            }
            if ($attrs) { $p->set_attributes($attrs); }
            idm_r8_journal_pending($batch_key, $journal, $row['sku']);
            $new_id = idm_r8_mark_and_save($p, $row, $id);
            if ($row['brand_slug']) {
                $brand_id = idm_r8_term('product_brand', $row['brand_slug']);
                $assigned = wp_set_object_terms($new_id, array($brand_id), 'product_brand');
                if (is_wp_error($assigned) || !in_array($brand_id, array_map('intval', $assigned), true)) {
                    throw new RuntimeException('R8-C brand term assignment failed; manual reconcile required');
                }
                $fingerprint = idm_r8_fingerprint(wc_get_product($new_id));
                update_post_meta($new_id, '_idm_r8_import_fingerprint', $fingerprint);
                if (get_post_meta($new_id, '_idm_r8_import_fingerprint', true) !== $fingerprint) {
                    throw new RuntimeException('R8-C final brand fingerprint persistence failed');
                }
            }
            idm_r8_journal_record($batch_key, $journal, $new_id);
            $parent_created[$row['sku']] = $new_id;
        }
        foreach ($children as $row) {
            $p = new WC_Product_Variation();
            $p->set_parent_id($parent_created[$row['parent_sku']]);
            $p->set_sku($row['sku']);
            $p->set_status('publish'); // Woo internal variation status; parent remains Draft
            $p->set_regular_price($row['price_usd']);
            $attr = array();
            if ($row['material']) { $attr['pa_material'] = $row['material']; }
            if ($row['size_code']) { $attr['pa_size_code'] = $row['size_code']; }
            $p->set_attributes($attr);
            $parent = $parent_skus[strtolower($row['parent_sku'])];
            if ($parent['fulfillment_mode'] === 'local_stock') {
                $p->set_manage_stock(true);
                $p->set_stock_quantity((int)$row['stock_quantity']);
            }
            idm_r8_journal_pending($batch_key, $journal, $row['sku']);
            $new_id = idm_r8_mark_and_save($p, $row, $id);
            idm_r8_journal_record($batch_key, $journal, $new_id);
        }
        /* Woo may sync/modify the variable parent when the child variations
         * are saved. Capture the *final* product state, not its earlier parent
         * state, or a valid rollback could incorrectly fail on its own writes. */
        foreach ($journal['ids'] as $pid) {
            $final = wc_get_product($pid);
            if (!$final || (string)$final->get_meta('_idm_r8_batch_id', true) !== $id) {
                throw new RuntimeException('R8-C final product readback/tag mismatch');
            }
            $final_fingerprint = idm_r8_fingerprint($final);
            update_post_meta($pid, '_idm_r8_import_fingerprint', $final_fingerprint);
            if (get_post_meta($pid, '_idm_r8_import_fingerprint', true) !== $final_fingerprint) {
                throw new RuntimeException('R8-C final product fingerprint not persisted');
            }
        }
        /* A partial batch must NEVER become a committed applied batch,
         * even if every saved row has its own correct fingerprint. */
        if ($journal['created_count'] !== (int)$plan['total_rows']
            || count($journal['ids']) !== (int)$plan['total_rows']
            || !empty($journal['pending_sku'])) {
            throw new RuntimeException('R8-C incomplete batch; journal remains in_progress');
        }
        $journal['state'] = 'applied';
        if (!update_option($batch_key, $journal, false)) {
            throw new RuntimeException('R8-C final journal commit failed; manual reconcile required');
        }
        echo 'R8_DEV_APPLY_COMPLETE batch='.$id.' created='.$journal['created_count'].PHP_EOL;
    } else {
        /* Allow exactly the current batch's additions while preserving the
         * original product/term integrity snapshot. */
        idm_r8_assert_live_baseline(
            $source_snapshot, $live_snapshot, $id,
            static function($live_id) {
                $p = wc_get_product($live_id);
                return $p ? (string)$p->get_meta('_idm_r8_batch_id', true) : '';
            }
        );
        if (!is_array($journal) || $journal['site'] !== 'https://dev.idollmate.com') {
            throw new RuntimeException('R8-C unknown/unowned rollback batch');
        }
        if ($journal['state'] === 'rolled_back') {
            echo 'R8_DEV_ROLLBACK_ALREADY_COMPLETE'.PHP_EOL;
        } else {
            $ids = idm_r8_batch_ids($id);
            if (!$ids) { throw new RuntimeException('R8-C no batch-tagged records; manual review'); }
            /* Compare exact DB-tagged set with immutable per-batch journal.
             * Missing or foreign items block ALL deletions. */
            idm_r8_assert_batch_journal_ids($ids, $journal);
            /* Preflight ALL surviving products before deleting ANY. */
            idm_r8_assert_owned_products_unchanged($ids, $id);
            usort($ids, static function($a, $b) {
                $aa = wc_get_product($a); $bb = wc_get_product($b);
                return ($aa->get_type() === 'variation' ? -1 : 1)
                     <=> ($bb->get_type() === 'variation' ? -1 : 1);
            });
            foreach ($ids as $pid) {
                $p = wc_get_product($pid);
                if (!$p || !$p->delete(true)) {
                    throw new RuntimeException('R8-C partial rollback; manual reconcile batch '.$id);
                }
            }
            $journal['state'] = 'rolled_back';
            $journal['deleted_count'] = count($ids);
            if (!update_option($batch_key, $journal, false)) {
                throw new RuntimeException('R8-C journal persistence failed; manual reconcile required');
            }
            echo 'R8_DEV_ROLLBACK_COMPLETE batch='.$id.' deleted='.count($ids).PHP_EOL;
        }
    }
} finally {
    if ($lock_acquired) { delete_option($lock_key); }
}
