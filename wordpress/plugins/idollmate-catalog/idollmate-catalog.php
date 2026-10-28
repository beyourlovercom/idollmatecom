<?php
/**
 * Plugin Name: iDollmate Catalog Fields
 * Description: Minimal theme-independent fulfillment and dispatch metadata for WooCommerce products.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * WC requires at least: 9.0
 * Text Domain: idollmate-catalog
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'IDM_Catalog_Fields_V1' ) ) {
	final class IDM_Catalog_Fields_V1 {
		public const VERSION = '0.2.0';
		public const MODE_META = '_idm_fulfillment_mode';
		public const REGION_META = '_idm_dispatch_region';
		public const DAYS_META = '_idm_estimated_dispatch_days';
		public const IMAGE_URL_META = '_idm_reference_image_url';

		/**
		 * WooCommerce owns product price, stock, product brands and attributes.
		 * This plugin does not write those native fields, schedule tasks, or
		 * add storefront output or any external requests.
		 */
		public static function boot(): void {
			add_action( 'plugins_loaded', array( __CLASS__, 'init' ), 20 );
		}

		public static function init(): void {
			if ( ! class_exists( 'WooCommerce' ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
				return;
			}

			add_action( 'woocommerce_product_options_shipping', array( __CLASS__, 'render_fields' ) );
			add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_fields' ) );
		}

		public static function woocommerce_notice(): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-warning"><p>';
			echo esc_html__( 'iDollmate Catalog Fields requires WooCommerce. No catalog metadata is processed while WooCommerce is inactive.', 'idollmate-catalog' );
			echo '</p></div>';
		}

		public static function mode_options(): array {
			return array(
				''            => __( 'Not confirmed', 'idollmate-catalog' ),
				'factory_mto' => __( 'Factory made-to-order', 'idollmate-catalog' ),
				'local_stock' => __( 'Physical stock (verify inventory)', 'idollmate-catalog' ),
			);
		}

		public static function region_options(): array {
			return array(
				''      => __( 'Not confirmed', 'idollmate-catalog' ),
				'CN'    => __( 'China / factory', 'idollmate-catalog' ),
				'US'    => __( 'United States', 'idollmate-catalog' ),
				'EU'    => __( 'European Union', 'idollmate-catalog' ),
				'OTHER' => __( 'Other / manual', 'idollmate-catalog' ),
			);
		}

		public static function render_fields(): void {
			global $product_object;
			if ( ! function_exists( 'woocommerce_wp_select' ) || ! function_exists( 'woocommerce_wp_text_input' ) ) {
				return;
			}
			$product = $product_object instanceof WC_Product ? $product_object : null;
			if ( ! $product ) {
				return;
			}

			echo '<div class="options_group">';
			woocommerce_wp_select(
				array(
					'id'          => self::MODE_META,
					'label'       => __( 'Fulfillment mode', 'idollmate-catalog' ),
					'description' => __( 'Internal catalog planning only. Does not create stock or shipping promises.', 'idollmate-catalog' ),
					'desc_tip'    => true,
					'value'       => $product->get_meta( self::MODE_META, true ),
					'options'     => self::mode_options(),
				)
			);
			woocommerce_wp_select(
				array(
					'id'          => self::REGION_META,
					'label'       => __( 'Dispatch region', 'idollmate-catalog' ),
					'description' => __( 'Internal origin hint, independent of actual Woo shipping zones.', 'idollmate-catalog' ),
					'desc_tip'    => true,
					'value'       => $product->get_meta( self::REGION_META, true ),
					'options'     => self::region_options(),
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'                => self::DAYS_META,
					'label'             => __( 'Estimated dispatch days', 'idollmate-catalog' ),
					'description'       => __( 'Optional non-negative number (0–180). Not displayed to customers in V1.', 'idollmate-catalog' ),
					'desc_tip'          => true,
					'type'              => 'number',
					'value'             => $product->get_meta( self::DAYS_META, true ),
					'custom_attributes' => array( 'min' => '0', 'max' => '180', 'step' => '1' ),
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => self::IMAGE_URL_META,
					'label'       => __( 'YourDoll image reference URL', 'idollmate-catalog' ),
					'description' => __( 'Dev reference link only. No image files are copied or imported. Original external images may return 403 and require usage rights before commercial display.', 'idollmate-catalog' ),
					'desc_tip'    => true,
					'type'        => 'url',
					'value'       => $product->get_meta( self::IMAGE_URL_META, true ),
				)
			);
			echo '</div>';
		}

		/**
		 * Accept ONLY original YourDoll image URLs. Do not proxy, mirror,
		 * download, create WP media attachments or auto-render images.
		 */
		public static function valid_image_url( string $url ): bool {
			if ( strlen( $url ) > 2048 ) {
				return false;
			}
			$parsed = wp_parse_url( $url );
			if ( ! is_array( $parsed ) ) {
				return false;
			}
			if ( 'https' !== strtolower( (string) ( $parsed['scheme'] ?? '' ) )
				|| ! in_array( strtolower( (string) ( $parsed['host'] ?? '' ) ), array( 'yourdoll.com', 'www.yourdoll.com' ), true )
				|| isset( $parsed['user'] ) || isset( $parsed['pass'] ) || isset( $parsed['port'] )
				|| isset( $parsed['query'] ) || isset( $parsed['fragment'] ) ) {
				return false;
			}
			$path = (string) ( $parsed['path'] ?? '' );
			return str_starts_with( $path, '/wp-content/uploads/' )
				&& (bool) preg_match( '/\\.(?:jpe?g|png|webp|gif|avif)$/i', $path )
				&& false === strpos( $path, '..' );
		}

		/**
		 * Runs in WooCommerce's normal authenticated product save lifecycle.
		 * Missing fields are left unchanged, preventing accidental loss when
		 * another product editor does not render these inputs.
		 */
		public static function save_fields( $product ): void {
			if ( ! $product instanceof WC_Product || ! current_user_can( 'edit_post', $product->get_id() ) ) {
				return;
			}
			$selects = array(
				self::MODE_META   => self::mode_options(),
				self::REGION_META => self::region_options(),
			);
			foreach ( $selects as $key => $options ) {
				if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
					continue;
				}
				$raw = sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
				if ( ! array_key_exists( $raw, $options ) ) {
					continue;
				}
				if ( '' === $raw ) {
					$product->delete_meta_data( $key );
				} else {
					$product->update_meta_data( $key, $raw );
				}
			}
			if ( isset( $_POST[ self::IMAGE_URL_META ] ) && is_scalar( $_POST[ self::IMAGE_URL_META ] ) ) {
				$raw = trim( (string) wp_unslash( (string) $_POST[ self::IMAGE_URL_META ] ) );
				if ( '' === $raw ) {
					$product->delete_meta_data( self::IMAGE_URL_META );
				} elseif ( self::valid_image_url( $raw ) ) {
					$product->update_meta_data( self::IMAGE_URL_META, esc_url_raw( $raw ) );
				}
			}
			if ( isset( $_POST[ self::DAYS_META ] ) && is_scalar( $_POST[ self::DAYS_META ] ) ) {
				$raw = trim( sanitize_text_field( wp_unslash( (string) $_POST[ self::DAYS_META ] ) ) );
				if ( '' === $raw ) {
					$product->delete_meta_data( self::DAYS_META );
				} elseif ( ctype_digit( $raw ) && (int) $raw <= 180 ) {
					$product->update_meta_data( self::DAYS_META, (int) $raw );
				}
			}
		}
	}
	IDM_Catalog_Fields_V1::boot();
}
