<?php 
/**
 * WooCommerce Arkpay Class
 *
 * @link       https://arkpay.io
 * @since      1.0.0
 *
 * @package    Arkpay
 * @subpackage Arkpay/includes
 */


class Arkpay_Woocommerce {

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( ) {
        add_filter( 'woocommerce_get_price_html', array( $this, 'dual_price_display' ) );
        add_filter( 'woocommerce_cart_item_price', array( $this, 'dual_price_display' ) );
        add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'dual_price_display' ) );
        add_filter( 'woocommerce_currencies', array( $this, 'add_ark_currency' ) );
        add_filter( 'woocommerce_currency_symbol', array( $this, 'add_ark_currency_symbol' ), 10, 2);
        add_filter( 'woocommerce_payment_gateways', array( $this, 'add_to_gateways' ) );
        add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_cart_arkprice' ) );
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'display_checkout_arkprice' ) );
    }

    /**
	 * Add ARK Crypto Currency to WooCommerce List of Store Currencies (As Custom Currency)
	 *
	 * @since    1.0.0
	 * @param    string $currencies
	 * @return   string $currencies
	 */
    function add_ark_currency( $currencies )  {
        // Add ISO 4217 currency identifier
        $currencies['ARK'] = 'ARK';
        return $currencies;
    }

    /**
	 * Add the ARK Crypto Currency Symbol to WooCommerce (As Custom Currency)
	 *
	 * @since     1.0.0
	 * @param     string $currency_symbol	
     * @param     string $currency
	 * @return    string $currency_symbol	
	 */
    function add_ark_currency_symbol( $currency_symbol, $currency ) {
        switch( $currency )  {
            // Add ISO 4217 currency symbol
            case 'ARK': $currency_symbol = 'Ѧ'; break;
        }
        return $currency_symbol;
    }

    /**
	 * ArknPay Dual Price Display
	 *
	 * @since    1.0.0
	 * @param    string $price
	 * @param    string $price
	 */
    function dual_price_display( $price ) {
        // Gather and/or set variables
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $store_currency = get_woocommerce_currency();

        // Show ARK price if default store currency other than ARK is chosen and dual price display is switched on
        if ( $store_currency != 'ARK' && $arkgatewaysettings['arkdualprice'] == 'on' ) {
            // Clean up price string and convert to float
            $float_price = arkcommerce_price_number_conversion( $price );
            
            if ( is_array( $float_price ) ) {
                // Variable price detected
                if ( substr_count( $price, "&ndash" ) > 0 ) {
                    $arkprice = ( '<br>Ѧ' . arkcommerce_conversion_into_ark ( $float_price[0] ) . ' –' );
                    $arkprice .= ( '<br>Ѧ' . arkcommerce_conversion_into_ark ( $float_price[1] ) );
                    $price .= $arkprice;
                    return $price;
                } else { // Sale price detected
                    $arkprice = ( '<br><del>Ѧ' . arkcommerce_conversion_into_ark ( $float_price[0] ) . '</del>' );
                    $arkprice .= ( '<br>Ѧ' . arkcommerce_conversion_into_ark ( $float_price[1] ) );
                    $price .= $arkprice;
                    return $price;
                }
            } else { // Regular price detected
                $arkprice = ( '<br>Ѧ' . arkcommerce_conversion_into_ark ( $float_price ) );
                $price .= $arkprice;
                return $price;
            }
        }
        // Price already in ARK
        else return $price;
    }


    /**
	 * Display ARK Price+Timeout Notice to Customers at Cart Checkout	
	 *
	 * @since    1.0.0
	 * @param    string $price
	 * @return void arkcommercecheckoutnotice
	 */
    function display_cart_arkprice() {
        // Gather and/or set variables
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $store_currency = get_woocommerce_currency();

        // Check if ARK is already chosen as main currency and do nothing if so
        if( $store_currency != 'ARK' ) {
            // Gather and prepare fiat prices
            $total_float = arkcommerce_price_number_conversion( WC()->cart->get_cart_total() );
            $shipping_float = arkcommerce_price_number_conversion( WC()->cart->get_shipping_total() );
            
            // Execute conversion from fiat to ARK
            $arkprice = arkcommerce_conversion_into_ark( ( $total_float + $shipping_float ) );
            
            // Assemble the cart notice
            $arkcommercecartnotice = ( '<span class="dashicons-before dashicons-arkcommerce"> </span> <strong>' . __( 'Total', 'arkpay' ) . ': Ѧ' . $arkprice . '<br></strong><small>' . __( 'Order Expiry', 'arkcommerce' ) . ': ' . arkcommerce_get_order_timeout() . '</small>' );
        }  else {
            $arkcommercecartnotice = ( '<span class="dashicons-before dashicons-arkcommerce"> </span> <small>' . __( 'Order Expiry', 'arkpay' ). ': ' . arkcommerce_get_order_timeout() . '</small>' );
        }
        
        // Output the cart notice if enabled
        if( $arkgatewaysettings['arkdisplaycart'] == 'on' )	{
            wc_print_notice( $arkcommercecartnotice, 'notice' );
        }
    }


    /**
	 * Add the Gateway to WooCommerce	
	 *
	 * @since    1.0.0
	 * @param    array $gateways all available WC gateways	
	 * @return   array $gateways all WC gateways + WC_Gateway_ARK	
	 */
    function add_to_gateways( $gateways ) {
        $api_client = Arkpay_API_Client::getInstance();
        
        if (!$api_client->is_gateway_enabled()) {
            return $gateways;
        }
    
        $gateways[] = 'WC_Gateway_ARK';
        return $gateways;
    }


    /**
	 * Display ARK Price+Timeout Notice To Customers at Cart Checkout	
	 *
	 * @since    1.0.0
	 * @return void $arkcommercecheckoutnotice
	 */
    function display_checkout_arkprice() {
        // Gather and/or set variables
        global $woocommerce;
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $store_currency = get_woocommerce_currency();

        // Check if ARK is already chosen as main currency and do nothing if so
        if( $store_currency != 'ARK' ) 
        {
            // Gather and prepare fiat prices
            $total_float = arkcommerce_price_number_conversion( WC()->cart->get_cart_total() );
            $shipping_float = arkcommerce_price_number_conversion( WC()->cart->get_shipping_total() );
            
            // Execute conversion from fiat to ARK
            $arkprice = arkcommerce_conversion_into_ark( ( $total_float + $shipping_float ) );
            
            // Assemble the cart notice
            $arkcommercecheckoutnotice = ( '<span class="dashicons-before dashicons-arkcommerce"> </span> <strong>' . __( 'Total', 'arkcommerce' ) . ': Ѧ' . $arkprice . '<br></strong><small>' . __( 'Order Expiry', 'arkcommerce' ) . ': ' . arkcommerce_get_order_timeout() . '</small>' );
        }
        else $arkcommercecheckoutnotice = ( '<span class="dashicons-before dashicons-arkcommerce"> </span> <small>' . __( 'Order Expiry', 'arkcommerce' ). ': ' . arkcommerce_get_order_timeout() . '</small>' );
        
        // Output the checkout notice
        wc_print_notice( $arkcommercecheckoutnotice, 'notice' );
    }
    
}
?>