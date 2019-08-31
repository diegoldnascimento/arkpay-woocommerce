<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://arknpay.io/
 * @since      1.0.0
 *
 * @package    Ark Pay
 * @subpackage ArknPay/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 *
 * @package    Ark Pay
 * @subpackage ArknPay/admin
 * @author     Ark Gateway
 */
class Arkpay_Admin {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
        $this->version = $version;

        add_filter( 'woocommerce_get_settings_pages', array( $this, 'arknpay_add_settings' ), 15 );
        add_filter( 'plugin_action_links', array( $this, 'get_action_links' ), 10, 2 );
        
        $this->arknpay_add_notices();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/arkpay-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . '-arcommerce-legacy', plugin_dir_url( __FILE__ ) . 'css/arkcommerce-legacy.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name . '-arcommerce-wordpress-legacy', plugin_dir_url( __FILE__ ) . 'css/arkcommerce-wp-legacy.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/arkpay-admin.js', array( 'jquery' ), $this->version, false );
	}

	/**
	 * Create settings tab in Woocommerce settings 
	 *
	 * @since    1.0.0
	 */
	public function arknpay_add_settings() {
		require_once plugin_dir_path( __FILE__ ) . 'class-wc-arkpay-settings.php';
		return new WC_Settings_Arkpay();
    }

    /**
	 * Create settings tab in Woocommerce settings 
	 *
	 * @since    1.0.0
	 */
	public function arknpay_add_notices() {
		require_once plugin_dir_path( __FILE__ ) . 'class-arkpay-notice.php';
		return new ArkPay_Notice();
    }
    
    /**
	 * Create extra action links to Plugin settings
	 *
	 * @since    1.0.0
	 */
    public function get_action_links( $links, $file ) {
        $base = explode( '/', plugin_basename( __FILE__ ) );
        $file = explode( '/', $file );

        if( $base[0] === $file[0] ) {
            $extraLinks = array(
                sprintf('<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=arkpay' ), __( 'Settings', 'arkpay' ))
            );
            $links = array_merge($links, $extraLinks);
        }

        return (array) array_reverse($links);
    }

}
