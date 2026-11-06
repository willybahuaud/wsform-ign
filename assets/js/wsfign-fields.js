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
	 * Initialise les éléments pas encore pris en charge d'un formulaire.
	 *
	 * @param {Object} formInstance Instance WS Form.
	 */
	function initForm( formInstance ) {
		registrations.forEach( function initRegistration( registration ) {
			$( registration.selector + ':not([' + INIT_ATTRIBUTE + '])', formInstance.form_canvas_obj ).each(
				function initElement( elementIndex, element ) {
					const $element = $( element );

					$element.attr( INIT_ATTRIBUTE, '' );
					registration.initialize( formInstance, $element );
				}
			);
		} );
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

	window.wsfignFields = { register };
}( jQuery ) );
