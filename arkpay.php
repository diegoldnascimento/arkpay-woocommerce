<?php
/**
 *
 * @link              https://arkpay.io
 * @since             1.0.0
 * @package           Arkpay
 *
 * @wordpress-plugin
 * Plugin Name:       Ark Pay Gateway
 * Plugin URI:        https://arkpay.io
 * Description:       Ark Payment Gateway for Bridgechains
 * Version:           1.0.0
 * Author:            Danaric (Diego)
 * Author URI:        https://arkpay.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       arkpay
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
/**
 * Currently plugin version.
 */
define( 'ARKPAY_VERSION', '1.0.0' );

/**
 * 
 */
function activate_arkpay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-arkpay-activator.php';
	Arkpay_Activator::activate();
}

/**
 * 
 */
function deactivate_arkpay() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-arkpay-deactivator.php';
	Arkpay_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_arkpay' );
register_deactivation_hook( __FILE__, 'deactivate_arkpay' );

/**
 * 
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-arkpay.php';

/**
 * Begins execution of the plugin.
 *
 *
 * @since    1.0.0
 */
function run_arkpay() {
	$arkpay = new Arkpay();
    $arkpay->run();
}
run_arkpay();
