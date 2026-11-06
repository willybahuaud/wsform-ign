<?php
/**
 * Mise à jour du plugin depuis les releases GitHub.
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

/**
 * Branche une release GitHub sur le système de mise à jour de WordPress.
 *
 * Le préfixe évite un conflit sur un même site : un nom générique, repris par un
 * autre plugin dans une autre version, ferait charger la mauvaise classe.
 */
class WSFIGN_Updater {

	const AUTHOR_LINK = '<a href="https://wabeo.fr">Willy Bahuaud</a>';

	/**
	 * Basename du plugin (dossier/fichier.php).
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Slug du plugin (nom du dossier).
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Version actuelle du plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Nom affiché du plugin.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Adresse du dépôt.
	 *
	 * @var string
	 */
	private $repository_url;

	/**
	 * Lecture de la dernière release.
	 *
	 * @var WSFIGN_GitHub_Release
	 */
	private $release;

	/**
	 * Prépare les mises à jour d'un plugin.
	 *
	 * @param string $plugin_file Chemin du fichier principal.
	 * @param string $version     Version actuelle.
	 * @param string $owner       Propriétaire du dépôt GitHub.
	 * @param string $repo        Nom du dépôt GitHub.
	 * @param string $name        Nom affiché.
	 */
	public function __construct( $plugin_file, $version, $owner, $repo, $name ) {
		$this->basename       = plugin_basename( $plugin_file );
		$this->slug           = dirname( $this->basename );
		$this->version        = $version;
		$this->name           = $name;
		$this->repository_url = 'https://github.com/' . $owner . '/' . $repo;
		$this->release        = new WSFIGN_GitHub_Release( $owner, $repo, $this->slug, $version );
	}

	/**
	 * Branche les filtres de WordPress.
	 *
	 * Le filtre update_plugins_github.com vient de l'en-tête Update URI : WordPress range
	 * lui-même notre réponse dans response ou no_update, ce qui affiche l'interrupteur des
	 * mises à jour automatiques même sans mise à jour en attente.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'update_plugins_github.com', array( $this, 'filter_update' ), 10, 3 );
		add_filter( 'plugins_api', array( $this, 'filter_plugin_information' ), 10, 3 );
		add_filter( 'upgrader_source_selection', array( $this, 'fix_directory_name' ), 10, 4 );
	}

	/**
	 * Répond à WordPress pour ce plugin.
	 *
	 * Tout plugin dont l'Update URI vise github.com passe par ce filtre : il ne faut
	 * répondre que pour le nôtre.
	 *
	 * @param array|false $update      Réponse d'un filtre précédent.
	 * @param array       $plugin_data En-têtes du plugin.
	 * @param string      $plugin_file Basename du plugin.
	 * @return array|false
	 */
	public function filter_update( $update, $plugin_data, $plugin_file ) {
		if ( $this->basename !== $plugin_file ) {
			return $update;
		}

		$release = $this->release->get();
		$entry   = array(
			'slug'    => $this->slug,
			'url'     => $this->repository_url,
			'version' => $this->version,
			'package' => '',
		);

		if ( null === $release || ! version_compare( $release['version'], $this->version, '>' ) ) {
			return $entry;
		}

		return array_merge(
			$entry,
			array(
				'url'          => $release['url'],
				'version'      => $release['version'],
				'package'      => $release['package'],
				'requires'     => $release['requires'],
				'requires_php' => $release['requires_php'],
				'tested'       => $release['tested'],
			)
		);
	}

	/**
	 * Remplit la fenêtre « Voir les détails ».
	 *
	 * @param false|object|array $result Résultat par défaut.
	 * @param string             $action Action demandée.
	 * @param object             $args   Arguments de la requête.
	 * @return false|object|array
	 */
	public function filter_plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || $this->slug !== $args->slug ) {
			return $result;
		}

		$release = $this->release->get();

		if ( null === $release ) {
			return $result;
		}

		return (object) array(
			'name'          => $this->name,
			'slug'          => $this->slug,
			'version'       => $release['version'],
			'author'        => self::AUTHOR_LINK,
			'homepage'      => $this->repository_url,
			'requires'      => $release['requires'],
			'requires_php'  => $release['requires_php'],
			'tested'        => $this->get_tested_version( $release['tested'] ),
			'last_updated'  => $release['published_at'],
			'download_link' => $release['package'],
			'sections'      => array( 'changelog' => WSFIGN_Release_Notes::to_html( $release['body'] ) ),
		);
	}

	/**
	 * Retourne la version testée à présenter à la fenêtre des détails.
	 *
	 * « Tested up to: 7.1 » couvre toute la branche, mais cette fenêtre compare les versions
	 * telles quelles : sans ce relais, elle préviendrait à tort sur WordPress 7.1.4.
	 *
	 * @param string $tested Version déclarée par la release.
	 * @return string
	 */
	private function get_tested_version( $tested ) {
		$wordpress_version = get_bloginfo( 'version' );

		return 0 === strpos( $wordpress_version . '.', $tested . '.' ) ? $wordpress_version : $tested;
	}

	/**
	 * Garantit le nom du dossier installé, pour que le plugin reste actif.
	 *
	 * @param string      $source        Dossier extrait.
	 * @param string      $remote_source Dossier parent de l'extraction.
	 * @param WP_Upgrader $upgrader      Instance de l'upgrader.
	 * @param array       $hook_extra    Contexte de la mise à jour.
	 * @return string|WP_Error
	 */
	public function fix_directory_name( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! isset( $hook_extra['plugin'] ) || $this->basename !== $hook_extra['plugin'] ) {
			return $source;
		}

		$expected_directory = trailingslashit( $remote_source ) . $this->slug . '/';

		if ( trailingslashit( $source ) === $expected_directory ) {
			return $source;
		}

		if ( $wp_filesystem->move( $source, $expected_directory ) ) {
			return $expected_directory;
		}

		return new WP_Error( 'wsfign_rename_failed', __( 'The plugin folder could not be renamed during the update.', 'wsform-ign' ) );
	}
}
