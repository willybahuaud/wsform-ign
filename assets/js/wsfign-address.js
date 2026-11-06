/**
 * Champ « Adresse BAN » : autocomplétion en combobox, mapping et liaison à une carte.
 *
 * La carte est pilotée par des événements sur son input : ce script ne dépend pas de
 * Leaflet, et le champ fonctionne sans carte.
 *
 * @package WSForm_IGN
 */

( function wsfignAddressModule( $, banClient, settings ) {
	'use strict';

	const DEBOUNCE_MS = 250;
	const DEFAULT_MAP_ZOOM = 16;
	const COORDINATE_COMPONENTS = [ 'lat', 'lng' ];

	/**
	 * Une adresse attachée à un input de formulaire.
	 */
	class BanAddressField {
		/**
		 * @param {Object} formInstance Instance WS Form qui contient le champ.
		 * @param {jQuery} $input       Input texte du champ.
		 */
		constructor( formInstance, $input ) {
			this.formInstance = formInstance;
			this.$input = $input;
			this.field = formInstance.get_field( $input );
			this.suffix = formInstance.get_section_repeatable_suffix( $input );
			this.suggestions = [];
			this.activeIndex = -1;
			this.chosenValue = null;
			this.searchController = null;
			this.reverseController = null;
			this.debounceTimer = null;
			this.$mapInput = this.findMapInput();

			this.createCombobox();
			this.bindEvents();
		}

		/**
		 * Trouve un champ du formulaire par son identifiant, dans la même ligne répétable.
		 *
		 * @param {string|number} fieldId Identifiant du champ.
		 * @return {jQuery} Input du champ, éventuellement vide.
		 */
		findField( fieldId ) {
			const elementId = this.formInstance.form_id_prefix + 'field-' + fieldId + this.suffix;

			return $( document.getElementById( elementId ) );
		}

		/**
		 * Trouve l'input de la carte liée.
		 *
		 * @return {jQuery} Input de la carte, éventuellement vide.
		 */
		findMapInput() {
			const mapFieldId = this.formInstance.get_object_meta_value( this.field, 'wsfign_address_map', '' );

			return mapFieldId ? this.findField( mapFieldId ) : $();
		}

		/**
		 * Pose le balisage de combobox et les zones de message.
		 *
		 * Le message d'état reste invisible, pour les lecteurs d'écran : la liste
		 * ouverte suffit à l'œil. Une panne, elle, doit se voir.
		 */
		createCombobox() {
			const listboxId = this.$input.attr( 'id' ) + '-listbox';

			this.$listbox = this.$input.nextAll( '.wsfign-address-anchor' ).first().find( '.wsfign-address-listbox' );
			this.$listbox.attr( { id: listboxId, 'aria-label': settings.i18n.suggestions } );

			this.$input.attr( {
				role: 'combobox',
				autocomplete: 'off',
				'aria-autocomplete': 'list',
				'aria-expanded': 'false',
				'aria-controls': listboxId,
			} );

			this.$status = $( '<div class="wsfign-address-status" role="status"></div>' );
			this.$notice = $( '<div class="wsf-help wsfign-address-notice" role="alert"></div>' );
			this.$listbox.parent().after( this.$status, this.$notice );
		}

		/**
		 * Branche les événements.
		 */
		bindEvents() {
			this.$input.on( 'input', this.onInput.bind( this ) );
			this.$input.on( 'keydown', this.onKeydown.bind( this ) );
			this.$input.on( 'blur', this.closeList.bind( this ) );

			// Garde le focus dans l'input : sinon le blur fermerait la liste avant le clic.
			this.$listbox.on( 'mousedown', this.onListMousedown );
			this.$listbox.on( 'click', '[role="option"]', this.onOptionClick.bind( this ) );

			if ( 'on' === this.formInstance.get_object_meta_value( this.field, 'wsfign_address_map_reverse', '' ) ) {
				this.$mapInput.on( 'wsfign-map-placed', this.onMapPlaced.bind( this ) );
			}
		}

		/**
		 * Temporise la recherche et oublie les coordonnées d'une adresse modifiée.
		 *
		 * Seules les coordonnées sont vidées : elles ne décrivent plus le texte saisi,
		 * alors que corriger « 8 » en « 8 bis » ne change ni la ville ni le code postal.
		 */
		onInput() {
			const query = this.$input.val().trim();

			if ( null !== this.chosenValue && this.$input.val() !== this.chosenValue ) {
				this.chosenValue = null;
				this.clearCoordinateFields();
			}

			window.clearTimeout( this.debounceTimer );
			this.abortSearch();

			if ( ! banClient.isSearchable( query ) ) {
				this.closeList();
				return;
			}

			this.debounceTimer = window.setTimeout( this.runSearch.bind( this, query ), DEBOUNCE_MS );
		}

		/**
		 * Annule la recherche en cours : sa réponse arriverait après une saisie plus récente.
		 */
		abortSearch() {
			if ( this.searchController ) {
				this.searchController.abort();
			}
		}

		/**
		 * Lance une recherche et affiche ses résultats.
		 *
		 * @param {string} query Texte saisi.
		 */
		async runSearch( query ) {
			this.searchController = new AbortController();

			try {
				const suggestions = await banClient.search( query, this.searchController.signal );

				this.$notice.text( '' );
				this.showSuggestions( suggestions );
			} catch ( searchError ) {
				this.onServiceError( searchError );
			}
		}

		/**
		 * Signale une panne du service, sans bloquer la saisie ni l'envoi du formulaire.
		 *
		 * @param {Error} serviceError Erreur reçue.
		 */
		onServiceError( serviceError ) {
			if ( 'AbortError' !== serviceError.name ) {
				this.closeList();
				this.$notice.text( settings.i18n.unavailable );
			}
		}

		/**
		 * Affiche les suggestions.
		 *
		 * @param {Object[]} suggestions Suggestions à afficher.
		 */
		showSuggestions( suggestions ) {
			this.suggestions = suggestions;
			this.setActiveIndex( -1 );
			this.$listbox.empty().append( suggestions.map( this.createOption, this ) );
			this.$listbox.prop( 'hidden', 0 === suggestions.length );
			this.$input.attr( 'aria-expanded', String( suggestions.length > 0 ) );
			this.$status.text( this.getResultsMessage( suggestions.length ) );
		}

		/**
		 * Crée une option de la liste.
		 *
		 * @param {Object} suggestion Suggestion.
		 * @param {number} index      Position dans la liste.
		 * @return {jQuery} Option.
		 */
		createOption( suggestion, index ) {
			return $( '<li role="option" class="wsfign-address-option" aria-selected="false"></li>' )
				.attr( { id: this.getOptionId( index ), 'data-index': index } )
				.text( suggestion.label );
		}

		/**
		 * Retourne l'identifiant d'une option.
		 *
		 * @param {number} index Position dans la liste.
		 * @return {string} Identifiant.
		 */
		getOptionId( index ) {
			return this.$input.attr( 'id' ) + '-option-' + index;
		}

		/**
		 * Retourne le message annonçant le nombre de résultats.
		 *
		 * @param {number} count Nombre de résultats.
		 * @return {string} Message.
		 */
		getResultsMessage( count ) {
			if ( 0 === count ) {
				return settings.i18n.noResults;
			}

			return 1 === count ? settings.i18n.oneResult : settings.i18n.manyResults.replace( '%d', count );
		}

		/**
		 * Gère le clavier, selon le modèle combobox de l'ARIA Authoring Practices.
		 *
		 * @param {KeyboardEvent} keyEvent Événement clavier.
		 */
		onKeydown( keyEvent ) {
			const isOpen = ! this.$listbox.prop( 'hidden' );

			if ( 'ArrowDown' === keyEvent.key || 'ArrowUp' === keyEvent.key ) {
				keyEvent.preventDefault();
				this.moveActiveOption( 'ArrowDown' === keyEvent.key ? 1 : -1 );
			} else if ( 'Escape' === keyEvent.key && isOpen ) {
				keyEvent.preventDefault();
				this.closeList();
			} else if ( 'Enter' === keyEvent.key && isOpen && this.activeIndex >= 0 ) {
				// Sans cela, Entrée pourrait aussi envoyer le formulaire.
				keyEvent.preventDefault();
				keyEvent.stopPropagation();
				this.chooseSuggestion( this.suggestions[ this.activeIndex ] );
			}
		}

		/**
		 * Déplace l'option active, en bouclant, et rouvre la liste au besoin.
		 *
		 * @param {number} step Déplacement (1 ou -1).
		 */
		moveActiveOption( step ) {
			const count = this.suggestions.length;

			if ( 0 === count ) {
				return;
			}

			if ( this.$listbox.prop( 'hidden' ) ) {
				this.$listbox.prop( 'hidden', false );
				this.$input.attr( 'aria-expanded', 'true' );
			}

			const firstIndex = step > 0 ? 0 : count - 1;

			this.setActiveIndex( this.activeIndex < 0 ? firstIndex : ( this.activeIndex + step + count ) % count );
		}

		/**
		 * Marque l'option active. Le focus reste dans l'input : c'est aria-activedescendant qui l'indique.
		 *
		 * @param {number} index Position de l'option, ou -1 pour aucune.
		 */
		setActiveIndex( index ) {
			this.activeIndex = index;
			this.$listbox.find( '[role="option"]' ).attr( 'aria-selected', 'false' );

			if ( index < 0 ) {
				this.$input.removeAttr( 'aria-activedescendant' );
				return;
			}

			const activeOption = document.getElementById( this.getOptionId( index ) );

			$( activeOption ).attr( 'aria-selected', 'true' );
			this.$input.attr( 'aria-activedescendant', activeOption.id );
			activeOption.scrollIntoView( { block: 'nearest' } );
		}

		/**
		 * Ferme la liste, sans toucher au texte saisi.
		 */
		closeList() {
			this.$listbox.prop( 'hidden', true );
			this.$input.attr( 'aria-expanded', 'false' );
			this.setActiveIndex( -1 );
		}

		/**
		 * Empêche la liste de prendre le focus au clic.
		 *
		 * @param {MouseEvent} mouseEvent Événement souris.
		 */
		onListMousedown( mouseEvent ) {
			mouseEvent.preventDefault();
		}

		/**
		 * Choisit l'option cliquée.
		 *
		 * @param {MouseEvent} clickEvent Événement du clic.
		 */
		onOptionClick( clickEvent ) {
			const index = parseInt( clickEvent.currentTarget.getAttribute( 'data-index' ), 10 );

			this.chooseSuggestion( this.suggestions[ index ] );
		}

		/**
		 * Applique l'adresse choisie au champ, au mapping et à la carte.
		 *
		 * @param {Object} suggestion Suggestion choisie.
		 */
		chooseSuggestion( suggestion ) {
			window.clearTimeout( this.debounceTimer );
			this.abortSearch();
			this.closeList();
			this.fillFields( suggestion );
			this.$mapInput.triggerHandler( 'wsfign-map-show', [
				{
					lat: suggestion.lat,
					lng: suggestion.lng,
					zoom: this.getMapZoom(),
					address: suggestion.label,
				},
			] );
		}

		/**
		 * Écrit l'adresse dans le champ et les champs mappés.
		 *
		 * Le mapping passe après le libellé : il peut viser ce champ lui-même, comme
		 * le champ Google, qui ne garde alors que le composant demandé.
		 *
		 * @param {Object} suggestion Suggestion à écrire.
		 */
		fillFields( suggestion ) {
			this.$input.val( suggestion.label ).trigger( 'change' );
			this.getMappingRows().forEach( this.fillMappedField.bind( this, suggestion ) );
			this.chosenValue = this.$input.val();
		}

		/**
		 * Retourne les lignes de mapping configurées.
		 *
		 * @return {Object[]} Lignes de mapping.
		 */
		getMappingRows() {
			const rows = this.formInstance.get_object_meta_value( this.field, 'wsfign_address_field_mapping', [] );

			return Array.isArray( rows ) ? rows : [];
		}

		/**
		 * Écrit un composant dans le champ visé par une ligne de mapping.
		 *
		 * @param {Object} suggestion Suggestion à écrire.
		 * @param {Object} row        Ligne de mapping.
		 */
		fillMappedField( suggestion, row ) {
			const $field = row.ws_form_field ? this.findField( row.ws_form_field ) : $();
			const value = suggestion[ row.wsfign_address_component ];

			// Une commune n'a pas de ligne d'adresse : le champ ne doit pas se vider pour autant.
			if ( $field.is( this.$input ) && ! value ) {
				return;
			}

			this.setFieldValue( row.ws_form_field, $field, value );
		}

		/**
		 * Écrit une valeur par l'API du cœur, qui gère les listes et déclenche les conditions.
		 *
		 * @param {string|number} fieldId Identifiant du champ.
		 * @param {jQuery}        $field  Input du champ.
		 * @param {*}             value   Valeur à écrire.
		 */
		setFieldValue( fieldId, $field, value ) {
			if ( ! $field.length ) {
				return;
			}

			const wrapperId = this.formInstance.form_id_prefix + 'field-wrapper-' + fieldId + this.suffix;

			this.formInstance.field_value_set( $( document.getElementById( wrapperId ) ), $field, String( value ) );
		}

		/**
		 * Vide les champs mappés sur les coordonnées.
		 */
		clearCoordinateFields() {
			this.getMappingRows()
				.filter( this.isCoordinateRow )
				.forEach( this.clearMappedField, this );
		}

		/**
		 * Indique si une ligne de mapping vise des coordonnées.
		 *
		 * @param {Object} row Ligne de mapping.
		 * @return {boolean} Vrai pour la latitude et la longitude.
		 */
		isCoordinateRow( row ) {
			return COORDINATE_COMPONENTS.includes( row.wsfign_address_component );
		}

		/**
		 * Vide le champ visé par une ligne de mapping.
		 *
		 * @param {Object} row Ligne de mapping.
		 */
		clearMappedField( row ) {
			if ( row.ws_form_field ) {
				this.setFieldValue( row.ws_form_field, this.findField( row.ws_form_field ), '' );
			}
		}

		/**
		 * Retourne le zoom à appliquer à la carte.
		 *
		 * @return {number} Zoom.
		 */
		getMapZoom() {
			const rawZoom = this.formInstance.get_object_meta_value( this.field, 'wsfign_address_map_zoom', '' );
			const resolvedZoom = this.formInstance.parse_variables_process( String( rawZoom ) ).output;

			return parseInt( resolvedZoom, 10 ) || DEFAULT_MAP_ZOOM;
		}

		/**
		 * Cherche l'adresse du point où le visiteur vient de poser le marqueur.
		 *
		 * @param {jQuery.Event} placedEvent Événement de la carte.
		 * @param {Object}       position    Position du marqueur.
		 */
		async onMapPlaced( placedEvent, position ) {
			if ( this.reverseController ) {
				this.reverseController.abort();
			}

			this.reverseController = new AbortController();

			try {
				const suggestions = await banClient.reverse( position.lat, position.lng, this.reverseController.signal );

				this.applyReverseResult( suggestions[ 0 ], position );
			} catch ( reverseError ) {
				this.onServiceError( reverseError );
			}
		}

		/**
		 * Applique le résultat du géocodage inverse.
		 *
		 * Les coordonnées mappées restent celles du marqueur : l'adresse trouvée n'est
		 * que la plus proche, et le visiteur a choisi ce point précis.
		 *
		 * @param {Object|undefined} suggestion Adresse trouvée.
		 * @param {Object}           position   Position du marqueur.
		 */
		applyReverseResult( suggestion, position ) {
			if ( ! suggestion ) {
				this.$notice.text( settings.i18n.noAddressFound );
				return;
			}

			this.$notice.text( '' );
			this.fillFields( { ...suggestion, lat: position.lat, lng: position.lng } );
			this.$mapInput.triggerHandler( 'wsfign-map-address', [ suggestion.label ] );
		}
	}

	/**
	 * Initialise un champ adresse.
	 *
	 * @param {Object} formInstance Instance WS Form.
	 * @param {jQuery} $input       Input du champ.
	 */
	function initAddressField( formInstance, $input ) {
		new BanAddressField( formInstance, $input );
	}

	window.wsfignFields.register( '[data-wsfign-address]', initAddressField );
}( jQuery, window.wsfignBanClient, window.wsfignAddressSettings ) );
