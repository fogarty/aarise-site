<?php
/**
 * Page d'une offre d'emploi : intitulé et résumé (« Extrait »), contenu de l'offre, puis le
 * formulaire de candidature avec le poste pré-rempli.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="content" class="aa-main aa-main--project aa-main--job">
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<header class="aa-project-hero">
			<?php echo aarise_scene( 'horizon', get_the_title() ); // phpcs:ignore WordPress.Security.EscapeOutput -- SVG décoratif du thème. ?>
			<div class="aa-project-hero__text">
				<p class="is-style-eyebrow"><a href="<?php echo esc_url( home_url( '/jobs/' ) ); ?>">Jobs</a></p>
				<h1 class="aa-project-hero__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="aa-project-hero__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<div class="wp-block-buttons">
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#apply">Apply</a></div>
				</div>
			</div>
		</header>

		<div class="aa-content aa-prose">
			<?php the_content(); ?>
		</div>

		<?php $aa_form = aarise_job_application_form(); ?>
		<?php if ( $aa_form ) : ?>
			<section id="apply" class="aa-section aa-section--ink aa-job-apply" data-aa-position="<?php echo esc_attr( get_the_title() ); ?>">
				<div class="wp-block-columns aa-split aa-form-split">
					<div class="wp-block-column" style="flex-basis:38%">
						<p class="is-style-eyebrow">Apply</p>
						<h2>Join us as <em><?php the_title(); ?></em>.</h2>
						<p>Send us your CV and share your work: portfolio, ArtStation, showreel. We answer every application.</p>
					</div>
					<div class="wp-block-column">
						<?php echo $aa_form; // phpcs:ignore WordPress.Security.EscapeOutput -- formulaire rendu par SureForms. ?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
