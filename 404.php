<?php
/**
 * Page introuvable.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--404">
	<?php echo aarise_scene( 'orbits', '404' ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG décoratif du thème. ?>
	<div class="aa-404">
		<p class="is-style-eyebrow">Error 404</p>
		<h1 class="aa-404__title">Lost in another world.</h1>
		<p class="is-style-lead">The page you are looking for does not exist or has moved.</p>
		<div class="wp-block-buttons">
			<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to home</a></div>
		</div>
	</div>
</main>
<?php
get_footer();
