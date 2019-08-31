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
    const API_ENDPOINT = '';

    /**
     * 
    */
    private $_network = 'mainnet';
    
    /**
	 * The API URL
	 * @var string
	 */
    private $_api_url;

    /**
	 * The Peers lists for Mainnet
	 * @var string
	 */
    private $_peers_mainnet_url = 'https://raw.githubusercontent.com/ArkEcosystem/peers/master/mainnet.json';

    /**
	 * The Peers lists for Devnet
	 * @var string
	 */
    private $_peers_devnet_url = 'https://raw.githubusercontent.com/ArkEcosystem/peers/master/devnet.json';

    /**
	 * The Peers lists for mainnet and devnet
	 * @var string
	 */
    private $_peers = array();

    /**
     * The Singleton's instance is stored in a static field. This field is an
     * array, because we'll allow our Singleton to have subclasses. Each item in
     * this array will be an instance of a specific Singleton's subclass. You'll
     * see how this works in a moment.
     */
    private static $instances = [];
    
    /**
	 * Default contructor
	 * @param string  $public_token   The consumer key
     * @param string  $secret_token   The consumer key
	 */
	public function __construct() {
        $this->_network = $this->get_network_environment();
        $this->set_peers();
        $this->_api_url = $this->build_peer_url( $this->get_peer() );
        $this->_api_url = rtrim($this->_api_url, '/' );
        
    }

    /**
     * Singletons should not be cloneable.
     */
    protected function __clone() { }

    /**
     * This is the static method that controls the access to the singleton
     * instance. On the first run, it creates a singleton object and places it
     * into the static field. On subsequent runs, it returns the client existing
     * object stored in the static field.
     *
     * This implementation lets you subclass the Singleton class while keeping
     * just one instance of each subclass around.
     */
    public static function getInstance() {
        $cls = static::class;
        if (!isset(static::$instances[$cls])) {
            static::$instances[$cls] = new static;
        }

        return static::$instances[$cls];
    }

    /**
	 * Set peers lists
	 * @param string $token
	 */
	public function set_peers() {
		$this->_peers[$this->_network] = $this->get_peers();
    }

    /**
	 * Ger peers list based on network
	 * @param array $peers List of peers for Ark Network
	 */
    public function get_peers() {
        $peers = isset($this->_peers[$this->_network]) ? $this->_peers[$this->_network] : array();

        if (empty($peers)) {
            $endpoint = $this->_network === 'mainnet' ? $this->_peers_mainnet_url : $this->_peers_devnet_url;

            $response = wp_remote_request( $endpoint, array(
                'method' => 'GET'
            ) );

            if ( !is_wp_error($response) ) {
                $peers = array_map(function ($peer) {
                    $peer->protocol = property_exists( $peer, 'protocol' ) ? $peer->protocol : 'http';
                    /* Override port variable to be 4003 instead 4001 */
                    $peer->port = '4003';
                    return $peer;
                }, json_decode($response['body']));
            }

            $this->_peers[ $this->_network ] = $peers;
        }

        return $peers;
    }

    /**
	 * Ger peer based on network
	 * @param string $peer Peer for connect to Ark Network
	 */
    public function get_peer() {
        $length = count($this->_peers[ $this->_network ]);
        return $this->_peers[ $this->_network ][rand(0, $length - 1)];
    }

    /**
	 * Build peer url for be accepted into requests for API
	 * @param  string $peer
	 * @return string $url
	 */

    public function build_peer_url( $peer ) {
        return "{$peer->protocol}://{$peer->ip}:{$peer->port}";
    }

    /**
	 * Get network environment
	 * @return string string
	 */

    public function get_network_environment() {
        return get_option('arkpay_network_select') !== 'devnet' ? 'mainnet' : 'devnet';
    }

    /**
	 * Verify if network environment is Mainnet
	 * @return string string
	 */
    public function is_network_mainnet() {
        return $this->get_network_environment() !== 'devnet';
    }

    /**
	 * Verify if network environment is Devnet
	 * @return string string
	 */
    public function is_network_devnet() {
        return !$this->is_network_mainnet();
    }

    /**
	 * Verify if payment plugin is enabled
	 * @return string string
	 */
    public function is_gateway_enabled() {
        return get_option('enabled') !== 'no' ? true : false;
    }

    /**
	 * Get the secret token
	 * @return string string
	 */
	public function get_wallet_address() {
		return $this->is_network_mainnet() ? get_option('arkpay_mainnet_wallet') : get_option('arkpay_devnet_wallet');
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

        if (empty($wallet)) {
            return false;
        }

        $response = $this->_make_api_call("/api/v2/wallets/{$wallet}", array(), "GET");

        if ( !is_wp_error($response) ) {
            $arkbaresponse = json_decode( $response['body'], true );
            if ($arkbaresponse['data']) {
                if ( count($arkbaresponse['data']) > 0 ) {
                    $balance = number_format( ( float ) $arkbaresponse['data']['balance'] / 100000000, 8, '.', '' );
                }
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

        if (empty($wallet)) {
            return false;
        }

        $response = $this->_make_api_call("/api/transactions/search?limit={$limit}", array(
            'recipientId' => $wallet
        ), "POST");

        if( !is_wp_error($response) )  {
            $arktxresponse = json_decode( $response['body'], true );

            if ($arktxresponse['data']) {
                if( count($arktxresponse['data']) > 0 ) {
                    $transactions = $arktxresponse['data'];
                }
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

        $response = wp_remote_request( $url, $args );

        if ($response['response']['code'] !== 200) {
            $this->_api_url = $this->build_peer_url( $this->get_peer() );
            return $this->_make_api_call( $endpoint, $params, $method );
        }

        return $response;
    }		
}
?>