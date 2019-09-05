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
class Arkpay_Notice {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( ) {
        add_action( 'admin_notices', array( $this, 'arkpay_attention_required' ) );
	}
    
    /**
	 * Create custom message with Brand logo
	 *
	 * @since    1.0.0
	 */
    public function add_notice( $message ) {
        $arkadmurl = admin_url( 'admin.php?page=wc-settings&tab=arkpay' );

        return sprintf('<div class="notice notice-error is-dismissible"><p><span class="dashicons-before dashicons-arkcommerce" style="vertical-align:middle;"> </span> <strong><a href="%s">Ark Pay</a> %s</strong></p></div>', $arkadmurl, $message);
    }

    public function arkpay_attention_required() {
        $api = Arkpay_API_Client::getInstance();
        // Gather and/or set variables
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $currency_supported = true; //arkcommerce_check_currency_support();
        
        $arkprefurl = admin_url( 'admin.php?page=arknpay_preferences' );
        $arkinfourl = admin_url( 'admin.php?page=arkcommerce_information' );
        
        $notice = null;
        
        // Settings check
        if( $api->get_wallet_address() == "" && $api->is_gateway_enabled() && $currency_supported === true && $api->is_network_mainnet() ) {
            $notice = $this->add_notice( __( 'requires your attention for a functioning setup. Please enter a valid ARK Wallet Address or disable ArknPay.', 'arkpay' ) );
        } elseif( $currency_supported === false && $api->is_gateway_enabled() && $arkgatewaysettings['arkexchangetype'] != 'fixedrate') {
            $notice = $this->add_notice( __( 'Currently selected store currency is not supported in automatic exchange rate mode. Please switch to the fixed exchange rate in', 'arkpay' ) . ' <a href="' . $arkprefurl . '">' . __( 'Preferences', 'arkpay' ) );
        }
        elseif( $currency_supported === false && $api->is_gateway_enabled() && $arkgatewaysettings['arkexchangetype'] == 'fixedrate' && empty( $arkgatewaysettings['arkmanual'] ) ) {
            $notice = $this->add_notice( __( 'Currently selected store currency does not have its fixed exchange rate defined, please do so in', 'arkpay' ) . ' <a href="' . $arkprefurl . '">' . __( 'Preferences', 'arkpay' ) );
        }
        elseif( $currency_supported === true && $api->is_gateway_enabled() && $arkgatewaysettings['arkexchangetype'] == 'multirate' && empty( $arkgatewaysettings['arkmultiplier'] ) ) {
            $notice = $this->add_notice( __( 'Currently selected store currency exchange rate multiplier has not been defined, please do so in', 'arkpay' ) . ' <a href="' . $arkprefurl . '">' . __( 'Preferences', 'arkpay' ) );
        }
        elseif( !$api->is_gateway_enabled() )  {		
            $notice = $this->add_notice( __( 'payment gateway plugin is currently not enabled. Please configure and enable it or deactivate ArknPay.', 'arkpay' ) );            
        }
        elseif( $api->get_wallet_address() == "" && $api->is_gateway_enabled() && $api->is_network_devnet() ) {
            $notice = $this->add_notice( __( 'requires your attention for a functioning DARK Mode setup. Please enter a valid DARK Wallet Address or disable DARK Mode.', 'arkpay' ) );
        }
        elseif( $currency_supported === true && $api->is_gateway_enabled() && !defined('DISABLE_WP_CRON')) {	
            /* Todo: Enable */	
            // $notice = $this->add_notice( __( 'It is highly recommended turning on "Hard Cron" scheduled task operation. Guides are found in', 'arkpay' ) . ' <a href="' . $arkinfourl . '">' . __( 'Documentation', 'arkpay' ) );
        }

        if (!empty($notice)) {
            echo $notice;
        }
    }

}
