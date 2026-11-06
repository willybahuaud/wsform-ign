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
		if ( isset( $field->type ) && WSFIGN_Map_Field::FIELD_TYPE === $field->type ) {
			self::enqueue_map_assets();
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
		wp_enqueue_script( 'wsfign-map', WSFIGN_URL . 'assets/js/wsfign-map.js', array( 'jquery', 'wsfign-leaflet' ), WSFIGN_VERSION, true );

		wp_localize_script( 'wsfign-map', 'wsfignMapSettings', self::get_map_settings() );
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
				),
				'ortho' => array(
					'label'  => __( 'Aerial photos', 'wsform-ign' ),
					'layer'  => 'ORTHOIMAGERY.ORTHOPHOTOS',
					'format' => 'image/jpeg',
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
