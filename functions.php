<?php
/**
 * AARISE — thème enfant Astra.
 *
 * Le thème fournit le cadre (en-tête, pied de page, mise en forme) ; tout le contenu des pages
 * est fait de blocs modifiables dans l'éditeur. Les gabarits du thème remplacent ceux d'Astra
 * (page.php, single-project.php, index.php, 404.php), dont le CSS et le JS ne sont pas chargés.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

define( 'AARISE_VERSION', '0.7.3' );

/** Domaine de production : en dehors, le site n'est jamais indexé. */
define( 'AARISE_PRODUCTION_HOST', 'www.aarise.games' );

/** Description par défaut (partage, moteurs de recherche) quand une page n'a pas d'« Extrait ». */
define( 'AARISE_DEFAULT_DESCRIPTION', 'AARISE is an independent video game studio in La Rochelle, France, founded by the original team behind Empire of the Ants. The Art of Immersion.' );

require_once __DIR__ . '/inc/consent.php';
require_once __DIR__ . '/inc/projects.php';
require_once __DIR__ . '/inc/jobs.php';
require_once __DIR__ . '/inc/scenes.php';
require_once __DIR__ . '/inc/hero.php';
require_once __DIR__ . '/inc/team.php';

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
 * Menus, traductions, styles de l'éditeur, images.
 */
function aarise_setup() {
	load_child_theme_textdomain( 'aarise', get_stylesheet_directory() . '/languages' );

	register_nav_menus(
		array(
			'primary' => 'Main menu (header)',
			'legal'   => 'Legal links (footer)',
		)
	);

	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/site.css', 'assets/css/editor.css' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	// Couleurs et tailles proposées dans l'éditeur : uniquement celles du site.
	add_theme_support(
		'editor-color-palette',
		array(
			array( 'name' => 'Night', 'slug' => 'night', 'color' => '#0c0c0e' ),
			array( 'name' => 'Ink', 'slug' => 'ink', 'color' => '#16161a' ),
			array( 'name' => 'Bone', 'slug' => 'bone', 'color' => '#ece8df' ),
			array( 'name' => 'Ash', 'slug' => 'ash', 'color' => '#9b978e' ),
			array( 'name' => 'Gold', 'slug' => 'gold', 'color' => '#cdab5b' ),
		)
	);
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-gradients' );
	add_theme_support( 'editor-gradient-presets', array() );

	// Partage sur les réseaux : l'« Extrait » d'une page sert de description, son « Image mise
	// en avant » d'image de partage (recadrée en 1200×630).
	add_post_type_support( 'page', 'excerpt' );
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'aarise-og', 1200, 630, true );
}
add_action( 'after_setup_theme', 'aarise_setup', 20 );

/**
 * Domaine unique : toute adresse qui n'est pas celle du site (aarise.games sans www, etc.)
 * est redirigée en 301 vers la même page sur le domaine principal, en conservant le chemin
 * et les paramètres. Le domaine principal est celui de « Adresse web du site » dans WordPress.
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
 * Anciennes adresses du site (avant la refonte d'octobre 2026) → nouvelles, en 301.
 */
function aarise_redirect_legacy_urls() {
	if ( ! is_404() ) {
		return;
	}
	$legacy = array(
		'earlyaccess' => '/',
		'sample-page' => '/',
	);
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- comparé à une liste fixe.
	if ( isset( $legacy[ $path ] ) ) {
		wp_safe_redirect( home_url( $legacy[ $path ] ), 301, 'AARISE' );
		exit;
	}
}
add_action( 'template_redirect', 'aarise_redirect_legacy_urls', 1 );

/**
 * Hors production (staging.aarise.games…), le site n'est jamais indexé : balise robots et
 * en-tête X-Robots-Tag, qui couvre aussi les fichiers et l'API.
 */
function aarise_is_production() {
	return AARISE_PRODUCTION_HOST === wp_parse_url( home_url(), PHP_URL_HOST );
}

function aarise_noindex_outside_production( $robots ) {
	if ( ! aarise_is_production() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'], $robots['max-image-preview'] );
	}
	return $robots;
}
add_filter( 'wp_robots', 'aarise_noindex_outside_production', 99 );

