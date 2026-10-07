<?php
/**
 * Template Name: Designed page (no title)
 * Template Post Type: page
 *
 * Page composée de sections (compositions « AARISE » de l'éditeur) : le gabarit n'ajoute
 * que l'en-tête et le pied de page, sans titre automatique.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--canvas">
	<div class="aa-content">
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
