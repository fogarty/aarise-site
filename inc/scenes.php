<?php
/**
 * Scènes décoratives des hauts de page, dans le même langage que le héros de l'accueil
 * (lignes fines, or, brume) mais différentes selon la page :
 *
 *   contours  relief en courbes de niveau (About, projets sans image)
 *   orbits    mondes en orbite (Projects, 404)
 *   horizon   soleil qui se lève sur l'horizon (Jobs)
 *   beams     faisceaux de projecteurs (Press kit)
 *   ripples   ondes qui se propagent (Contact)
 *   grid      sol en perspective (pages simples, pages légales)
 *
 * Les sections « Page intro » (groupe de classe aa-intro) reçoivent la scène de leur page.
 * Pour en choisir une autre, ajouter au groupe la classe aa-scene-<nom> (ex. aa-scene-orbits).
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/** Scène par défaut de chaque page, d'après son slug. */
const AARISE_PAGE_SCENES = array(
	'about'     => 'contours',
	'projects'  => 'orbits',
	'jobs'      => 'horizon',
	'press-kit' => 'beams',
	'contact'   => 'ripples',
);

/**
 * Ajoute la scène aux sections « Page intro ».
 *
 * @param string $content Rendu du bloc Groupe.
 * @param array  $block   Bloc analysé.
 * @return string
 */
function aarise_intro_scene( $content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( is_admin() || ! preg_match( '/(^|\s)aa-intro(\s|$)/', $class ) ) {
		return $content;
	}
	if ( preg_match( '/(?:^|\s)aa-scene-([a-z]+)/', $class, $m ) ) {
		$variant = $m[1];
	} else {
		$slug    = is_singular() ? get_post_field( 'post_name', get_queried_object_id() ) : '';
		$variant = isset( AARISE_PAGE_SCENES[ $slug ] ) ? AARISE_PAGE_SCENES[ $slug ] : 'contours';
	}
	$scene = aarise_scene( $variant, get_the_title() );
	return preg_replace( '/^(\s*<div[^>]*>)/', '$1' . $scene, $content, 1 );
}
add_filter( 'render_block_core/group', 'aarise_intro_scene', 10, 2 );

/**
 * Balisage d'une scène.
 *
 * @param string $variant Nom de la scène.
 * @param string $seed    Texte qui détermine les variations (ex. titre de la page).
 * @return string
 */
function aarise_scene( $variant, $seed = '' ) {
	$builders = array(
		'contours' => 'aarise_scene_contours',
		'orbits'   => 'aarise_scene_orbits',
		'horizon'  => 'aarise_scene_horizon',
		'beams'    => 'aarise_scene_beams',
		'ripples'  => 'aarise_scene_ripples',
		'grid'     => 'aarise_scene_grid',
	);
	if ( ! isset( $builders[ $variant ] ) ) {
		$variant = 'contours';
	}
	$art = call_user_func( $builders[ $variant ], $seed );
	return sprintf(
		'<div class="aa-scene aa-scene--%1$s" aria-hidden="true" data-aa-scene><div class="aa-scene__art aa-scene__layer" data-depth="0.25">%2$s</div><div class="aa-scene__fog aa-scene__fog--front"></div><canvas class="aa-scene__dust" data-aa-dust></canvas></div>',
		esc_attr( $variant ),
		$art
	);
}

/**
 * Générateur pseudo-aléatoire déterminé par un texte (mêmes valeurs pour un même texte).
 *
 * @param string $seed Texte.
 * @return callable function( $min, $max ): float
 */
function aarise_seeded_random( $seed ) {
	$state = crc32( strtolower( remove_accents( (string) $seed ) ) ) ?: 1;
	return function ( $min, $max ) use ( &$state ) {
		$state ^= ( $state << 13 ) & 0xFFFFFFFF;
		$state ^= $state >> 17;
		$state ^= ( $state << 5 ) & 0xFFFFFFFF;
		$state &= 0xFFFFFFFF;
		return $min + ( $state / 0xFFFFFFFF ) * ( $max - $min );
	};
}

