<?php
/**
 * Gabarit de secours (articles, archives, recherche) : liste simple aux couleurs du site.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--page">
	<?php if ( is_singular() ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<header class="aa-page-header">
				<h1 class="aa-page-header__title"><?php the_title(); ?></h1>
			</header>
			<div class="aa-content aa-prose">
				<?php the_content(); ?>
			</div>
		<?php endwhile; ?>
	<?php else : ?>
		<header class="aa-page-header">
			<h1 class="aa-page-header__title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
		</header>
		<div class="aa-content aa-prose">
			<?php if ( have_posts() ) : ?>
				<ul class="aa-archive">
					<?php
					while ( have_posts() ) :
						the_post();
						?>
						<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
					<?php endwhile; ?>
				</ul>
				<?php the_posts_pagination(); ?>
			<?php else : ?>
				<p>Nothing here yet.</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
