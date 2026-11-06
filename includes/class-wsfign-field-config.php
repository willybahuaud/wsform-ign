<?php
/**
 * Morceaux de configuration communs aux champs du plugin.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Évite que le champ Adresse dépende de la classe du champ Carte.
 */
class WSFIGN_Field_Config {

	const FIELD_GROUP = 'wsfign';

	/**
	 * Ajoute un type de champ au groupe du plugin, en créant le groupe au besoin.
	 *
	 * @param array  $field_types Types de champs connus de WS Form.
	 * @param string $type        Identifiant du type de champ.
	 * @param array  $config      Définition du type de champ.
	 * @return array
	 */
	public static function add_to_group( $field_types, $type, $config ) {
		if ( ! isset( $field_types[ self::FIELD_GROUP ] ) ) {
			$field_types[ self::FIELD_GROUP ] = array(
				'label' => __( 'French public services', 'wsform-ign' ),
				'types' => array(),
			);
		}

		$field_types[ self::FIELD_GROUP ]['types'][ $type ] = $config;

		return $field_types;
	}

	/**
	 * Construit le réglage d'un texte, vide par défaut pour suivre les traductions.
	 *
	 * @param string $label        Libellé du réglage.
	 * @param string $default_text Texte utilisé si le réglage reste vide.
	 * @return array
	 */
	public static function build_text_meta_key( $label, $default_text ) {
		return array(
			'label'           => $label,
			'type'            => 'text',
			'default'         => '',
			'placeholder'     => $default_text,
			'variable_helper' => true,
		);
	}

	/**
	 * Retourne les groupes de fin de l'onglet Basique.
	 *
	 * @return array
	 */
	public static function get_common_basic_fieldsets() {
		return array(
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
		);
	}

	/**
	 * Retourne l'onglet Avancé. Seuls le style et les libellés diffèrent d'un champ à l'autre.
	 *
	 * @param string[] $style_keys Réglages du groupe Style.
	 * @param string[] $label_keys Réglages du groupe Libellés.
	 * @return array
	 */
	public static function get_advanced_tab( $style_keys, $label_keys ) {
		return array(
			'label'     => __( 'Advanced', 'wsform-ign' ),
			'fieldsets' => array(
				array(
					'label'     => __( 'Style', 'wsform-ign' ),
					'meta_keys' => $style_keys,
				),
				array(
					'label'     => __( 'Classes', 'wsform-ign' ),
					'meta_keys' => array( 'class_field_wrapper', 'class_field' ),
				),
				array(
					'label'     => __( 'Labels', 'wsform-ign' ),
					'meta_keys' => $label_keys,
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
		);
	}
}
