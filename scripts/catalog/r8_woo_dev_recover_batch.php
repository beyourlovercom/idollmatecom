<?php
/**
 * R8-C controlled recovery of the *already-created* four QA records only.
 * Preview mode: read only. Repair mode: metadata+batch journal only.
 * NO post creation, deletion, publication, images, payment, or production.
 * This is a one-off recovery for the exact approved Dev batch.
 */
defined('ABSPATH') || exit;
if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('R8-C recovery requires WP-CLI');
}
$batch = 'cfc3ead963a72e0aa44740d5';
$expected_ids = array(32, 33, 34, 35);
$legacy_tag = '17';
$approved_csv_sha = 'e03ee78f41976d8c68692536bf0e3e13f1b7b70cdaccd0ee6840da3155bd85e2';
$approved_snapshot_sha = '58eed76c86f14c307b3ee8e12df51ee3ef6e8b94894a72cc6d2dbd95f5c925fd';
$approved_snapshot_file_sha = '4d40f50f9bc339225cfcd0fd5484afb7004bff3cebb6742476ead8975d46d08e';
$action = getenv('R8_RECOVER_ACTION');
if (!in_array($action, array('preview','repair'), true)) {
    throw new RuntimeException('R8-C recovery action must be preview or repair');
}
if ($action === 'repair'
    && (getenv('R8_OWNER_DEV_WRITE_PASS') !== 'R8-OWNER-PASS-'.$batch
        || getenv('R8_RECOVER_CONFIRM') !== 'R8 REPAIR '.$batch.' 32 33 34 35')) {
    throw new RuntimeException('R8-C repair confirmation missing; no changes made');
}
if (get_option('siteurl') !== 'https://dev.idollmate.com'
    || get_option('home') !== 'https://dev.idollmate.com'
    || (string)get_option('blog_public') !== '0'
    || (string)get_option('woocommerce_coming_soon') !== 'yes'
    || get_woocommerce_currency() !== 'USD') {
    throw new RuntimeException('R8-C recovery only on protected USD Dev');
}
$directory = __DIR__;
$baseline_path = $directory.'/r8_gateT_after_20261029.json';
$csv_path = $directory.'/R8_QA_ONLY_SAMPLE_IMPORT.csv';
$plan_path = $directory.'/r8_gateC_fresh_plan_20261029.json';
$policy_path = $directory.'/r8_catalog_policy.json';
foreach (array($baseline_path,$csv_path,$plan_path,$policy_path) as $f) {
    if (!is_file($f) || is_link($f)) {
        throw new RuntimeException('R8-C recovery missing/symlink source');
    }
}
if (!hash_equals($approved_snapshot_file_sha, hash_file('sha256',$baseline_path))
    || !hash_equals($approved_csv_sha, hash_file('sha256',$csv_path))) {
    throw new RuntimeException('R8-C approved QA source/baseline changed');
}
$plan = json_decode(file_get_contents($plan_path),true);
$baseline = json_decode(file_get_contents($baseline_path),true);
if (!is_array($plan) || !is_array($baseline)
    || $plan['plan_id'] !== $batch
    || $plan['site'] !== 'https://dev.idollmate.com'
    || $plan['total_rows'] !== 4
    || $plan['ready_for_owner_review'] !== true
    || $plan['source_sha256'] !== $approved_csv_sha
    || $plan['snapshot_sha256'] !== $approved_snapshot_sha
    || $plan['policy_sha256'] !== hash_file('sha256',$policy_path)
    || $plan['blocker_count'] !== 0 || $plan['error_count'] !== 0
    || count($baseline['products']) !== 4) {
    throw new RuntimeException('R8-C recovery Plan/source mismatch');
}
require_once $directory.'/r8_woo_batch_guards.php';
$journal_key='idm_r8_batch_'.$batch;
$lock_key='idm_r8_lock_'.$batch;
if (get_option($lock_key) !== false) {
    throw new RuntimeException('R8-C active importer lock; manual review');
}
$lock_acquired=false;
try {
    if ($action === 'repair') {
        if (!add_option($lock_key, 'exact-id-repair', '', false)) {
            throw new RuntimeException('R8-C could not acquire exclusive batch lock');
        }
        $lock_acquired=true;
    }
    $live = idm_r8_capture_snapshot_isolated($directory.'/r8_woo_dev_snapshot.php');
    $original_only = $live;
    $original_only['products'] = array_values(array_filter(
        $live['products'],
        static function($row) use($expected_ids) {
            return !in_array($row['id'], $expected_ids, true);
        }
    ));
    if ($original_only !== $baseline || count($live['products']) !== 8) {
        throw new RuntimeException('R8-C original R7 baseline/terms or Woo record set changed; STOP');
    }
    $j = get_option($journal_key);
    if (!is_array($j)
        || ($j['state'] ?? null) !== 'in_progress'
        || ($j['site'] ?? null) !== 'https://dev.idollmate.com'
        || ($j['csv_sha256'] ?? null) !== $approved_csv_sha
        || ($j['created_count'] ?? null) !== 4
        || !empty($j['pending_sku'])
        || ($j['ids'] ?? null) !== $expected_ids) {
        throw new RuntimeException('R8-C incomplete/altered journal; STOP');
    }
    global $wpdb;
    $tagged_legacy = array_map('intval',$wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s",
        '_idm_r8_batch_id', $legacy_tag
    )));
    $tagged_new = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s",
        '_idm_r8_batch_id', $batch
    ));
    sort($tagged_legacy,SORT_NUMERIC);
    if ($tagged_legacy !== $expected_ids || $tagged_new !== array()) {
        throw new RuntimeException('R8-C tags no longer exactly match four QA products; STOP');
    }
    if ((int)(wp_count_posts('product')->publish ?? 0) !== 0) {
        throw new RuntimeException('R8-C unexpected published Woo product');
    }
    $rows=array(
      32=>array('IDM-R8-EXAMPLE-SIMPLE','simple','draft',0,'599.00',16,'tpe','QA-EXAMPLE-001','Original Example Simple Doll','original-example-simple-doll'),
      33=>array('IDM-R8-EXAMPLE-PARENT','variable','draft',0,'',17,'','QA-EXAMPLE-002','Original Example Configurable Doll','original-example-configurable-doll'),
      34=>array('IDM-R8-EXAMPLE-TPE','variation','publish',33,'999.00',0,'tpe','QA-EXAMPLE-003',null,null),
      35=>array('IDM-R8-EXAMPLE-SIL','variation','publish',33,'1099.00',0,'silicone','QA-EXAMPLE-004',null,null),
    );
    foreach ($rows as $pid=>$expected) {
        $p=wc_get_product($pid);
        if (!$p || (int)wc_get_product_id_by_sku($expected[0]) !== $pid
            || $p->get_sku() !== $expected[0] || $p->get_type() !== $expected[1]
            || $p->get_status() !== $expected[2]
            || (int)$p->get_parent_id() !== $expected[3]
            || (string)$p->get_regular_price() !== $expected[4]
            || $p->get_catalog_visibility() !== 'hidden'
            || $p->get_meta('_idm_r8_source_id',true) !== $expected[7]
            || get_post_meta($pid,'_idm_r8_batch_id',true) !== $legacy_tag
            || (int)$p->get_image_id() !== 0
            || (method_exists($p,'get_gallery_image_ids') ? $p->get_gallery_image_ids() : array()) !== array()
            || $p->get_manage_stock()
            || $p->get_stock_quantity() !== null) {
            throw new RuntimeException('R8-C QA product drift at ID '.$pid);
        }
        if ($expected[8] !== null
            && ($p->get_name() !== $expected[8]
                || $p->get_slug() !== $expected[9]
                || $p->get_category_ids() !== array($expected[5]))) {
            throw new RuntimeException('R8-C QA parent content/category drift '.$pid);
        }
        $attrs=$p->get_attributes();
        if ($pid === 32) {
            $v=$attrs['pa_material'] ?? null;
            if (!$v instanceof WC_Product_Attribute || $v->get_options() !== array(18)) {
                throw new RuntimeException('R8-C simple Material attribute mismatch');
            }
        } elseif ($pid === 33) {
            $v=$attrs['pa_material'] ?? null;
            $options=$v instanceof WC_Product_Attribute ? array_map('intval',$v->get_options()) : array();
            sort($options,SORT_NUMERIC);
            if ($options !== array(18,19) || !$v->get_variation()) {
                throw new RuntimeException('R8-C variable Material attribute mismatch');
            }
        } elseif (($attrs['pa_material'] ?? '') !== $expected[6]) {
            throw new RuntimeException('R8-C variation Material attribute mismatch '.$pid);
        }
        $references=(int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta
              WHERE meta_key IN ('_product_id','_variation_id') AND meta_value=%s",
            (string)$pid
        ));
        if ($references !== 0) {
            throw new RuntimeException('R8-C QA ID has existing order references '.$pid);
        }
    }
    if ($action === 'preview') {
        echo 'R8_RECOVERY_PREFLIGHT_PASS batch='.$batch.' exact_ids=32,33,34,35 original_r7=unchanged write=0'.PHP_EOL;
        return;
    }
    foreach ($expected_ids as $pid) {
        $updated=update_post_meta($pid,'_idm_r8_batch_id',$batch);
        if ($updated === false || get_post_meta($pid,'_idm_r8_batch_id',true) !== $batch) {
            throw new RuntimeException('R8-C partial metadata repair; STOP at product '.$pid);
        }
        $product=wc_get_product($pid);
        $fingerprint=idm_r8_fingerprint($product);
        update_post_meta($pid,'_idm_r8_import_fingerprint',$fingerprint);
        if (get_post_meta($pid,'_idm_r8_import_fingerprint',true) !== $fingerprint) {
            throw new RuntimeException('R8-C partial fingerprint repair; STOP at '.$pid);
        }
    }
    $correct_ids = array_map('intval',$wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s",
        '_idm_r8_batch_id', $batch
    )));
    sort($correct_ids,SORT_NUMERIC);
    if ($correct_ids !== $expected_ids) {
        throw new RuntimeException('R8-C incomplete corrected batch ownership; STOP');
    }
    $j['state']='applied';
    if (!update_option($journal_key,$j,false)
        || (get_option($journal_key)['state'] ?? null) !== 'applied') {
        throw new RuntimeException('R8-C completed 4 tags but journal state not durable; STOP');
    }
    echo 'R8_RECOVERY_COMPLETE batch='.$batch.' repaired=4 created=0 deleted=0 published=0'.PHP_EOL;
} finally {
    if ($lock_acquired) {
        delete_option($lock_key);
    }
}