/**
 * Relief en courbes de niveau : anneaux concentriques déformés autour d'un sommet.
 *
 * @param string $seed  Texte qui détermine le relief.
 * @param array  $frame [largeur, hauteur] du dessin.
 * @param float  $step  Écart moyen entre deux courbes.
 * @return array { paths: string, summit: [x, y] }
 */
function aarise_contour_map( $seed, $frame, $step ) {
	$rand   = aarise_seeded_random( $seed );
	list( $w, $h ) = $frame;
	$summit = array( $rand( $w * 0.33, $w * 0.67 ), $rand( $h * 0.34, $h * 0.6 ) );
	$ridge  = array( $summit[0] + $rand( -$w * 0.4, $w * 0.4 ), $summit[1] + $rand( -$h * 0.25, $h * 0.32 ) );
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
		$radius = $step * 0.5 + $k * $rand( $step * 0.9, $step * 1.1 );
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
		$paths .= sprintf(
			'<path class="%s" d="%s"/>',
			0 === $k % 4 ? 'aa-contour aa-contour--index' : 'aa-contour',
			aarise_closed_curve( $points )
		);
	}
	return array( 'paths' => $paths, 'summit' => $summit );
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

/* ---------- Scènes ---------- */

function aarise_scene_contours( $seed ) {
	$map = aarise_contour_map( $seed . ' landscape', array( 900, 700 ), 34 );
	return sprintf(
		'<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMid slice"><circle cx="%1$.1f" cy="%2$.1f" r="260" class="aa-scene__glow"/><g class="aa-scene__spin">%3$s</g><circle cx="%1$.1f" cy="%2$.1f" r="4" class="aa-scene__dot"/></svg>',
		$map['summit'][0],
		$map['summit'][1],
		$map['paths']
	);
}

function aarise_scene_orbits( $seed ) {
	$rand   = aarise_seeded_random( $seed . ' orbits' );
	$cx     = 450;
	$cy     = 350;
	$orbits = '';
	$worlds = '';
	foreach ( array( 70, 125, 185, 250, 320 ) as $i => $rx ) {
		$ry    = $rx * 0.36;
		$path  = sprintf( 'M%1$.1f %2$.1f a%3$.1f %4$.1f 0 1 0 %5$.1f 0 a%3$.1f %4$.1f 0 1 0 -%5$.1f 0', $cx - $rx, $cy, $rx, $ry, 2 * $rx );
		$id    = 'aa-orbit-' . $i;
		$size  = 2 === $i ? 9 : $rand( 2.5, 6 );
		$class = 2 === $i ? 'aa-scene__world aa-scene__world--gold' : 'aa-scene__world';
		$dur   = round( 18 + $rx * $rand( 0.12, 0.2 ) );
		$begin = round( -$rand( 0, $dur ), 1 );
		$orbits .= sprintf( '<path id="%s" class="aa-scene__orbit" d="%s"/>', $id, $path );
		$worlds .= sprintf(
			'<circle class="%1$s" r="%2$.1f"><animateMotion dur="%3$ss" begin="%4$ss" repeatCount="indefinite"><mpath href="#%5$s"/></animateMotion></circle>',
			$class,
			$size,
			$dur,
			$begin,
			$id
		);
	}
	return sprintf(
		'<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMid slice"><g transform="rotate(-12 %1$d %2$d)"><circle cx="%1$d" cy="%2$d" r="150" class="aa-scene__glow"/><circle cx="%1$d" cy="%2$d" r="16" class="aa-scene__star"/>%3$s%4$s</g></svg>',
		$cx,
		$cy,
		$orbits,
		$worlds
	);
}

