<?php
/**
 * Page d'un projet : grand visuel (« Image mise en avant »), titre et accroche (« Extrait »),
 * puis le contenu du projet, puis les autres projets.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--project">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="aa-project-hero<?php echo has_post_thumbnail() ? ' has-image' : ''; ?>">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'full', array( 'class' => 'aa-project-hero__image', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'alt' => '' ) ); ?>
			<?php else : ?>
				<?php echo aarise_scene( 'contours', get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG décoratif du thème. ?>
			<?php endif; ?>
			<div class="aa-project-hero__text">
				<p class="is-style-eyebrow"><a href="<?php echo esc_url( home_url( '/projects/' ) ); ?>">Projects</a></p>
				<h1 class="aa-project-hero__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="aa-project-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</header>

		<div class="aa-content">
			<?php the_content(); ?>
		</div>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
