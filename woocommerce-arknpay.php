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
 * Author:            Diego
 * Author URI:        https://arkpay.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       arkpay
 * Domain Path:       /languages
 */

define( 'ARKNPAY_VERSION', '1.0.0' );

require('arkpay.php');

// Prohibit direct access
if( !defined( 'ABSPATH' ) ) exit;

// Make sure WooCommerce is active
if( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

function wc_arknpay_gateway_plugin_links( $links ) {
	// List of links added to plugin entry
	$plugin_links = array( '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=arkpay' ) . '">' . __( 'Settings', 'arkcommerce' ) . '</a>', '<a href="' . admin_url( 'admin.php?page=arknpay_preferences' ) . '">' . __( 'Preferences', 'arkcommerce' ) . '</a>');
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_arknpay_gateway_plugin_links' );

//////////////////////////////////////////////////////////////////////////////////////////
// Register Activation/Feactivation/Uninstall Hooks										//
//////////////////////////////////////////////////////////////////////////////////////////
register_activation_hook( __FILE__, 'arkcommerce_activation' );
register_deactivation_hook( __FILE__, 'arkcommerce_deactivation' );
register_uninstall_hook( __FILE__, 'arkcommerce_uninstall' );

//////////////////////////////////////////////////////////////////////////////////////////
// ArknPay Plugin Activation Function												//
// @record option array woocommerce_ark_gateway_settings								//
// @record start scheduled tasks														//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_activation() 
{
    // Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$adminslist = get_users( array( 'role' => 'administrator' ) );
	foreach( $adminslist as $adminuser ) $adminusermail = $adminuser->user_email;
	
	// Set default ArknPay options values array if none exist already
	// Add ArknPay enable/disable switch to options array
	if( empty( $arkgatewaysettings['enabled'] ) ) $arkgatewaysettings['enabled'] = 'no';
	
	// Add ArknPay notification target administrator (last from the set of fetched admins) to options array
	if( empty( $arkgatewaysettings['arknotify'] ) ) $arkgatewaysettings['arknotify'] = $adminusermail;
	
	// Add ArknPay order fulfillment admin notification to options array
	if( empty( $arkgatewaysettings['arkorderfillednotify'] ) ) $arkgatewaysettings['arkorderfillednotify'] = 'on';
	
	// Add ArknPay order placement admin notification to options array
	if( empty( $arkgatewaysettings['arkorderplacednotify'] ) ) $arkgatewaysettings['arkorderplacednotify'] = 'on';
	
	// Add ArknPay order expiry admin notification to options array
	if( empty( $arkgatewaysettings['arkorderexpirednotify'] ) ) $arkgatewaysettings['arkorderexpirednotify'] = 'on';
	
	// Add ArknPay initial exchange rate to options array
	if( empty( $arkgatewaysettings['arkexchangerate'] ) ) $arkgatewaysettings['arkexchangerate'] = '';
	
	// Add ArknPay store exchange rate type to options array: autorate/multirate/fixedrate
	if( empty( $arkgatewaysettings['arkexchangetype'] ) ) $arkgatewaysettings['arkexchangetype'] = 'autorate';
	
	// Add ArknPay store exchange rate multiplier to options array
	if( empty( $arkgatewaysettings['arkmultiplier'] ) ) $arkgatewaysettings['arkmultiplier'] = 1.01;
	
	// Add ArknPay store manual exchange rate to options array
	if( empty( $arkgatewaysettings['arkmanual'] ) ) $arkgatewaysettings['arkmanual'] = null;
	
	// Add ArknPay order expiry to options array
	if( empty( $arkgatewaysettings['arktimeout'] ) ) $arkgatewaysettings['arktimeout'] = 225;
	
	// Add ArknPay dual price display option to options array
	if( empty( $arkgatewaysettings['arkdualprice'] ) ) $arkgatewaysettings['arkdualprice'] = 'on';
	
	// Add ArknPay cart display option to options array
	if( empty( $arkgatewaysettings['arkdisplaycart'] ) ) $arkgatewaysettings['arkdisplaycart'] = 'on';
	
	// Add ArknPay DARK Mode option to options array
	if( empty( $arkgatewaysettings['darkmode'] ) ) $arkgatewaysettings['darkmode'] = '';

	
	
	// Add ArknPay payment title to options array
	if( empty( $arkgatewaysettings['title'] ) ) $arkgatewaysettings['title'] = __( 'ARK Payment', 'arkcommerce' );
	
	// Add ArknPay payment description to options array
	if( empty( $arkgatewaysettings['description'] ) ) $arkgatewaysettings['description'] = __( 'Pay for your purchase with ARK crypto currency by making a direct transaction to the ARK wallet address of the store.', 'arkcommerce' );
	
	// Add ArknPay order instructions to options array
	if( empty( $arkgatewaysettings['instructions'] ) ) $arkgatewaysettings['instructions'] = __( 'Please carry out the ARK transaction using the supplied data. Do not use an exchange wallet for the transaction.', 'arkcommerce' );
	
	// Add ArknPay service status to options array
	if( empty( $arkgatewaysettings['arkservice'] ) ) $arkgatewaysettings['arkservice'] = 0;
	
	// Update the ArknPay plugin settings array	
	update_option( 'woocommerce_ark_gateway_settings', $arkgatewaysettings );
	
	// Plugin upgrade from v1.0.x to v1.1.0 (if applicable)
	if( !empty( $arkgatewaysettings['arkapikey'] ) ) arkcommerce_upgrade_plugin_once();
}
//////////////////////////////////////////////////////////////////////////////////////////
// ArknPay Plugin Deactivation Function												//
// @record kill scheduled tasks															//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_deactivation() 
{
	// Kill recurring tasks
	wp_clear_scheduled_hook( 'arkcommerce_refresh_exchange_rate' );
	wp_clear_scheduled_hook( 'arkcommerce_check_for_open_orders' );
}
//////////////////////////////////////////////////////////////////////////////////////////
// ArknPay Plugin Uninstall Function												//
// @record remove arr arkgatewaysettings												//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_uninstall()
{
	// Gather and/or set variables
	$arkgatewaysettings = 'woocommerce_ark_gateway_settings';
	
	// Remove ArknPay configuration array entry for Multi Site or Single Site deployment
	if( is_multisite() ) delete_site_option( $arkgatewaysettings );
	else delete_option( $arkgatewaysettings );
}
//////////////////////////////////////////////////////////////////////////////////////////
// ArknPay Plugin Upgrade Function for Existing Deployments							//
// @record option array woocommerce_ark_gateway_settings								//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_upgrade_plugin_once()
{
	// Fetch current settings
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
		
	// Remove deprecated ArknPay Node-related settings from the options array
	unset( $arkgatewaysettings['darkapikey'] );
	unset( $arkgatewaysettings['arkapikey'] );
	unset( $arkgatewaysettings['arkusername'] );
	unset( $arkgatewaysettings['arkpassword'] );
	unset( $arkgatewaysettings['arkemail'] );
	unset( $arkgatewaysettings['arknodeadmin'] );
	unset( $arkgatewaysettings['arkapps'] );
	
	// Record updated settings
	update_option( 'woocommerce_ark_gateway_settings', $arkgatewaysettings );
}
//////////////////////////////////////////////////////////////////////////////////////////
// QR Code Generator, ArknPay Modules Inclusion										//
//////////////////////////////////////////////////////////////////////////////////////////
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/libraries/phpqrcode.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/libraries/phpqrcode.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-wc-arknpay-helper.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/class-wc-arknpay-helper.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/widgets/class-wc-arknpay-faq-widget.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/widgets/class-wc-arknpay-faq-widget.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/widgets/class-wc-arknpay-widget.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/widgets/class-wc-arknpay-widget.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/admin/class-wc-arknpay-admin.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/admin/class-wc-arknpay-admin.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-wc-arknpay-woocommerce.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/class-wc-arknpay-woocommerce.php' );
if( file_exists( plugin_dir_path( __FILE__ ) . 'includes/admin/class-wc-arknpay-notify.php' ) ) include( plugin_dir_path( __FILE__ ) . 'includes/admin/class-wc-arknpay-notify.php' );


//////////////////////////////////////////////////////////////////////////////////////////
// ArknPay Payment Gateway															//
// @class 		WC_Gateway_ARK															//
// @extends		WC_Payment_Gateway														//
// @package		WooCommerce/Classes/Payment												//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_gateway_init() 
{
	// ArknPay payment gateway class
	class WC_Gateway_ARK extends WC_Payment_Gateway 
	{
		public function __construct() 
		{
			// Gather and/or set variables
			$this->id                 = 'ark_gateway';
			$this->icon               = ''; // apply_filters( 'woocommerce_gateway_icon', plugin_dir_url( __FILE__ ) . 'assets/images/ark.jpg' );
			$this->has_fields         = false;
			$this->method_title       = 'ArknPay';
			$this->method_description = arkcommerce_headers( 'settings' );
			
			// Load the settings
			$this->init_form_fields();
			$this->init_settings();
			
			// Define variables
			$this->arknotify				= $this->get_option( 'arknotify' );
			$this->arkorderfillednotify		= $this->get_option( 'arkorderfillednotify' );
			$this->arkorderplacednotify		= $this->get_option( 'arkorderplacednotify' );
			$this->arkorderexpirednotify	= $this->get_option( 'arkorderexpirednotify' );
			$this->arkdisplaycart			= $this->get_option( 'arkdisplaycart' );
			$this->arkexchangetype			= $this->get_option( 'arkexchangetype' );
			$this->arkexchangerate			= $this->get_option( 'arkexchangerate' );
			$this->arkmultiplier			= $this->get_option( 'arkmultiplier' );
			$this->arkmanual				= $this->get_option( 'arkmanual' );
			$this->arktimeout				= $this->get_option( 'arktimeout' );
			$this->arkdualprice				= $this->get_option( 'arkdualprice' );
			$this->enabled					= $this->get_option( 'enabled' );
			$this->title					= $this->get_option( 'title' );
			$this->description				= $this->get_option( 'description' );
			$this->arkaddress				= $this->get_option( 'arkaddress' );
			$this->darknode					= $this->get_option( 'darknode' );
			$this->darkmode					= $this->get_option( 'darkmode' );
			$this->arkservice				= $this->get_option( 'arkservice' );
			$this->instructions				= $this->get_option( 'instructions', $this->description );
			
			// Actions
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'arkcommerce_order_placed_page' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, 'arkcommerce_generate_qr_code' );
			add_action( 'woocommerce_email_before_order_table', array( $this, 'arkcommerce_order_placed_email' ), 10, 3 );
			
			// Filters
			add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings_array' ) );
		}
//////////////////////////////////////////////////////////////////////////////////////////
//		 Initialize ArknPay Payment Gateway Settings Form Fields					//
//////////////////////////////////////////////////////////////////////////////////////////
		public function init_form_fields() 
		{
            arknpay_get_bridgechain_name();
	  		$this->form_fields = apply_filters( 'wc_ark_gateway_fields', array(
				'enabled'	 		=> array(
					'title'			=> __( 'Enable/Disable', 'arkcommerce' ),
					'type'			=> 'checkbox',
					'description'	=> __( 'Turn on ArknPay payment gateway to offer it to customers on checkout.', 'arkcommerce' ),
					'label'			=> __( 'Enable '. arknpay_get_bridgechain_name() .' Payments', 'arkcommerce' ),
					'default'		=> 'no',
                    'desc_tip'		=> true, ),
                'bridgechain' 		=> array(
                    'title'			=> __( 'Bridgechain Name', 'arkcommerce' ),
                    'type'			=> 'text',
                    'description'	=> __( 'The Bridgechain Name to be used for payments to his store.', 'arkcommerce' ),
                    'default'		=> '',
                    'desc_tip'		=> true, ),
                'bridgechaintestnet' 		=> array(
                    'title'			=> __( 'Bridgechain Testnet Name', 'arkcommerce' ),
                    'type'			=> 'text',
                    'description'	=> __( 'The Bridgechain Testnet Name to be used for payments to his store.', 'arkcommerce' ),
                    'default'		=> '',
                    'desc_tip'		=> true, ),
				'arkaddress' 		=> array(
					'title'			=> __( arknpay_get_bridgechain_name() . ' Wallet Address', 'arkcommerce' ),
					'type'			=> 'text',
					'description'	=> __( 'The ARK wallet address to be used for payments to his store.', 'arkcommerce' ),
					'default'		=> '',
					'desc_tip'		=> true, ),
				'darkmode' 			=> array(
					'title'			=> __( arknpay_get_bridgechain_testnet_name() . ' Mode (sandbox)', 'arkcommerce' ),
					'type'			=> 'checkbox',
					'description'	=> __( 'Enable Testnet Mode is for testing purposes only; when it is enabled, ArknPay connects to the ARK Devnet blockchain and uses the supplied DARK wallet address.', 'arkcommerce' ),
					'label'			=> __( 'Enable Testnet Mode (for testing purposes only)', 'arkcommerce' ),
					'default'		=> 'no',
					'desc_tip'		=> true, )));
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Validate ARK Wallet Address Field												//
// 		@param str $key																	//
//		@param str $value																//
//		@return str $value																//
//////////////////////////////////////////////////////////////////////////////////////////
		public function validate_arkaddress_field( $key, $value )
		{
			// Check if the ARK address is exactly 34 characters long and starts with 'A', throw an error if not
			if( strlen( trim( $value ) ) == 34 && strpos( trim( $value ), 'A' ) === 0 ) return trim( $value );
			else WC_Admin_Settings::add_error( esc_html( __( 'Error in ARK Address formatting.', 'arkcommerce' ) ) );
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Validate DARK Wallet Address Field												//
// 		@param str $key																	//
//		@param str $value																//
//		@return str $value																//
//////////////////////////////////////////////////////////////////////////////////////////
		public function validate_darkaddress_field( $key, $value )
		{
			// Check if the ARK address is exactly 34 characters long and starts with 'A', throw an error if not
			if( !empty( $value ) )
			{
				if ( strlen( trim( $value ) ) == 34 && strpos( trim( $value ), 'D' ) === 0 ) return trim( $value );
				else WC_Admin_Settings::add_error( esc_html( __( 'Error in DARK Address formatting.', 'arkcommerce' ) ) );
			}
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Sanitize Settings 																//
// 		@param arr $settings															//
//		@return arr $settings															//
//////////////////////////////////////////////////////////////////////////////////////////		
		public function sanitize_settings_array( $settings )
		{
			if( isset( $settings) )
			{
				// Sanitize ARK wallet address
				if( isset( $settings['arkaddress'] ) ) $settings['arkaddress'] = sanitize_text_field( trim( Arkpay_API_Client::getInstance()->get_wallet_address() ) );
				
			}
			// Return sanitized array
			return $settings;
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Order Change to On-hold, Reduce Stock, Empty Cart, Notify Admin					//
// 		@param int $order_id															//
//		@return array																	//
//		@record post order																//
//////////////////////////////////////////////////////////////////////////////////////////
		public function process_payment( $order_id ) 
		{
			// Gather and/or set variables
			global $woocommerce;
			$arkopenorders = arkcommerce_open_order_queue_count();
			$order = wc_get_order( $order_id );
			$ark_neworder_data = $order->get_data();
			$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
			$ark_neworder_currency = get_woocommerce_currency();
			$api_client = Arkpay_API_Client::getInstance();
            $arkblockheight = $api_client->get_block_height();
			$arkexchangerate = arkcommerce_get_exchange_rate();
			$storewalletaddress = $api_client->get;
			
			// Check for max open order count
			if( intval( $arkopenorders ) <= 48 )
			{
				// DARK Mode settings
				if( $arkgatewaysettings['darkmode'] == 'yes' ) $storewalletaddress = $arkgatewaysettings['darkaddress'];
			
				// Establish if ARK is default currency and convert to arktoshi
				if( $ark_neworder_currency == 'ARK' ) $ark_neworder_total = $ark_neworder_data['total'] * 100000000;
			
				// Convert from fiat currency into ARK and subsequent result into arktoshi
				else 
				{
					$ark_converted_total = arkcommerce_conversion_into_ark( $ark_neworder_data['total'] );
					$ark_neworder_total = $ark_converted_total * 100000000;
				}
			
				// Validate order in arktoshi not being zero due to conversion error and validate block height
				if( $ark_neworder_total != 0 && $arkblockheight != 0 ) 
				{
					if( $arkgatewaysettings['arktimeout'] == 'never' ) 
					{
						// Set order expiration to never
						$arkorderexpiryblock = 'never';

						// Record order metadata
						$order->update_meta_data( 'ark_total', ( $ark_neworder_total / 100000000 ) );
						$order->update_meta_data( 'ark_arktoshi_total', $ark_neworder_total );
						$order->update_meta_data( 'ark_store_currency', $ark_neworder_currency );
						$order->update_meta_data( 'ark_order_block', $arkblockheight );
						$order->update_meta_data( 'ark_expiration_block', $arkorderexpiryblock );
						$order->update_meta_data( 'ark_exchange_rate', $arkexchangerate );
						$order->update_meta_data( 'ark_store_wallet_address', $storewalletaddress );
						$order->save();
					
						// Mark as on-hold (awaiting the payment) and notify admin of triggering the initial payment check
						$ark_order_onhold_note = ( __( 'Awaiting initial ARK transaction check for the order. Current block height', 'arkcommerce' ) . ': ' . $arkblockheight . '. ' . __( 'The order does not expire.', 'arkcommerce' ) );
						$order->update_status( 'on-hold', $ark_order_onhold_note );
					}
					else 
					{
						// Calculate order expiration
						$arkorderexpiryblock = ( $arkblockheight + $arkgatewaysettings['arktimeout'] );
					
						// Record order metadata
						$order->update_meta_data( 'ark_total', ( $ark_neworder_total / 100000000 ) );
						$order->update_meta_data( 'ark_arktoshi_total', $ark_neworder_total );
						$order->update_meta_data( 'ark_store_currency', $ark_neworder_currency );
						$order->update_meta_data( 'ark_order_block', $arkblockheight );
						$order->update_meta_data( 'ark_expiration_block', $arkorderexpiryblock );
						$order->update_meta_data( 'ark_exchange_rate', $arkexchangerate );
						$order->update_meta_data( 'ark_store_wallet_address', $storewalletaddress );
						$order->save();
					
						// Mark as on-hold (awaiting the payment) and notify admin of triggering the initial payment check
						$ark_order_onhold_note = ( __( 'Awaiting initial ARK transaction check for the order. Current block height', 'arkcommerce' ) . ': ' . $arkblockheight . '. ' . __( 'This order expires at block height:', 'arkcommerce' ) . ' ' . $arkorderexpiryblock . '.' );
						$order->update_status( 'on-hold', $ark_order_onhold_note );
					}
					// Reduce stock levels
					wc_reduce_stock_levels( $order_id );
				
					// Remove cart
					WC()->cart->empty_cart();
				
					// Notify admin if enabled
					if( $arkgatewaysettings['arkorderplacednotify'] == 'on' ) arkcommerce_admin_notification( $order_id, 'orderplaced' );
				
					// Return successful result and redirect
					return array( 'result' => 'success', 'redirect' => $this->get_return_url( $order ) );
				}
				// Error: currency conversion error
				elseif( $ark_neworder_total == 0 && $arkblockheight != 0 ) 
				{
					// Output the error notice
					wc_add_notice( ( '<span class="dashicons-before dashicons-arkcommerce"> </span> ' . __( 'Error: ARK currency conversion service unresponsive, please try again later.', 'arkcommerce' ) ), 'error' );
					
					// Mark as cancelled (currency conversion malfunction)
					$ark_order_cancelled_note = __( 'ArknPay ARK currency conversion error.', 'arkcommerce' );
					$order->update_status( 'cancelled', $ark_order_cancelled_note );
					return;
				}
				// Error: ARK/DARK Node unresponsive error
				elseif( $ark_neworder_total != 0 && $arkblockheight == 0 ) 
				{
					// Output the error notice
					wc_add_notice( ( '<span class="dashicons-before dashicons-arkcommerce"> </span> ' . __( 'Error: ARK network unresponsive, please try again later.', 'arkcommerce' ) ), 'error' );
					
					// Mark as cancelled (ARK network unresponsive)
					$ark_order_cancelled_note = __( 'ARK network unresponsive or unreachable.', 'arkcommerce' );
					$order->update_status( 'cancelled', $ark_order_cancelled_note );
					return;
				}
			}
			// Open order queue full (>48 open orders) due to ARK/DARK Node API query result count limit (50 total)
			else
			{
				// Output the error notice
				wc_add_notice( ( '<span class="dashicons-before dashicons-arkcommerce"> </span> ' . __( 'Error: ARK payment gateway order queue full, please try again later.', 'arkcommerce' ) ), 'error' );
				
				// Mark as cancelled (queue full)
				$ark_order_cancelled_note = __( 'ArknPay open order queue full.', 'arkcommerce' );
				$order->update_status( 'cancelled', $ark_order_cancelled_note );
				return;
			}
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Add Content to the Order Received/'Thank You' Page								//
// 		@param WC_Order $order_id														//
// 		@output ArknPay information													//
//////////////////////////////////////////////////////////////////////////////////////////
		public function arkcommerce_order_placed_page( $order_id ) 
		{
			if( $this->instructions ) 
			{
				// Gather and/or set variables
				$orderdata = wc_get_order($order_id);
				$arkorderid = $orderdata->get_id();
				$arkprice = $orderdata->get_meta( $key = 'ark_total' );
				$arkcontent = arkcommerce_order_data_content( $arkorderid, $arkprice );
								
				// Output the QR Code, admin-defined instructions, and the ARK data table
				echo $arkcontent;
			}
		}
//////////////////////////////////////////////////////////////////////////////////////////
// 		Add Content to the Order Email Before Order Table								//
// 		@param WC_Order $order															//
//		@param bool $sent_to_admin														//
// 		@param bool $plain_text															//
// 		@output ArknPay information													//
//////////////////////////////////////////////////////////////////////////////////////////
		public function arkcommerce_order_placed_email( $order, $sent_to_admin, $plain_text = false ) 
		{
			if( $this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) 
			{
				// Gather and/or set variables
				$arkorderid = $order->get_id();
				$arkprice = get_metadata( 'post', $arkorderid, 'ark_total', true );
				$arkcontent = arkcommerce_order_data_content( $arkorderid, $arkprice );				
				
				// Output the QR Code, admin-defined instructions, and the ARK data table
				echo( $arkcontent . PHP_EOL );
			}
		}
	}
}
add_action( 'plugins_loaded', 'arkcommerce_gateway_init', 11 );