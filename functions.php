<?php
/**
 * AARISE — thème enfant Astra.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

define( 'AARISE_VERSION', '0.1.0' );

/**
 * Régénère les règles de réécriture et vide le cache une fois par version du thème
 * (équivaut à « Enregistrer » dans Réglages > Permaliens ; utile après un déploiement).
 */
function aarise_maybe_flush_rewrite_rules() {
	if ( get_option( 'aarise_rewrite_version' ) === AARISE_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'aarise_rewrite_version', AARISE_VERSION );

	// Vide le cache de pages Breeze (et Varnish) pour que toutes les pages reprennent le nouveau thème.
	do_action( 'breeze_clear_all_cache' );
	do_action( 'breeze_clear_varnish' );
}
add_action( 'init', 'aarise_maybe_flush_rewrite_rules', 99 );

/**
 * Traductions du thème, styles de l'éditeur.
 */
function aarise_setup() {
	load_child_theme_textdomain( 'aarise', get_stylesheet_directory() . '/languages' );

	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/site.css' );

	// Partage sur les réseaux : l'« Extrait » d'une page sert de description, son « Image mise
	// en avant » d'image de partage (recadrée en 1200×630).
	add_post_type_support( 'page', 'excerpt' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'aarise-og', 1200, 630, true );
}
add_action( 'after_setup_theme', 'aarise_setup' );

/**
 * Domaine unique : toute adresse qui n'est pas celle du site (aarise.games sans www, etc.)
 * est redirigée en 301 vers la même page sur le domaine principal, en conservant le chemin
 * et les paramètres. Le domaine principal est celui de « Adresse web du site » dans WordPress
 * (aujourd'hui https://www.aarise.games).
 */
function aarise_redirect_to_primary_domain() {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || 'cli' === PHP_SAPI ) {
		return;
	}
	$primary = wp_parse_url( home_url(), PHP_URL_HOST );
	$host    = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( preg_replace( '/:\d+$/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) ) : '';
	if ( ! $primary || ! $host || $host === $primary ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- recomposé avec l'hôte du site uniquement.
	wp_redirect( 'https://' . $primary . '/' . ltrim( $path, '/' ), 301, 'AARISE' ); // phpcs:ignore WordPress.Security.SafeRedirect -- hôte fixé au domaine du site.
	exit;
}
add_action( 'template_redirect', 'aarise_redirect_to_primary_domain', 0 );

/**
 * Styles du site, chargés après ceux d'Astra.
 */
function aarise_enqueue_assets() {
	wp_enqueue_style(
		'aarise',
		get_stylesheet_directory_uri() . '/assets/css/site.css',
		array(),
		AARISE_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'aarise_enqueue_assets', 15 );

/**
 * Catégorie « AARISE » dans l'onglet Compositions de l'éditeur.
 */
function aarise_pattern_category() {
	register_block_pattern_category( 'aarise', array( 'label' => 'AARISE' ) );
}
add_action( 'init', 'aarise_pattern_category' );

/**
 * Pas de script ni de styles d'émojis WordPress sur le site.
 */
function aarise_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'aarise_disable_emojis' );
