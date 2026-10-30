<?php
/**
 * Plugin Name: iDollmate Catalog Discovery
 * Description: Woo-native category/material discovery, SEO canonical and restricted R8 QA preview.
 * Version: 0.1.1
 * Requires PHP: 8.1
 * Text Domain: idollmate-discovery
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
const IDM_DISCOVERY_VERSION = '0.1.1';
const IDM_DISCOVERY_BATCH = 'cfc3ead963a72e0aa44740d5';

/* Root taxonomy and variation model belong to WooCommerce; no migrations,
 * product writes, auto-publication, price filters, payments or rewrite flush. */
function idm_discovery_archive(): bool {
    return function_exists('is_shop') && (is_shop() || is_product_category());
}
function idm_discovery_material_from_raw($raw): string {
    if ($raw === null || $raw === '') { return ''; }
    if (!is_string($raw) || strlen($raw) > 32) { return '__invalid__'; }
    if (!in_array($raw, array('tpe','silicone'), true)) { return '__invalid__'; }
    return $raw;
}
function idm_discovery_requested_material(): string {
    $raw = $_GET['idm_material'] ?? null;
    return idm_discovery_material_from_raw($raw);
}
function idm_discovery_category_slug($value): string {
    return is_string($value) && in_array($value, array('mini-dolls','full-body-dolls'), true) ? $value : '';
}
function idm_discovery_apply_material_tax_query(array $existing, string $material): array {
    if ($material === '') { return $existing; }
    if ($material === '__invalid__') {
        // An invalid URL must never silently broaden to an unfiltered result.
        $existing[] = array('taxonomy'=>'pa_material', 'field'=>'slug',
            'terms'=>array('idm-no-such-material'), 'operator'=>'IN');
    } else {
        $existing[] = array('taxonomy'=>'pa_material', 'field'=>'slug',
            'terms'=>array($material), 'operator'=>'IN');
    }
    return $existing;
}
function idm_discovery_product_query($query): void {
    if (is_admin() || !idm_discovery_archive() || !$query->is_main_query()) { return; }
    $material = idm_discovery_requested_material();
    if ($material === '') { return; }
    $existing = $query->get('tax_query');
    $query->set('tax_query', idm_discovery_apply_material_tax_query(
        is_array($existing) ? $existing : array(), $material
    ));
}

/* Woo 11.2 product archive templates render a Product Collection block.
 * Classic archive hooks do NOT run in that block. Add a scoped frontend
 * query facet and place the form before the inherited main collection. */
function idm_discovery_block_query_vars(array $args, $block, $page): array {
    if (is_admin() || !idm_discovery_archive()
        || !is_object($block) || !isset($block->context['query'])
        || !is_array($block->context['query'])) { return $args; }
    $context=$block->context['query'];
    if (empty($context['isProductCollectionBlock']) || empty($context['inherit'])
        || ($args['post_type'] ?? 'product') !== 'product') { return $args; }
    $material=idm_discovery_requested_material();
    if ($material === '') { return $args; }
    $args['tax_query']=idm_discovery_apply_material_tax_query(
        is_array($args['tax_query']??null) ? $args['tax_query'] : array(),$material
    );
    return $args;
}
function idm_discovery_block_frontend($html, $parsed, $block) {
    if (is_admin() || !idm_discovery_archive()
        || !is_array($parsed) || ($parsed['blockName'] ?? '') !== 'woocommerce/product-collection'
        || empty($parsed['attrs']['query']['inherit'])) { return $html; }
    ob_start();
    idm_discovery_filter_form();
    return ob_get_clean().$html;
}

