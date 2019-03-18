<?php
/**
 * Plugin Name: WooCommerce XRP
 * Plugin URI: http://github.com/empatogen/woocommerce-xrp
 * Description: A payment gateway for WooCommerce to accept <a href="https://ripple.com/xrp">XRP</a> payments.
 * Version: 1.0.2
 * Author: Jesper Wallin
 * Author URI: https://ifconfig.se/
 * Developer: Jesper Wallin
 * Developer URI: https://ifconfig.se/
 * Text Domain: wc-gateway-xrp
 * Domain Path: /languages/
 *
 * WC requires at least: 3.5.6
 * WC tested up to: 3.5.6
 *
 * Copyright: © 2019 Jesper Wallin.
 * License: ISC license
 */

defined( 'ABSPATH' ) or die( 'Nothing to see here' );

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


/**
 * Load translations.
 */
add_action( 'plugins_loaded', 'wc_gateway_xrp_load_text_domain' );
function wc_gateway_xrp_load_text_domain() {
    load_plugin_textdomain( 'wc-gateway-xrp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}


if ( ! function_exists( 'woocommerce_xrp_payment' ) ) {
	/**
	 * Unique access to instance of WC_Payment_XRP class
	 *
	 * @return \WC_Payment_XRP
	 */
	function woocommerce_xrp_payment() {
		// Load required classes and functions
		require_once( plugin_dir_path( __FILE__ ) . 'includes/class-base.php' );

		return WC_Payment_XRP::get_instance();
	}
}


if ( ! function_exists( 'wc_gateway_xrp_constructor' ) ) {
	function wc_gateway_xrp_constructor() {
        load_plugin_textdomain( 'wc-gateway-xrp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
        woocommerce_xrp_payment();
	}
}
add_action( 'plugins_loaded', 'wc_gateway_xrp_constructor' );


/**
 * Add custom meta_query so we can search by destination_tag.
 */
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'handle_destination_tag_query', 10, 2 );
function handle_destination_tag_query( $query, $query_vars ) {
    if ( ! empty( $query_vars['destination_tag'] ) ) {
        $query['meta_query'][] = array(
            'key' => 'destination_tag',
            'value' => esc_attr( $query_vars['destination_tag'] ),
        );
    }

    return $query;
}

/**
 * Handle the AJAX callback to reload checkout details.
 */
add_action( 'wp_ajax_xrp_checkout', 'xrp_checkout_handler' );
add_action( 'wp_ajax_nopriv_xrp_checkout', 'xrp_checkout_handler' );
function xrp_checkout_handler() {
    $order = wc_get_order( $_POST['order_id'] );

    if ( $order == false ) {
        header( 'HTTP/1.0 404 Not Found' );
        wp_die();
    }

    $gateway      = new WC_Gateway_XRP;
    $tag          = get_post_meta( $_POST['order_id'], 'destination_tag', true );
    $xrp_total    = round( get_post_meta( $_POST['order_id'], 'total_amount', true ), 6 );
    $xrp_received = round( get_post_meta( $_POST['order_id'], 'delivered_amount', true ), 6 );
    $remaining    = round( (float)$xrp_total - (float)$xrp_received , 6 );
    $status       = get_post_status( $_POST['order_id'] );

    $result = array(
        'xrp_account'   => $gateway->settings['xrp_account'],
        'tag'           => $tag,
        'xrp_total'     => $xrp_total,
        'xrp_received'  => $xrp_received,
        'xrp_remaining' => $remaining,
        'status'        => $gateway->helpers->wc_pretty_status( $status ),
        'qr'            => $gateway->helpers->xrp_qr( $gateway->settings['xrp_account'], $tag, $remaining ),
        'raw_status'    => $status
    );

    echo json_encode($result);
    wp_die();
}


