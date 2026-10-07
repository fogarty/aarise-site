<?php
/**
 * Candidatures → tableau de suivi (Google Sheets).
 *
 * À chaque envoi du formulaire de candidature (SureForms, slug job-application), le site
 * transmet la candidature et le CV (PDF) au script Google lié au tableau « Suivi candidatures ».
 * Le script range le CV dans Drive, le fait analyser par Claude et ajoute une ligne au tableau.
 * Code du script : tools/recruitment-sheet/Code.gs.
 *
 * L'envoi se fait en tâche de fond (WP-Cron) pour ne pas ralentir le formulaire, avec
 * jusqu'à 3 nouvelles tentatives en cas d'échec. Le script ignore les doublons.
 *
 * Réglage : Job offers > Applications sheet (adresse du script et clé partagée, jamais
 * dans le dépôt). Sans adresse, rien n'est envoyé (cas du staging par défaut).
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

const AARISE_RECRUIT_OPTION = 'aarise_recruitment_sheet';

/**
 * Réglages enregistrés : adresse du script et clé partagée.
 *
 * @return array{url:string,secret:string}
 */
function aarise_recruitment_settings() {
	$settings = get_option( AARISE_RECRUIT_OPTION, array() );
	return array(
		'url'    => isset( $settings['url'] ) ? (string) $settings['url'] : '',
		'secret' => isset( $settings['secret'] ) ? (string) $settings['secret'] : '',
	);
}

/**
 * Candidature reçue : on programme son envoi au tableau.
 *
 * @param array $response Réponse SureForms (form_id, entry_id, data indexé par slug de champ).
 */
function aarise_recruitment_on_submit( $response ) {
	$form = get_page_by_path( 'job-application', OBJECT, 'sureforms_form' );
	if ( ! $form || empty( $response['form_id'] ) || (int) $response['form_id'] !== $form->ID ) {
		return;
	}
	if ( '' === aarise_recruitment_settings()['url'] ) {
		return;
	}

	$data  = isset( $response['data'] ) && is_array( $response['data'] ) ? $response['data'] : array();
	$field = static function ( $slug ) use ( $data ) {
		return isset( $data[ $slug ] ) ? trim( wp_strip_all_tags( (string) $data[ $slug ] ) ) : '';
	};

	$application = array(
		'entry_id'     => isset( $response['entry_id'] ) ? (int) $response['entry_id'] : 0,
		'submitted_at' => gmdate( 'c' ),
		'site'         => wp_parse_url( home_url(), PHP_URL_HOST ),
		'first_name'   => $field( 'first-name' ),
		'last_name'    => $field( 'last-name' ),
		'email'        => $field( 'srfm-email' ),
		'position'     => $field( 'position' ),
		'portfolio'    => $field( 'portfolio' ),
		'linkedin'     => $field( 'linkedin' ),
		'message'      => $field( 'message' ),
		'consent'      => '' !== $field( 'srfm-gdpr' ) && 'false' !== $field( 'srfm-gdpr' ),
		'cv_url'       => trim( explode( ',', $field( 'cv' ) )[0] ),
	);
	if ( ! $application['entry_id'] ) {
		// Sans numéro d'entrée, une clé stable évite les doublons côté script.
		$application['entry_id'] = 'h' . substr( md5( $application['email'] . $application['position'] . $application['message'] ), 0, 12 );
	}

	wp_schedule_single_event( time(), 'aarise_recruitment_forward', array( $application, 0 ) );
}
add_action( 'srfm_form_submit', 'aarise_recruitment_on_submit' );

/**
 * Retrouve le CV envoyé : fichier local d'abord, sinon téléchargement.
 *
 * @param string $url Adresse du fichier donnée par SureForms.
 * @return string Contenu du PDF, vide si introuvable.
 */
