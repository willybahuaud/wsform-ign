<?php
/**
 * Affichage de la valeur de la carte dans la liste des soumissions, le CSV et les e-mails.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Le cœur ne formate que le type googlemap : ici, on rend le JSON lisible.
 */
class WSFIGN_Submission_Display {

	const JSON_OBJECT = '/\{[^{}]*\}/';

	/**
	 * Branche le formatage sur les filtres de WS Form.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'wsf_table_submit_field_type_list', array( __CLASS__, 'format_for_list' ), 10, 3 );
		add_filter( 'wsf_submit_field_type_csv', array( __CLASS__, 'format_for_csv' ), 10, 3 );
		add_filter( 'wsf_action_email_message', array( __CLASS__, 'format_in_email_message' ), 10, 3 );
	}

	/**
	 * Formate une valeur pour la liste des soumissions.
	 *
	 * Le cœur échappe chaque ligne avant ce filtre, puis les joint avec un vrai <br /> dans
	 * une section répétable. On ne ré-échappe donc pas la chaîne, ce séparateur s'afficherait
	 * en toutes lettres : seul le JSON est remplacé, par des coordonnées purement numériques.
	 *
	 * @param mixed      $value      Valeur déjà échappée par le cœur.
	 * @param int|string $field_id   Identifiant du champ.
	 * @param string     $field_type Type du champ.
	 * @return mixed
	 */
	public static function format_for_list( $value, $field_id, $field_type ) {
		if ( WSFIGN_Map_Field::FIELD_TYPE !== $field_type || ! is_string( $value ) ) {
			return $value;
		}

		return preg_replace_callback( self::JSON_OBJECT, array( __CLASS__, 'format_escaped_json_match' ), $value );
	}

	/**
	 * Formate une valeur pour l'export CSV.
	 *
	 * Dans une section répétable, le cœur joint les lignes par une virgule, déjà utilisée
	 * entre latitude et longitude : le point-virgule garde les paires distinctes.
	 *
	 * @param mixed      $value      Valeur enregistrée.
	 * @param int|string $field_id   Identifiant du champ.
	 * @param string     $field_type Type du champ.
	 * @return mixed
	 */
	public static function format_for_csv( $value, $field_id, $field_type ) {
		if ( WSFIGN_Map_Field::FIELD_TYPE !== $field_type || ! is_string( $value ) ) {
			return $value;
		}

		return self::replace_json_values( str_replace( '},{', '};{', $value ) );
	}

	/**
	 * Remplace le JSON des cartes dans un e-mail déjà composé.
	 *
	 * Le cœur n'offre aucun filtre sur la valeur d'un champ dans #email_submission
	 * ou #field(ID) : seul le message final est modifiable. On remplace la valeur
	 * exacte de la soumission, sous sa forme brute (texte) et échappée (HTML).
	 *
	 * @param string $message Message de l'e-mail.
	 * @param object $form    Formulaire.
	 * @param object $submit  Soumission.
	 * @return string
	 */
	public static function format_in_email_message( $message, $form, $submit ) {
		foreach ( (array) $submit->meta as $field_meta ) {
			if ( ! WSFIGN_Map_Field::is_map_meta( $field_meta ) ) {
				continue;
			}

			$coordinates = self::format_coordinates( $field_meta['value'] );

			if ( '' !== $coordinates ) {
				$message = str_replace( array( $field_meta['value'], esc_html( $field_meta['value'] ) ), $coordinates, $message );
			}
		}

		return $message;
	}

	/**
	 * Remplace chaque objet JSON d'une chaîne par ses coordonnées.
	 *
	 * Une section répétable concatène plusieurs valeurs avec un délimiteur : la
	 * chaîne entière n'est alors pas un JSON valide, et les délimiteurs doivent rester.
	 *
	 * @param string $value Valeur enregistrée.
	 * @return string
	 */
	private static function replace_json_values( $value ) {
		return preg_replace_callback( self::JSON_OBJECT, array( __CLASS__, 'format_json_match' ), $value );
	}

	/**
	 * Formate un objet JSON trouvé par replace_json_values().
	 *
	 * @param string[] $matches Résultat de la recherche.
	 * @return string
	 */
	private static function format_json_match( $matches ) {
		return self::format_coordinates( $matches[0] );
	}

	/**
	 * Formate un objet JSON échappé, trouvé par format_for_list().
	 *
	 * @param string[] $matches Résultat de la recherche.
	 * @return string
	 */
	private static function format_escaped_json_match( $matches ) {
		return self::format_coordinates( wp_specialchars_decode( $matches[0], ENT_QUOTES ) );
	}

	/**
	 * Convertit une valeur JSON en « latitude,longitude », comme le champ Google.
	 *
	 * @param string $value Valeur JSON.
	 * @return string Coordonnées, ou chaîne vide si la valeur est illisible.
	 */
	private static function format_coordinates( $value ) {
		$coordinates = json_decode( $value, true );

		if ( ! is_array( $coordinates ) || ! isset( $coordinates['lat'], $coordinates['lng'] ) || ! is_numeric( $coordinates['lat'] ) || ! is_numeric( $coordinates['lng'] ) ) {
			return '';
		}

		return sprintf( '%.7F,%.7F', $coordinates['lat'], $coordinates['lng'] );
	}
}
