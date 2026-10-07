<?php
/**
 * Données structurées (schema.org, JSON-LD) pour Google.
 *
 * - Accueil : WebSite (nom du site dans les résultats) et Organization (logo, LinkedIn, adresse).
 * - Offre d'emploi : JobPosting, pour les résultats emploi de Google. Tout est tiré de la fiche :
 *   titre, date de publication, contenu, et la liste de faits en tête d'offre (style « Facts ») :
 *     Contract  … Permanent (CDI) …          → type de contrat
 *     Location  … on site / hybrid …          → « fully remote » = télétravail complet
 *     Experience … 3 years …                  → expérience demandée
 *     Salary    … €32,000 to €35,000 … per year → fourchette de salaire
 *   Une ligne absente est simplement omise. Une offre passée en brouillon disparaît de Google.
 *
 * @package AARISE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le studio, tel que décrit à Google.
 *
 * @return array
 */
function aarise_schema_organization() {
	return array(
		'@type'   => 'Organization',
		'@id'     => home_url( '/#organization' ),
		'name'    => 'AARISE',
		'url'     => home_url( '/' ),
		'logo'    => get_stylesheet_directory_uri() . '/assets/img/favicon/apple-touch-icon.png',
		'sameAs'  => array( 'https://www.linkedin.com/company/aarise-games' ),
		'address' => aarise_schema_address(),
	);
}

function aarise_schema_address() {
	return array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => '6 rue Virginie Hériot',
		'addressLocality' => 'La Rochelle',
		'postalCode'      => '17000',
		'addressRegion'   => 'Nouvelle-Aquitaine',
		'addressCountry'  => 'FR',
	);
}

/**
 * Lignes « Libellé valeur » de la liste de faits d'une offre.
 *
 * @param string $content Contenu de l'offre.
 * @return array Libellé en minuscules => valeur en texte brut.
 */
function aarise_job_facts( $content ) {
	$facts = array();
	if ( preg_match_all( '#<li[^>]*>\s*<strong>([^<:]+):?</strong>(.*?)</li>#is', $content, $matches, PREG_SET_ORDER ) ) {
		foreach ( $matches as $m ) {
			$label = strtolower( trim( wp_strip_all_tags( $m[1] ) ) );
			if ( ! isset( $facts[ $label ] ) ) {
				$facts[ $label ] = trim( html_entity_decode( wp_strip_all_tags( $m[2] ), ENT_QUOTES, 'UTF-8' ) );
			}
		}
	}
	return $facts;
}

/**
 * JobPosting d'une offre publiée.
 *
 * @param WP_Post $post Offre.
 * @return array
 */
function aarise_schema_job( $post ) {
	$facts = aarise_job_facts( $post->post_content );
	$job   = array(
		'@type'              => 'JobPosting',
		'title'              => get_the_title( $post ),
		'description'        => wp_kses_post( do_blocks( $post->post_content ) ),
		'datePosted'         => get_the_date( 'c', $post ),
		'url'                => get_permalink( $post ),
		'identifier'         => array(
			'@type' => 'PropertyValue',
			'name'  => 'AARISE',
			'value' => (string) $post->ID,
		),
		'hiringOrganization' => array(
			'@type'  => 'Organization',
			'name'   => 'AARISE',
			'sameAs' => home_url( '/' ),
			'logo'   => get_stylesheet_directory_uri() . '/assets/img/favicon/apple-touch-icon.png',
		),
		'jobLocation'        => array(
			'@type'   => 'Place',
			'address' => aarise_schema_address(),
		),
		'directApply'        => true,
	);

	$contract = isset( $facts['contract'] ) ? $facts['contract'] : '';
	$types    = array(
		'INTERN'     => '/\b(intern|internship|stage)\b/i',
		'PART_TIME'  => '/part[- ]time/i',
		'TEMPORARY'  => '/\b(fixed[- ]term|temporary|CDD)\b/i',
		'CONTRACTOR' => '/\b(freelance|contractor)\b/i',
		'FULL_TIME'  => '/\b(permanent|CDI|full[- ]time)\b/i',
	);
	foreach ( $types as $type => $pattern ) {
		if ( preg_match( $pattern, $contract ) ) {
			$job['employmentType'] = $type;
			break;
		}
	}

	$location = isset( $facts['location'] ) ? $facts['location'] : '';
	if ( preg_match( '/full(y)?[- ]remote|100\s*%\s*remote/i', $location ) ) {
		$job['jobLocationType']                = 'TELECOMMUTE';
		$job['applicantLocationRequirements'] = array( '@type' => 'Country', 'name' => 'France' );
	}

	if ( isset( $facts['experience'] ) && preg_match( '/(\d+)\s*\+?\s*years?/i', $facts['experience'], $m ) ) {
		$job['experienceRequirements'] = array(
			'@type'              => 'OccupationalExperienceRequirements',
			'monthsOfExperience' => (int) $m[1] * 12,
		);
	}

	if ( isset( $facts['salary'] ) && preg_match_all( '/(\d{1,3}(?:[ ,.\x{202F}\x{A0}]\d{3})+|\d{4,})/u', $facts['salary'], $m ) ) {
		$amounts = array_map(
			static function ( $n ) {
				return (int) preg_replace( '/\D/', '', $n );
			},
			$m[1]
		);
		$value = array(
			'@type'    => 'QuantitativeValue',
			'unitText' => preg_match( '/month/i', $facts['salary'] ) ? 'MONTH' : 'YEAR',
		);
		if ( count( $amounts ) > 1 ) {
			$value['minValue'] = min( $amounts );
			$value['maxValue'] = max( $amounts );
		} else {
			$value['value'] = $amounts[0];
		}
		$job['baseSalary'] = array(
			'@type'    => 'MonetaryAmount',
			'currency' => 'EUR',
			'value'    => $value,
		);
	}

	return $job;
}

/**
 * Sortie dans le <head>, sauf si une extension SEO s'en charge.
 */
function aarise_schema_output() {
	if ( defined( 'SURERANK_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}

	$graph = array();
	if ( is_front_page() ) {
		$graph[] = array(
			'@type'     => 'WebSite',
			'@id'       => home_url( '/#website' ),
			'name'      => 'AARISE',
			'url'       => home_url( '/' ),
			'publisher' => array( '@id' => home_url( '/#organization' ) ),
		);
		$graph[] = aarise_schema_organization();
	} elseif ( is_singular( 'job' ) && 'publish' === get_post_status() ) {
		$graph[] = aarise_schema_job( get_queried_object() );
	}

	if ( $graph ) {
		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG )
		);
	}
}
add_action( 'wp_head', 'aarise_schema_output', 6 );
