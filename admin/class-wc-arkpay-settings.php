<?php 
/**
 * Class WC_Settings_Arkpay file.
 *
 * @package arkpay\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Settings_Arkpay' ) ) :
	/**
	 * Settings class
	 *
	 * @since 1.0.0
	 */
	class WC_Settings_Arkpay extends WC_Settings_Page {
		
		/**
		 * Setup settings class
		 *
		 * @since  1.0
		 */
		public function __construct() {

			$this->id    = 'arkpay';
			$this->label = __( 'Ark Payment', 'arkpay' );
			
			add_filter( 'woocommerce_settings_tabs_array',        array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id,      array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		}
		
		
		/**
		 * Get settings array
		 *
		 * @since 1.0.0
		 * @return array Array of settings
		 */
		public function get_settings() {

				/**
				 * Filter Plugin Settings
				 *
				 * @since 1.0.0
				 * @param array $settings Array of the plugin settings
				 */
				$settings = apply_filters( 'arkpay_settings', array(
					array(
						'name' => __( 'Ark Payment Gateway', 'arkpay' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'arkpay_gateway'
                    ),                    
                    array(						
                        'type' => 'checkbox',
                        'title' => 'Enable or Disable',
                        'name' => __( 'Enable Ark Payment', 'arkpay' ),
                        'desc' => __( 'Turn on Ark Payment gateway to offer it to customers on checkout.', 'arkpay' ),
                        'id'   => 'enabled',
                        'default' => 'yes',
                        
                    ),
                    array(
						'type'     => 'select',
						'id'       => 'arkpay_network_select',
						'name'     => __( 'Network', 'arkpay' ),
						'options'  => array(
                            'mainnet'		=> __( 'Mainnet', 'arkpay' ),
                            'testnet'       => __( 'Testnet', 'arkpay' ),
						),
						'class'    => 'wc-enhanced-select',
						'desc_tip' => __( 'Select which network the plugin should use', 'arkpay' ),
						'default'  => 'mainnet',
                    ),
					array(
						'name' => __( 'Ark Wallet Address', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => __( 'Enter here the wallet that should be used to receive transactions.', 'arkpay' ),
                        'id'   => 'arkpay_mainnet_wallet',
                        'class' => 'arkpay_mainnet',
					),
					array(
						'name' => __( 'Ark Node Address', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => __( 'IP address or the hostname of an ARK Mainnet node used to query the blockchain. If port is left out, the plugin uses either 443 for https or 80 for http connections. For directly accessible nodes the default port is 4001 and without https encryption.'),
                        'id'   => 'arkpay_mainnet_node',
                        'class' => 'arkpay_mainnet',
                        // 'default' => 'api.arkpay.io',
                        'default' => 'api.ark.io'
                    ),
                    array(
						'name' => __( 'Ark Wallet Address (Testnet)', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => "<p class='description'>Enter here the wallet that should be used to receive transactions.</p>",
                        'id'   => 'arkpay_testnet_wallet',
                        'class' => 'arkpay_testnet',
					),
					/* array(
						'name' => __( 'Debug Mode', 'arkpay' ),
						'type' => 'checkbox',
						'desc' => __( 'When enabled, debug logs will be added to the order notes', 'arkpay'),
						'id'   => 'arkpay_debug_mode',
                    ), */                    
                    array(
						'type' => 'sectionend',
						'id'   => 'arkpay_gateway'
                    ),					
                    array(
						'name' => __( 'Ark Settings', 'arkpay' ),
						'type' => 'title',
						'desc' => '',
						'id'   => 'arkpay_network_settings',
                    ),
                    array(
						'name' => __( 'Currency', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => "Enter here the currency that will represent your cryptocurrency",
                        'id'   => 'arkpay_settings_currency',
                        'class' => '',
                        'default' => 'ARK',
                    ),
                    array(
						'name' => __( 'Symbol', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => "Enter here the symbol that will represent your cryptocurrency",
                        'id'   => 'arkpay_cryptocurrency_symbol',
                        'class' => 'arkpay_cryptocurrency_symbol',
                        'default' => 'Ѧ',
                    ),
                    array(
						'name' => __( 'Explorer Address', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => "",
                        'id'   => 'arkpay_mainnet_explorer',
                        'default' => 'https://explorer.ark.io'
                    ),
                    array(
						'name' => __( 'Explorer Address (Testnet)', 'arkpay' ),
						'type' => 'text',
						'desc_tip' => "",
                        'id'   => 'arkpay_testnet_explorer',
                        'default' => 'https://dexplorer.ark.io/',
                    ),
                    array(
						'type' => 'hidden',
                        'id'   => 'arkpay_ark_exchange_rate',
					),
                    array(
						'type' => 'sectionend',
						'id'   => 'arkpay_gateway'
                    ),
				) );


			/**
			 * Filter Arkpay Settings
			 *
			 * @since 1.0.0
			 * @param array $settings Array of the plugin settings
			 */
			return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
			
		}
		
		
		/**
		 * Output the settings
		 *
		 * @since 1.0
		 */
		public function output() {
			
			$settings = $this->get_settings();
			WC_Admin_Settings::output_fields( $settings );
		}
		
		
		/**
	 	 * Save settings
	 	 *
	 	 * @since 1.0
		 */
		public function save() {
			
			$settings = $this->get_settings();
			WC_Admin_Settings::save_fields( $settings );
        }
        
        public function get_network_environment() {
            return get_option('arkpay_network_select') !== 'testnet' ? 'mainnet' : 'testnet';
        }

        public function is_network_mainnet() {
            return $this->get_network_environment() !== 'testnet';
        }

        public function get_wallet_address() {
            return $this->is_network_mainnet() ? get_option('arkpay_mainnet_wallet') : get_option('arkpay_testnet_wallet');
        }
	}
endif;