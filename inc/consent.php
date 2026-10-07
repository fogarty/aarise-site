<?php
/**
 * Bandeau de consentement aux cookies, sans CMP externe, compatible
 * Google Consent Mode v2 et Google Tag Manager.
 *
 * Fonctionnement :
 * 1. Dans <head>, avant tout tag Google : consentement « refusé » par défaut.
 * 2. Si le visiteur a déjà choisi (cookie aa_consent), son choix est réappliqué
 *    (gtag consent update + événement dataLayer « aa_consent_update »).
 * 3. GTM n'est chargé qu'après un consentement (mode « basic », recommandé CNIL),
 *    ou dès l'arrivée en mode « advanced » (pings sans cookie avant accord).
 * 4. Le choix est conservé 6 mois, puis redemandé. Incrémenter
 *    AARISE_CONSENT_VERSION redemande le consentement à tout le monde
 *    (à faire si de nouvelles finalités sont ajoutées).
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/** Identifiant du conteneur Google Tag Manager du site AARISE (ex. « GTM-XXXXXXX »). Vide = GTM désactivé. */
define( 'AARISE_GTM_ID', 'GTM-N36SX5P4' );

/** « basic » : GTM chargé seulement après consentement. « advanced » : GTM toujours chargé, Consent Mode gère les tags. */
define( 'AARISE_CONSENT_MODE', 'basic' );

/** Version des finalités : l'incrémenter redemande le consentement. */
define( 'AARISE_CONSENT_VERSION', 1 );

/** Durée de conservation du choix : 6 mois (recommandation CNIL). */
define( 'AARISE_CONSENT_MAX_AGE', 182 * DAY_IN_SECONDS );

/**
 * Textes du bandeau.
 *
 * @return array<string, string>
 */
function aarise_consent_strings() {
	return array(
		'consent_label'          => 'Cookie consent',
		'consent_text'           => 'We use cookies to measure traffic and improve the site.',
		'consent_more'           => 'Our cookie policy',
		'consent_reject'         => 'Reject all',
		'consent_accept'         => 'Accept all',
		'consent_panel_title'    => 'Choose by category',
		'consent_necessary'      => 'Necessary —',
		'consent_necessary_desc' => 'site operation and remembering your choice. Always on.',
		'consent_analytics'      => 'Analytics —',
		'consent_analytics_desc' => 'anonymised visit statistics (Google Analytics) to improve the site.',
		'consent_marketing'      => 'Marketing —',
		'consent_marketing_desc' => 'measuring our campaigns and personalised ads (Google, social networks).',
		'consent_save'           => 'Save my choices',
	);
}

/**
 * URL de la politique cookies.
 *
 * @return string
 */
function aarise_cookie_policy_url() {
	$page = get_page_by_path( 'cookie-policy' );
	return $page ? get_permalink( $page ) : home_url( '/cookie-policy/' );
}

/**
 * Consent Mode par défaut + chargement conditionnel de GTM. Doit être le premier script du <head>.
 */
