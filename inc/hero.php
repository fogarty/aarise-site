<?php
/**
 * Scène du héros : le « A » d'AARISE en montagne qui émerge de la brume, crêtes en plans
 * successifs (parallaxe à la souris et au défilement), poussière dorée qui s'élève.
 *
 * Ajoutée automatiquement aux blocs Couverture de classe « aa-hero » qui n'ont ni image ni
 * vidéo de fond : il suffit de choisir une image dans le bloc pour remplacer la scène.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Insère la scène dans le rendu du bloc Couverture.
 *
 * @param string $content Rendu du bloc.
 * @param array  $block   Bloc analysé.
 * @return string
 */
function aarise_hero_scene( $content, $block ) {
	$class = isset( $block['attrs']['className'] ) ? $block['attrs']['className'] : '';
	if ( is_admin() || ! preg_match( '/(^|\s)aa-hero(\s|$)/', $class ) || ! empty( $block['attrs']['url'] ) ) {
		return $content;
	}
	$scene = aarise_hero_scene_markup();
	// Juste avant le contenu du bloc, au-dessus du fond.
	$content = preg_replace( '/<div class="wp-block-cover__inner-container/', $scene . '$0', $content, 1 );
	return preg_replace( '/class="wp-block-cover /', 'class="wp-block-cover has-aa-scene ', $content, 1 );
}
add_filter( 'render_block_core/cover', 'aarise_hero_scene', 10, 2 );

/**
 * Balisage de la scène (décoratif, ignoré par les lecteurs d'écran).
 *
 * @return string
 */
function aarise_hero_scene_markup() {
	$mark = aarise_mark_paths();
	// Le jambage blanc et le jambage doré reçoivent chacun un dégradé qui se fond dans la brume.
	$mark = str_replace( 'fill="#cdab5b"', 'fill="url(#aa-peak-gold)"', $mark );
	$mark = str_replace( 'fill="currentColor"', 'fill="url(#aa-peak-bone)"', $mark );

	ob_start();
	?>
	<div class="aa-scene" aria-hidden="true" data-aa-scene>
		<svg class="aa-scene__layer aa-scene__far" data-depth="0.12" viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMax slice">
			<defs>
				<linearGradient id="aa-ridge-far" x1="0" y1="0" x2="0" y2="1">
					<stop offset="0" stop-color="#1d1d23"/>
					<stop offset="0.55" stop-color="#0c0c0e"/>
				</linearGradient>
			</defs>
			<path fill="url(#aa-ridge-far)" d="M0 640 L110 590 L190 615 L300 540 L380 575 L470 520 L560 560 L640 545 L720 600 L820 560 L930 610 L1040 555 L1150 590 L1260 520 L1350 560 L1450 515 L1530 545 L1600 530 V1000 H0 Z"/>
		</svg>

		<div class="aa-scene__layer aa-scene__peak" data-depth="0.3">
			<svg viewBox="45 18 121 112">
				<defs>
					<linearGradient id="aa-peak-gold" x1="0" y1="0" x2="0" y2="1">
						<stop offset="0.12" stop-color="#cdab5b" stop-opacity="0.75"/>
						<stop offset="0.85" stop-color="#cdab5b" stop-opacity="0"/>
					</linearGradient>
					<linearGradient id="aa-peak-bone" x1="0" y1="0" x2="0" y2="1">
						<stop offset="0.35" stop-color="#ece8df" stop-opacity="0.22"/>
						<stop offset="0.95" stop-color="#ece8df" stop-opacity="0"/>
					</linearGradient>
				</defs>
				<?php echo $mark; // phpcs:ignore WordPress.Security.EscapeOutput -- tracés SVG statiques du thème. ?>
			</svg>
		</div>

		<div class="aa-scene__fog aa-scene__fog--back"></div>

		<svg class="aa-scene__layer aa-scene__near" data-depth="0.5" viewBox="0 0 1600 1000" preserveAspectRatio="xMidYMax slice">
			<defs>
				<linearGradient id="aa-ridge-near" x1="0" y1="0" x2="0" y2="1">
					<stop offset="0" stop-color="#141418"/>
					<stop offset="0.45" stop-color="#0c0c0e"/>
				</linearGradient>
			</defs>
			<path fill="url(#aa-ridge-near)" d="M0 770 L120 705 L230 745 L350 690 L470 770 L600 750 L720 815 L860 800 L990 845 L1130 790 L1250 810 L1380 735 L1490 760 L1600 715 V1000 H0 Z"/>
		</svg>

		<div class="aa-scene__fog aa-scene__fog--front"></div>
		<canvas class="aa-scene__dust" data-aa-dust></canvas>
	</div>
	<?php
	return trim( ob_get_clean() );
}
