<?php
/**
 * Offres d'emploi : un type de contenu « Job offer » (menu Job offers de l'administration).
 *
 * Une offre publiée apparaît dans la liste de la page Jobs (bloc Boucle de requête) et a sa
 * propre page, avec le formulaire de candidature en bas (poste pré-rempli). Pour retirer une
 * offre sans la perdre : la repasser en brouillon. Sans offre publiée, la page Jobs affiche
 * « No open positions right now » ; la candidature spontanée reste toujours disponible.
 *
 * Fiche d'une offre : titre (intitulé du poste), « Extrait » (résumé : contrat, lieu…),
 * contenu libre en blocs.
 *
 * Adresses : /jobs/ est la page Jobs (modifiable), /jobs/<offre>/ chaque offre.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le type de contenu « job ».
 */
function aarise_register_jobs() {
	register_post_type(
		'job',
		array(
			'labels'        => array(
				'name'               => 'Job offers',
				'singular_name'      => 'Job offer',
				'add_new'            => 'Add job offer',
				'add_new_item'       => 'Add new job offer',
				'edit_item'          => 'Edit job offer',
				'new_item'           => 'New job offer',
				'view_item'          => 'View job offer',
				'search_items'       => 'Search job offers',
				'not_found'          => 'No job offers found',
				'not_found_in_trash' => 'No job offers in the trash',
				'all_items'          => 'All job offers',
				'menu_name'          => 'Job offers',
			),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'jobs', 'with_front' => false ),
			'menu_position' => 21,
			'menu_icon'     => 'dashicons-groups',
			'supports'      => array( 'title', 'editor', 'excerpt', 'revisions', 'page-attributes' ),
			'template'      => array(
				array( 'core/pattern', array( 'slug' => 'aarise/job-body' ) ),
			),
		)
	);
}
add_action( 'init', 'aarise_register_jobs' );

/**
 * Texte d'aide du champ titre d'une offre.
 *
 * @param string  $text Texte d'aide.
 * @param WP_Post $post Offre en cours.
 * @return string
 */
function aarise_job_title_placeholder( $text, $post ) {
	return 'job' === $post->post_type ? 'Job title' : $text;
}
add_filter( 'enter_title_here', 'aarise_job_title_placeholder', 10, 2 );

/**
 * Colonne « Status » lisible dans la liste des offres : Online / Offline.
 *
 * @param array $columns Colonnes.
 * @return array
 */
function aarise_job_columns( $columns ) {
	$columns['aarise_status'] = 'On the site';
	return $columns;
}
add_filter( 'manage_job_posts_columns', 'aarise_job_columns' );

function aarise_job_column_content( $column, $post_id ) {
	if ( 'aarise_status' === $column ) {
		echo 'publish' === get_post_status( $post_id ) ? '<strong style="color:#1a7f37">● Online</strong>' : '<span style="color:#787c82">○ Offline (draft)</span>';
	}
}
add_action( 'manage_job_posts_custom_column', 'aarise_job_column_content', 10, 2 );

/**
 * Formulaire de candidature (SureForms, slug job-application), rendu en bloc.
 *
 * @return string HTML, vide si le formulaire n'existe pas.
 */
function aarise_job_application_form() {
	$form = get_page_by_path( 'job-application', OBJECT, 'sureforms_form' );
	if ( ! $form || 'publish' !== $form->post_status ) {
		return '';
	}
	return do_blocks( sprintf( '<!-- wp:srfm/form {"id":%d} /-->', $form->ID ) );
}
