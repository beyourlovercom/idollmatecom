<?php
/**
 * Isolated test: PHP CLI only, no WordPress/WooCommerce site or database.
 * Run: php tests/idollmate_catalog_smoke.php
 */
declare(strict_types=1);

define('ABSPATH', __DIR__ . '/');
$GLOBALS['idm_actions'] = [];
$GLOBALS['idm_allowed'] = true;
function add_action($hook, $callback, $priority = 10): void {
	$GLOBALS['idm_actions'][$hook][] = $callback;
}
function current_user_can($cap, ...$args): bool { return $GLOBALS['idm_allowed']; }
function __(string $str, string $domain = ''): string { return $str; }
function esc_html__(string $str, string $domain = ''): string { return $str; }
function wp_unslash(string $s): string { return stripslashes($s); }
function sanitize_text_field(string $s): string { return trim(strip_tags($s)); }
function woocommerce_wp_select(array $args): void {}
function woocommerce_wp_text_input(array $args): void {}
class WooCommerce {}
class WC_Product {
	public $saved = [];
	public function __construct(private int $id) {}
	public function get_id(): int { return $this->id; }
	public function get_meta(string $key, bool $single = true) { return $this->saved[$key] ?? ''; }
	public function update_meta_data(string $key, $value): void { $this->saved[$key] = $value; }
	public function delete_meta_data(string $key): void { unset($this->saved[$key]); }
}
require dirname(__DIR__) . '/wordpress/plugins/idollmate-catalog/idollmate-catalog.php';
function check(bool $result, string $label): void {
	if (!$result) { fwrite(STDERR, "FAILED: $label\n"); exit(1); }
	echo "PASS: $label\n";
}
IDM_Catalog_Fields_V1::init();
check(count($GLOBALS['idm_actions']['woocommerce_product_options_shipping'] ?? []) === 1, 'Product editor registered only on Woo');
check(count($GLOBALS['idm_actions']['woocommerce_admin_process_product_object'] ?? []) === 1, 'Woo product save hook registered');
$p = new WC_Product(19);
$_POST = [
	'_idm_fulfillment_mode' => 'factory_mto',
	'_idm_dispatch_region' => 'US',
	'_idm_estimated_dispatch_days' => '21',
];
IDM_Catalog_Fields_V1::save_fields($p);
check($p->saved === [
	'_idm_fulfillment_mode' => 'factory_mto',
	'_idm_dispatch_region' => 'US',
	'_idm_estimated_dispatch_days' => 21,
], 'Valid form inputs persist through product object');
$_POST = [
	'_idm_fulfillment_mode' => 'bogus',
	'_idm_dispatch_region' => 'INJECT',
	'_idm_estimated_dispatch_days' => '-1',
];
IDM_Catalog_Fields_V1::save_fields($p);
check($p->saved['_idm_estimated_dispatch_days'] === 21 &&
	$p->saved['_idm_dispatch_region'] === 'US' &&
	$p->saved['_idm_fulfillment_mode'] === 'factory_mto', 'Invalid values cannot overwrite existing data');
$_POST = ['_idm_fulfillment_mode' => '', '_idm_dispatch_region' => '', '_idm_estimated_dispatch_days' => ''];
IDM_Catalog_Fields_V1::save_fields($p);
check($p->saved === [], 'Blank values clear metadata');
$_POST = ['_idm_fulfillment_mode' => 'local_stock', '_idm_dispatch_region' => 'EU',
	'_idm_estimated_dispatch_days' => '0'];
$GLOBALS['idm_allowed'] = false;
IDM_Catalog_Fields_V1::save_fields($p);
check($p->saved === [], 'Unauthorized user cannot modify metadata');
$GLOBALS['idm_allowed'] = true;
$_POST = ['_idm_fulfillment_mode' => 'local_stock', '_idm_dispatch_region' => 'EU',
	'_idm_estimated_dispatch_days' => '181'];
IDM_Catalog_Fields_V1::save_fields($p);
check(!isset($p->saved['_idm_estimated_dispatch_days']) &&
	$p->saved['_idm_fulfillment_mode'] === 'local_stock', 'Lead time upper bound 180 enforced');
$_POST = [];
IDM_Catalog_Fields_V1::save_fields($p);
check($p->saved['_idm_fulfillment_mode'] === 'local_stock', 'Missing fields do not delete metadata');
echo "ALL ISOLATED CATALOG SMOKE TESTS PASSED\n";
