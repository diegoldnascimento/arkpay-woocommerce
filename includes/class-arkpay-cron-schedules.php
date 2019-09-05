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


class Arkpay_Cron_Schedules {

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( ) {
        add_filter( 'cron_schedules', array( $this, 'wpcron_schedule' ) );
        add_action( 'arkcommerce_refresh_exchange_rate', array( $this, 'update_exchange_rate' ) );
        add_action( 'arkcommerce_check_for_open_orders', array( $this, 'arkcommerce_validation_worker' ) );

        $this->init();
    }

    /**
	 * Add wp_cron Minutely and Biminutely Interval Schedules if not is scheduled
	 *
	 * @since    1.0.0
	 * @return   void
	 */
    public function init() {
        // Conclude with the exchange rate update and order lookup Cron jobs
        if( !wp_next_scheduled( 'arkcommerce_refresh_exchange_rate' ) ) {
            wp_schedule_event( time(), 'arkcommerce_biminutely', 'arkcommerce_refresh_exchange_rate' );
        }
        
        if( !wp_next_scheduled( 'arkcommerce_check_for_open_orders' ) ) {
            wp_schedule_event( time(), 'arkcommerce_minutely', 'arkcommerce_check_for_open_orders' );
        };
    }

    /**
	 * Add wp_cron Minutely and Biminutely Interval Schedules
	 *
	 * @since    1.0.0
	 * @param    array $schedules
	 * @return   array $schedules
	 */
    public function wpcron_schedule( $schedules ) {
        // Create 120s schedule and add to array
        $schedules['arkcommerce_biminutely'] = array(
                    'interval'  => 120,
                    'display'   => __( 'Biminutely', 'arkpay' ) );
        
        // Create 60s schedule and add to array
        $schedules['arkcommerce_minutely'] = array(
                    'interval'  => 60,
                    'display'   => __( 'Minutely', 'arkpay' ) );

        return $schedules;
    }
    /**
     * Update Currency Market Exchange Rate between chosen store Fiat and ARK Pairs 
     *
     * @return float $arkexchangerate
     */
    public function update_exchange_rate() {
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $store_currency = get_woocommerce_currency();

        $arkexchangerate = Arkpay_API_Client::getInstance()->get_exchange_rate( 'ARK',  $store_currency );

        $arkgatewaysettings['arkexchangerate'] = $arkexchangerate;
        update_option( 'woocommerce_ark_gateway_settings', $arkgatewaysettings );
    }

