<?php
/**
 * Projets du studio : un type de contenu « Project » (menu Projects de l'administration).
 *
 * Chaque projet = un titre, une « Image mise en avant » (visuel principal), un « Extrait »
 * (accroche affichée sur les cartes) et un contenu libre en blocs. Les listes de projets
 * (accueil, page Projects) sont des blocs « Boucle de requête » qui les affichent tous :
 * ajouter un projet suffit à le faire apparaître partout.
 *
 * Adresses : /projects/ est la page Projects (modifiable), /projects/<projet>/ chaque projet.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Déclare le type de contenu « project ».
 */
function aarise_register_projects() {
	register_post_type(
		'project',
		array(
			'labels'        => array(
				'name'               => 'Projects',
				'singular_name'      => 'Project',
				'add_new'            => 'Add project',
				'add_new_item'       => 'Add new project',
				'edit_item'          => 'Edit project',
				'new_item'           => 'New project',
				'view_item'          => 'View project',
				'search_items'       => 'Search projects',
				'not_found'          => 'No projects found',
				'not_found_in_trash' => 'No projects in the trash',
				'all_items'          => 'All projects',
				'menu_name'          => 'Projects',
			),
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => false,
			'rewrite'       => array( 'slug' => 'projects', 'with_front' => false ),
			'menu_position' => 20,
			'menu_icon'     => 'dashicons-games',
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
			'template'      => array(
				array( 'core/pattern', array( 'slug' => 'aarise/project-body' ) ),
			),
		)
	);
}
add_action( 'init', 'aarise_register_projects' );

/**
 * Texte d'aide du champ titre d'un projet.
 *
 * @param string  $text Texte d'aide du titre.
 * @param WP_Post $post Projet en cours.
 * @return string
 */
function aarise_project_title_placeholder( $text, $post ) {
	return 'project' === $post->post_type ? 'Project name' : $text;
}
add_filter( 'enter_title_here', 'aarise_project_title_placeholder', 10, 2 );
