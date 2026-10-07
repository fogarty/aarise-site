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
 * Paysage en courbes de niveau déterminé par un nom.
 *
 * @param string $name Nom du membre.
 * @return string SVG.
 */
function aarise_team_portrait_svg( $name ) {
	$state = crc32( strtolower( remove_accents( $name ) ) ) ?: 1;
	// Générateur pseudo-aléatoire (xorshift32) : mêmes valeurs pour un même nom.
	$rand = function ( $min, $max ) use ( &$state ) {
		$state ^= ( $state << 13 ) & 0xFFFFFFFF;
		$state ^= $state >> 17;
		$state ^= ( $state << 5 ) & 0xFFFFFFFF;
		$state &= 0xFFFFFFFF;
		return $min + ( $state / 0xFFFFFFFF ) * ( $max - $min );
	};

	$w      = 400;
	$h      = 500;
	$summit = array( $rand( 130, 270 ), $rand( 170, 300 ) );
	$ridge  = array( $summit[0] + $rand( -160, 160 ), $summit[1] + $rand( -120, 160 ) );
	$rings  = (int) round( $rand( 11, 15 ) );
	$waves  = array();
	for ( $j = 0; $j < 3; $j++ ) {
		$waves[] = array(
			'f' => (int) round( $rand( 2, 5 ) ),
			'a' => $rand( 0.04, 0.13 ),
			'p' => $rand( 0, M_PI * 2 ),
		);
	}

	$paths = '';
	for ( $k = 1; $k <= $rings; $k++ ) {
		$t      = $k / $rings;
		$radius = 10 + $k * $rand( 19, 23 );
		$cx     = $summit[0] + ( $ridge[0] - $summit[0] ) * $t * 0.55;
		$cy     = $summit[1] + ( $ridge[1] - $summit[1] ) * $t * 0.55;
		$points = array();
		for ( $i = 0; $i < 64; $i++ ) {
			$theta = $i / 64 * M_PI * 2;
			$r     = 1;
			foreach ( $waves as $wave ) {
				$r += $wave['a'] * ( 0.35 + $t ) * sin( $wave['f'] * $theta + $wave['p'] + $t * 1.3 );
			}
			$points[] = array( $cx + cos( $theta ) * $radius * $r, $cy + sin( $theta ) * $radius * $r * 0.86 );
		}
		$index  = 0 === $k % 4;
		$paths .= sprintf(
			'<path class="%s" d="%s"/>',
			$index ? 'aa-contour aa-contour--index' : 'aa-contour',
			aarise_closed_curve( $points )
		);
	}

	return sprintf(
		'<svg class="aa-member__portrait" viewBox="0 0 %1$d %2$d" aria-hidden="true" focusable="false"><rect width="%1$d" height="%2$d" class="aa-member__ground"/><circle cx="%3$.1f" cy="%4$.1f" r="140" class="aa-member__glow"/><g class="aa-member__contours">%5$s</g><circle cx="%3$.1f" cy="%4$.1f" r="3" class="aa-member__summit"/></svg>',
		$w,
		$h,
		$summit[0],
		$summit[1],
		$paths
	);
}

/**
 * Courbe fermée lissée (Catmull-Rom convertie en courbes de Bézier) passant par les points.
 *
 * @param array $p Points [x, y].
 * @return string Attribut d d'un tracé SVG.
 */
function aarise_closed_curve( $p ) {
	$n = count( $p );
	$d = sprintf( 'M%.1f %.1f', $p[0][0], $p[0][1] );
	for ( $i = 0; $i < $n; $i++ ) {
		$p0 = $p[ ( $i - 1 + $n ) % $n ];
		$p1 = $p[ $i ];
		$p2 = $p[ ( $i + 1 ) % $n ];
		$p3 = $p[ ( $i + 2 ) % $n ];
		$d .= sprintf(
			'C%.1f %.1f %.1f %.1f %.1f %.1f',
			$p1[0] + ( $p2[0] - $p0[0] ) / 6,
			$p1[1] + ( $p2[1] - $p0[1] ) / 6,
			$p2[0] - ( $p3[0] - $p1[0] ) / 6,
			$p2[1] - ( $p3[1] - $p1[1] ) / 6,
			$p2[0],
			$p2[1]
		);
	}
	return $d . 'Z';
}