    /**
     * Periodic Worker Triggering Transaction Validation Jobs on ArknPay Open Orders
     *
     * @return void
     */
    function arkcommerce_validation_worker() {
        // Gather and/or set variables
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        global $wpdb;
        
        // Construct a query for all WC orders made using ArknPay payment gateway
        $arkordersquery = ( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_value='ark_gateway';" );
        
        // Execute the query
        $arkorders = $wpdb->get_results( $arkordersquery );

        // Determine valid database connection
        if( !empty( $arkorders ) ) {
            // Iterate through open orders and commence tx check processing for each open order
            foreach( $arkorders as $arkorder ):setup_postdata( $arkorder );
                $order = wc_get_order( $arkorder->post_id );
                if( $order->has_status( 'on-hold' ) ) $this->arkcommerce_ark_transaction_validation( $arkorder->post_id );
            endforeach;	
        }
    }


    /**
     * Function Checking for Order Payment Fulfillment, Handling and Notification
     * 
     * @param int $order_id		
     * @return int arkservice
     */
    function arkcommerce_ark_transaction_validation( $order_id ) {
        // Gather and/or set variables
        $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
        $ark_transaction_found = false;
        $order = wc_get_order( $order_id );
        $ark_order_data = $order->get_data();
        $ark_order_id = $ark_order_data['id'];
        $api_client = Arkpay_API_Client::getInstance();
        $arkblockheight = $api_client->get_block_height();
        $ark_arktoshi_total = $order->get_meta( $key = 'ark_arktoshi_total' );
        $arkorderblock = $order->get_meta( $key = 'ark_order_block' );
        $arkorderexpiryblock = $order->get_meta( $key = 'ark_expiration_block' );
        $storewalletaddress = $order->get_meta( $key = 'ark_store_wallet_address' );
        
        // Validate the order is on hold and the chosen payment method is ArknPay
        
        $payment_gateway = wc_get_payment_gateway_by_order( $order );

        if( $order->has_status( 'on-hold' ) && 'ark_gateway' === $payment_gateway->id ) 
        {
            // Fetch last 50 transactions into store wallet
            $arktxrecordarray = $api_client->get_transactions( 50 );
        //  var_dump($arktxrecordarray);
            
            if( is_array( $arktxrecordarray ) && $arkblockheight !=0 ) 
            {
                // ARK/DARK node responsive
                $arkgatewaysettings['arkservice'] = 0;
                
                // Process each result in array to establish the correct transaction filling this particular order
                foreach( $arktxrecordarray as $arktxrecord ):setup_postdata( $arktxrecord );
        
                    // Validate TX as correct payment tx for the order
                    if( $arktxrecord['vendorField'] == $ark_order_id && intval( $arktxrecord['amount'] ) == intval( $ark_arktoshi_total ) ) 
                    {
                        $arktxrecordtxid = $arktxrecord['id'];
                        // Query ARK/DARK Node API for the specific transaction
                        $arknodetxresponse = $api_client->get_transaction($arktxrecordtxid);
                       // var_dump($arknodetxresponse);
                       // die();
                        // API response
                        if( !is_wp_error($arknodetxresponse) ) 
                        {
                            $arktxresponse = $arknodetxresponse;
                            // Validate response
                            
                            if ( count($arktxresponse) > 0 ) {
                                $api_client = Arkpay_API_Client::getInstance();
                                $arktxblockheight = $api_client->get_block_height($arktxresponse['blockId']);                            
                            }
                            //if( $arktxresponse['success'] === true ) $arktxblockheight = $arktxresponse['transaction']['height'];
                        }
                    
                        // Validate found TX block height is higher or equal to blockchain block height at the time order was made and the order has not expired at that time
                        if( $arkorderexpiryblock != 'never' && intval( $arktxblockheight ) <= intval( $arkorderexpiryblock ) && intval( $arktxblockheight ) >= intval( $arkorderblock ) ) 
                        {
                            // Correct payment TX found
                            $ark_transaction_found = true;
                            $ark_transaction_identifier = $arktxrecordtxid;
                            $ark_transaction_block = $arktxblockheight;
                        }
                        // Alternatively, validate found TX block height is higher or equal to blockchain block height at the time order was made
                        elseif( $arkorderexpiryblock == 'never' && intval( $arktxblockheight ) >= intval( $arkorderblock ) ) 
                        {
                            // Correct payment TX found
                            $ark_transaction_found = true;
                            $ark_transaction_identifier = $arktxrecordtxid;
                            $ark_transaction_block = $arktxblockheight;
                        }
                    }
                endforeach;
            
                // Payment TX found and it occurred after the order had been committed
                if( $ark_transaction_found === true ) 
                {
                    // Mark as complete (the payment has been made), add transaction id plus payment block number to order metadata
                    $ark_order_completed_note = ( __( 'ARK order filled at block:', 'arkcommerce' ) . ' ' . $ark_transaction_block . ' TX: <a href="' . $api_client->build_transaction_url( $ark_transaction_identifier ) . '">' . $ark_transaction_identifier . '</a>.' );
                    $order->update_meta_data( 'ark_transaction_id', $ark_transaction_identifier );
                    $order->update_meta_data( 'ark_payment_block', $ark_transaction_block );
                    $order->save();
                    $order->update_status( 'completed', $ark_order_completed_note );
                    
                    // Notify admin if enabled
                    if( $arkgatewaysettings['arkorderfillednotify'] == 'on' ) arkcommerce_admin_notification( $order_id, 'orderfilled' );
                }
                // No payment TX for the order number found at this time
                elseif( $arkorderexpiryblock != 'never' && $ark_transaction_found === false && intval( $arkblockheight ) <= intval( $arkorderexpiryblock ) ) 
                {
                    // Make note of unsuccessful check (the payment has not yet been made)
                    $order->add_order_note( ( __( 'ARK transaction check yields no matches. Current block height', 'arkcommerce' ) . ': ' . $arkblockheight . '.' ), false, true );
                }
                // No payment TX for the order number found at this time (for orders that do not expire)
                elseif( $arkorderexpiryblock == 'never' && $ark_transaction_found === false ) 
                {
                    // Make note of unsuccessful check (the payment has not yet been made)
                    $order->add_order_note( ( __( 'ARK transaction check yields no matches. Current block height', 'arkcommerce' ) . ': ' . $arkblockheight . '.' ), false, true );
                }
                // The order has expired - the timeout since order commit exceeds set limit
                elseif( $arkorderexpiryblock != 'never' && $ark_transaction_found === false && intval( $arkblockheight ) > intval( $arkorderexpiryblock ) ) 
                {
                    // Make note of order timeout reached (the payment has not been made within the set window) and mark as cancelled
                    $ark_order_cancelled_note = ( __( 'ARK order expired at block height:', 'arkcommerce' ) . ' ' . $arkorderexpiryblock . '. ' . __( 'Current block height', 'arkcommerce' ) . ': ' . $arkblockheight . '.' );
                    $order->update_status( 'cancelled', $ark_order_cancelled_note );
                    
                    // Notify admin if enabled
                    if( $arkgatewaysettings['arkorderexpirednotify'] == 'on' ) arkcommerce_admin_notification( $order_id, 'orderexpired' );
                }
            }
            // ARK/DARK API response invalid or node unreachable
            else 
            {
                // Make note of unsuccessful check (the payment has not yet been made)
                $order->add_order_note( __( 'ARK network unresponsive or unreachable.', 'arkcommerce' ), false, true );
                $arkgatewaysettings['arkservice'] = 1;
            }
            // Record ARK/DARK Node status
            update_option( 'woocommerce_ark_gateway_settings', $arkgatewaysettings );
        }
    }
    
    
}
?>