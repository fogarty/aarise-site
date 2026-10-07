<?php
/**
 * En-tête du site (remplace celui d'Astra) : logo, menu principal.
 *
 * Le menu se modifie dans Apparence > Menus, emplacement « Main menu (header) ».
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="aa-skip" href="#content">Skip to content</a>

<header class="aa-header" data-aa-header>
	<div class="aa-header__inner">
		<?php aarise_logo(); ?>

		<button type="button" class="aa-nav-toggle" aria-expanded="false" aria-controls="aa-nav" data-aa-nav-toggle>
			<span class="aa-nav-toggle__label">Menu</span>
			<span class="aa-nav-toggle__icon" aria-hidden="true"></span>
		</button>

		<nav id="aa-nav" class="aa-nav" aria-label="Main">
			<?php aarise_menu( 'primary' ); ?>
		</nav>
	</div>
</header>
