/**
 * Client du géocodage de la Géoplateforme : traduit ses réponses en suggestions d'adresse.
 *
 * @package WSForm_IGN
 */

( function wsfignBanClientModule() {
	'use strict';

	const BASE_URL = 'https://data.geopf.fr/geocodage/';
	const SUGGESTION_LIMIT = 5;
	const MIN_QUERY_LENGTH = 3;
	const MAX_QUERY_LENGTH = 200;
	const DEFAULT_PAUSE_SECONDS = 5;
	const STATUS_TOO_MANY_REQUESTS = 429;

	// Après un 429, le service refuse tout pendant quelques secondes : inutile d'insister.
	let pausedUntil = 0;

	/**
	 * Indique si le service acceptera cette recherche : il répond 400 aux autres.
	 *
	 * @param {string} query Texte saisi.
	 * @return {boolean} Vrai si la recherche peut partir.
	 */
	function isSearchable( query ) {
		return query.length >= MIN_QUERY_LENGTH && query.length <= MAX_QUERY_LENGTH && /^[\p{L}\p{N}]/u.test( query );
	}

	/**
	 * Convertit un résultat GeoJSON en suggestion.
	 *
	 * La propriété street est vide en géocodage inverse, et name vaut le nom de la
	 * commune pour le type municipality : seul name, hors commune, donne la ligne d'adresse.
	 *
	 * @param {Object} feature Résultat GeoJSON.
	 * @return {Object} Suggestion, dont les clés sont les composants du mapping.
	 */
	function toSuggestion( feature ) {
		const properties = feature.properties;
		const addressLine = 'municipality' === properties.type ? '' : properties.name || '';
		const labelParts = [ addressLine, properties.postcode, properties.city ];

		return {
			label: labelParts.filter( Boolean ).join( ' ' ),
			address_line: addressLine,
			postcode: properties.postcode || '',
			city: properties.city || '',
			citycode: properties.citycode || '',
			// depcode manque en géocodage inverse et outre-mer, alors que context commence toujours par le code.
			department: properties.depcode || String( properties.context || '' ).split( ',' )[ 0 ].trim(),
			lat: feature.geometry.coordinates[ 1 ],
			lng: feature.geometry.coordinates[ 0 ],
		};
	}

	/**
	 * Interroge le service.
	 *
	 * @param {string}      path   Chemin du service.
	 * @param {Object}      params Paramètres de la requête.
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<Object[]>} Suggestions. Rejetée si le service est indisponible.
	 */
	async function requestSuggestions( path, params, signal ) {
		if ( Date.now() < pausedUntil ) {
			throw new Error( 'Geocoding paused after HTTP 429' );
		}

		const response = await fetch( BASE_URL + path + '?' + new URLSearchParams( params ), { signal } );

		if ( STATUS_TOO_MANY_REQUESTS === response.status ) {
			const pauseSeconds = parseInt( response.headers.get( 'retry-after' ), 10 ) || DEFAULT_PAUSE_SECONDS;

			pausedUntil = Date.now() + pauseSeconds * 1000;
		}

		if ( ! response.ok ) {
			throw new Error( 'Geocoding failed with HTTP ' + response.status );
		}

		const collection = await response.json();

		return collection.features.map( toSuggestion );
	}

	/**
	 * Cherche des adresses à partir d'un début de saisie.
	 *
	 * @param {string}      query  Texte saisi.
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<Object[]>} Suggestions.
	 */
	function search( query, signal ) {
		return requestSuggestions( 'search', { q: query, autocomplete: 1, limit: SUGGESTION_LIMIT, index: 'address' }, signal );
	}

	/**
	 * Cherche l'adresse la plus proche d'un point.
	 *
	 * @param {number}      lat    Latitude.
	 * @param {number}      lng    Longitude.
	 * @param {AbortSignal} signal Signal d'annulation.
	 * @return {Promise<Object[]>} Suggestions : vide si rien n'est proche, en mer par exemple.
	 */
	function reverse( lat, lng, signal ) {
		return requestSuggestions( 'reverse', { lat, lon: lng, limit: 1, index: 'address' }, signal );
	}

	window.wsfignBanClient = { isSearchable, search, reverse };
}() );
