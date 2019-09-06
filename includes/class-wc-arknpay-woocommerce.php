<?php
/*
ArknPay
Copyright (C) 2017-2018 Milan Semen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
//////////////////////////////////////////////////////////////////////////////////////////
// START OF ARKCOMMERCE WOO INTEGRATION FUNCTIONS										//
//////////////////////////////////////////////////////////////////////////////////////////
// Prohibit direct access
if( !defined( 'ABSPATH' ) ) exit;


//////////////////////////////////////////////////////////////////////////////////////////
// Convert Potentially Complex Price Sring(s) to Float(s)								//
// @param str $price_input																//
// @return float $price/float arr $amounts												//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_price_number_conversion( $price_input )
{
	// Gather and/or set variables
	$cs = get_woocommerce_currency_symbol();
	
	// Determine whether input is one or more strings
	if( substr_count( $price_input, $cs ) > 1 )
	{
		// More than one price contained in input string
		$pricearray = explode( " ", $price_input );
		foreach( $pricearray as $price )
		{
			// Clear witespaces and currency symbol
			$price = trim( $price );
			$price = str_replace( ' ', '', $price );
			$price = str_replace( $cs, '', $price );
    
			// Check case where string has "," and "."
			$dot = strpos( $price, '.' );
			$semi = strpos( $price, ',' );
			if( $dot !== false && $semi !== false )
			{
				// Change fraction sign to #, we change it again later
				$price = str_replace( '#', '', $price ); 
				if( $dot < $semi ) $price = str_replace( ',', '#', $price );
				else $price = str_replace( '.', '#', $price );
		
				// Remove another ",", "." and change "#" to "."
				$price = str_replace( [',', '.', '#'], ['','', '.'], $price );
			}
			// Clear usless elements
			$price = str_replace( ',', '.', $price ); 
			$price = preg_replace( "/[^0-9\.]/", "", $price );
	
			// Convert to float
			$price = floatval( $price );
		
			// Add to result array if not 0 (the process produces several 0 values) 
			if( $price != 0 ) $amounts[] = $price;
		}
		return $amounts;
	}
	// One price contained in input string
	else
	{
		// Clear witespaces and currency symbol
		$price = trim( $price_input );
		$price = str_replace( ' ', '', $price );
		$price = str_replace( $cs, '', $price );
    
		// Check case where string has "," and "."
		$dot = strpos( $price, '.' );
		$semi = strpos( $price, ',' );
		if( $dot !== false && $semi !== false )
		{
			// Change fraction sign to #, we change it again later
			$price = str_replace( '#', '', $price ); 
			if( $dot < $semi ) $price = str_replace( ',', '#', $price );
			else $price = str_replace( '.', '#', $price );
		
			// Remove another ",", "." and change "#" to "."
			$price = str_replace( [',', '.', '#'], ['','', '.'], $price );
		}
		// Clear usless elements
		$price = str_replace( ',', '.', $price ); 
		$price = preg_replace( "/[^0-9\.]/", "", $price );
	
		// Convert to float and return the result
		$price = floatval( $price );
		
		// Return result
		return $price;
	}
}
//////////////////////////////////////////////////////////////////////////////////////////
// Currency Check of the Store															//
// @return bool $currency_supported														//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_check_currency_support() 
{
	// Gather and/or set variables
	$store_currency = get_woocommerce_currency();
	
	// List supported currencies (coinmarketcap.com listings as of 6/2018)
	$supported_currencies = array( "ARK", "BTC", "USD", "AUD", "BRL", "CAD", "CHF", "CLP", "CNY", "CZK", "DKK", "EUR", "GBP", "HKD", "HUF", "IDR", "ILS", "INR", "JPY", "KRW", "MXN", "MYR", "NOK", "NZD", "PHP", "PKR", "PLN", "RUB", "SEK", "SGD", "THB", "TRY", "TWD", "ZAR" );
	
	// Currency support check
	if( in_array( $store_currency, $supported_currencies ) ) $currency_supported = true;
	else $currency_supported = false;
	
	// Return result
	return $currency_supported;
}

//////////////////////////////////////////////////////////////////////////////////////////
// 		Content for Order Data for Received/'Thank You' Page And Order Email			//
// 		@param $order_id																//
// 		@param $arkprice																//
// 		@return str $arkcommerceinformation												//
//////////////////////////////////////////////////////////////////////////////////////////
function arkcommerce_order_data_content( $order_id, $arkprice ) 
{
	// Gather and/or set variables
	$arkgatewaysettings = get_option( 'woocommerce_ark_gateway_settings' );
	$store_currency = get_woocommerce_currency();
	$timeout = arkcommerce_get_order_timeout();
	$storewalletaddress = Arkpay_API_Client::getInstance()->get_wallet_address();

    // Include the QR Code of store ARK wallet address and form a table containing the store ARK address, order number, and amount total
    $qrcode = sprintf( '<hr><table><tr><td>%s</td></tr></table><hr>', wptexturize( $arkgatewaysettings['instructions'] ) );
	// $qrcode = sprintf( '<hr><table><tr><th><img alt="QRCODE" width="130" height="130" src="%s"></th><td>%s</td></tr></table><hr>', ( plugin_dir_url( __FILE__ ) . '/../../assets/images/qrcode.png' ), wptexturize( $arkgatewaysettings['instructions'] ) );
    $payButton = sprintf('<a href="ark:%s?amount=%s&amp;vendorField=%s" class="btn btn-primary pay-button" title="Click here to pay your order with Ark Wallet">Pay Now <br> with Ark Wallet Ѧ</a>', $storewalletaddress, $arkprice, $order_id);	
    $arktable = sprintf( '<table><tr><th><b>' . __( 'ARK Wallet Address', 'arkcommerce' ) . '</b></th><td>%s</td></tr><tr><th><b>SmartBridge</b></th><td>%s</td></tr><tr><th><b>' . __( 'ARK Total', 'arkcommerce' ) . '</b></th><td>Ѧ%s</td></tr><tr><th><b>' . __( 'Order Expiry', 'arkcommerce' ) . '</b></th><td>%s</td></tr></table><hr>', $storewalletaddress, $order_id, $arkprice, $timeout );
    
    
?>
    
<?php 
	// Compese and return the QR Code, admin-defined instructions in the complete ArknPay data table
    // $arkcommerceinformation = ( $qrcode . wptexturize ( $arktable ) . wptexturize($payButton) );
    $arkcommerceinformation = $qrcode. '<div class="arkpay-order-payment">'. wptexturize($payButton) .'<div class="">'. wptexturize ( $arktable ) .'</div></div><hr>';
	return $arkcommerceinformation;
}
//////////////////////////////////////////////////////////////////////////////////////////
// END OF ARKCOMMERCE WOO INTEGRATION FUNCTIONS											//
//////////////////////////////////////////////////////////////////////////////////////////