function aarise_recruitment_read_cv( $url ) {
	if ( '' === $url ) {
		return '';
	}
	$uploads = wp_get_upload_dir();
	$path    = wp_normalize_path( str_replace( $uploads['baseurl'], $uploads['basedir'], rawurldecode( $url ) ) );
	if ( 0 === strpos( $path, wp_normalize_path( $uploads['basedir'] ) ) && is_readable( $path ) ) {
		return (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	// Fichier protégé (SureForms Pro) : on le cherche par son nom dans le dossier SureForms.
	$name = sanitize_file_name( basename( (string) wp_parse_url( rawurldecode( $url ), PHP_URL_PATH ) ) );
	if ( $name ) {
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $uploads['basedir'] . '/sureforms', FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file ) {
			if ( $file->getFilename() === $name && $file->isReadable() ) {
				return (string) file_get_contents( $file->getPathname() ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}
		}
	}

	$download = wp_remote_get( $url, array( 'timeout' => 30 ) );
	if ( ! is_wp_error( $download ) && 200 === wp_remote_retrieve_response_code( $download ) ) {
		return (string) wp_remote_retrieve_body( $download );
	}
	return '';
}

/**
 * Envoie une candidature au script Google du tableau.
 *
 * @param array $application Candidature.
 * @param int   $attempt     Tentative en cours (0 = première).
 */
function aarise_recruitment_forward( $application, $attempt = 0 ) {
	$settings = aarise_recruitment_settings();
	if ( '' === $settings['url'] ) {
		return;
	}
	if ( function_exists( 'set_time_limit' ) ) {
		set_time_limit( 300 ); // L'analyse du CV prend jusqu'à une minute ou deux.
	}

	$pdf     = aarise_recruitment_read_cv( $application['cv_url'] );
	$payload = $application;
	unset( $payload['cv_url'] );
	$payload['secret']   = $settings['secret'];
	$payload['cv_name']  = sanitize_file_name( basename( (string) wp_parse_url( rawurldecode( $application['cv_url'] ), PHP_URL_PATH ) ) );
	$payload['cv_pdf']   = '%PDF' === substr( $pdf, 0, 4 ) ? base64_encode( $pdf ) : ''; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	$payload['cv_found'] = '' !== $payload['cv_pdf'];

	$result = wp_remote_post(
		$settings['url'],
		array(
			'timeout' => 240,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
		)
	);

	$body = is_wp_error( $result ) ? '' : wp_remote_retrieve_body( $result );
	$json = json_decode( $body, true );
	if ( is_array( $json ) && ! empty( $json['ok'] ) ) {
		return;
	}

	$error = is_wp_error( $result ) ? $result->get_error_message() : substr( $body, 0, 300 );
	error_log( sprintf( 'AARISE recruitment sheet: attempt %d failed for entry %s: %s', $attempt + 1, $application['entry_id'], $error ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	if ( $attempt < 3 && ! ( is_array( $json ) && ! empty( $json['fatal'] ) ) ) {
		wp_schedule_single_event( time() + 300 * ( $attempt + 1 ), 'aarise_recruitment_forward', array( $application, $attempt + 1 ) );
	}
}
add_action( 'aarise_recruitment_forward', 'aarise_recruitment_forward', 10, 2 );

/**
 * Page de réglage : Job offers > Applications sheet.
 */
function aarise_recruitment_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=job',
		'Applications sheet',
		'Applications sheet',
		'manage_options',
		'aarise-recruitment-sheet',
		'aarise_recruitment_admin_page'
	);
}
add_action( 'admin_menu', 'aarise_recruitment_admin_menu' );

function aarise_recruitment_register_setting() {
	register_setting(
		'aarise_recruitment',
		AARISE_RECRUIT_OPTION,
		array(
			'type'              => 'array',
			'show_in_rest'      => false,
			'sanitize_callback' => static function ( $value ) {
				$previous = aarise_recruitment_settings();
				$secret   = isset( $value['secret'] ) ? trim( (string) $value['secret'] ) : '';
				return array(
					'url'    => isset( $value['url'] ) ? esc_url_raw( trim( (string) $value['url'] ), array( 'https' ) ) : '',
					// Champ laissé vide = clé inchangée (elle n'est jamais réaffichée).
					'secret' => '' === $secret ? $previous['secret'] : $secret,
				);
			},
		)
	);
}
add_action( 'admin_init', 'aarise_recruitment_register_setting' );

function aarise_recruitment_admin_page() {
	$settings = aarise_recruitment_settings();
	?>
	<div class="wrap">
		<h1>Applications sheet</h1>
		<p>Each application sent from the site is added to the recruitment spreadsheet, with the CV saved in Google Drive and analysed by Claude.
			Leave the address empty to turn this off.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'aarise_recruitment' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="aarise-recruit-url">Google script address</label></th>
					<td><input type="url" class="large-text code" id="aarise-recruit-url" name="<?php echo esc_attr( AARISE_RECRUIT_OPTION ); ?>[url]" value="<?php echo esc_attr( $settings['url'] ); ?>" placeholder="https://script.google.com/macros/s/…/exec"></td>
				</tr>
				<tr>
					<th scope="row"><label for="aarise-recruit-secret">Shared key</label></th>
					<td>
						<input type="password" class="regular-text code" id="aarise-recruit-secret" name="<?php echo esc_attr( AARISE_RECRUIT_OPTION ); ?>[secret]" value="" autocomplete="new-password" placeholder="<?php echo $settings['secret'] ? esc_attr__( 'Saved — leave empty to keep it' ) : ''; ?>">
						<p class="description">The same value as SHARED_SECRET in the script properties.</p>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
