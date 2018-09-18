<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Tony S

this plugin written based on https://docs.woocommerce.com/document/payment-gateway-api/

*/


add_action( 'plugins_loaded', 'finpay_gateway_init');
function finpay_gateway_init() {


  // #3 Pending Payment
  include 'class/class.finpay-gateway-briva.php';
  include 'class/class.finpay-gateway-finpay021.php';
  include 'class/class.finpay-gateway-finpayst021.php';
}

/**
 * add finpay to payment gateway
 * I. cc, permatanet, tcash
 * II. mandiriclickpay
 * III. briva, finpay021, finpaysyst021, finpaytsel, vabni, vapermata, vastbni, vastpermata,vamandiri, vastmandiri
 */
function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay_Briva';
  $methods[] = 'WC_Gateway_Finpay_Finpay021';
  $methods[] = 'WC_Gateway_Finpay_Finpayst021';

  return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );
