<?php
/**
 * Validation côté serveur de la valeur de la carte.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * La valeur vient du navigateur : on refuse tout ce qui n'est pas une position.
 */
class WSFIGN_Submission_Validation {

	const MAX_LATITUDE  = 90;
	const MAX_LONGITUDE = 180;

	/**
	 * Branche la validation sur WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_submit_field_validate', array( __CLASS__, 'validate_field' ), 10, 6 );
	}

	/**
	 * Ajoute une erreur si un champ carte contient autre chose qu'une position.
	 *
	 * Une valeur vide passe : le caractère obligatoire est contrôlé par le cœur.
	 *
	 * @param array      $actions                  Erreurs de validation déjà relevées.
	 * @param int|string $field_id                 Identifiant du champ.
	 * @param mixed      $field_value              Valeur envoyée.
	 * @param int|false  $section_repeatable_index Ligne de section répétable.
	 * @param string     $post_mode                Mode d'envoi.
	 * @param object     $submit                   Soumission.
	 * @return array
	 */
	public static function validate_field( $actions, $field_id, $field_value, $section_repeatable_index, $post_mode, $submit ) {
		if ( '' === $field_value || ! self::is_map_field( $field_id, $submit ) || self::is_valid_position( $field_value ) ) {
			return $actions;
		}

		// Le cœur transforme une chaîne ajoutée à ce tableau en message sous le champ.
		$actions[] = __( 'This location is not valid.', 'wsform-ign' );

		return $actions;
	}

	/**
	 * Indique si un champ est une carte : le filtre ne transmet pas son type.
	 *
	 * @param int|string $field_id Identifiant du champ.
	 * @param object     $submit   Soumission.
	 * @return bool
	 */
	private static function is_map_field( $field_id, $submit ) {
		$fields = WS_Form_Common::get_fields_from_form( $submit->form_object );

		if ( ! isset( $fields[ $field_id ] ) ) {
			return false;
		}

		$field = (object) $fields[ $field_id ];

		return isset( $field->type ) && WSFIGN_Map_Field::FIELD_TYPE === $field->type;
	}

	/**
	 * Vérifie une valeur : JSON, ou ancien format « lat,lng » accepté comme par le champ Google.
	 *
	 * @param mixed $field_value Valeur envoyée.
	 * @return bool
	 */
	private static function is_valid_position( $field_value ) {
		if ( ! is_string( $field_value ) ) {
			return false;
		}

		$position = json_decode( $field_value, true );

		if ( ! is_array( $position ) ) {
			$parts    = explode( ',', $field_value );
			$position = count( $parts ) >= 2 ? array_combine( array( 'lat', 'lng' ), array_slice( $parts, 0, 2 ) ) : array();
		}

		return isset( $position['lat'], $position['lng'] )
			&& is_numeric( $position['lat'] )
			&& is_numeric( $position['lng'] )
			&& abs( (float) $position['lat'] ) <= self::MAX_LATITUDE
			&& abs( (float) $position['lng'] ) <= self::MAX_LONGITUDE;
	}
}