function aarise_consent_head() {
	if ( is_admin() ) {
		return;
	}
	$config = array(
		'gtm'     => AARISE_GTM_ID,
		'mode'    => AARISE_CONSENT_MODE,
		'version' => AARISE_CONSENT_VERSION,
		'cookie'  => 'aa_consent',
		'maxAge'  => AARISE_CONSENT_MAX_AGE,
		// Hors production (staging…), GTM ne se charge qu'en mode Aperçu de Tag Manager.
		'preview' => ! aarise_is_production(),
	);
	?>
	<script id="aa-consent-default">
	(function () {
		var cfg = <?php echo wp_json_encode( $config ); ?>;
		if (cfg.preview) {
			// L'Aperçu ouvre le site avec ?gtm_debug= ; on s'en souvient pour les pages suivantes de l'onglet.
			try {
				if (/[?&]gtm_debug=/.test(location.search)) { sessionStorage.setItem('aa_gtm_debug', '1'); }
				if (!sessionStorage.getItem('aa_gtm_debug')) { cfg.gtm = ''; }
			} catch (e) {
				cfg.gtm = '';
			}
		}
		window.dataLayer = window.dataLayer || [];
		function gtag() { window.dataLayer.push(arguments); }
		window.gtag = window.gtag || gtag;

		gtag('consent', 'default', {
			ad_storage: 'denied',
			ad_user_data: 'denied',
			ad_personalization: 'denied',
			analytics_storage: 'denied',
			functionality_storage: 'granted',
			security_storage: 'granted',
			wait_for_update: 500
		});
		gtag('set', 'ads_data_redaction', true);

		var gtmLoaded = false;
		function loadGtm() {
			if (gtmLoaded || !cfg.gtm) { return; }
			gtmLoaded = true;
			window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
			var s = document.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(cfg.gtm);
			document.head.appendChild(s);
		}

		function read() {
			try {
				var m = document.cookie.match(new RegExp('(?:^|; )' + cfg.cookie + '=([^;]*)'));
				var c = m ? JSON.parse(decodeURIComponent(m[1])) : null;
				return c && c.v === cfg.version ? c : null;
			} catch (e) {
				return null;
			}
		}

		// Supprime les cookies de mesure/publicité déjà posés quand le consentement est retiré.
		function clearTrackers() {
			var host = location.hostname.split('.');
			document.cookie.split('; ').forEach(function (pair) {
				var name = pair.split('=')[0];
				if (!/^(_ga|_gid|_gat|_gcl|_fbp|_fbc|_ttp|_tt_)/.test(name)) { return; }
				for (var i = 0; i < host.length - 1; i++) {
					var domain = host.slice(i).join('.');
					document.cookie = name + '=; Max-Age=0; Path=/; Domain=.' + domain;
				}
				document.cookie = name + '=; Max-Age=0; Path=/';
			});
		}

		function apply(c) {
			var analytics = c.analytics ? 'granted' : 'denied';
			var marketing = c.marketing ? 'granted' : 'denied';
			gtag('consent', 'update', {
				analytics_storage: analytics,
				ad_storage: marketing,
				ad_user_data: marketing,
				ad_personalization: marketing
			});
			window.dataLayer.push({ event: 'aa_consent_update', aa_consent: { analytics: !!c.analytics, marketing: !!c.marketing } });
			if (cfg.mode === 'advanced' || c.analytics || c.marketing) { loadGtm(); }
		}

		function save(choice) {
			var previous = read();
			var c = { v: cfg.version, analytics: !!choice.analytics, marketing: !!choice.marketing, ts: Date.now() };
			document.cookie = cfg.cookie + '=' + encodeURIComponent(JSON.stringify(c)) +
				'; Max-Age=' + cfg.maxAge + '; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
			// Accord donné après un refus enregistré : GTM a déjà vu ce refus sur cette page et n'y
			// déclencherait pas la mesure ; on recharge pour repartir avec le nouvel accord.
			if (previous && !previous.analytics && !previous.marketing && (c.analytics || c.marketing)) {
				location.reload();
				return c;
			}
			apply(c);
			if (previous && ((previous.analytics && !c.analytics) || (previous.marketing && !c.marketing))) {
				// Consentement retiré : les scripts Google déjà chargés pourraient réécrire leurs
				// cookies tant que la page reste ouverte. On les efface et on recharge la page, qui
				// repart sans GTM.
				clearTrackers();
				if (window.google_tag_manager) {
					location.reload();
				}
			}
			return c;
		}

		var stored = read();
		// Sans accord, aucun cookie de mesure ne doit rester (anciens cookies, choix retiré…).
		if (!stored || (!stored.analytics && !stored.marketing)) {
			clearTrackers();
		}
		if (stored) {
			apply(stored);
		} else {
			// Pas encore de choix : le bandeau s'affiche dès le premier rendu (voir consent.css).
			document.documentElement.classList.add('aa-needs-consent');
			if (cfg.mode === 'advanced') { loadGtm(); }
		}

		window.aaConsent = { get: read, save: save };
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'aarise_consent_head', 1 );

/**
 * Styles et script de l'interface du bandeau.
 */
function aarise_consent_assets() {
	$uri = get_stylesheet_directory_uri();
	wp_enqueue_style( 'aarise-consent', $uri . '/assets/css/consent.css', array(), AARISE_VERSION );
	wp_enqueue_script( 'aarise-consent', $uri . '/assets/js/consent.js', array(), AARISE_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
}
add_action( 'wp_enqueue_scripts', 'aarise_consent_assets' );

/**
 * Bandeau et panneau de personnalisation (masqués jusqu'à ce que le script décide de les afficher).
 */
function aarise_consent_banner() {
	$s = aarise_consent_strings();
	?>
	<div id="aa-consent" class="aa-consent" role="region" aria-label="<?php echo esc_attr( $s['consent_label'] ); ?>" hidden>
		<div class="aa-consent__bar">
			<p class="aa-consent__text">
				<?php echo esc_html( $s['consent_text'] ); ?>
				<a href="<?php echo esc_url( aarise_cookie_policy_url() ); ?>"><?php echo esc_html( $s['consent_more'] ); ?></a>
			</p>
			<div class="aa-consent__actions">
				<button type="button" class="aa-consent__btn aa-consent__btn--ghost" data-aa-consent="reject"><?php echo esc_html( $s['consent_reject'] ); ?></button>
				<button type="button" class="aa-consent__btn" data-aa-consent="accept"><?php echo esc_html( $s['consent_accept'] ); ?></button>
			</div>
		</div>

		<div id="aa-consent-panel" class="aa-consent__panel" hidden>
			<fieldset>
				<legend><?php echo esc_html( $s['consent_panel_title'] ); ?></legend>

				<label class="aa-consent__option">
					<input type="checkbox" checked disabled>
					<span><strong><?php echo esc_html( $s['consent_necessary'] ); ?></strong> <?php echo esc_html( $s['consent_necessary_desc'] ); ?></span>
				</label>

				<label class="aa-consent__option">
					<input type="checkbox" name="analytics">
					<span><strong><?php echo esc_html( $s['consent_analytics'] ); ?></strong> <?php echo esc_html( $s['consent_analytics_desc'] ); ?></span>
				</label>

				<label class="aa-consent__option">
					<input type="checkbox" name="marketing">
					<span><strong><?php echo esc_html( $s['consent_marketing'] ); ?></strong> <?php echo esc_html( $s['consent_marketing_desc'] ); ?></span>
				</label>
			</fieldset>

			<button type="button" class="aa-consent__btn" data-aa-consent="save"><?php echo esc_html( $s['consent_save'] ); ?></button>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'aarise_consent_banner', 5 );