function aarise_scene_horizon( $seed ) {
	$lines = '';
	for ( $i = 0; $i < 9; $i++ ) {
		$y      = 470 + pow( $i, 1.6 ) * 9;
		$lines .= sprintf( '<line x1="0" x2="900" y1="%.1f" y2="%.1f" class="aa-scene__line"/>', $y, $y );
	}
	$rays = '';
	for ( $a = -80; $a <= 80; $a += 10 ) {
		$rad   = deg2rad( $a - 90 );
		$rays .= sprintf( '<line x1="520" y1="470" x2="%.1f" y2="%.1f" class="aa-scene__ray"/>', 520 + cos( $rad ) * 700, 470 + sin( $rad ) * 700 );
	}
	return '<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMax slice"><defs><radialGradient id="aa-sun" cx="0.5" cy="0.5" r="0.5"><stop offset="0" stop-color="#cdab5b" stop-opacity="0.85"/><stop offset="0.55" stop-color="#cdab5b" stop-opacity="0.25"/><stop offset="1" stop-color="#cdab5b" stop-opacity="0"/></radialGradient><clipPath id="aa-sky"><rect width="900" height="470"/></clipPath></defs><g class="aa-scene__rays">' . $rays . '</g><g clip-path="url(#aa-sky)"><circle class="aa-scene__sun" cx="520" cy="440" r="300" fill="url(#aa-sun)"/><circle class="aa-scene__sun-core" cx="520" cy="440" r="110"/></g><line x1="0" x2="900" y1="470" y2="470" class="aa-scene__horizon"/>' . $lines . '</svg>';
}

function aarise_scene_beams( $seed ) {
	$beams = '';
	foreach ( array( array( 380, -14, 0 ), array( 560, 6, -4 ), array( 730, 20, -9 ) ) as $i => $beam ) {
		list( $x, $angle, $delay ) = $beam;
		$beams .= sprintf(
			'<g class="aa-scene__beam" style="--aa-angle:%2$ddeg;--aa-delay:%3$ds;transform-origin:%1$dpx -40px"><polygon points="%1$d,-40 %4$d,720 %5$d,720" fill="url(#aa-beam)"/></g>',
			$x,
			$angle,
			$delay,
			$x - 170,
			$x + 170
		);
	}
	return '<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMid slice"><defs><linearGradient id="aa-beam" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ece8df" stop-opacity="0.22"/><stop offset="0.7" stop-color="#cdab5b" stop-opacity="0.05"/><stop offset="1" stop-color="#cdab5b" stop-opacity="0"/></linearGradient></defs>' . $beams . '<ellipse cx="560" cy="640" rx="320" ry="40" class="aa-scene__stage"/></svg>';
}

function aarise_scene_ripples( $seed ) {
	$rings = '';
	foreach ( array( 60, 120, 180, 240, 300, 360 ) as $r ) {
		$rings .= sprintf( '<circle cx="540" cy="350" r="%d" class="aa-scene__ring"/>', $r );
	}
	$waves = '';
	for ( $i = 0; $i < 4; $i++ ) {
		$waves .= sprintf( '<circle cx="540" cy="350" r="360" class="aa-scene__wave" style="--aa-delay:%ds"/>', -$i * 2 );
	}
	return '<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMid slice"><circle cx="540" cy="350" r="200" class="aa-scene__glow"/>' . $rings . $waves . '<circle cx="540" cy="350" r="6" class="aa-scene__dot aa-scene__pulse"/></svg>';
}

function aarise_scene_grid( $seed ) {
	$lines = '';
	for ( $x = -900; $x <= 1800; $x += 90 ) {
		$lines .= sprintf( '<line x1="450" y1="260" x2="%d" y2="700" class="aa-scene__line"/>', $x );
	}
	$rows = '';
	for ( $i = 0; $i < 10; $i++ ) {
		$rows .= sprintf( '<line x1="-200" x2="1100" y1="%.1f" y2="%.1f" class="aa-scene__line aa-scene__row" style="--aa-delay:%.1fs"/>', 700, 700, -$i * 1.2 );
	}
	return '<svg viewBox="0 0 900 700" preserveAspectRatio="xMidYMax slice"><defs><linearGradient id="aa-grid-fade" x1="0" y1="0" x2="0" y2="1"><stop offset="0.35" stop-color="#fff" stop-opacity="0"/><stop offset="1" stop-color="#fff" stop-opacity="1"/></linearGradient><mask id="aa-grid-mask"><rect width="900" height="700" fill="url(#aa-grid-fade)"/></mask></defs><circle cx="450" cy="260" r="180" class="aa-scene__glow"/><g mask="url(#aa-grid-mask)">' . $lines . $rows . '</g></svg>';
}
