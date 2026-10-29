<?php
/**
 * Plugin Name: iDollmate Storefront UI
 * Description: Original presentation-only styling for WooCommerce catalog, product, cart and checkout.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: idollmate-storefront
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;

/**
 * No product import, checkout mutation, image requests, rewrite rules,
 * Woo templates, order hooks, gateway activation, or database migrations.
 */
function idm_storefront_is_woo_view(): bool {
	return function_exists( 'is_product' ) && (
		is_product()
		|| is_shop()
		|| is_product_category()
		|| is_product_tag()
		|| is_cart()
		|| is_checkout()
	);
}

function idm_storefront_body_class( array $classes ): array {
	if ( ! is_admin() && idm_storefront_is_woo_view() ) {
		$classes[] = 'idm-storefront-ui';
	}
	return $classes;
}

function idm_storefront_enqueue(): void {
	if ( is_admin() || ! idm_storefront_is_woo_view() ) {
		return;
	}
	wp_enqueue_style(
		'idollmate-storefront',
		plugins_url( 'assets/storefront.css', __FILE__ ),
		array(),
		'0.1.0'
	);
}

add_filter( 'body_class', 'idm_storefront_body_class' );
add_action( 'wp_enqueue_scripts', 'idm_storefront_enqueue', 30 );
