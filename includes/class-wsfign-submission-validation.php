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

	const MAX_LATITUDE     = 90;
	const MAX_LONGITUDE    = 180;
	const MAX_ZOOM         = 22;
	const MAX_VALUE_LENGTH = 2048;
	const LEGACY_FORMAT    = '/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*(?:,\s*\d+\s*)?$/';

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

		return isset( $fields[ $field_id ]->type ) && WSFIGN_Map_Field::FIELD_TYPE === $fields[ $field_id ]->type;
	}

	/**
	 * Vérifie une valeur : JSON, ou ancien format « lat,lng » accepté comme par le champ Google.
	 *
	 * Les clés inconnues restent permises : un point qui préremplit le formulaire depuis ACF
	 * porte aussi city ou place_id, et le visiteur le renvoie tel quel s'il n'y touche pas.
	 *
	 * @param mixed $field_value Valeur envoyée.
	 * @return bool
	 */
	private static function is_valid_position( $field_value ) {
		if ( ! is_string( $field_value ) || strlen( $field_value ) > self::MAX_VALUE_LENGTH ) {
			return false;
		}

		$position = json_decode( $field_value, true );

		if ( is_array( $position ) ) {
			return self::has_valid_coordinates( $position ) && self::has_valid_options( $position ) && self::has_clean_values( $position );
		}

		return 1 === preg_match( self::LEGACY_FORMAT, $field_value, $parts )
			&& self::has_valid_coordinates(
				array(
					'lat' => $parts[1],
					'lng' => $parts[2],
				)
			);
	}

	/**
	 * Vérifie la latitude et la longitude.
	 *
	 * @param array $position Position.
	 * @return bool
	 */
	private static function has_valid_coordinates( $position ) {
		return isset( $position['lat'], $position['lng'] )
			&& is_numeric( $position['lat'] )
			&& is_numeric( $position['lng'] )
			&& abs( (float) $position['lat'] ) <= self::MAX_LATITUDE
			&& abs( (float) $position['lng'] ) <= self::MAX_LONGITUDE;
	}

	/**
	 * Vérifie le zoom et la couche, s'ils sont présents.
	 *
	 * @param array $position Position.
	 * @return bool
	 */
	private static function has_valid_options( $position ) {
		$zoom_is_valid  = ! isset( $position['zoom'] ) || ( is_numeric( $position['zoom'] ) && $position['zoom'] >= 0 && $position['zoom'] <= self::MAX_ZOOM );
		$layer_is_valid = ! isset( $position['layer'] ) || in_array( $position['layer'], array( 'plan', 'ortho' ), true );

		return $zoom_is_valid && $layer_is_valid;
	}

	/**
	 * Refuse les valeurs imbriquées et le HTML.
	 *
	 * L'adresse finit en méta, et un thème pourrait l'afficher sans l'échapper. Aucune
	 * adresse réelle ne contient de chevron.
	 *
	 * @param array $position Position.
	 * @return bool
	 */
	private static function has_clean_values( $position ) {
		foreach ( $position as $value ) {
			if ( is_array( $value ) || ( is_string( $value ) && false !== strpbrk( $value, '<>' ) ) ) {
				return false;
			}
		}

		return true;
	}
}
