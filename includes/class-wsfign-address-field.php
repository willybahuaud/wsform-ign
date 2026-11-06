<?php
/**
 * Déclaration du type de champ « Adresse BAN » auprès de WS Form.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Type de champ wsfign_address : un champ texte enrichi, donc la saisie libre reste possible.
 */
class WSFIGN_Address_Field {

	const FIELD_TYPE = 'wsfign_address';

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
	 * Ajoute le type de champ au groupe du plugin.
	 *
	 * @param array $field_types Types de champs connus de WS Form.
	 * @return array
	 */
	public static function register_field_type( $field_types ) {
		return WSFIGN_Map_Field::add_to_group( $field_types, self::FIELD_TYPE, self::get_field_type_config() );
	}

	/**
	 * Retourne la définition du type de champ.
	 *
	 * @return array
	 */
	private static function get_field_type_config() {
		return array(
			'label'                       => __( 'BAN Address', 'wsform-ign' ),
			'label_default'               => __( 'Address', 'wsform-ign' ),
			'icon'                        => 'googlemap',
			'submit_save'                 => true,
			'submit_edit'                 => true,
			'submit_array'                => false,
			'calc_in'                     => false,
			'calc_out'                    => false,
			'text_in'                     => false,
			'text_out'                    => true,
			'value_out'                   => true,
			'mappable'                    => true,
			'has_required'                => true,
			'progress'                    => true,
			'keyword'                     => __( 'location place address adresse ban', 'wsform-ign' ),
			'conditional'                 => array(
				'logics_enabled'  => array( 'equals', 'equals_not', 'contains', 'contains_not', 'starts', 'starts_not', 'ends', 'ends_not', 'blank', 'blank_not', 'cc==', 'cc!=', 'cc>', 'cc<', 'cw==', 'cw!=', 'cw>', 'cw<', 'regex', 'regex_not', 'field_match', 'field_match_not', 'validate', 'validate_not', 'focus', 'blur', 'change', 'input' ),
				'actions_enabled' => array( 'visibility', 'required', 'focus', 'value', 'disabled', 'readonly', 'set_custom_validity', 'class_add_wrapper', 'class_remove_wrapper', 'class_add_field', 'class_remove_field', 'reset', 'clear' ),
				'condition_event' => 'change input',
			),
			'events'                      => array(
				'event'        => 'change',
				'event_action' => __( 'Field', 'wsform-ign' ),
			),
			'trigger'                     => 'change',
			'mask_field'                  => '#pre_label#pre_help<input type="text" id="#id" name="#name" value="#value" data-wsfign-address#attributes /><div class="wsfign-address-anchor"><ul class="wsfign-address-listbox" role="listbox" hidden></ul></div>#post_label#invalid_feedback#post_help',
			'mask_field_attributes'       => array( 'class', 'disabled', 'readonly', 'required', 'placeholder', 'aria_describedby', 'aria_labelledby', 'aria_label', 'custom_attributes', 'hidden_bypass' ),
			'mask_field_label'            => '<label id="#label_id" for="#id"#attributes>#label</label>',
			'mask_field_label_attributes' => array( 'class' ),
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
				'meta_keys' => array( 'label', 'label_render', 'required', 'hidden', 'hidden_warning', 'placeholder', 'help' ),
				'fieldsets' => array(
					array(
						'label'     => __( 'Address Fields', 'wsform-ign' ),
						'meta_keys' => array( 'wsfign_address_field_mapping' ),
					),
					array(
						'label'     => __( 'IGN Map', 'wsform-ign' ),
						'meta_keys' => array( 'wsfign_address_map', 'wsfign_address_map_zoom', 'wsfign_address_map_reverse' ),
					),
					array(
						'label'     => __( 'Accessibility', 'wsform-ign' ),
						'meta_keys' => array( 'aria_label' ),
					),
					array(
						'label'     => __( 'Exclusions', 'wsform-ign' ),
						'meta_keys' => array( 'exclude_email' ),
					),
					array(
						'label'     => __( 'Hidden Behavior', 'wsform-ign' ),
						'meta_keys' => array( 'hidden_bypass' ),
					),
				),
			),
			'advanced' => array(
				'label'     => __( 'Advanced', 'wsform-ign' ),
				'fieldsets' => array(
					array(
						'label'     => __( 'Style', 'wsform-ign' ),
						'meta_keys' => array( 'label_position_no_inside', 'label_column_width', 'help_position', 'class_single_vertical_align' ),
					),
					array(
						'label'     => __( 'Classes', 'wsform-ign' ),
						'meta_keys' => array( 'class_field_wrapper', 'class_field' ),
					),
					array(
						'label'     => __( 'Restrictions', 'wsform-ign' ),
						'meta_keys' => array( 'field_user_status', 'field_user_roles', 'field_user_capabilities' ),
					),
					array(
						'label'     => __( 'Validation', 'wsform-ign' ),
						'meta_keys' => array( 'invalid_feedback_render', 'validate_inline', 'invalid_feedback' ),
					),
					array(
						'label'     => __( 'Custom Attributes', 'wsform-ign' ),
						'meta_keys' => array( 'custom_attributes' ),
					),
					array(
						'label'     => __( 'Breakpoints', 'wsform-ign' ),
						'meta_keys' => array( 'breakpoint_sizes' ),
						'class'     => array( 'wsf-fieldset-panel' ),
					),
				),
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
		return array_merge( $meta_keys, self::get_mapping_meta_keys(), self::get_map_link_meta_keys() );
	}

	/**
	 * Retourne les réglages du mapping des composants d'adresse.
	 *
	 * @return array
	 */
	private static function get_mapping_meta_keys() {
		return array(
			'wsfign_address_field_mapping' => array(
				'label'            => __( 'Field Mapping', 'wsform-ign' ),
				'type'             => 'repeater',
				'meta_keys'        => array( 'wsfign_address_component', 'ws_form_field_wsfign_address' ),
				'meta_keys_unique' => array( 'ws_form_field' ),
				'default'          => array(
					array(
						'wsfign_address_component' => 'address_line',
						'ws_form_field'            => '',
					),
					array(
						'wsfign_address_component' => 'postcode',
						'ws_form_field'            => '',
					),
					array(
						'wsfign_address_component' => 'city',
						'ws_form_field'            => '',
					),
				),
				'help'             => __( 'Fields to fill in when the visitor chooses an address.', 'wsform-ign' ),
			),
			'wsfign_address_component'     => array(
				'label'   => __( 'Component', 'wsform-ign' ),
				'type'    => 'select',
				'options' => self::get_component_options(),
			),
			// Le suffixe suit la convention du cœur : la valeur est rangée sous la clé ws_form_field.
			'ws_form_field_wsfign_address' => array(
				'label'                      => __( 'Form Field', 'wsform-ign' ),
				'type'                       => 'select',
				'options'                    => 'fields',
				'options_blank'              => __( 'Select...', 'wsform-ign' ),
				'fields_filter_type'         => array( 'wsfign_address', 'text', 'textarea', 'number', 'select', 'hidden', 'tel' ),
				'fields_filter_include_self' => true,
				'key'                        => 'ws_form_field',
			),
		);
	}

	/**
	 * Retourne les composants d'adresse proposés au mapping.
	 *
	 * Les valeurs correspondent aux clés produites par wsfign-ban-client.js.
	 *
	 * @return array
	 */
	private static function get_component_options() {
		$components = array(
			'label'        => __( 'Full address', 'wsform-ign' ),
			'address_line' => __( 'Number and street', 'wsform-ign' ),
			'postcode'     => __( 'Postal code', 'wsform-ign' ),
			'city'         => __( 'City', 'wsform-ign' ),
			'citycode'     => __( 'INSEE code', 'wsform-ign' ),
			'department'   => __( 'Department', 'wsform-ign' ),
			'lat'          => __( 'Latitude', 'wsform-ign' ),
			'lng'          => __( 'Longitude', 'wsform-ign' ),
		);
		$options    = array();

		foreach ( $components as $value => $text ) {
			$options[] = array(
				'value' => $value,
				'text'  => $text,
			);
		}

		return $options;
	}

	/**
	 * Retourne les réglages de la liaison à une carte IGN.
	 *
	 * @return array
	 */
	private static function get_map_link_meta_keys() {
		return array(
			'wsfign_address_map'         => array(
				'label'              => __( 'Field', 'wsform-ign' ),
				'type'               => 'select',
				'options'            => 'fields',
				'options_blank'      => __( 'Select...', 'wsform-ign' ),
				'fields_filter_type' => array( WSFIGN_Map_Field::FIELD_TYPE ),
				'help'               => __( 'IGN Map field to center on the chosen address.', 'wsform-ign' ),
			),
			'wsfign_address_map_zoom'    => array(
				'label'           => __( 'Zoom', 'wsform-ign' ),
				'type'            => 'text',
				'variable_helper' => true,
				'default'         => '16',
				'placeholder'     => '16',
				'help'            => __( 'Zoom of the map once an address is chosen.', 'wsform-ign' ),
			),
			'wsfign_address_map_reverse' => array(
				'label'   => __( 'Find the address on map click', 'wsform-ign' ),
				'type'    => 'checkbox',
				'default' => '',
				'help'    => __( 'When the visitor places the marker, this field and its mapped fields receive the nearest address.', 'wsform-ign' ),
			),
		);
	}
}