function aarise_noindex_header_outside_production() {
	if ( ! aarise_is_production() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}
add_action( 'send_headers', 'aarise_noindex_header_outside_production' );

/**
 * Styles et scripts front. Le CSS et le JS d'Astra (et de ses extensions) ne servent pas :
 * tous les gabarits sont ceux du thème.
 */
function aarise_enqueue_assets() {
	foreach ( array( 'astra-theme-css', 'astra-addon-css', 'astra-theme-dynamic' ) as $handle ) {
		wp_dequeue_style( $handle );
	}
	foreach ( array( 'astra-theme-js', 'astra-addon-js' ) as $handle ) {
		wp_dequeue_script( $handle );
	}

	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'aarise', $uri . '/assets/css/site.css', array(), AARISE_VERSION );
	wp_enqueue_script( 'aarise', $uri . '/assets/js/site.js', array(), AARISE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'aarise_enqueue_assets', 20 );

/**
 * Images envoyées dans la médiathèque : les tailles intermédiaires (celles que le site affiche)
 * sont générées en WebP, bien plus légères que le PNG ou le JPEG d'origine.
 *
 * @param array $formats Correspondances type d'origine => type produit.
 * @return array
 */
function aarise_webp_subsizes( $formats ) {
	$formats['image/png']  = 'image/webp';
	$formats['image/jpeg'] = 'image/webp';
	return $formats;
}
add_filter( 'image_editor_output_format', 'aarise_webp_subsizes' );

/**
 * Vignettes des listes de projets : chargées en différé (elles ne sont jamais tout en haut
 * de la page) et à la bonne taille (une carte fait au plus ~60 % de la largeur de l'écran).
 *
 * @param string $content Rendu du bloc Image mise en avant.
 * @return string
 */
function aarise_featured_image_loading( $content ) {
	if ( is_admin() || is_singular( 'project' ) ) {
		return $content;
	}
	$content = preg_replace( '/\s(fetchpriority|loading|sizes)="[^"]*"/', '', $content );
	return str_replace( '<img ', '<img loading="lazy" sizes="(max-width: 900px) 100vw, 60vw" ', $content );
}
add_filter( 'render_block_core/post-featured-image', 'aarise_featured_image_loading' );

/**
 * Fichiers d'extensions inutiles sur ce site, retirés pour alléger les pages :
 * - les polices Google d'Astra (le site héberge les siennes ; évite aussi d'envoyer
 *   l'adresse IP des visiteurs à Google) ;
 * - le script DOMPurify d'Astra Pro (fonctions non utilisées par nos gabarits) ;
 * - les styles de SureForms Pro, sauf sur les pages qui contiennent un formulaire.
 */
function aarise_dequeue_unused_assets() {
	wp_dequeue_style( 'astra-google-fonts' );
	wp_dequeue_script( 'astra-dom-purify' );
}
add_action( 'wp_enqueue_scripts', 'aarise_dequeue_unused_assets', 999 );

/**
 * La page affichée contient-elle un formulaire SureForms ?
 *
 * @return bool
 */
function aarise_page_has_form() {
	return is_singular( 'job' ) || ( is_singular() && has_block( 'srfm/form', get_queried_object() ) );
}

/**
 * Styles de SureForms Pro : seulement sur les pages avec un formulaire. Retirés à l'écriture de
 * la balise, car l'extension les ajoute après les autres styles.
 *
 * @param string $tag    Balise <link>.
 * @param string $handle Identifiant du style.
 * @return string
 */
function aarise_drop_form_styles( $tag, $handle ) {
	if ( ! is_admin() && in_array( $handle, array( 'sureforms-pro-signature', 'sureforms-pro-custom-styles' ), true ) && ! aarise_page_has_form() ) {
		return '';
	}
	return $tag;
}
add_filter( 'style_loader_tag', 'aarise_drop_form_styles', 10, 2 );

/**
 * Pas de préconnexion aux serveurs de Google Fonts (ajoutée par Astra) : le site n'en utilise pas.
 *
 * @param array  $urls          Adresses.
 * @param string $relation_type Type de lien (preconnect, dns-prefetch…).
 * @return array
 */
function aarise_remove_google_fonts_hints( $urls, $relation_type ) {
	if ( 'preconnect' !== $relation_type && 'dns-prefetch' !== $relation_type ) {
		return $urls;
	}
	return array_values(
		array_filter(
			$urls,
			function ( $url ) {
				$href = is_array( $url ) ? ( isset( $url['href'] ) ? $url['href'] : '' ) : $url;
				return false === strpos( $href, 'fonts.googleapis.com' ) && false === strpos( $href, 'fonts.gstatic.com' );
			}
		)
	);
}
add_filter( 'wp_resource_hints', 'aarise_remove_google_fonts_hints', 99, 2 );

/**
 * Précharge les polices principales (évite le changement de police au chargement).
 */
function aarise_preload_fonts() {
	$dir = get_stylesheet_directory_uri() . '/assets/fonts/';
	foreach ( array( 'inter-var.woff2', 'cormorant-garamond-500.woff2', 'cormorant-garamond-500-italic.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $dir . $font ) );
	}
}
add_action( 'wp_head', 'aarise_preload_fonts', 2 );

/**
 * Icônes du site : le « A » d'AARISE. Ignorées si une « Icône du site » est définie
 * dans WordPress (Apparence > Personnaliser > Identité du site).
 */
function aarise_favicons() {
	if ( has_site_icon() ) {
		return;
	}
	$dir = get_stylesheet_directory_uri() . '/assets/img/favicon/';
	?>
	<link rel="icon" href="<?php echo esc_url( $dir . 'favicon.svg' ); ?>" type="image/svg+xml">
	<link rel="icon" href="<?php echo esc_url( $dir . 'favicon-32.png' ); ?>" type="image/png" sizes="32x32">
	<link rel="apple-touch-icon" href="<?php echo esc_url( $dir . 'apple-touch-icon.png' ); ?>">
	<?php
}
add_action( 'wp_head', 'aarise_favicons', 2 );

function aarise_theme_color() {
	echo '<meta name="theme-color" content="#0c0c0e">' . "\n";
}
add_action( 'wp_head', 'aarise_theme_color', 2 );

/**
 * Styles de blocs proposés dans l'éditeur (panneau de droite > Styles).
 */
function aarise_block_styles() {
	register_block_style( 'core/paragraph', array( 'name' => 'eyebrow', 'label' => 'Eyebrow' ) );
	register_block_style( 'core/paragraph', array( 'name' => 'lead', 'label' => 'Lead' ) );
	register_block_style( 'core/heading', array( 'name' => 'display', 'label' => 'Display' ) );
	register_block_style( 'core/group', array( 'name' => 'panel', 'label' => 'Panel' ) );
	register_block_style( 'core/list', array( 'name' => 'facts', 'label' => 'Facts' ) );
	register_block_style( 'core/button', array( 'name' => 'arrow', 'label' => 'Text + arrow' ) );
}
add_action( 'init', 'aarise_block_styles' );

/**
 * Catégorie « AARISE » dans l'onglet Compositions de l'éditeur.
 * Les compositions sont chargées automatiquement depuis le dossier /patterns.
 */
function aarise_pattern_category() {
	register_block_pattern_category( 'aarise', array( 'label' => 'AARISE' ) );
}
add_action( 'init', 'aarise_pattern_category' );

/**
 * Gabarits de page proposés dans l'éditeur (panneau de droite > Modèle).
 */
function aarise_page_templates( $templates ) {
	return array( 'templates/canvas.php' => 'Designed page (no title)' ) + $templates;
}
add_filter( 'theme_page_templates', 'aarise_page_templates' );

/**
 * Les gabarits d'Astra (single.php, archive.php…) ne sont jamais utilisés : sans leur CSS,
 * ils s'afficheraient mal. Tout ce qui n'a pas de gabarit dans ce thème passe par index.php.
 *
 * @param string $template Gabarit choisi par WordPress.
 * @return string
 */
function aarise_template_include( $template ) {
	if ( 0 === strpos( wp_normalize_path( $template ), wp_normalize_path( get_stylesheet_directory() ) ) ) {
		return $template;
	}
	if ( 0 === strpos( wp_normalize_path( $template ), wp_normalize_path( get_template_directory() ) ) ) {
		return get_stylesheet_directory() . '/index.php';
	}
	return $template;
}
add_filter( 'template_include', 'aarise_template_include', 99 );

/**
 * Pas d'émojis WordPress : script et styles inutiles sur ce site.
 */
function aarise_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'aarise_disable_emojis' );

/**
 * Lecteurs YouTube intégrés en mode « confidentialité renforcée » (pas de cookie avant lecture).
 *
 * @param string $html Code HTML de l'intégration.
 * @return string
 */
function aarise_youtube_nocookie( $html ) {
	return str_replace( 'youtube.com/embed/', 'youtube-nocookie.com/embed/', $html );
}
add_filter( 'embed_oembed_html', 'aarise_youtube_nocookie' );
add_filter( 'render_block_core/embed', 'aarise_youtube_nocookie' );

/**
 * Vidéos YouTube « légères » (comme sur kiwiboing.com) : tant que le visiteur n'a pas cliqué,
 * on affiche le visuel de la page (« Image mise en avant ») avec une pastille « Watch the
 * trailer », au lieu de charger le lecteur YouTube (≈ 500 Ko de scripts, et aucune connexion à
 * Google avant le clic). Le lecteur (youtube-nocookie, lecture automatique) est inséré au clic
 * par assets/js/site.js.
 *
 * @param string $content Rendu du bloc.
 * @param array  $block   Bloc analysé.
 * @return string
 */
function aarise_youtube_facade( $content, $block ) {
	if ( is_admin() || empty( $block['attrs']['providerNameSlug'] ) || 'youtube' !== $block['attrs']['providerNameSlug'] ) {
		return $content;
	}
	if ( ! preg_match( '#<iframe[^>]*src="https://www\.youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,})[^"]*"[^>]*>\s*</iframe>#', $content, $m ) ) {
		return $content;
	}
	$title   = preg_match( '#title="([^"]*)"#', $m[0], $t ) ? html_entity_decode( $t[1], ENT_QUOTES ) : 'YouTube';
	$src     = 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0';
	$post_id = is_singular() ? get_queried_object_id() : 0;
	$poster  = $post_id && has_post_thumbnail( $post_id )
		? get_the_post_thumbnail( $post_id, 'large', array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 1300px) 100vw, 1240px' ) )
		: '';
	$facade  = sprintf(
		'<button type="button" class="aa-video" data-src="%1$s" data-title="%2$s" aria-label="%3$s">%4$s<span class="aa-video__cta"><span class="aa-video__icon" aria-hidden="true"></span>Watch the trailer</span></button>',
		esc_url( $src ),
		esc_attr( $title ),
		esc_attr( 'Play the video: ' . $title ),
		$poster
	);
	return str_replace( $m[0], $facade, $content );
}
add_filter( 'render_block_core/embed', 'aarise_youtube_facade', 20, 2 );

/**
 * Balises de description et de partage (Open Graph / Twitter), sauf si une extension SEO
 * s'en charge déjà.
 */
function aarise_social_meta() {
	if ( defined( 'SURERANK_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$post_id = is_singular() ? get_queried_object_id() : 0;

	// Description : champ « Extrait » de la page, sinon la description par défaut.
	$excerpt     = $post_id ? trim( wp_strip_all_tags( get_post_field( 'post_excerpt', $post_id ) ) ) : '';
	$description = '' !== $excerpt ? $excerpt : AARISE_DEFAULT_DESCRIPTION;

	// Image : « Image mise en avant » de la page (recadrée en 1200×630), sinon la vignette du studio.
	$image   = get_stylesheet_directory_uri() . '/assets/img/og-image.jpg?v=' . AARISE_VERSION;
	$image_w = 1200;
	$image_h = 630;
	if ( $post_id && has_post_thumbnail( $post_id ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ), 'aarise-og' );
		if ( $src ) {
			list( $image, $image_w, $image_h ) = $src;
		}
	}

	$title = ( is_front_page() || ! $post_id ) ? 'AARISE — The Art of Immersion' : get_the_title( $post_id ) . ' — AARISE';
	$url   = $post_id ? get_permalink( $post_id ) : home_url( add_query_arg( array() ) );
	?>
	<meta name="description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:type" content="website">
	<meta property="og:site_name" content="AARISE">
	<meta property="og:locale" content="en_US">
	<meta property="og:title" content="<?php echo esc_attr( $title ); ?>">
	<meta property="og:description" content="<?php echo esc_attr( $description ); ?>">
	<meta property="og:url" content="<?php echo esc_url( $url ); ?>">
	<meta property="og:image" content="<?php echo esc_url( $image ); ?>">
	<meta property="og:image:width" content="<?php echo esc_attr( $image_w ); ?>">
	<meta property="og:image:height" content="<?php echo esc_attr( $image_h ); ?>">
	<meta name="twitter:card" content="summary_large_image">
	<?php
}
add_action( 'wp_head', 'aarise_social_meta', 5 );

/**
 * Titre de l'onglet : « AARISE — The Art of Immersion » sur l'accueil, « Page — AARISE » ailleurs.
 */
function aarise_document_title( $parts ) {
	if ( is_front_page() ) {
		return array( 'title' => 'AARISE — The Art of Immersion' );
	}
	$parts['site'] = 'AARISE';
	unset( $parts['tagline'] );
	return $parts;
}
add_filter( 'document_title_parts', 'aarise_document_title', 20 );
add_filter( 'document_title_separator', fn() => '—', 20 );

/**
 * Logo du studio (symbole + nom), lien vers l'accueil.
 *
 * @param string $class Classe CSS.
 */
function aarise_logo( $class = 'aa-logo' ) {
	printf(
		'<a class="%1$s" href="%2$s" aria-label="AARISE — home"><svg class="aa-logo__mark" viewBox="45 18 121 112" aria-hidden="true">%3$s</svg><span class="aa-logo__name">AARISE</span></a>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		aarise_mark_paths() // SVG statique.
	);
}

/**
 * Tracés du symbole « A » (le jambage doré et le jambage blanc, en currentColor).
 *
 * @return string
 */
function aarise_mark_paths() {
	return '<path fill="#cdab5b" d="M122.48886 97.10288C112.45378 79.393905 104.06465 64.845532 103.84637 64.773149c-.21831-.07245-1.71457 2.514999-3.32507 5.749762-1.610492 3.234762-3.058817 5.881387-3.218496 5.881387-.159747 0-2.890971-4.378621-6.069515-9.73026l-5.779197-9.730259 9.074786-17.509273c4.991151-9.63011 9.216472-17.50929 9.389632-17.50929.26551 0 59.77332 106.736094 59.77332 107.212324 0 .0899-5.16542.16353-11.47869.16353h-11.47871z"/><path fill="currentColor" d="M45.52741 128.94079c0-.72432 36.135068-66.102005 36.561869-66.149885.342495-.03845 24.000851 41.952675 34.866481 61.884305l2.28399 4.18971-11.67041-.12081-11.67043-.1208-6.741822-11.98088c-3.707995-6.58948-6.894303-11.94873-7.080675-11.90942-.186372.0393-3.435839 5.5324-7.221011 12.20693l-6.882158 12.13547-11.222926.12098c-6.172588.0665-11.222908-.0485-11.222908-.25568z"/>';
}

/**
 * Menu de secours tant qu'aucun menu n'est attribué (Apparence > Menus) : pages principales
 * repérées par leur slug.
 *
 * @param string $location Emplacement du menu.
 */
function aarise_fallback_menu( $location ) {
	$slugs = 'legal' === $location
		? array( 'legal-notice', 'privacy-policy', 'cookie-policy' )
		: array( 'projects', 'about', 'jobs', 'press-kit', 'contact' );
	echo '<ul class="aa-menu">';
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $page ) ), esc_html( get_the_title( $page ) ) );
		}
	}
	echo '</ul>';
}

