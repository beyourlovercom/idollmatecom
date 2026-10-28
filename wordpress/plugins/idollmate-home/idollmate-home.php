<?php
/**
 * Plugin Name: iDollmate Home Layout
 * Description: An original, theme-neutral WooCommerce discovery homepage via [idollmate_home].
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: GPL-2.0-or-later
 * Text Domain: idollmate-home
 */
defined( 'ABSPATH' ) || exit;

/**
 * No automatic page creation, product import, storefront takeover,
 * rewrite rules, theme switching, checkout modification or remote calls.
 */
add_action(
	'init',
	static function (): void {
		add_shortcode( 'idollmate_home', 'idm_render_home' );
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post || ! has_shortcode( $post->post_content, 'idollmate_home' ) ) {
			return;
		}
		wp_enqueue_style(
			'idollmate-home',
			plugins_url( 'assets/home.css', __FILE__ ),
			array(),
			'0.1.0'
		);
	}
);

function idm_render_home(): string {
	if ( ! function_exists( 'wc_get_page_permalink' ) || ! function_exists( 'wc_get_products' ) ) {
		return '';
	}
	$shop_link = wc_get_page_permalink( 'shop' );
	if ( ! is_string( $shop_link ) || '' === $shop_link ) {
		$shop_link = home_url( '/shop/' );
	}

	$categories = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( is_wp_error( $categories ) || ! is_array( $categories ) ) {
		$categories = array();
	}

	$items = wc_get_products(
		array(
			'limit'  => 1,
			'status' => 'publish',
			'return' => 'ids',
		)
	);
	$has_products = is_array( $items ) && count( $items ) > 0;

	ob_start();
	?>
	<div class="idm-home" aria-label="<?php echo esc_attr__( 'iDollmate storefront', 'idollmate-home' ); ?>">
		<section class="idm-home__hero" aria-labelledby="idm-home-heading">
			<div class="idm-home__hero-copy">
				<span class="idm-home__eyebrow"><?php echo esc_html__( 'iDollmate Collection', 'idollmate-home' ); ?></span>
				<h1 id="idm-home-heading"><?php echo esc_html__( 'Explore a collection designed around your choices.', 'idollmate-home' ); ?></h1>
				<p><?php echo esc_html__( 'Compare materials, sizes and styles in one thoughtful catalog.', 'idollmate-home' ); ?></p>
				<a class="idm-home__cta" href="<?php echo esc_url( $shop_link ); ?>">
					<?php echo esc_html__( 'Explore the catalog', 'idollmate-home' ); ?>
				</a>
			</div>
			<div class="idm-home__hero-art" aria-hidden="true">
				<span class="idm-home__art-circle idm-home__art-circle--one"></span>
				<span class="idm-home__art-circle idm-home__art-circle--two"></span>
				<span class="idm-home__art-label"><?php echo esc_html__( 'Made for individual preferences', 'idollmate-home' ); ?></span>
			</div>
		</section>
		<section class="idm-home__section" aria-labelledby="idm-home-categories">
			<div class="idm-home__section-head">
				<h2 id="idm-home-categories"><?php echo esc_html__( 'Shop by category', 'idollmate-home' ); ?></h2>
				<a href="<?php echo esc_url( $shop_link ); ?>"><?php echo esc_html__( 'View all', 'idollmate-home' ); ?></a>
			</div>
			<?php if ( $categories ) : ?>
				<div class="idm-home__categories">
					<?php foreach ( $categories as $category ) : ?>
						<?php
						$link = get_term_link( $category );
						if ( is_wp_error( $link ) ) {
							continue;
						}
						?>
						<a class="idm-home__category" href="<?php echo esc_url( $link ); ?>">
							<span><?php echo esc_html( $category->name ); ?></span>
							<span aria-hidden="true">&rarr;</span>
						</a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="idm-home__empty"><?php echo esc_html__( 'Our categories are being organized. Explore the catalog for current listings.', 'idollmate-home' ); ?></div>
			<?php endif; ?>
		</section>
		<section class="idm-home__section" aria-labelledby="idm-home-products">
			<div class="idm-home__section-head">
				<h2 id="idm-home-products"><?php echo esc_html__( 'Discover the collection', 'idollmate-home' ); ?></h2>
			</div>
			<?php if ( $has_products ) : ?>
				<div class="idm-home__products">
					<?php echo do_shortcode( '[products limit="8" columns="4" orderby="date" order="DESC" visibility="visible"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Woo output. ?>
				</div>
			<?php else : ?>
				<div class="idm-home__empty"><?php echo esc_html__( 'Products will appear here once their specifications and imagery are verified.', 'idollmate-home' ); ?></div>
			<?php endif; ?>
		</section>
		<section class="idm-home__footnote" aria-label="<?php echo esc_attr__( 'Catalog information', 'idollmate-home' ); ?>">
			<p><?php echo esc_html__( 'Browse verified product specifications before choosing your configuration.', 'idollmate-home' ); ?></p>
		</section>
	</div>
	<?php
	return (string) ob_get_clean();
}
