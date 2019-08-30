<?php
/*
ArknPay
Copyright (C) 2017-2018 Milan Semen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
//////////////////////////////////////////////////////////////////////////////////////////
// START OF ARKCOMMERCE UTILITIES														//
//////////////////////////////////////////////////////////////////////////////////////////
// Prohibit direct access
if( !defined( 'ABSPATH' ) ) exit;

//////////////////////////////////////////////////////////////////////////////////////////
// Generate Store ARK Wallet Address QR Code											//
// @record blob qrcode																	//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_generate_qr_code() 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$filepath = ( plugin_dir_path( __FILE__ ) . '../../assets/images/qrcode.png' );
	$backcolor = 0xFFFFFF;
	$forecolor = 0x4AB6FF;
	
	// DARK Mode settings
	if( $arkgatewaysettings['darkmode'] == 'yes' ) $storewalletaddress = $arkgatewaysettings['darkaddress'];
	else $storewalletaddress = $arkgatewaysettings['arkaddress'];
	
	// Adhere to the proper ARK QR code format
	$storewalletaddress = ( '{"a":"' . $storewalletaddress . '"}' );
	
	// Execute the external PHP QR Code Generator
	if( $storewalletaddress != null ) QRcode::png( $storewalletaddress, $filepath, "L", 8, 1, $backcolor, $forecolor);
}
//////////////////////////////////////////////////////////////////////////////////////////
// Determine the Order Expiry Timeout and Return the Value to Be Displayed to Customer	//
// @return str $timeout																	//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_get_order_timeout() 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	// Determine order expiry timeout
    if( $arkgatewaysettings['arktimeout'] == 30 ) $timeout = ( __( '30 blocks (cca 3 min)', 'arkcommerce' ) );
    elseif( $arkgatewaysettings['arktimeout'] == 55 ) $timeout = ( __( '55 blocks (cca 7.5 min)', 'arkcommerce' ) );
    elseif( $arkgatewaysettings['arktimeout'] == 110 ) $timeout = ( __( '110 blocks (cca 15 min)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 225 ) $timeout = ( __( '225 blocks (cca 30 min)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 450 ) $timeout = ( __( '450 blocks (cca 60 min)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 900 ) $timeout = ( __( '900 blocks (cca 2 hours)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 1800 ) $timeout = ( __( '1800 blocks (cca 4 hours)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 3600 ) $timeout = ( __( '3600 blocks (cca 8 hours)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 5400 ) $timeout = ( __( '5400 blocks (cca 12 hours)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 10800 ) $timeout = ( __( '10800 blocks (cca 24 hours)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 75600 ) $timeout = ( __( '75600 blocks (cca 7 days)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 151200 ) $timeout = ( __( '151200 blocks (cca 2 weeks)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 324000 ) $timeout = ( __( '324000 blocks (cca 1 month)', 'arkcommerce' ) );
	elseif( $arkgatewaysettings['arktimeout'] == 'never' ) $timeout = ( __( 'None (order never expires)', 'arkcommerce' ) );
	
	// Return the result
	return $timeout;
}
//////////////////////////////////////////////////////////////////////////////////////////
// Periodic Worker Triggering Transaction Validation Jobs on ArknPay Open Orders	//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_validation_worker() 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	global $wpdb;
	
	// Construct a query for all WC orders made using ArknPay payment gateway
	$arkordersquery = ( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_value='ark_gateway';" );
	
	// Execute the query
	$arkorders = $wpdb->get_results( $arkordersquery );
	//var_dump($arkorders);
	// Determine valid database connection
	if( !empty( $arkorders ) ) 
	{
		// Iterate through open orders and commence tx check processing for each open order
		foreach( $arkorders as $arkorder ):setup_postdata( $arkorder );
			$order = wc_get_order( $arkorder->post_id );
			if( $order->has_status( 'on-hold' ) ) arkcommerce_ark_transaction_validation( $arkorder->post_id );
		endforeach;	
	}
}
add_action( 'admin_init', 'arkcommerce_validation_worker' );
add_action ( 'arkcommerce_check_for_open_orders', 'arkcommerce_validation_worker' );


//////////////////////////////////////////////////////////////////////////////////////////
// Get Store Fiat-ARK Exchange Rate														//
// @return float $arkexchangerate														//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_get_exchange_rate() 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$store_currency = get_woocommerce_currency();
	
	// ARK is the chosen default store currency
	if( $store_currency == 'ARK' ) $arkexchangerate = 1;
	
	// Establish and set the correct exchange rate (autorate/multirate/fixedrate)
	else
	{
		if( $arkgatewaysettings['arkexchangetype'] == 'autorate' ) $arkexchangerate = $arkgatewaysettings['arkexchangerate'];
		elseif( $arkgatewaysettings['arkexchangetype'] == 'multirate' ) $arkexchangerate = ( $arkgatewaysettings['arkexchangerate'] / $arkgatewaysettings['arkmultiplier'] );
		elseif( $arkgatewaysettings['arkexchangetype'] == 'fixedrate' ) $arkexchangerate = $arkgatewaysettings['arkmanual'];
	}
	// Return exchange rate
	return $arkexchangerate;
}

//////////////////////////////////////////////////////////////////////////////////////////
// Internal Currency Conversion Between Fiat and ARK Pairs								//
// @param int/float fiat $amount														//
// @return float $arkamount																//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_conversion_into_ark( $amount ) 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$store_currency = get_woocommerce_currency();
	$arkexchangerate = arkcommerce_get_exchange_rate();
	
	// Check for supported currency
	$currency_supported = arkcommerce_check_currency_support();
	
	// Supported fiat currency input
	if( $store_currency != 'ARK' && $arkexchangerate != 0 && $currency_supported === true ) $arkamount = number_format( ( float )( $amount / $arkexchangerate ), 8, '.', '' );
	
	// ARK input equals ARK output
	elseif( $store_currency == 'ARK' ) $arkamount = $amount;
	
	// Currency not supported and fixed rate not chosen
	elseif( $currency_supported === false && $arkgatewaysettings['arkexchangetype'] != 'fixedrate' ) $arkamount = 0;
	
	// Unsupported fiat currency output using fixed exchange rate
	elseif( $currency_supported === false && $arkexchangerate != 0 && $arkgatewaysettings['arkexchangetype'] == 'fixedrate' ) $arkamount = number_format( ( float )( $amount / $arkexchangerate ), 8, '.', '' );
	
	// Return converted amount
	return $arkamount;
}
//////////////////////////////////////////////////////////////////////////////////////////
// Open ArknPay Orders Queue Count Checker											//
// @return int $arkopenordercount														//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_open_order_queue_count() 
{
	// Gather and/or set variables
	global $wpdb;
	$arkopenordercount = 0;
	
	// Construct a query for all WC orders made using ArknPay payment gateway
	$arkordersquery = ( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_value='ark_gateway';" );
	
	// Execute the query
	$arkorders = $wpdb->get_results( $arkordersquery );
	
	// Determine valid database connection
	if( !empty( $arkorders ) ) 
	{
		// Iterate through open orders and count total of open orders
		foreach( $arkorders as $arkorder ):setup_postdata( $arkorder );
			$order = wc_get_order( $arkorder->post_id );
			if( $order->has_status( 'on-hold' ) ) $arkopenordercount = ( $arkopenordercount + 1 );
		endforeach;
	}
	// Return result
	return $arkopenordercount;
}

/*
* Return Bridgechain name. (e.g. Ark)
*
* @return string $gatewaysettings['bridgechain"']. Ark
**/

function arknpay_get_bridgechain_name() {
    $gatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
    return isset($gatewaysettings['bridgechain']) ? $gatewaysettings['bridgechain'] : 'Ark';
}

/*
* Return Bridgechain Testnet name. (e.g. Dark) 
*
* @return string $gatewaysettings['bridgechaintestnet"']. Dark
**/

function arknpay_get_bridgechain_testnet_name() {
    $gatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
    return isset($gatewaysettings['bridgechaintestnet']) ? $gatewaysettings['bridgechaintestnet'] : 'Dark';
}

function arknpay_get_unique_price($order_id) {
    $order = wc_get_order( $order_id );
}

//////////////////////////////////////////////////////////////////////////////////////////
// END OF ARKCOMMERCE UTILITIES															//
//////////////////////////////////////////////////////////////////////////////////////////