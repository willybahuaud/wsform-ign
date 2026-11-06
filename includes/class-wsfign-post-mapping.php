<?php
/**
 * Passage de la valeur de la carte aux champs personnalisés de Post Management.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Présente la valeur en tableau aux passerelles ACF, Meta Box et autres.
 *
 * Elles attendent le tableau du champ Google, sans filtre sur leur conversion. Meta Box
 * écrirait « 0,0,5 » en silence à partir d'une chaîne JSON. Le mapping de méta simple
 * passe juste après et doit, lui, recevoir la chaîne : d'où la restauration.
 */
class WSFIGN_Post_Mapping {

	const PRIORITY_BEFORE_GATEWAYS = 9;
	const PRIORITY_AFTER_GATEWAYS  = 11;

	/**
	 * Valeurs d'origine, par clé de méta de la soumission.
	 *
	 * @var string[]
	 */
	private static $original_values = array();

	/**
	 * Encadre le traitement des passerelles.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_action_post_field_mapping', array( __CLASS__, 'expose_values_as_arrays' ), self::PRIORITY_BEFORE_GATEWAYS, 3 );
		add_filter( 'wsf_action_post_field_mapping', array( __CLASS__, 'restore_values' ), self::PRIORITY_AFTER_GATEWAYS, 3 );
		add_filter( 'wsf_action_post_get', array( __CLASS__, 'encode_populated_values' ), self::PRIORITY_AFTER_GATEWAYS, 2 );
	}

	/**
	 * Convertit en JSON les positions lues pour préremplir un formulaire.
	 *
	 * Les passerelles renvoient un tableau, que le cœur ne sait écrire dans l'input
	 * que pour le type googlemap : le champ recevrait sinon « [object Object] ».
	 *
	 * @param array  $populated_values Valeurs de préremplissage.
	 * @param object $form             Formulaire.
	 * @return array
	 */
	public static function encode_populated_values( $populated_values, $form ) {
		$fields = WS_Form_Common::get_fields_from_form( $form );

		foreach ( array( 'fields', 'fields_repeatable' ) as $group_key ) {
			if ( empty( $populated_values[ $group_key ] ) || ! is_array( $populated_values[ $group_key ] ) ) {
				continue;
			}

			foreach ( $populated_values[ $group_key ] as $field_id => $value ) {
				if ( isset( $fields[ $field_id ] ) && WSFIGN_Map_Field::FIELD_TYPE === $fields[ $field_id ]->type ) {
					$populated_values[ $group_key ][ $field_id ] = self::encode_position( $value, 'fields_repeatable' === $group_key );
				}
			}
		}

		return $populated_values;
	}

	/**
	 * Encode une position, ou chaque ligne d'une section répétable.
	 *
	 * @param mixed $value      Position, ou liste de positions.
	 * @param bool  $repeatable Vrai si la valeur est une liste de lignes.
	 * @return mixed
	 */
	private static function encode_position( $value, $repeatable ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		return $repeatable ? array_map( array( __CLASS__, 'encode_row' ), $value ) : wp_json_encode( $value );
	}

	/**
	 * Encode la position d'une ligne répétable.
	 *
	 * @param mixed $row_value Position de la ligne.
	 * @return mixed
	 */
	private static function encode_row( $row_value ) {
		return is_array( $row_value ) ? wp_json_encode( $row_value ) : $row_value;
	}

	/**
	 * Convertit les valeurs des champs carte en tableaux.
	 *
	 * @param mixed  $field_mapping Résultat du filtre, transmis tel quel.
	 * @param object $form          Formulaire.
	 * @param object $submit        Copie de la soumission propre à l'action.
	 * @return mixed
	 */
	public static function expose_values_as_arrays( $field_mapping, $form, $submit ) {
		self::$original_values = array();

		foreach ( (array) $submit->meta as $meta_key => $field_meta ) {
			if ( ! WSFIGN_Map_Field::is_map_meta( $field_meta ) ) {
				continue;
			}

			$position = json_decode( $field_meta['value'], true );

			if ( is_array( $position ) ) {
				self::$original_values[ $meta_key ] = $field_meta['value'];
				$submit->meta[ $meta_key ]['value'] = $position;
			}
		}

		return $field_mapping;
	}

	/**
	 * Remet les valeurs d'origine.
	 *
	 * @param mixed  $field_mapping Résultat du filtre, transmis tel quel.
	 * @param object $form          Formulaire.
	 * @param object $submit        Copie de la soumission propre à l'action.
	 * @return mixed
	 */
	public static function restore_values( $field_mapping, $form, $submit ) {
		foreach ( self::$original_values as $meta_key => $original_value ) {
			$submit->meta[ $meta_key ]['value'] = $original_value;
		}

		return $field_mapping;
	}
}
