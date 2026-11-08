<?php
/**
 * Lecture de la dernière release GitHub du plugin.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Récupère, valide et garde en cache la dernière release.
 */
class WSFIGN_GitHub_Release {

	const API_URL          = 'https://api.github.com';
	const REQUEST_TIMEOUT  = 5;
	const SUCCESS_CACHE    = 12 * HOUR_IN_SECONDS;
	const FAILURE_CACHE    = HOUR_IN_SECONDS;
	const FAILURE_SENTINEL = 'failed';

	/**
	 * Propriétaire du dépôt.
	 *
	 * @var string
	 */
	private $owner;

	/**
	 * Nom du dépôt.
	 *
	 * @var string
	 */
	private $repo;

	/**
	 * Slug du plugin, préfixe des fichiers joints à la release.
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Version actuelle du plugin.
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Release déjà lue pendant cette requête.
	 *
	 * @var array|string|null
	 */
	private $loaded_release = null;

	/**
	 * Prépare la lecture d'un dépôt.
	 *
	 * @param string $owner           Propriétaire du dépôt.
	 * @param string $repo            Nom du dépôt.
	 * @param string $slug            Slug du plugin.
	 * @param string $current_version Version actuelle du plugin.
	 */
	public function __construct( $owner, $repo, $slug, $current_version ) {
		$this->owner           = $owner;
		$this->repo            = $repo;
		$this->slug            = $slug;
		$this->current_version = $current_version;
	}

	/**
	 * Retourne la dernière release installable, ou null s'il n'y en a pas.
	 *
	 * @return array|null Clés version, package, url, published_at, body, requires, requires_php, tested.
	 */
	public function get() {
		if ( null === $this->loaded_release ) {
			$this->loaded_release = $this->load();
		}

		return self::FAILURE_SENTINEL === $this->loaded_release ? null : $this->loaded_release;
	}

	/**
	 * Lit le cache, sinon interroge GitHub.
	 *
	 * Un échec reste en cache : l'API anonyme est limitée à 60 requêtes par heure et
	 * par IP, vite atteintes sur un mutualisé, et chaque appel raté ralentirait l'admin.
	 *
	 * Le cache est celui du réseau, comme update_plugins : sur un multisite, un seul
	 * appel sert tous les sites, au lieu d'un appel par site.
	 *
	 * @return array|string Release, ou le marqueur d'échec.
	 */
	private function load() {
		$cache_key = $this->slug . '_github_release';
		$cached    = get_site_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$release = $this->fetch();

		if ( null === $release ) {
			set_site_transient( $cache_key, self::FAILURE_SENTINEL, self::FAILURE_CACHE );
			return self::FAILURE_SENTINEL;
		}

		set_site_transient( $cache_key, $release, self::SUCCESS_CACHE );

		return $release;
	}

	/**
	 * Interroge GitHub et ne garde qu'une release complète.
	 *
	 * Le zipball de GitHub n'est jamais utilisé : il contient tout le dépôt, documentation
	 * et outils compris. Sans archive jointe, on ne propose pas de mise à jour.
	 *
	 * @return array|null
	 */
	private function fetch() {
		$url     = sprintf( '%s/repos/%s/%s/releases/latest', self::API_URL, $this->owner, $this->repo );
		$release = $this->request_json( $url );

		if ( ! is_object( $release ) || 1 !== preg_match( '/^v?(\d+\.\d+\.\d+)$/', $this->read_string( $release, 'tag_name' ), $tag_parts ) ) {
			return null;
		}

		$version = $tag_parts[1];
		$package = $this->find_asset_url( $release, $this->slug . '-' . $version . '.zip' );

		if ( '' === $package ) {
			return null;
		}

		return array_merge(
			array(
				'version'      => $version,
				'package'      => $package,
				'url'          => $this->read_string( $release, 'html_url' ),
				'published_at' => $this->read_string( $release, 'published_at' ),
				'body'         => $this->read_string( $release, 'body' ),
			),
			$this->read_manifest( $release, $version )
		);
	}

	/**
	 * Lit la compatibilité déclarée par le manifeste joint à la release.
	 *
	 * Les en-têtes du plugin installé décrivent l'ancienne version, pas celle qui arrive.
	 *
	 * @param object $release Release GitHub.
	 * @param string $version Version de la release.
	 * @return array Clés requires, requires_php et tested, vides si le manifeste manque.
	 */
	private function read_manifest( $release, $version ) {
		$url      = $this->find_asset_url( $release, $this->slug . '-' . $version . '.json' );
		$manifest = '' === $url ? null : $this->request_json( $url );

		return array(
			'requires'     => $this->read_string( $manifest, 'requires' ),
			'requires_php' => $this->read_string( $manifest, 'requires_php' ),
			'tested'       => $this->read_string( $manifest, 'tested' ),
		);
	}

	/**
	 * Lit un texte dans une réponse décodée, sans présumer de son type.
	 *
	 * @param mixed  $response Réponse décodée.
	 * @param string $key      Clé à lire.
	 * @return string Texte, ou chaîne vide.
	 */
	private function read_string( $response, $key ) {
		return isset( $response->$key ) && is_string( $response->$key ) ? $response->$key : '';
	}

	/**
	 * Vérifie qu'un fichier se télécharge bien depuis GitHub, en HTTPS.
	 *
	 * @param string $url URL à vérifier.
	 * @return bool
	 */
	private function is_github_url( $url ) {
		return 'https' === wp_parse_url( $url, PHP_URL_SCHEME ) && 'github.com' === wp_parse_url( $url, PHP_URL_HOST );
	}

	/**
	 * Cherche un fichier joint à la release, par son nom exact.
	 *
	 * @param object $release Release GitHub.
	 * @param string $name    Nom du fichier.
	 * @return string URL de téléchargement, ou chaîne vide.
	 */
	private function find_asset_url( $release, $name ) {
		$assets = isset( $release->assets ) && is_array( $release->assets ) ? $release->assets : array();

		foreach ( $assets as $asset ) {
			$url = $this->read_string( $asset, 'browser_download_url' );

			if ( $name === $this->read_string( $asset, 'name' ) && $this->is_github_url( $url ) ) {
				return $url;
			}
		}

		return '';
	}

	/**
	 * Lance une requête GET et décode sa réponse JSON.
	 *
	 * @param string $url URL à lire.
	 * @return mixed Réponse décodée, ou null pour un 404, un 403 de limite ou un délai dépassé.
	 */
	private function request_json( $url ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout' => self::REQUEST_TIMEOUT,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => $this->slug . '/' . $this->current_version,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}
}
