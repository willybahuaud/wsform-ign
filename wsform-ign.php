<?php
/**
 * Plugin Name:       Carte IGN pour WS Form
 * Description:       IGN map and BAN address fields for WS Form. No Google, no API key.
 * Version:           0.1.0
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

define( 'WSFIGN_VERSION', '0.1.0' );
define( 'WSFIGN_URL', plugin_dir_url( __FILE__ ) );
define( 'WSFIGN_DIR', plugin_dir_path( __FILE__ ) );

require_once WSFIGN_DIR . 'includes/class-wsfign-map-field.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-address-field.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-assets.php';
require_once WSFIGN_DIR . 'includes/class-wsfign-submission-display.php';

WSFIGN_Map_Field::register_hooks();
WSFIGN_Address_Field::register_hooks();
WSFIGN_Assets::register_hooks();
WSFIGN_Submission_Display::register_hooks();
