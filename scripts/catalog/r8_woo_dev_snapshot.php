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
        if (!$product) {
            throw new RuntimeException('R8 snapshot cannot safely inspect product record '.$id);
        }
        /* Capture a protected-field digest. A rollback must not ignore price,
         * inventory, content, media, attribute or term-assignment edits to
         * pre-existing R7 products merely because SKU/status stayed the same. */
        $category_ids = array_map('intval', $product->get_category_ids());
        sort($category_ids, SORT_NUMERIC);
        $gallery_ids = method_exists($product, 'get_gallery_image_ids')
            ? array_map('intval', $product->get_gallery_image_ids()) : array();
        $brand_ids = taxonomy_exists('product_brand')
            ? wp_get_object_terms($id, 'product_brand', array('fields'=>'ids')) : array();
        if (is_wp_error($brand_ids)) { throw new RuntimeException('Brand term read failed'); }
        $brand_ids = array_map('intval', $brand_ids);
        sort($brand_ids, SORT_NUMERIC);
        $protected = array(
            'post_modified_gmt' => (string)get_post_field('post_modified_gmt', $id),
            'name' => (string)$product->get_name(),
            'description' => (string)$product->get_description(),
            'short_description' => (string)$product->get_short_description(),
            'price' => (string)$product->get_price(),
            'regular_price' => (string)$product->get_regular_price(),
            'sale_price' => (string)$product->get_sale_price(),
            'manage_stock' => (bool)$product->get_manage_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'stock_status' => (string)$product->get_stock_status(),
            'tax_status' => (string)$product->get_tax_status(),
            'tax_class' => (string)$product->get_tax_class(),
            'visibility' => (string)$product->get_catalog_visibility(),
            'category_ids' => $category_ids,
            'brand_ids' => $brand_ids,
            'attribute_data' => serialize($product->get_attributes()),
            'image_id' => (int)$product->get_image_id(),
            'gallery_ids' => $gallery_ids,
            'source_meta' => array_map(static function($k) use ($product) {
                return $product->get_meta($k, true);
            }, array('_idm_fulfillment_mode','_idm_dispatch_region',
                     '_idm_estimated_dispatch_days','_idm_reference_image_url',
                     '_idm_height_cm','_idm_weight_kg')),
        );
        $records[] = array(
            'integrity_sha256' => hash('sha256', serialize($protected)),
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
    'attributes'=>(object)$attributes,
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
