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
	const COORDINATE_DECIMALS = 7;
	const DEFAULT_LAT = 46.6;
	const DEFAULT_LNG = 2.4;
	const DEFAULT_ZOOM = 6;
	const DEFAULT_HEIGHT = '56.25%';

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
			parsedValue = 2 === parts.length ? { lat: parts[ 0 ], lng: parts[ 1 ] } : null;
		}

		if ( ! parsedValue || 'object' !== typeof parsedValue ) {
			return null;
		}

		const lat = parseFloat( parsedValue.lat );
		const lng = parseFloat( parsedValue.lng );

		return isNaN( lat ) || isNaN( lng ) ? null : { ...parsedValue, lat, lng };
	}

	/**
	 * Sélecteur de couches dont les radios ne partent pas avec le formulaire.
	 *
	 * Leaflet leur donne un name : sans ce détachement, WS Form les enverrait au
	 * serveur. Un attribut form sans cible les retire du formulaire sans casser leur groupe.
	 */
	const DetachedLayersControl = L.Control.Layers.extend( {
		_createRadioElement: function createDetachedRadio( name, checked ) {
			const radio = L.Control.Layers.prototype._createRadioElement.call( this, name, checked );

			radio.setAttribute( 'form', 'wsfign-detached' );

			return radio;
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
				// La Licence Ouverte demande la source et la date : la fiche liée donne les deux.
				attribution: $( '<a target="_blank" rel="noopener"></a>' )
					.attr( 'href', layer.source )
					.text( 'IGN, ' + layer.label )
					.prop( 'outerHTML' ),
			} );

			tileLayer.layerKey = layerKey;
			tileLayer.on( 'tileerror', this.onTileError, this );

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
			} );

			this.map.attributionControl.setPrefix( '<a href="https://leafletjs.com/" target="_blank" rel="noopener">Leaflet</a>' );
			canvas.setAttribute( 'aria-label', this.formInstance.get_object_meta_value( this.field, 'aria_label', '' ) || settings.i18n.mapLabel );

			if ( 'on' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_control_layers', 'on' ) ) {
				new DetachedLayersControl( this.getLayerChoices() ).addTo( this.map );
			}
		}

		/**
		 * Retourne les couches proposées au sélecteur, par libellé.
		 *
		 * @return {Object} Couches Leaflet indexées par libellé.
		 */
		getLayerChoices() {
			return {
				[ settings.layers.plan.label ]: this.tileLayers.plan,
				[ settings.layers.ortho.label ]: this.tileLayers.ortho,
			};
		}

		/**
		 * Ajoute sous la carte les boutons et la zone de message.
		 *
		 * Sans le bouton de pose, un marqueur ne pourrait être placé qu'à la souris.
		 */
		createInterface() {
			const $actions = $( '<div class="wsfign-map-actions"></div>' );

			$actions.append( this.createButton( settings.i18n.placeAtCenter, this.onPlaceAtCenterClick ) );

			if ( 'browser' === this.formInstance.get_object_meta_value( this.field, 'wsfign_map_center', '' ) && navigator.geolocation ) {
				$actions.append( this.createButton( settings.i18n.locateMe, this.onLocateClick ) );
			}

			this.$notice = $( '<div class="wsfign-map-notice" role="status"></div>' );
			this.$map.after( $actions, this.$notice );
		}

		/**
		 * Crée un bouton d'action. Le type button évite d'envoyer le formulaire.
		 *
		 * @param {string}   label   Libellé.
		 * @param {Function} handler Méthode à appeler au clic.
		 * @return {jQuery} Bouton.
		 */
		createButton( label, handler ) {
			return $( '<button type="button" class="wsf-button"></button>' )
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
			this.map.on( 'baselayerchange', this.onBaseLayerChange, this );

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
		}

		/**
		 * Reporte le déplacement du marqueur dans la valeur.
		 */
		onMarkerDragEnd() {
			this.placeMarker( this.marker.getLatLng() );
		}

		/**
		 * Enregistre la couche choisie, puis met la valeur à jour.
		 *
		 * @param {L.LayersControlEvent} layerEvent Événement Leaflet.
		 */
		onBaseLayerChange( layerEvent ) {
			this.layerKey = layerEvent.layer.layerKey;
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
		 * Affiche un seul message si les tuiles échouent : le formulaire reste envoyable.
		 */
		onTileError() {
			if ( ! this.tilesFailed ) {
				this.tilesFailed = true;
				this.$notice.text( settings.i18n.tilesError );
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

			this.address = location.address;
			this.showMarker( latLng );

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
			this.address = '';
			this.showMarker( latLng );
			this.writeValue( latLng );
			this.$notice.text(
				settings.i18n.markerPlaced
					.replace( '%1$s', latLng.lat.toFixed( COORDINATE_DECIMALS ) )
					.replace( '%2$s', latLng.lng.toFixed( COORDINATE_DECIMALS ) )
			);
			this.$input.triggerHandler( 'wsfign-map-placed', [ { lat: latLng.lat, lng: latLng.lng } ] );
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
