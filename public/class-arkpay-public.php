<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://arkpay.io
 * @since      1.0.0
 *
 * @package    Arkpay
 * @subpackage arkpay/public
 */

/**
 * The public-facing functionality of Arkpay.
 *
 *
 * @package    Arkpay
 * @subpackage Arkpay/public
 * @author     Danaric (Diego)
 */
class Arkpay_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
    private $version;
    
    /**
	 * Client API wrapper
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private $api_client;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->api_client = Arkpay_API_Client::getInstance();
        // $this->api_client->get_wallet_balance( get_option('arkpay_mainnet_wallet') );
    }

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        if ( ! is_checkout() ) return;

        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/arkpay-public.js', array( 'jquery' ), $this->version, false );
    }
}