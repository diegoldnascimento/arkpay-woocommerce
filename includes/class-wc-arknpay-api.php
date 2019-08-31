<?php

// Prohibit direct access
if( !defined( 'ABSPATH' ) ) exit;

//////////////////////////////////////////////////////////////////////////////////////////
// Function Checking for Order Payment Fulfillment, Handling and Notification			//
// @param int $order_id																	//
// @record int arkservice																//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_ark_transaction_validation( $order_id ) 
{
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
	
	$arknodeprefix = 'https://';
	
	// DARK Mode settings
	if( $arkgatewaysettings['darkmode'] == 'yes' ) 
	{
		$arknode = ( $arknodeprefix . $arkgatewaysettings['darknode'] );
		$explorerurl = 'https://dexplorer.ark.io/tx/';
	}
	else
	{
		$arknode = ( $arknodeprefix . $arkgatewaysettings['arknode'] );
		$explorerurl = 'https://explorer.ark.io/tx/';
	}
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
					// Extract the potential transaction ID
					$arktxrecordtxid = $arktxrecord['id'];
					
					// Construct query for the specific potential transaction URI for ARK/DARK Node
					$ark_txquery = "$arknode/api/v2/transactions/$arktxrecordtxid";
					
					// Query ARK/DARK Node API for the specific transaction
					$arknodetxresponse = wp_remote_get( $ark_txquery );
                    // var_dump($arknodetxresponse);
					// API response
					if( !is_wp_error($arknodetxresponse) ) 
					{
						$arktxresponse = json_decode( $arknodetxresponse['body'], true );
		
                        // Validate response
                        
                        if ( count($arktxresponse['data']) > 0 ) {
                            $api_client = Arkpay_API_Client::getInstance();
                            $arktxblockheight = $api_client->get_block_height($arktxresponse['data']['blockId']);
       
                            var_dump($arktxresponse['data']['blockId']);
                            
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
				$ark_order_completed_note = ( __( 'ARK order filled at block:', 'arkcommerce' ) . ' ' . $ark_transaction_block . ' TX: <a href="' . $explorerurl . $ark_transaction_identifier . '">' . $ark_transaction_identifier . '</a>.' );
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


 /**
 * Update Currency Market Exchange Rate between chosen store Fiat and ARK Pairs 
 *
 * @return float $arkexchangerate
 */

function arkpay_update_exchange_rate() 
{

    $arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$store_currency = get_woocommerce_currency();

    $arkexchangerate = Arkpay_API_Client::getInstance()->get_exchange_rate( 'ARK',  $store_currency );
    
	$arkgatewaysettings['arkexchangerate'] = $arkexchangerate;
	update_option( 'woocommerce_ark_gateway_settings', $arkgatewaysettings );
}
add_action ( 'arkcommerce_refresh_exchange_rate', 'arkpay_update_exchange_rate' );