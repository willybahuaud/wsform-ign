<?php
/**
 * Chargement à la demande de Leaflet et des scripts du plugin.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ne charge les fichiers que si un formulaire de la page contient un de nos champs.
 */
class WSFIGN_Assets {

	const LEAFLET_VERSION = '1.9.4';

	/**
	 * Branche le chargement sur WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'wsf_form_pre_process_field', array( __CLASS__, 'maybe_enqueue_for_field' ), 10, 1 );
	}

	/**
	 * Charge les fichiers quand WS Form parcourt un de nos champs.
	 *
	 * Ce crochet ne se déclenche que pour les formulaires réellement rendus,
	 * contrairement à wsf_enqueue_scripts qui ne dit rien de leur contenu.
	 *
	 * @param object $field Champ en cours de traitement.
	 * @return void
	 */
	public static function maybe_enqueue_for_field( $field ) {
		if ( ! isset( $field->type ) ) {
			return;
		}

		if ( WSFIGN_Map_Field::FIELD_TYPE === $field->type ) {
			self::enqueue_map_assets();
		}

		if ( WSFIGN_Address_Field::FIELD_TYPE === $field->type ) {
			self::enqueue_address_assets();
		}
	}

	/**
	 * Charge Leaflet et le script de la carte.
	 *
	 * @return void
	 */
	private static function enqueue_map_assets() {
		wp_enqueue_style( 'wsfign-leaflet', WSFIGN_URL . 'assets/vendor/leaflet/leaflet.css', array(), self::LEAFLET_VERSION );
		wp_enqueue_style( 'wsfign-map', WSFIGN_URL . 'assets/css/wsfign-map.css', array( 'wsfign-leaflet' ), WSFIGN_VERSION );

		wp_enqueue_script( 'wsfign-leaflet', WSFIGN_URL . 'assets/vendor/leaflet/leaflet.js', array(), self::LEAFLET_VERSION, true );
		wp_enqueue_script( 'wsfign-fields', WSFIGN_URL . 'assets/js/wsfign-fields.js', array( 'jquery' ), WSFIGN_VERSION, true );
		wp_enqueue_script( 'wsfign-map', WSFIGN_URL . 'assets/js/wsfign-map.js', array( 'jquery', 'wsfign-leaflet', 'wsfign-fields' ), WSFIGN_VERSION, true );

		wp_localize_script( 'wsfign-map', 'wsfignMapSettings', self::get_map_settings() );
	}

	/**
	 * Charge le script de l'adresse, sans Leaflet : le champ peut être seul dans le formulaire.
	 *
	 * @return void
	 */
	private static function enqueue_address_assets() {
		wp_enqueue_style( 'wsfign-address', WSFIGN_URL . 'assets/css/wsfign-address.css', array(), WSFIGN_VERSION );

		wp_enqueue_script( 'wsfign-fields', WSFIGN_URL . 'assets/js/wsfign-fields.js', array( 'jquery' ), WSFIGN_VERSION, true );
		wp_enqueue_script( 'wsfign-ban-client', WSFIGN_URL . 'assets/js/wsfign-ban-client.js', array(), WSFIGN_VERSION, true );
		wp_enqueue_script( 'wsfign-address', WSFIGN_URL . 'assets/js/wsfign-address.js', array( 'jquery', 'wsfign-fields', 'wsfign-ban-client' ), WSFIGN_VERSION, true );

		wp_localize_script( 'wsfign-address', 'wsfignAddressSettings', self::get_address_settings() );
	}

	/**
	 * Retourne les textes destinés au script de l'adresse.
	 *
	 * @return array
	 */
	private static function get_address_settings() {
		return array(
			'i18n' => array(
				'suggestions'    => __( 'Address suggestions', 'wsform-ign' ),
				'oneResult'      => __( '1 suggestion available. Use the arrow keys to browse.', 'wsform-ign' ),
				/* translators: %d: number of suggestions. */
				'manyResults'    => __( '%d suggestions available. Use the arrow keys to browse.', 'wsform-ign' ),
				'noResults'      => __( 'No suggestion. You can keep typing your address.', 'wsform-ign' ),
				'unavailable'    => __( 'Address suggestions are unavailable right now. You can type your address by hand.', 'wsform-ign' ),
				'noAddressFound' => __( 'No address found at this location.', 'wsform-ign' ),
			),
		);
	}

	/**
	 * Retourne les couches et les textes destinés au script de la carte.
	 *
	 * @return array
	 */
	private static function get_map_settings() {
		return array(
			'layers' => array(
				'plan'  => array(
					'label'  => __( 'IGN map', 'wsform-ign' ),
					'layer'  => 'GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2',
					'format' => 'image/png',
					'source' => 'https://cartes.gouv.fr/rechercher-une-donnee/dataset/IGNF_PLAN-IGN',
				),
				'ortho' => array(
					'label'  => __( 'Aerial photos', 'wsform-ign' ),
					'layer'  => 'ORTHOIMAGERY.ORTHOPHOTOS',
					'format' => 'image/jpeg',
					'source' => 'https://cartes.gouv.fr/rechercher-une-donnee/dataset/IGNF_BD-ORTHO',
				),
			),
			'i18n'   => array(
				'mapLabel'      => __( 'Map', 'wsform-ign' ),
				'placeAtCenter' => __( 'Place the marker at the center of the map', 'wsform-ign' ),
				'locateMe'      => __( 'Center on my location', 'wsform-ign' ),
				'locateError'   => __( 'Your location could not be determined.', 'wsform-ign' ),
				'tilesError'    => __( 'The map background is unavailable right now. You can still send this form.', 'wsform-ign' ),
				/* translators: 1: latitude, 2: longitude. */
				'markerPlaced'  => __( 'Marker placed at latitude %1$s, longitude %2$s.', 'wsform-ign' ),
				'markerCleared' => __( 'Marker removed.', 'wsform-ign' ),
			),
		);
	}
}
