<?php
/**
 * Déclaration du type de champ « Carte IGN » auprès de WS Form.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Type de champ wsfign_map : mêmes réglages que le champ Google Map du cœur.
 */
class WSFIGN_Map_Field {

	const FIELD_TYPE = 'wsfign_map';

	/**
	 * Branche le champ sur les filtres de configuration de WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_config_field_types', array( __CLASS__, 'register_field_type' ) );
		add_filter( 'wsf_config_meta_keys', array( __CLASS__, 'register_meta_keys' ), 10, 1 );
	}

	/**
	 * Ajoute le type de champ et son groupe.
	 *
	 * @param array $field_types Types de champs connus de WS Form.
	 * @return array
	 */
	public static function register_field_type( $field_types ) {
		return WSFIGN_Field_Config::add_to_group( $field_types, self::FIELD_TYPE, self::get_field_type_config() );
	}

	/**
	 * Indique si une méta de soumission porte la valeur d'une carte.
	 *
	 * @param mixed $field_meta Méta de la soumission.
	 * @return bool
	 */
	public static function is_map_meta( $field_meta ) {
		return isset( $field_meta['type'], $field_meta['value'] ) && self::FIELD_TYPE === $field_meta['type'] && is_string( $field_meta['value'] );
	}

	/**
	 * Retourne la définition du type de champ.
	 *
	 * L'input reste un champ texte masqué, et non type="hidden" : la validation
	 * HTML5 ignore les champs hidden, ce qui casserait le marqueur obligatoire.
	 *
	 * @return array
	 */
	private static function get_field_type_config() {
		return array(
			'label'                       => __( 'IGN Map', 'wsform-ign' ),
			'label_default'               => __( 'Map', 'wsform-ign' ),
			'icon'                        => 'googlemap',
			'submit_save'                 => true,
			'submit_edit'                 => false,
			'submit_array'                => false,
			'calc_in'                     => false,
			'calc_out'                    => false,
			'text_in'                     => false,
			'text_out'                    => true,
			'value_out'                   => true,
			'mappable'                    => true,
			'has_required'                => true,
			'progress'                    => true,
			'keyword'                     => __( 'location place address latitude longitude ign carte', 'wsform-ign' ),
			'conditional'                 => array(
				'logics_enabled'  => array( 'equals', 'equals_not', 'blank', 'blank_not', 'field_match', 'field_match_not', 'validate', 'validate_not', 'change' ),
				'actions_enabled' => array( 'visibility', 'required', 'value', 'set_custom_validity', 'class_add_wrapper', 'class_remove_wrapper', 'class_add_field', 'class_remove_field', 'reset', 'clear' ),
				'condition_event' => 'change',
			),
			'events'                      => array(
				'event'        => 'change',
				'event_action' => __( 'Field', 'wsform-ign' ),
			),
			'trigger'                     => 'change',
			'mask_field'                  => '#pre_label#pre_help<input type="text" id="#id" name="#name" value="#value" style="display: none;" data-wsfign-map#attributes /><div id="#id-map" class="wsfign-map"><div class="wsfign-map-canvas"></div></div>#post_label#invalid_feedback#post_help',
			'mask_field_attributes'       => array( 'class', 'required', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes', 'hidden_bypass' ),
			'mask_field_label'            => '<label id="#label_id" for="#id"#attributes>#label</label>',
			'mask_field_label_attributes' => array( 'class' ),
			'mask_help_append'            => '<span class="wsfign-map-clear">#help_append_separator<a href="#" data-action="wsfign-map-clear">#text_clear</a></span>',
			'mask_help_append_separator'  => '<br />',
			'fieldsets'                   => self::get_fieldsets(),
		);
	}

	/**
	 * Retourne les onglets de réglages du champ.
	 *
	 * @return array
	 */
	private static function get_fieldsets() {
		return array(
			'basic'    => array(
				'label'     => __( 'Basic', 'wsform-ign' ),
				'meta_keys' => array( 'label', 'label_render', 'required', 'hidden', 'hidden_warning', 'help' ),
				'fieldsets' => array_merge(
					array(
						array(
							'label'     => __( 'Map', 'wsform-ign' ),
							'meta_keys' => array( 'wsfign_map_lat', 'wsfign_map_lng', 'wsfign_map_zoom', 'wsfign_map_layer', 'wsfign_map_center' ),
						),
						array(
							'label'     => __( 'Controls', 'wsform-ign' ),
							'meta_keys' => array( 'wsfign_map_control_layers', 'wsfign_map_control_zoom' ),
						),
						array(
							'label'     => __( 'Marker', 'wsform-ign' ),
							'meta_keys' => array( 'wsfign_map_marker_title', 'wsfign_map_marker_icon_url', 'wsfign_map_show_coordinates' ),
						),
					),
					WSFIGN_Field_Config::get_common_basic_fieldsets()
				),
			),
			'advanced' => WSFIGN_Field_Config::get_advanced_tab(
				array( 'label_position_no_inside', 'label_column_width', 'help_position', 'class_single_vertical_align', 'wsfign_map_height' ),
				array( 'text_clear', 'wsfign_map_text_hint', 'wsfign_map_text_place_button', 'wsfign_map_text_placed', 'wsfign_map_text_updated', 'wsfign_map_text_removed', 'wsfign_map_text_unavailable', 'wsfign_map_text_locate_error' )
			),
		);
	}

	/**
	 * Déclare les réglages propres au champ.
	 *
	 * @param array $meta_keys Réglages connus de WS Form.
	 * @return array
	 */
	public static function register_meta_keys( $meta_keys ) {
		return array_merge( $meta_keys, self::get_map_meta_keys(), self::get_marker_meta_keys(), self::get_text_meta_keys() );
	}

	/**
	 * Retourne les réglages de la carte. Le centre par défaut est celui de la France.
	 *
	 * @return array
	 */
	private static function get_map_meta_keys() {
		return array(
			'wsfign_map_lat'              => array(
				'label'           => __( 'Latitude', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '46.6',
				'help'            => __( 'Initial latitude of the map.', 'wsform-ign' ),
			),
			'wsfign_map_lng'              => array(
				'label'           => __( 'Longitude', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '2.4',
				'help'            => __( 'Initial longitude of the map.', 'wsform-ign' ),
			),
			'wsfign_map_zoom'             => array(
				'label'           => __( 'Zoom', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '6',
				'placeholder'     => '6',
				'help'            => __( 'Initial zoom of the map (0 = world, 19 = building).', 'wsform-ign' ),
			),
			'wsfign_map_height'           => array(
				'label'           => __( 'Height', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '56.25%',
				'placeholder'     => '56.25%',
				'help'            => __( 'Height of the map: a percentage of its width (56.25% for 16:9) or a value in pixels (400px).', 'wsform-ign' ),
			),
			'wsfign_map_layer'            => array(
				'label'   => __( 'Base layer', 'wsform-ign' ),
				'type'    => 'select',
				'options' => array(
					array(
						'value' => 'plan',
						'text'  => __( 'IGN map', 'wsform-ign' ),
					),
					array(
						'value' => 'ortho',
						'text'  => __( 'Aerial photos', 'wsform-ign' ),
					),
				),
				'default' => 'plan',
			),
			'wsfign_map_center'           => array(
				'label'   => __( 'Visitor location', 'wsform-ign' ),
				'type'    => 'select',
				'options' => array(
					array(
						'value' => '',
						'text'  => __( 'None', 'wsform-ign' ),
					),
					array(
						'value' => 'browser',
						'text'  => __( 'Offer a button to center on the visitor', 'wsform-ign' ),
					),
				),
				'default' => '',
				'help'    => __( 'The browser only asks for the location after the visitor clicks the button.', 'wsform-ign' ),
			),
			'wsfign_map_control_layers'   => array(
				'label'   => __( 'Layer switcher', 'wsform-ign' ),
				'type'    => 'checkbox',
				'default' => 'on',
			),
			'wsfign_map_control_zoom'     => array(
				'label'   => __( 'Zoom buttons', 'wsform-ign' ),
				'type'    => 'checkbox',
				'default' => 'on',
			),
			'wsfign_map_show_coordinates' => array(
				'label'   => __( 'Show coordinates', 'wsform-ign' ),
				'type'    => 'checkbox',
				'default' => 'on',
				'help'    => __( 'Shows the latitude and longitude once the point is placed. Screen readers announce them either way.', 'wsform-ign' ),
			),
		);
	}

	/**
	 * Retourne les textes par défaut que chaque champ peut remplacer.
	 *
	 * @return string[] Textes, par clé attendue du script de la carte.
	 */
	public static function get_default_texts() {
		return array(
			'hintMouse'     => __( 'Click the map to place the point.', 'wsform-ign' ),
			'placeAtCenter' => __( 'Place the point at the center', 'wsform-ign' ),
			'markerPlaced'  => __( 'Point placed.', 'wsform-ign' ),
			'markerUpdated' => __( 'Point updated.', 'wsform-ign' ),
			'markerCleared' => __( 'Point removed.', 'wsform-ign' ),
			'tilesError'    => __( 'The map background is unavailable right now. You can still send this form.', 'wsform-ign' ),
			'locateError'   => __( 'Your location could not be determined.', 'wsform-ign' ),
		);
	}

	/**
	 * Retourne les réglages des textes de la carte.
	 *
	 * @return array
	 */
	private static function get_text_meta_keys() {
		$defaults = self::get_default_texts();

		return array(
			'wsfign_map_text_hint'         => WSFIGN_Field_Config::build_text_meta_key( __( 'Help above the map', 'wsform-ign' ), $defaults['hintMouse'] ),
			'wsfign_map_text_place_button' => WSFIGN_Field_Config::build_text_meta_key( __( 'Place button', 'wsform-ign' ), $defaults['placeAtCenter'] ),
			'wsfign_map_text_placed'       => WSFIGN_Field_Config::build_text_meta_key( __( 'Point placed', 'wsform-ign' ), $defaults['markerPlaced'] ),
			'wsfign_map_text_updated'      => WSFIGN_Field_Config::build_text_meta_key( __( 'Point updated', 'wsform-ign' ), $defaults['markerUpdated'] ),
			'wsfign_map_text_removed'      => WSFIGN_Field_Config::build_text_meta_key( __( 'Point removed', 'wsform-ign' ), $defaults['markerCleared'] ),
			'wsfign_map_text_unavailable'  => WSFIGN_Field_Config::build_text_meta_key( __( 'Map unavailable', 'wsform-ign' ), $defaults['tilesError'] ),
			'wsfign_map_text_locate_error' => WSFIGN_Field_Config::build_text_meta_key( __( 'Location error', 'wsform-ign' ), $defaults['locateError'] ),
		);
	}

	/**
	 * Retourne les réglages du marqueur.
	 *
	 * @return array
	 */
	private static function get_marker_meta_keys() {
		return array(
			'wsfign_map_marker_title'    => array(
				'label'           => __( 'Title', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '',
				'help'            => __( 'Text shown when hovering the marker.', 'wsform-ign' ),
			),
			'wsfign_map_marker_icon_url' => array(
				'label'           => __( 'Icon URL', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '',
				'help'            => __( 'URL of an image to replace the default marker.', 'wsform-ign' ),
			),
		);
	}
}