function idm_discovery_archive_base(): string {
    if (is_product_category()) {
        $term=get_queried_object();
        if ($term instanceof WP_Term && $term->taxonomy==='product_cat') {
            $link=get_term_link($term);
            if (!is_wp_error($link)) { return $link; }
        }
    }
    return wc_get_page_permalink('shop');
}
function idm_discovery_filter_form(): void {
    static $rendered=false;
    if ($rendered || !idm_discovery_archive()) { return; }
    $rendered=true;
    $base = idm_discovery_archive_base();
    $material = idm_discovery_requested_material();
    $current = $material === '__invalid__' ? '' : $material;
    $terms = get_terms(array('taxonomy'=>'pa_material','hide_empty'=>false,'slug'=>array('tpe','silicone')));
    if (is_wp_error($terms) || !is_array($terms)) { $terms=array(); }
    $category_links=array();
    foreach (array('mini-dolls','full-body-dolls') as $slug) {
        $term=get_term_by('slug',$slug,'product_cat');
        if (!$term) { continue; }
        $link=get_term_link($term);
        if (!is_wp_error($link)) { $category_links[]=array($term->name,$link); }
    }
    echo '<section class="idm-discovery" aria-label="Catalog filters">';
    echo '<nav class="idm-discovery__categories" aria-label="Shop by category">';
    echo '<a href="'.esc_url(wc_get_page_permalink('shop')).'">All products</a>';
    foreach ($category_links as $item) {
        echo '<a href="'.esc_url($item[1]).'">'.esc_html($item[0]).'</a>';
    }
    echo '</nav><form action="'.esc_url($base).'" method="get" class="idm-discovery__filter">';
    echo '<label for="idm-material-filter">Material</label>';
    echo '<select name="idm_material" id="idm-material-filter">';
    echo '<option value="">All materials</option>';
    foreach ($terms as $term) {
        echo '<option value="'.esc_attr($term->slug).'"'.selected($current,$term->slug,false).'>'
             .esc_html($term->name).'</option>';
    }
    echo '</select>';
    $sort=isset($_GET['orderby']) && is_string($_GET['orderby']) ? sanitize_key($_GET['orderby']) : '';
    if (in_array($sort, array('menu_order','popularity','rating','date','price','price-desc'), true)) {
        echo '<input type="hidden" name="orderby" value="'.esc_attr($sort).'">';
    }
    echo '<button type="submit">Filter products</button>';
    if ($material !== '') {
        echo '<a class="idm-discovery__clear" href="'.esc_url($base).'">Clear filter</a>';
    }
    echo '</form></section>';
}
function idm_discovery_pagination(array $args): array {
    if (!idm_discovery_archive()) { return $args; }
    $material=idm_discovery_requested_material();
    if ($material !== '' && $material !== '__invalid__') {
        $args['add_args']=array_merge(is_array($args['add_args']??null)?$args['add_args']:array(),
            array('idm_material'=>$material));
    }
    return $args;
}
function idm_discovery_robots(array $robots): array {
    // Production semantics are not authorized: this only adds a noindex for
    // filtered archives. The Dev site itself remains globally noindex.
    if (idm_discovery_archive() && idm_discovery_requested_material() !== '') {
        $robots['noindex']=true;
    }
    return $robots;
}
function idm_discovery_canonical(): void {
    if (!idm_discovery_archive()) { return; }
    if (function_exists('wpseo_canonical') || class_exists('RankMath') || defined('AIOSEO_VERSION')) {
        return; // Do not compete with an installed SEO canonical provider.
    }
    $base=idm_discovery_archive_base();
    if (!$base) { return; }
    $paged=max(1,(int)get_query_var('paged',1));
    $canonical=$paged>1 && idm_discovery_requested_material()===''
        ? get_pagenum_link($paged) : $base;
    // A filtered page is noindex and canonicalized to its unfiltered root.
    echo '<link rel="canonical" href="'.esc_url($canonical).'">'.PHP_EOL;
}
function idm_discovery_admin_qa_menu(): void {
    add_submenu_page('edit.php?post_type=product','iDollmate R8 QA','iDollmate R8 QA',
        'manage_woocommerce','idollmate-r8-qa','idm_discovery_admin_qa');
}
/**
 * Validate the exact synthetic QA batch in THIS Woo database.
 * A historical server's post IDs must never be used to locate Local fixtures.
 * Return ordered IDs only when SKU, batch, type, parentage and journal agree.
 */
