<?php
/**
 * Équipe : chaque membre est un groupe « aa-member » (composition « Team member ») contenant
 * son nom (titre) et son poste (paragraphe). Sans photo, le thème dessine à sa place un
 * paysage en courbes de niveau, généré à partir du nom : unique, et toujours le même pour
 * une même personne. Pour utiliser une vraie photo, il suffit d'ajouter un bloc Image dans
 * le groupe.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ajoute le portrait généré aux membres sans image.
 *
 * @param string $content Rendu du bloc Groupe.
 * @param array  $block   Bloc analysé.
 * @return string
 */
function aarise_team_member_portrait( $content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( is_admin() || ! preg_match( '/(^|\s)aa-member(\s|$)/', $class ) || false !== strpos( $content, '<img' ) ) {
		return $content;
	}
	if ( ! preg_match( '/<h[2-6][^>]*>(.*?)<\/h[2-6]>/s', $content, $m ) ) {
		return $content;
	}
	$name     = trim( wp_strip_all_tags( $m[1] ) );
	$portrait = aarise_team_portrait_svg( $name );
	// Juste après la balise ouvrante du groupe.
	return preg_replace( '/^(\s*<div[^>]*>)/', '$1' . $portrait, $content, 1 );
}
add_filter( 'render_block_core/group', 'aarise_team_member_portrait', 10, 2 );

/**
 * Paysage en courbes de niveau déterminé par un nom (générateur dans inc/scenes.php).
 *
 * @param string $name Nom du membre.
 * @return string SVG.
 */
function aarise_team_portrait_svg( $name ) {
	$map = aarise_contour_map( $name, array( 400, 500 ), 21 );
	return sprintf(
		'<svg class="aa-member__portrait" viewBox="0 0 400 500" aria-hidden="true" focusable="false"><rect width="400" height="500" class="aa-member__ground"/><circle cx="%1$.1f" cy="%2$.1f" r="140" class="aa-member__glow"/><g class="aa-member__contours aa-contours">%3$s</g><circle cx="%1$.1f" cy="%2$.1f" r="3" class="aa-member__summit"/></svg>',
		$map['summit'][0],
		$map['summit'][1],
		$map['paths']
	);
}
