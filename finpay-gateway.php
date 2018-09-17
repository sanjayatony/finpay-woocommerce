<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Tony S

this plugin written based on https://docs.woocommerce.com/document/payment-gateway-api/

*/


add_action( 'plugins_loaded', 'finpay_gateway_init');
function finpay_gateway_init() {
  include 'includes/class.finpay-gateway-briva.php';
}

/**
 * add finpay to payment gateway
 */
function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay_Briva';
  return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );

/**
* Add content to the WC emails.
*
* @access public
* @param WC_Order $order
* @param bool $sent_to_admin
* @param bool $plain_text
*/
// function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
//   if ( $this->instructions && ! $sent_to_admin && 'finpay' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
//       echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
//   }
// }

