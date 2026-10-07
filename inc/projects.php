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
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
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

/**
 * Vignette d'un projet sans « Image mise en avant » (ex. un projet pas encore annoncé) : un
 * relief en courbes de niveau généré à partir de son titre, à la place de l'image.
 *
 * @param string   $content  Rendu du bloc Image mise en avant.
 * @param array    $block    Bloc analysé.
 * @param WP_Block $instance Bloc (contexte : postId).
 * @return string
 */
function aarise_project_placeholder_image( $content, $block, $instance ) {
	$post_id = isset( $instance->context['postId'] ) ? (int) $instance->context['postId'] : 0;
	if ( '' !== trim( $content ) || ! $post_id || 'project' !== get_post_type( $post_id ) ) {
		return $content;
	}
	$title = get_the_title( $post_id );
	$map   = aarise_contour_map( $title . ' card', array( 1600, 900 ), 46 );
	$svg   = sprintf(
		'<svg class="aa-project-placeholder" viewBox="0 0 1600 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false"><rect width="1600" height="900" class="aa-member__ground"/><circle cx="%1$d" cy="%2$d" r="320" class="aa-member__glow"/><g class="aa-member__contours aa-contours">%3$s</g><circle cx="%1$d" cy="%2$d" r="5" class="aa-member__summit"/></svg>',
		round( $map['summit'][0] ),
		round( $map['summit'][1] ),
		$map['paths']
	);
	$is_link = ! empty( $block['attrs']['isLink'] );
	return sprintf(
		'<figure class="wp-block-post-featured-image is-placeholder">%s</figure>',
		$is_link ? sprintf( '<a href="%s" tabindex="-1" aria-hidden="true">%s</a>', esc_url( get_permalink( $post_id ) ), $svg ) : $svg
	);
}
add_filter( 'render_block_core/post-featured-image', 'aarise_project_placeholder_image', 5, 3 );

/**
 * Projets « teaser » : un projet mystère qui tient la place d'un jeu pas encore annoncé.
 *
 * Champ personnalisé du teaser : aarise_reveals = ID du vrai projet (planifié à la date de
 * l'annonce). Quand le vrai projet est publié, le teaser repasse en brouillon tout seul et son
 * adresse redirige (301) vers le vrai projet. Aucune action n'est nécessaire le jour J.
 */
function aarise_register_project_meta() {
	register_post_meta(
		'project',
		'aarise_reveals',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'aarise_register_project_meta' );

/**
 * À la publication d'un projet : ses teasers repassent en brouillon, le cache est vidé.
 *
 * @param string  $new_status Nouveau statut.
 * @param string  $old_status Ancien statut.
 * @param WP_Post $post       Projet.
 */
function aarise_retire_teasers( $new_status, $old_status, $post ) {
	if ( 'project' !== $post->post_type || 'publish' !== $new_status || 'publish' === $old_status ) {
		return;
	}
	$teasers = get_posts(
		array(
			'post_type'   => 'project',
			'post_status' => 'publish',
			'numberposts' => -1,
			'meta_key'    => 'aarise_reveals', // phpcs:ignore WordPress.DB.SlowDBQuery -- quelques projets au plus.
			'meta_value'  => $post->ID, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'      => 'ids',
		)
	);
	foreach ( $teasers as $teaser_id ) {
		wp_update_post( array( 'ID' => $teaser_id, 'post_status' => 'draft' ) );
	}
	if ( $teasers ) {
		do_action( 'breeze_clear_all_cache' );
		do_action( 'breeze_clear_varnish' );
	}
}
add_action( 'transition_post_status', 'aarise_retire_teasers', 10, 3 );

/**
 * Adresse d'un teaser retiré : redirection vers le projet dévoilé.
 */
function aarise_redirect_retired_teaser() {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- comparé aux slugs de projets.
	if ( ! preg_match( '#^projects/([a-z0-9-]+)$#', $path, $m ) ) {
		return;
	}
	$teaser = get_posts(
		array(
			'post_type'   => 'project',
			'name'        => $m[1],
			'post_status' => 'draft',
			'numberposts' => 1,
		)
	);
	$target = $teaser ? (int) get_post_meta( $teaser[0]->ID, 'aarise_reveals', true ) : 0;
	if ( $target && 'publish' === get_post_status( $target ) ) {
		wp_safe_redirect( get_permalink( $target ), 301, 'AARISE' );
		exit;
	}
}
add_action( 'template_redirect', 'aarise_redirect_retired_teaser', 3 );