/**
 * Affiche un menu attribué à un emplacement, sinon le menu de secours.
 *
 * @param string $location Emplacement du menu.
 */
function aarise_menu( $location ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'container'      => false,
				'menu_class'     => 'aa-menu',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		return;
	}
	aarise_fallback_menu( $location );
}

/**
 * Contenu d'un modèle synchronisé (Apparence > Compositions), repéré par son slug.
 *
 * @param string $slug Slug du modèle (ex. « site-footer »).
 * @return string HTML rendu, vide si le modèle n'existe pas ou n'est pas publié.
 */
function aarise_synced_pattern( $slug ) {
	$pattern = get_page_by_path( $slug, OBJECT, 'wp_block' );
	if ( ! $pattern || 'publish' !== $pattern->post_status ) {
		return '';
	}
	return do_blocks( $pattern->post_content );
}

/**
 * Barre d'administration (en haut du site, connecté) : accès direct au pied de page et aux menus.
 *
 * @param WP_Admin_Bar $bar Barre d'administration.
 */
function aarise_admin_bar_shortcuts( $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'aarise-site',
			'title' => 'Edit site',
			'href'  => admin_url( 'edit.php?post_type=wp_block' ),
		)
	);
	$footer = get_page_by_path( 'site-footer', OBJECT, 'wp_block' );
	if ( $footer ) {
		$bar->add_node(
			array(
				'parent' => 'aarise-site',
				'id'     => 'aarise-footer',
				'title'  => 'Footer (contact, studio, partners)',
				'href'   => get_edit_post_link( $footer->ID, 'raw' ),
			)
		);
	}
	$bar->add_node(
		array(
			'parent' => 'aarise-site',
			'id'     => 'aarise-menus',
			'title'  => 'Menus (header, legal links)',
			'href'   => admin_url( 'nav-menus.php' ),
		)
	);
	$bar->add_node(
		array(
			'parent' => 'aarise-site',
			'id'     => 'aarise-projects',
			'title'  => 'Projects',
			'href'   => admin_url( 'edit.php?post_type=project' ),
		)
	);
}
add_action( 'admin_bar_menu', 'aarise_admin_bar_shortcuts', 80 );

