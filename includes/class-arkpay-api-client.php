<?php 
/**
 * WooCommerce Arkpay API Client Class
 *
 * @link       https://arkpay.io
 * @since      1.0.0
 *
 * @package    Arkpay
 * @subpackage Arkpay/includes
 */


class Arkpay_API_Client {
    /**
	 * API base endpoint
	 */
    const API_ENDPOINT = 'https://api.ark.io';
    
    /**
	 * The API URL
	 * @var string
	 */
    private $_api_url;

    /**
	 * The WooCommerce Merchant Key
	 * @var string
	 */
    private $_public_token;

    /**
	 * The WooCommerce Merchant Key
	 * @var string
	 */
    private $_secret_token;

    private $_settings;
    
    /**
	 * Default contructor
	 * @param string  $public_token   The consumer key
     * @param string  $secret_token   The consumer key
	 */
	public function __construct( $network = 'mainnet' ) {
        $this->_api_url = rtrim($this->_api_url, '/' ) . self::API_ENDPOINT;
	}

    /**
	 * Set the public token
	 * @param string $token
	 */
	public function set_public_token( $token ) {
		$this->_public_token = $token;
    }

    /**
	 * Set the secret token
	 * @param string $token
	 */
	public function set_secret_token( $token ) {
		$this->_secret_token = $token;
    }
    
    /**
	 * Get the public token
	 * @return string string
	 */
	public function get_public_token() {
		return $this->_public_token;
    }

    /**
	 * Get the secret token
	 * @return string string
	 */
	public function get_wallet_address() {
		return !empty(get_option('arkpay_mainnet_wallet')) ? get_option('arkpay_mainnet_wallet') : get_option('arkpay_testnet_wallet');
    }

    /**
	 * Get current quote price based on subtotal
	 * @param  float $subtotal
	 * @param  array  $data
	 * @return mixed|json string
	 */
    public function get_quote( $subtotal ) {
        
    }

    /**
	 * Get current ark exchange rate
	 * @param  string $currency
	 * @param  string $base
	 * @return float $exchangerate
	 */
    public function get_exchange_rate( $currency = 'ark', $base = 'usd' ) {
        $response = wp_remote_get("https://api.cryptonator.com/api/ticker/${currency}-${base}");
        $exchangerate = 0;
        
        if( !is_wp_error($response) ) {
            $response = json_decode( $response['body'], true );            
            $exchangerate = $response['ticker']['price'];
        }

        return $exchangerate;
    }

    /**
	 * Get amount balance of Wallet
	 * @param  string $wallet Wallet address
	 * @return float $balance
	 */
    public function get_wallet_balance( $wallet = null ) {
        $balance = 0;
        $wallet = !empty($wallet) ? $wallet : $this->get_wallet_address();
        $response = $this->_make_api_call("/api/v2/wallets/{$wallet}", array(), "GET");

        if ( !is_wp_error($response) ) {
            $arkbaresponse = json_decode( $response['body'], true );
            
            if ( count($arkbaresponse['data']) > 0 ) {
                $balance = number_format( ( float ) $arkbaresponse['data']['balance'] / 100000000, 8, '.', '' );
            }
        }

        return $balance;
    }


    /*
    * Fetch Last Incoming Transactions	
    * 
    * @param int $limit
    * @return arr $transactions
    */
    public function get_transactions( $limit = 10, $wallet = null ) {
        $transactions = 0;
        $wallet = !empty($wallet) ? $wallet : $this->get_wallet_address();
        $response = $this->_make_api_call("/api/transactions/search?limit={$limit}", array(
            'recipientId' => $wallet
        ), "POST");

        if( !is_wp_error($response) )  {
            $arktxresponse = json_decode( $response['body'], true );

            if( count($arktxresponse['data']) > 0 ) {
                $transactions = $arktxresponse['data'];
            }
        }

        return $transactions; 
    }

    
    /*
    * Get ARK Blockchain Current Block Height
    * 
    * @param string $block_id
    * @return int $arklastblock
    */
    public function get_block_height( $block_id = null ) {
        $block_height = 0;
        $query = is_null($block_id) ? "/api/v2/blocks?limit=1" : "/api/v2/blocks/{$block_id}";
        $response = $this->_make_api_call($query, array(), "GET");

        if( !is_wp_error($response) ) {
            $arkblockheight = json_decode( $response['body'], true );

            if (is_null($block_id)) {
                if( !empty($arkblockheight['data'][0]['height']) ) {
                    $block_height = $arkblockheight['data'][0]['height'];
                } else {
                    $block_height = 0;
                }
            } else {
                $block_height = $arkblockheight['data']['height'];
            }
            
        } else {
            $block_height = 0;
        }

        return $block_height;
    }
    
    /*
	 * Make the call to the API
	 * @param  string $endpoint
	 * @param  array  $params
	 * @param  string $method
	 * @return mixed|json string
	 */
	private function _make_api_call( $endpoint, $params = array(), $method = 'GET' ) {
        $url = $this->_api_url . $endpoint;
        $args = array(
            'method'    => $method,
            'headers' 	=> array(
                'Content-Type' 	=> 'application/json'
            ),
            'body'      => $method === 'GET' ? $params : json_encode($params)
        );

        return wp_remote_request( $url, $args );
    }		
}
?>