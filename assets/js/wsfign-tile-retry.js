/**
 * Nouvelles tentatives sur les tuiles en erreur.
 *
 * La Géoplateforme renvoie des erreurs ponctuelles, parfois en rafale, et un second appel
 * répond 200. Leaflet ne retente jamais : le trou resterait visible tant que la vue ne change pas.
 *
 * @package WSForm_IGN
 */

( function wsfignTileRetryModule( window ) {
	'use strict';

	const RETRY_DELAYS_MS = [ 1000, 3000 ];
	const UNAVAILABLE_EVENT = 'wsfigntilesunavailable';

	const retryCounts = new WeakMap();
	const abandonedZooms = new WeakMap();
	const loadedZooms = new WeakMap();

	/**
	 * Retourne l'ensemble de zooms d'une couche.
	 *
	 * @param {WeakMap}     zoomsByLayer Zooms par couche.
	 * @param {L.TileLayer} tileLayer    Couche de tuiles.
	 * @return {Set<number>} Zooms de cette couche.
	 */
	function getZooms( zoomsByLayer, tileLayer ) {
		if ( ! zoomsByLayer.has( tileLayer ) ) {
			zoomsByLayer.set( tileLayer, new Set() );
		}

		return zoomsByLayer.get( tileLayer );
	}

	/**
	 * Relance le chargement d'une tuile, si elle est toujours affichée.
	 *
	 * L'URL change à chaque essai : WebKit ignore une réaffectation de src à l'identique.
	 * Les gestionnaires de Leaflet restent actifs : un succès marque la tuile comme
	 * chargée, un nouvel échec repasse par onTileError.
	 *
	 * @param {HTMLImageElement} tile Tuile à recharger.
	 */
	function reloadTile( tile ) {
		if ( ! tile.isConnected ) {
			return;
		}

		const retryUrl = new URL( tile.src );

		retryUrl.searchParams.set( '_retry', String( retryCounts.get( tile ) ) );
		tile.src = retryUrl.toString();
	}

	/**
	 * Mémorise un zoom où la couche a su charger une tuile.
	 *
	 * @param {L.TileEvent} tileEvent Événement Leaflet.
	 */
	function onTileLoad( tileEvent ) {
		getZooms( loadedZooms, tileEvent.target ).add( tileEvent.coords.z );
	}

	/**
	 * Programme un nouvel essai, ou abandonne ce niveau de zoom.
	 *
	 * Un échec persistant signale plutôt une zone hors couverture : l'IGN répond 404 hors
	 * de France à fort zoom. L'abandon vaut pour ce zoom seulement, car la couverture en
	 * dépend. Le service n'est signalé indisponible que si aucune tuile de ce zoom n'a chargé.
	 *
	 * @param {L.TileErrorEvent} errorEvent Événement Leaflet.
	 */
	function onTileError( errorEvent ) {
		const tileLayer = errorEvent.target;
		const tile = errorEvent.tile;
		const zoom = errorEvent.coords.z;
		const abandoned = getZooms( abandonedZooms, tileLayer );
		const retryCount = retryCounts.get( tile ) || 0;

		if ( abandoned.has( zoom ) ) {
			return;
		}

		if ( retryCount >= RETRY_DELAYS_MS.length ) {
			abandoned.add( zoom );

			if ( ! getZooms( loadedZooms, tileLayer ).has( zoom ) ) {
				tileLayer.fire( UNAVAILABLE_EVENT );
			}

			return;
		}

		retryCounts.set( tile, retryCount + 1 );
		window.setTimeout( reloadTile.bind( null, tile ), RETRY_DELAYS_MS[ retryCount ] );
	}

	/**
	 * Active les nouvelles tentatives sur une couche.
	 *
	 * @param {L.TileLayer} tileLayer Couche de tuiles.
	 */
	function watchTileLayer( tileLayer ) {
		tileLayer.on( 'tileload', onTileLoad );
		tileLayer.on( 'tileerror', onTileError );
	}

	window.wsfignTileRetry = { watch: watchTileLayer, UNAVAILABLE_EVENT };
}( window ) );
