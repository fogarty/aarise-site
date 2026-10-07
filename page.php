<?php
/**
 * Page par défaut : titre de la page puis son contenu (pages légales, pages simples).
 *
 * Pour une page entièrement composée de sections (accueil, About…), choisir le modèle
 * « Designed page (no title) » dans l'éditeur.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--page">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<div class="aa-page-hero">
			<?php echo aarise_scene( 'grid', get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG décoratif du thème. ?>
			<header class="aa-page-header">
				<h1 class="aa-page-header__title"><?php the_title(); ?></h1>
			</header>
		</div>
		<div class="aa-content aa-prose">
			<?php the_content(); ?>
		</div>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
