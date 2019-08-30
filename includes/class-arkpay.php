<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://arknpay.io
 * @since      1.0.0
 *
 * @package    ArknPay
 * @subpackage ArknPay/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ArknPay
 * @subpackage ArknPay/includes
 * @author     Ark Pay
 */
class Arkpay {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      ArknPay_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'Arkpay_VERSION' ) ) {
			$this->version = Arkpay_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'arknpay';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
        $this->define_public_hooks();

		if ( !$this->arkpay_is_woocommerce_active() ) {
			add_action( 'admin_notices', array($this,'arkpay_admin_notice__error') );
		}

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Arkpay_Loader. Orchestrates the hooks of the plugin.
	 * - Arkpay_i18n. Defines internationalization functionality.
	 * - Arkpay_Admin. Defines all hooks for the admin area.
	 * - Arkpay_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Check dependency of woocommerce
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-dependencies.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-arkpay-loader.php';

        /**
		 * The class responsible for defining API calls functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-arkpay-api-client.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-arkpay-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-arkpay-admin.php';
		
	
		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-arkpay-public.php';

		$this->loader = new Arkpay_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the ArknPay_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Arkpay_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$arkpay_admin = new Arkpay_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $arkpay_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $arkpay_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		
		global $arkpay_public;
		$arkpay_public = new Arkpay_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $arkpay_public, 'enqueue_scripts' );
	}

	/**
	 * [arkpay_admin_notice__error description]
	 * @return [type] [description]
	 */
	public function arkpay_admin_notice__error() {
		$class = 'notice notice-error';
		$message = __( 'ArknPay is enabled but not effective. It requires WooCommerce in order to work.', 'ArknPay' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}

	/**
	 * [arkpay_is_woocommerce_active description]
	 * @return [type] [description]
	 */
	private function arkpay_is_woocommerce_active() {
		return WC_GST_Dependencies::fn_woocommerce_active_check();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    ArknPay_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
