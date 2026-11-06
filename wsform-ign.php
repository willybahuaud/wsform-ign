<?php
/**
 * Plugin Name:       Carte IGN pour WS Form
 * Description:       IGN map and BAN address fields for WS Form. No Google, no API key.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Willy Bahuaud
 * Author URI:        https://wabeo.fr
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wsform-ign
 * Domain Path:       /languages
 * Update URI:        https://github.com/willybahuaud/wsform-ign
 *
 * @package WSForm_IGN
 */

defined( 'ABSPATH' ) || exit;

define( 'WSFIGN_VERSION', '0.1.1' );
define( 'WSFIGN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSFIGN_DIR', plugin_dir_path( __FILE__ ) );

require_once WSFIGN_DIR . 'includes/class-wsfign-map-field.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-address-field.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-assets.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-submission-display.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-submission-validation.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-post-mapping.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-github-release.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-release-notes.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-updater.php';

/**
 * Charge les traductions embarquées.
 *
 * Le plugin n'est pas sur WordPress.org : aucun paquet de langue ne viendrait les fournir.
 *
 * @return void
 */
function wsfign_load_textdomain() {
	load_plugin_textdomain( 'wsform-ign', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wsfign_load_textdomain', 0 );

WSFIGN_Map_Field::register_hooks();
WSFIGN_Address_Field::register_hooks();
WSFIGN_Assets::register_hooks();
WSFIGN_Submission_Display::register_hooks();
WSFIGN_Submission_Validation::register_hooks();
WSFIGN_Post_Mapping::register_hooks();

$wsfign_updater = new WSFIGN_Updater( __FILE__, WSFIGN_VERSION, 'willybahuaud', 'wsform-ign', 'Carte IGN pour WS Form' );
$wsfign_updater->register_hooks();
