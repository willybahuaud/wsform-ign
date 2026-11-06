/**
 * Initialisation commune aux champs du plugin, au rendu et à l'ajout d'une ligne répétable.
 *
 * @package WSForm_IGN
 */

( function wsfignFieldsModule( $ ) {
	'use strict';

	const INIT_ATTRIBUTE = 'data-wsfign-init';
	const registrations = [];

	/**
	 * Initialise un élément.
	 *
	 * @param {Object}      formInstance Instance WS Form.
	 * @param {Object}      registration Type de champ déclaré.
	 * @param {number}      elementIndex Position, imposée par jQuery.
	 * @param {HTMLElement} element      Input du champ.
	 */
	function initElement( formInstance, registration, elementIndex, element ) {
		const $element = $( element );

		$element.attr( INIT_ATTRIBUTE, '' );
		registration.initialize( formInstance, $element );
	}

	/**
	 * Initialise les éléments pas encore pris en charge d'un formulaire.
	 *
	 * @param {Object} formInstance Instance WS Form.
	 */
	function initForm( formInstance ) {
		for ( const registration of registrations ) {
			$( registration.selector + ':not([' + INIT_ATTRIBUTE + '])', formInstance.form_canvas_obj ).each(
				initElement.bind( null, formInstance, registration )
			);
		}
	}

	/**
	 * Lit un réglage en résolvant les variables WS Form (#field, etc.).
	 *
	 * @param {Object} formInstance Instance WS Form.
	 * @param {Object} field        Champ.
	 * @param {string} metaKey      Clé du réglage.
	 * @param {string} defaultValue Valeur si le réglage est vide.
	 * @return {string} Valeur du réglage.
	 */
	function getSetting( formInstance, field, metaKey, defaultValue ) {
		const rawValue = formInstance.get_object_meta_value( field, metaKey, defaultValue );
		const resolvedValue = formInstance.parse_variables_process( String( rawValue ) ).output;

		return '' === resolvedValue ? defaultValue : resolvedValue;
	}

	/**
	 * Parcourt tous les formulaires : une ligne répétable ajoutée après coup y passe aussi.
	 */
	function initAllForms() {
		Object.values( window.wsf_form_instances || {} ).forEach( initForm );
	}

	/**
	 * Déclare un type de champ à initialiser.
	 *
	 * @param {string}   selector   Sélecteur de l'input du champ.
	 * @param {Function} initialize Fonction appelée avec l'instance WS Form et l'input.
	 */
	function register( selector, initialize ) {
		registrations.push( { selector, initialize } );

		// Le formulaire peut avoir fini son rendu avant le chargement de ce script.
		$( initAllForms );
	}

	$( document ).on( 'wsf-rendered wsf-section-repeatable', initAllForms );

	window.wsfignFields = { register, getSetting };
}( jQuery ) );
