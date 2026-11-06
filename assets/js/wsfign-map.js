/**
 * Champ « Carte IGN » : Leaflet branché sur un input masqué de WS Form.
 *
 * @package WSForm_IGN
 */

( function wsfignMapModule( $, L, settings ) {
	'use strict';

	const TILE_URL =
		'https://data.geopf.fr/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER={layer}&STYLE=normal&FORMAT={format}&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}';
	const MAX_ZOOM = 19;
	const LOCATED_ZOOM = 15;
	// Environ un mètre : la valeur enregistrée garde toute sa précision.
	const COORDINATE_DECIMALS = 5;
	const DEFAULT_LAT = 46.6;
	const DEFAULT_LNG = 2.4;
	const DEFAULT_ZOOM = 6;
	const DEFAULT_HEIGHT = '56.25%';
	const LOCATE_ICON =
		'<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="7"/><path d="M12 1v5M12 18v5M1 12h5M18 12h5"/></svg>';

	/**
	 * Lit la valeur d'un input : JSON, ou ancien format « lat,lng » accepté par le cœur.
	 *
	 * @param {string} rawValue Valeur brute.
	 * @return {Object|null} Valeur avec lat et lng numériques, ou null si illisible.
	 */
	function parseValue( rawValue ) {
		let parsedValue = null;

		try {
			parsedValue = JSON.parse( rawValue );
		} catch ( parseError ) {
			const parts = String( rawValue ).split( ',' );
			// Meta Box stocke « lat,lng,zoom » : le zoom éventuel est ignoré, comme dans le cœur.
			parsedValue = parts.length >= 2 ? { lat: parts[ 0 ], lng: parts[ 1 ] } : null;
		}

		if ( ! parsedValue || 'object' !== typeof parsedValue ) {
			return null;
		}

		const lat = parseFloat( parsedValue.lat );
		const lng = parseFloat( parsedValue.lng );

		return isNaN( lat ) || isNaN( lng ) ? null : { ...parsedValue, lat, lng };
	}

	/**
	 * Contrôle Leaflet qui affiche un élément déjà construit.
	 *
	 * Sans l'arrêt de la propagation, un clic sur le contrôle poserait aussi le marqueur.
	 */
	const ElementControl = L.Control.extend( {
		onAdd: function getControlElement() {
			L.DomEvent.disableClickPropagation( this.options.element );

			return this.options.element;
		},
	} );

	/**
	 * Une carte IGN attachée à un input de formulaire.
	 */
	class IgnMapField {
		/**
		 * @param {Object} formInstance Instance WS Form qui contient le champ.
		 * @param {jQuery} $input       Input masqué qui porte la valeur.
		 */
		constructor( formInstance, $input ) {
			this.formInstance = formInstance;
			this.$input = $input;
			this.field = formInstance.get_field( $input );
			this.$map = $( document.getElementById( $input.attr( 'id' ) + '-map' ) );
			this.marker = null;
			this.tilesFailed = false;

			this.applyHeight();
			this.createMap();
			this.createInterface();
			this.syncFromInput();
			this.bindEvents();
		}

		/**
		 * Lit un réglage en résolvant les variables WS Form (#field, etc.).
		 *
		 * @param {string} metaKey      Clé du réglage.
		 * @param {string} defaultValue Valeur si le réglage est vide.
		 * @return {string} Valeur du réglage.
		 */
		getSetting( metaKey, defaultValue ) {
			const rawValue = this.formInstance.get_object_meta_value( this.field, metaKey, defaultValue );
			const resolvedValue = this.formInstance.parse_variables_process( String( rawValue ) ).output;

			return '' === resolvedValue ? defaultValue : resolvedValue;
		}

		/**
		 * Applique la hauteur réglée. Un padding accepte % et px sans autre calcul.
		 */
		applyHeight() {
			this.$map.css( 'padding-bottom', this.getSetting( 'wsfign_map_height', DEFAULT_HEIGHT ) );
		}

		/**
		 * Construit une couche de tuiles IGN.
		 *
		 * @param {string} layerKey Clé de la couche (plan ou ortho).
		 * @return {L.TileLayer} Couche Leaflet.
		 */
		createTileLayer( layerKey ) {
			const layer = settings.layers[ layerKey ];
			const tileLayer = L.tileLayer( TILE_URL, {
				layer: layer.layer,
				format: layer.format,
				maxZoom: MAX_ZOOM,
				// La Licence Ouverte demande la source et la date de mise à jour, et accepte un lien
				// vers la source : la fiche liée indique le producteur, la licence et le rythme de mise à jour.
				attribution: $( '<a target="_blank" rel="noopener"></a>' )
					.attr( 'href', layer.source )
					.text( 'IGN, ' + layer.label )
					.prop( 'outerHTML' ),
			} );

			tileLayer.layerKey = layerKey;
			window.wsfignTileRetry.watch( tileLayer );
			tileLayer.on( window.wsfignTileRetry.UNAVAILABLE_EVENT, this.onTilesUnavailable, this );
			tileLayer.on( 'tileload', this.onTileLoad, this );

			return tileLayer;
		}

		/**
		 * Crée la carte Leaflet et ses contrôles.
		 */
		createMap() {
			const canvas = this.$map.find( '.wsfign-map-canvas' ).get( 0 );
			const configuredLayer = this.getSetting( 'wsfign_map_layer', 'plan' );

			this.layerKey = settings.layers[ configuredLayer ] ? configuredLayer : 'plan';
			this.defaultCenter = [
				parseFloat( this.getSetting( 'wsfign_map_lat', String( DEFAULT_LAT ) ) ) || DEFAULT_LAT,
				parseFloat( this.getSetting( 'wsfign_map_lng', String( DEFAULT_LNG ) ) ) || DEFAULT_LNG,
			];
			this.defaultZoom = parseInt( this.getSetting( 'wsfign_map_zoom', String( DEFAULT_ZOOM ) ), 10 ) || DEFAULT_ZOOM;
			this.tileLayers = {
				plan: this.createTileLayer( 'plan' ),
				ortho: this.createTileLayer( 'ortho' ),
			};

			this.map = L.map( canvas, {
				zoomControl: 'on' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_control_zoom', 'on' ),
				layers: [ this.tileLayers[ this.layerKey ] ],
				center: this.defaultCenter,
				zoom: this.defaultZoom,
				scrollWheelZoom: false,
				// Un seul doigt doit faire défiler la page : la carte se déplace à deux doigts.
				dragging: ! L.Browser.mobile,
			} );

			this.map.attributionControl.setPrefix( '<a href="https://leafletjs.com/" target="_blank" rel="noopener">Leaflet</a>' );
			canvas.setAttribute( 'aria-label', this.formInstance.get_object_meta_value( this.field, 'aria_label', '' ) || settings.i18n.mapLabel );

			if ( 'on' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_control_layers', 'on' ) ) {
				this.$layerToggle = this.createButton( settings.layers[ this.getOtherLayerKey() ].label, this.onLayerToggleClick, 'wsfign-map-layer-toggle' );
				this.addMapControl( this.$layerToggle, 'topright' );
			}
		}

		/**
		 * Retourne la couche qui n'est pas affichée.
		 *
		 * @return {string} Clé de la couche.
		 */
		getOtherLayerKey() {
			return 'plan' === this.layerKey ? 'ortho' : 'plan';
		}

		/**
		 * Ajoute un bouton dans la carte.
		 *
		 * @param {jQuery} $button  Bouton.
		 * @param {string} position Position Leaflet.
		 */
		addMapControl( $button, position ) {
			const $container = $( '<div class="leaflet-bar wsfign-map-control"></div>' ).append( $button );

			new ElementControl( { element: $container.get( 0 ), position } ).addTo( this.map );
		}

		/**
		 * Ajoute l'aide, les boutons et la zone de message autour de la carte.
		 *
		 * Sans le bouton de pose, un marqueur ne pourrait être placé qu'à la souris.
		 */
		createInterface() {
			const hintId = this.$input.attr( 'id' ) + '-hint';
			const $hint = $( '<div class="wsfign-map-hint"></div>' )
				.attr( 'id', hintId )
				.text( L.Browser.mobile ? settings.i18n.hintTouch : settings.i18n.hintMouse );

			this.$placeButton = this.createButton( settings.i18n.placeAtCenter, this.onPlaceAtCenterClick, 'wsfign-map-button' );
			this.$notice = $( '<div class="wsfign-map-notice" role="status"></div>' );
			this.$clear = this.$input.parent().find( '.wsfign-map-clear' );

			this.$map.before( $hint );
			this.$map.after( $( '<div class="wsfign-map-actions"></div>' ).append( this.$placeButton ), this.$notice );
			this.$map.find( '.wsfign-map-canvas' ).attr( 'aria-describedby', hintId );

			if ( 'browser' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_center', '' ) && navigator.geolocation ) {
				this.addMapControl( this.createLocateButton(), 'topleft' );
			}
		}

		/**
		 * Crée le bouton de géolocalisation, une icône nommée pour les lecteurs d'écran.
		 *
		 * @return {jQuery} Bouton.
		 */
		createLocateButton() {
			return this.createButton( '', this.onLocateClick, 'wsfign-map-locate' )
				.attr( { 'aria-label': settings.i18n.locateMe, title: settings.i18n.locateMe } )
				.html( LOCATE_ICON );
		}

		/**
		 * Crée un bouton. Le type button évite d'envoyer le formulaire.
		 *
		 * @param {string}   label     Libellé.
		 * @param {Function} handler   Méthode à appeler au clic.
		 * @param {string}   className Classe du bouton.
		 * @return {jQuery} Bouton.
		 */
		createButton( label, handler, className ) {
			return $( '<button type="button"></button>' )
				.addClass( className )
				.text( label )
				.on( 'keydown', this.onButtonKeydown )
				.on( 'click', handler.bind( this ) );
		}

		/**
		 * Garde la touche Entrée active sur nos boutons.
		 *
		 * WS Form l'annule sur tous les :input du formulaire, par délégation, pour
		 * éviter un envoi accidentel. Sans cet arrêt, Entrée ne les activerait pas.
		 *
		 * @param {KeyboardEvent} keyEvent Événement clavier.
		 */
		onButtonKeydown( keyEvent ) {
			if ( 'Enter' === keyEvent.key ) {
				keyEvent.stopPropagation();
			}
		}

		/**
		 * Branche les événements de la carte et de l'input.
		 */
		bindEvents() {
			this.map.on( 'click', this.onMapClick, this );
			this.map.on( 'zoomend', this.onViewOptionChange, this );

			this.$input.on( 'change', this.syncFromInput.bind( this ) );
			this.$input.on( 'wsfign-map-show', this.onShowRequest.bind( this ) );
			this.$input.on( 'wsfign-map-address', this.onAddressRequest.bind( this ) );
			this.$input
				.parent()
				.find( '[data-action="wsfign-map-clear"]' )
				.on( 'click', this.onClearClick.bind( this ) );

			// Couvre onglets, sections et logique conditionnelle sans dépendre de leurs événements.
			this.resizeObserver = new ResizeObserver( this.onResize.bind( this ) );
			this.resizeObserver.observe( this.$map.get( 0 ) );
		}

		/**
		 * Recalcule la taille de la carte quand son conteneur change, un onglet qui s'ouvre par exemple.
		 */
		onResize() {
			this.map.invalidateSize();
		}

		/**
		 * Pose le marqueur au point cliqué.
		 *
		 * @param {L.LeafletMouseEvent} mouseEvent Événement Leaflet.
		 */
		onMapClick( mouseEvent ) {
			this.placeMarker( mouseEvent.latlng );
		}

		/**
		 * Pose le marqueur au centre de la carte.
		 */
		onPlaceAtCenterClick() {
			this.placeMarker( this.map.getCenter() );
		}

		/**
		 * Demande sa position au navigateur, seulement après ce clic.
		 */
		onLocateClick() {
			navigator.geolocation.getCurrentPosition( this.onLocated.bind( this ), this.onLocateError.bind( this ) );
		}

		/**
		 * Centre la carte sur la position du visiteur, sans poser de marqueur.
		 *
		 * @param {GeolocationPosition} position Position du navigateur.
		 */
		onLocated( position ) {
			this.map.setView( [ position.coords.latitude, position.coords.longitude ], LOCATED_ZOOM );
		}

		/**
		 * Signale l'échec de la géolocalisation.
		 */
		onLocateError() {
			this.$notice.text( settings.i18n.locateError );
		}

		/**
		 * Retire le marqueur quand le visiteur clique sur le lien de réinitialisation.
		 *
		 * @param {Event} clickEvent Événement du clic.
		 */
		onClearClick( clickEvent ) {
			clickEvent.preventDefault();
			this.$input.val( '' ).trigger( 'change' );
			this.$notice.text( settings.i18n.markerCleared );
			// Le lien vient de disparaître : le focus clavier ne doit pas se perdre avec lui.
			this.$placeButton.trigger( 'focus' );
		}

		/**
		 * Reporte le déplacement du marqueur dans la valeur.
		 */
		onMarkerDragEnd() {
			this.placeMarker( this.marker.getLatLng() );
		}

		/**
		 * Bascule sur l'autre couche, puis met la valeur à jour.
		 */
		onLayerToggleClick() {
			const nextLayerKey = this.getOtherLayerKey();

			this.map.removeLayer( this.tileLayers[ this.layerKey ] );
			this.map.addLayer( this.tileLayers[ nextLayerKey ] );
			this.layerKey = nextLayerKey;
			this.$layerToggle.text( settings.layers[ this.getOtherLayerKey() ].label );
			this.onViewOptionChange();
		}

		/**
		 * Garde le zoom et la couche à jour dans la valeur s'il y a un marqueur.
		 */
		onViewOptionChange() {
			if ( this.marker ) {
				this.writeValue( this.marker.getLatLng() );
			}
		}

		/**
		 * Signale l'indisponibilité du fond de carte : le formulaire reste envoyable.
		 */
		onTilesUnavailable() {
			this.tilesFailed = true;
			this.$notice.text( settings.i18n.tilesError );
		}

		/**
		 * Retire le message d'indisponibilité dès que le fond de carte revient.
		 *
		 * Le texte est comparé pour ne pas effacer un message posé depuis, sur le marqueur par exemple.
		 */
		onTileLoad() {
			if ( ! this.tilesFailed ) {
				return;
			}

			this.tilesFailed = false;

			if ( settings.i18n.tilesError === this.$notice.text() ) {
				this.$notice.text( '' );
			}
		}

		/**
		 * Centre la carte sur une adresse choisie dans un champ Adresse BAN.
		 *
		 * @param {jQuery.Event} showEvent Événement reçu.
		 * @param {Object}       location  Position, zoom et adresse.
		 */
		onShowRequest( showEvent, location ) {
			const latLng = L.latLng( location.lat, location.lng );
			const isUpdate = null !== this.marker;

			this.address = location.address;
			this.showMarker( latLng );
			this.announcePlacement( latLng, isUpdate );

			// Sans animation, le zoom est à jour tout de suite pour l'écriture de la valeur.
			this.map.setView( latLng, location.zoom, { animate: false } );
			this.writeValue( latLng );
		}

		/**
		 * Ajoute à la valeur l'adresse trouvée pour le marqueur, sans le déplacer.
		 *
		 * @param {jQuery.Event} addressEvent Événement reçu.
		 * @param {string}       address      Adresse trouvée.
		 */
		onAddressRequest( addressEvent, address ) {
			this.address = address;
			this.onViewOptionChange();
		}

		/**
		 * Pose ou déplace le marqueur à la demande du visiteur, écrit la valeur et l'annonce.
		 *
		 * L'adresse connue ne vaut plus pour ce nouveau point. L'événement permet à un
		 * champ Adresse BAN lié de chercher celle du point.
		 *
		 * @param {L.LatLng} latLng Position.
		 */
		placeMarker( latLng ) {
			const isUpdate = null !== this.marker;

			this.address = '';
			this.showMarker( latLng );
			this.writeValue( latLng );
			this.announcePlacement( latLng, isUpdate );
			this.$input.triggerHandler( 'wsfign-map-placed', [ { lat: latLng.lat, lng: latLng.lng } ] );
		}

		/**
		 * Confirme la pose ou le déplacement du point.
		 *
		 * Masquées par réglage, les coordonnées restent annoncées aux lecteurs d'écran :
		 * sans voir la carte, elles sont la seule confirmation de la position.
		 *
		 * @param {L.LatLng} latLng   Position.
		 * @param {boolean}  isUpdate Vrai si le point existait déjà.
		 */
		announcePlacement( latLng, isUpdate ) {
			const showCoordinates = 'on' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_show_coordinates', 'on' );
			const coordinates = settings.i18n.markerCoordinates
				.replace( '%1$s', latLng.lat.toFixed( COORDINATE_DECIMALS ) )
				.replace( '%2$s', latLng.lng.toFixed( COORDINATE_DECIMALS ) );

			this.$notice
				.text( ( isUpdate ? settings.i18n.markerUpdated : settings.i18n.markerPlaced ) + ' ' )
				.append( $( '<span></span>' ).toggleClass( 'wsfign-map-sr-only', ! showCoordinates ).text( coordinates ) );
		}

		/**
		 * Affiche le lien de retrait seulement s'il y a un marqueur à retirer.
		 */
		updateClearLink() {
			this.$clear.toggle( null !== this.marker );
		}

		/**
		 * Affiche le marqueur à une position, sans toucher à la valeur.
		 *
		 * @param {L.LatLng|Array} latLng Position.
		 */
		showMarker( latLng ) {
			if ( this.marker ) {
				this.marker.setLatLng( latLng );
				return;
			}

			const markerTitle = this.getSetting( 'wsfign_map_marker_title', '' );
			const iconUrl = this.getSetting( 'wsfign_map_marker_icon_url', '' );
			const markerOptions = { draggable: true, title: markerTitle, alt: markerTitle || settings.i18n.mapLabel };

			if ( iconUrl ) {
				markerOptions.icon = L.divIcon( {
					className: 'wsfign-map-marker',
					html: $( '<img alt="">' ).attr( 'src', iconUrl ).prop( 'outerHTML' ),
					iconSize: null,
				} );
			}

			this.marker = L.marker( latLng, markerOptions ).addTo( this.map );
			this.marker.on( 'dragend', this.onMarkerDragEnd, this );
			this.updateClearLink();
		}

		/**
		 * Écrit la valeur JSON et déclenche change seulement si elle a changé.
		 *
		 * @param {L.LatLng} latLng Position du marqueur.
		 */
		writeValue( latLng ) {
			const valueObject = {
				lat: latLng.lat,
				lng: latLng.lng,
				zoom: this.map.getZoom(),
				layer: this.layerKey,
			};

			if ( this.address ) {
				valueObject.address = this.address;
			}

			const fieldValue = JSON.stringify( valueObject );

			if ( this.$input.val() !== fieldValue ) {
				this.$input.val( fieldValue ).trigger( 'change' );
			}
		}

		/**
		 * Aligne la carte sur la valeur de l'input.
		 *
		 * Passe aussi par nos propres écritures : cette méthode ne doit donc
		 * jamais réécrire la valeur, sous peine de boucle sur change.
		 */
		syncFromInput() {
			const fieldValue = parseValue( this.$input.val() );

			this.address = fieldValue && fieldValue.address ? fieldValue.address : '';

			if ( ! fieldValue ) {
				this.removeMarker();
				return;
			}

			this.showMarker( [ fieldValue.lat, fieldValue.lng ] );

			if ( ! this.map.getBounds().contains( [ fieldValue.lat, fieldValue.lng ] ) ) {
				this.map.setView( [ fieldValue.lat, fieldValue.lng ], fieldValue.zoom || this.map.getZoom() );
			}
		}

		/**
		 * Retire le marqueur et ramène la carte à sa vue initiale.
		 */
		removeMarker() {
			if ( this.marker ) {
				this.marker.remove();
				this.marker = null;
				this.map.setView( this.defaultCenter, this.defaultZoom );
			}

			this.updateClearLink();
		}
	}

	/**
	 * Initialise un champ carte.
	 *
	 * @param {Object} formInstance Instance WS Form.
	 * @param {jQuery} $input       Input du champ.
	 */
	function initMapField( formInstance, $input ) {
		new IgnMapField( formInstance, $input );
	}

	window.wsfignFields.register( '[data-wsfign-map]', initMapField );
}( jQuery, L, window.wsfignMapSettings ) );