function idm_discovery_qa_batch_ids($journal, string $batch): array {
    if (!is_array($journal) || ($journal['state'] ?? null) !== 'applied'
        || ($journal['created_count'] ?? null) !== 4
        || !is_array($journal['ids'] ?? null)
        || !function_exists('wc_get_product_id_by_sku')) { return array(); }
    $skus=array(
        'simple'=>'IDM-R8-EXAMPLE-SIMPLE',
        'parent'=>'IDM-R8-EXAMPLE-PARENT',
        'tpe'=>'IDM-R8-EXAMPLE-TPE',
        'sil'=>'IDM-R8-EXAMPLE-SIL',
    );
    $ids=array();
    foreach ($skus as $kind=>$sku) {
        $pid=(int)wc_get_product_id_by_sku($sku);
        $product=$pid>0 ? wc_get_product($pid) : false;
        if (!$product || $product->get_sku()!==$sku
            || $product->get_meta('_idm_r8_batch_id',true)!==$batch) { return array(); }
        if ($kind==='simple' || $kind==='parent') {
            if (!$product->is_type($kind==='simple'?'simple':'variable')
                || $product->get_status()!=='draft'
                || $product->get_catalog_visibility()!=='hidden') { return array(); }
        } elseif (!$product->is_type('variation')) { return array(); }
        $ids[$kind]=$pid;
    }
    if (count(array_unique(array_values($ids)))!==4) { return array(); }
    foreach (array('tpe','sil') as $kind) {
        $child=wc_get_product($ids[$kind]);
        if ((int)$child->get_parent_id()!==$ids['parent']) { return array(); }
    }
    $expected=array_map('intval',$journal['ids']);
    $actual=array_values($ids);
    sort($expected,SORT_NUMERIC);
    sort($actual,SORT_NUMERIC);
    return $expected===$actual ? $ids : array();
}

