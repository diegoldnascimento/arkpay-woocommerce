<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://arkpay.io
 * @since      1.0.0
 *
 * @package    arkpay
 * @subpackage arkpay/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    arkpay
 * @subpackage arkpay/includes
 * @author     Diego
 */
class Arkpay_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
        wp_clear_scheduled_hook( 'arkcommerce_refresh_exchange_rate' );
        wp_clear_scheduled_hook( 'arkcommerce_check_for_open_orders' );
	}

}