/**
 * Sitemap (wp-sitemap.xml) : seulement les pages, projets et offres d'emploi. Pas de liste des
 * auteurs (elle donnerait les identifiants de connexion), ni des pages autonomes des formulaires.
 *
 * @param WP_Sitemaps_Provider|false $provider Fournisseur.
 * @param string                     $name     Nom du fournisseur.
 * @return WP_Sitemaps_Provider|false
 */
function aarise_sitemap_providers( $provider, $name ) {
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'aarise_sitemap_providers', 10, 2 );

function aarise_sitemap_post_types( $post_types ) {
	return array_intersect_key( $post_types, array_flip( array( 'page', 'project', 'job' ) ) );
}
add_filter( 'wp_sitemaps_post_types', 'aarise_sitemap_post_types' );

/**
 * Pages d'auteur (/author/<identifiant>/) : inutiles ici, redirigées vers l'accueil.
 */
function aarise_redirect_author_pages() {
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301, 'AARISE' );
		exit;
	}
}
add_action( 'template_redirect', 'aarise_redirect_author_pages', 2 );

/**
 * API REST : les utilisateurs et la médiathèque ne sont visibles que des personnes connectées
 * (la liste des médias révélerait les visuels de projets pas encore annoncés).
 *
 * @param array $endpoints Routes de l'API.
 * @return array
 */
function aarise_hide_rest_users( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
		unset( $endpoints['/wp/v2/media'], $endpoints['/wp/v2/media/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}
add_filter( 'rest_endpoints', 'aarise_hide_rest_users' );

/**
 * Pages de pièces jointes (/?attachment_id=…) : inutiles ici, redirigées vers l'accueil.
 */
function aarise_redirect_attachment_pages() {
	if ( is_attachment() ) {
		wp_safe_redirect( home_url( '/' ), 301, 'AARISE' );
		exit;
	}
}
add_action( 'template_redirect', 'aarise_redirect_attachment_pages', 2 );