function idm_discovery_admin_qa(): void {
    if (!current_user_can('manage_woocommerce')) { wp_die('Forbidden'); }
    if (!function_exists('wc_get_product')) { wp_die('WooCommerce unavailable'); }
    $batch=IDM_DISCOVERY_BATCH;
    $journal=get_option('idm_r8_batch_'.$batch);
    $category=idm_discovery_category_slug($_GET['idm_category']??'');
    $material=idm_discovery_material_from_raw($_GET['idm_material']??null);
    echo '<div class="wrap idm-r8-qa"><h1>iDollmate R8 Catalog &amp; Product QA</h1>';
    echo '<p>Administrator-only preview of synthetic QA drafts. Products remain hidden and unpublished.</p>';
    $qa_ids=idm_discovery_qa_batch_ids($journal,$batch);
    if (!$qa_ids) {
        echo '<p class="notice notice-error">Approved QA batch not in verified state. Preview disabled.</p></div>';
        return;
    }
    $root=admin_url('edit.php?post_type=product&page=idollmate-r8-qa');
    echo '<form method="get"><input type="hidden" name="post_type" value="product">';
    echo '<input type="hidden" name="page" value="idollmate-r8-qa">';
    echo '<label for="idm-admin-cat">Category</label> <select name="idm_category" id="idm-admin-cat">';
    foreach (array(''=>'All categories','mini-dolls'=>'Mini Dolls','full-body-dolls'=>'Full Body Dolls') as $slug=>$label) {
        echo '<option value="'.esc_attr($slug).'"'.selected($slug,$category,false).'>'.esc_html($label).'</option>';
    }
    echo '</select> <label for="idm-admin-material">Material</label> <select name="idm_material" id="idm-admin-material">';
    foreach (array(''=>'All materials','tpe'=>'TPE','silicone'=>'Silicone') as $slug=>$label) {
        echo '<option value="'.esc_attr($slug).'"'.selected($slug,$material,false).'>'.esc_html($label).'</option>';
    }
    echo '</select> <button class="button button-primary" type="submit">Preview filter</button> ';
    echo '<a href="'.esc_url($root).'">Reset</a></form>';
    echo '<div class="idm-r8-qa__grid">';
    $found=0;
    foreach (array($qa_ids['simple'],$qa_ids['parent']) as $pid) {
        $product=wc_get_product($pid);
        if (!$product || $product->get_status()!=='draft'
            || $product->get_catalog_visibility()!=='hidden'
            || $product->get_meta('_idm_r8_batch_id',true)!==$batch) { continue; }
        $term=wp_get_post_terms($pid,'product_cat',array('fields'=>'slugs'));
        if ($category!=='' && (!is_array($term)||!in_array($category,$term,true))) { continue; }
        $material_terms=array();
        $attr=$product->get_attributes()['pa_material']??null;
        if ($attr instanceof WC_Product_Attribute) {
            foreach ($attr->get_options() as $id) {
                $t=get_term((int)$id,'pa_material');
                if ($t instanceof WP_Term) { $material_terms[]=$t->slug; }
            }
        }
        if ($material!=='' && !in_array($material,$material_terms,true)) { continue; }
        $found++;
        $price=$product->get_type()==='variable' ? array() : array($product->get_regular_price());
        $children=array();
        if ($product->is_type('variable')) {
            foreach ($product->get_children() as $cid) {
                $child=wc_get_product($cid);
                if (!$child || $child->get_meta('_idm_r8_batch_id',true)!==$batch) { continue; }
                $children[]=array('sku'=>$child->get_sku(),'material'=>(string)$child->get_attribute('pa_material'),
                    'price'=>$child->get_regular_price());
                $price[]=$child->get_regular_price();
            }
        }
        $range=$price?min(array_map('floatval',$price)):0;
        $max=$price?max(array_map('floatval',$price)):0;
        echo '<article class="idm-r8-qa__card"><div class="idm-r8-qa__placeholder" aria-label="No authorized product image">QA · No image</div>';
        echo '<h2>'.esc_html($product->get_name()).'</h2>';
        echo '<p><code>'.esc_html($product->get_sku()).'</code></p>';
        echo '<p><strong>USD '.esc_html(number_format($range,2)).($max>$range?'–'.esc_html(number_format($max,2)):'').'</strong></p>';
        echo '<p>Material: '.esc_html(implode(' / ',$material_terms)).' | Status: Draft / Hidden</p>';
        foreach ($children as $child) {
            echo '<p>'.esc_html($child['material'].' — '.$child['sku'].' — USD '.$child['price']).'</p>';
        }
        echo '<p>Height: '.esc_html((string)$product->get_meta('_idm_height_cm',true)).' cm';
        echo ' | Weight: '.esc_html((string)$product->get_meta('_idm_weight_kg',true)).' kg</p>';
        $preview=get_preview_post_link($pid);
        if (is_string($preview) && $preview!=='') {
            echo '<a class="button" href="'.esc_url($preview).'" target="_blank" rel="noopener noreferrer">Open Woo product preview</a>';
        }
        echo '</article>';
    }
    echo '</div><p>'.esc_html((string)$found).' QA parent/simple products match. No public storefront exposure.</p></div>';
}
function idm_discovery_admin_style($hook): void {
    if ($hook!=='product_page_idollmate-r8-qa') { return; }
    wp_enqueue_style('idm-discovery-qa',plugins_url('assets/discovery.css',__FILE__),array(),IDM_DISCOVERY_VERSION);
}
function idm_discovery_storefront_style(): void {
    if (is_admin() || !idm_discovery_archive()) { return; }
    wp_enqueue_style('idm-discovery',plugins_url('assets/discovery.css',__FILE__),array(),IDM_DISCOVERY_VERSION);
}
function idm_discovery_specs(): void {
    if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) { return; }
    global $product;
    if (!$product instanceof WC_Product || $product->get_meta('_idm_r8_batch_id',true)!==IDM_DISCOVERY_BATCH) { return; }
    $height=$product->get_meta('_idm_height_cm',true);
    $weight=$product->get_meta('_idm_weight_kg',true);
    if (!is_numeric($height) && !is_numeric($weight)) { return; }
    echo '<section class="idm-specs" aria-label="QA product specifications"><h3>Specifications · QA data</h3><dl>';
    if (is_numeric($height)) { echo '<dt>Height</dt><dd>'.esc_html((string)$height).' cm</dd>'; }
    if (is_numeric($weight)) { echo '<dt>Weight</dt><dd>'.esc_html((string)$weight).' kg</dd>'; }
    echo '</dl><p>Draft test data — not a commercial shipping estimate.</p></section>';
}

add_action('woocommerce_product_query','idm_discovery_product_query',20);
add_action('woocommerce_before_shop_loop','idm_discovery_filter_form',12);
add_action('woocommerce_no_products_found','idm_discovery_filter_form',8);
add_filter('woocommerce_pagination_args','idm_discovery_pagination');
add_filter('wp_robots','idm_discovery_robots');
add_action('wp_head','idm_discovery_canonical',4);
add_action('admin_menu','idm_discovery_admin_qa_menu');
add_action('admin_enqueue_scripts','idm_discovery_admin_style');
add_action('wp_enqueue_scripts','idm_discovery_storefront_style',30);
add_action('woocommerce_single_product_summary','idm_discovery_specs',25);

add_filter('query_loop_block_query_vars','idm_discovery_block_query_vars',20,3);
add_filter('render_block_woocommerce/product-collection','idm_discovery_block_frontend',20,3);
