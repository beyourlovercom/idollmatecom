<?php
/**
 * R8-C authenticated WP-CLI read-only catalog snapshot.
 * No posts, terms, prices, stock, customers or payments are modified.
 * Run ONLY with wp eval-file on dev.idollmate.com and redirect JSON privately.
 */
defined('ABSPATH') || exit;
if (PHP_SAPI !== 'cli' || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('WP-CLI ONLY');
}
if (get_option('siteurl') !== 'https://dev.idollmate.com'
    || (string) get_option('blog_public') !== '0'
    || get_option('woocommerce_coming_soon') !== 'yes') {
    throw new RuntimeException('R8 SNAPSHOT ABORT: not protected iDollmate Dev');
}
if (!function_exists('wc_get_product')) {
    throw new RuntimeException('WooCommerce required');
}
$records = array();
$paged = 1;
do {
    $ids = get_posts(array(
        'post_type' => array('product', 'product_variation'),
        'post_status' => array('publish','draft','private','pending','inherit','future','trash'),
        'fields' => 'ids',
        'posts_per_page' => 200,
        'paged' => $paged,
        'orderby' => 'ID',
        'order' => 'ASC',
        'suppress_filters' => true,
    ));
    foreach ($ids as $id) {
        $product = wc_get_product($id);
        if (!$product) { continue; }
        $records[] = array(
            'id' => (int)$id,
            'sku' => (string)$product->get_sku(),
            'slug' => (string)$product->get_slug(),
            'type' => (string)$product->get_type(),
            'status' => (string)$product->get_status(),
            'parent_id' => (int)$product->get_parent_id(),
        );
        if (count($records) > 12000) {
            throw new RuntimeException('R8 snapshot too many product IDs');
        }
    }
    ++$paged;
} while (count($ids) === 200 && $paged < 100);
$term_slugs = static function($taxonomy) {
    if (!taxonomy_exists($taxonomy)) { return array(); }
    $terms = get_terms(array('taxonomy'=>$taxonomy,'hide_empty'=>false,'fields'=>'slugs'));
    if (is_wp_error($terms)) { throw new RuntimeException('Cannot read taxonomy '.$taxonomy); }
    sort($terms, SORT_STRING);
    return array_values($terms);
};
$attributes = array();
foreach (array('pa_material','pa_size_code','pa_height_cm','pa_skin_tone','pa_body_style','pa_head_style') as $tax) {
    if (taxonomy_exists($tax)) { $attributes[$tax] = $term_slugs($tax); }
}
usort($records, static function($a,$b){return $a['id'] <=> $b['id'];});
echo wp_json_encode(array(
    'site'=>'https://dev.idollmate.com',
    'blog_public'=>(string)get_option('blog_public'),
    'coming_soon'=>(string)get_option('woocommerce_coming_soon'),
    'products'=>$records,
    'categories'=>$term_slugs('product_cat'),
    'brands'=>$term_slugs('product_brand'),
    'attributes'=>$attributes,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
