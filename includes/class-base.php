<?php
/**
 * Main class
 *
 */

if( ! class_exists( 'WC_Payment_XRP' ) ){
	/**
	 * WooCommerce XRPL Payment main class
	 *
	 * @since 1.0.0
	 */
	class WC_Payment_XRP {
        /**
         * Single instance of the class
         *
         * @var \WC_Payment_XRP
         * @since 1.0.0
         */
        protected static $instance;

        /**
         * XRP Payment gateway id
         *
         * @var string Id of specific gateway
         * @since 1.0
         */
        public static $gateway_id = 'WC_Gateway_XRP';

        /**
         * The gateway object
         *
         * @var Woo_Xrp_Payment_Gateway
         * @since 1.0
         */
        protected $gateway = null;

        /**
         * Returns single instance of the class
         *
         * @return \Woo_Xrp_Payment
         * @since 1.0.0
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self;
            }

            return self::$instance;
        }


        /**
         * Constructor.
         *
         * @return \WC_Payment_XRP
         * @since 1.0.0
         */
        public function __construct() {
            // add filter to append wallet as payment gateway
            include_once 'class-webhooks.php';
            include_once 'class-rates.php';
            include_once 'class-helpers.php';
            add_filter( 'woocommerce_payment_gateways', array( $this, 'add_to_gateways' ) );
        }

        /**
         * Adds XRP Payment Gateway to payment gateways available for woocommerce checkout
         *
         * @param $methods array Previously available gataways, to filter with the function
         *
         * @return array New list of available gateways
         * @since 1.0.0
         */
        public function add_to_gateways( $methods ) {
            self::$gateway_id = apply_filters( 'wc_xrp_gateway_id', self::$gateway_id );

            include_once( 'class-gateway.php' );
            $methods[] = 'WC_Gateway_XRP';
            return $methods;
        }
    }
}
