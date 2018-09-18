<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Tony S

this plugin written based on https://docs.woocommerce.com/document/payment-gateway-api/

*/


add_action( 'plugins_loaded', 'finpay_gateway_init');
function finpay_gateway_init() {
  // #1 Payment Page
  include 'class/class.finpay-gateway.php';


  // #3 Pending Payment
  include 'class/class.finpay-gateway-briva.php';
  include 'class/class.finpay-gateway-finpay021.php';
  include 'class/class.finpay-gateway-finpayst021.php';
  include 'class/class.finpay-gateway-vabni.php';  
  include 'class/class.finpay-gateway-vastbni.php';
  include 'class/class.finpay-gateway-vapermata.php';
  include 'class/class.finpay-gateway-vastpermata.php';
  include 'class/class.finpay-gateway-vamandiri.php';
  include 'class/class.finpay-gateway-vastmandiri.php';
}

/**
 * add finpay to payment gateway
 * I. cc, permatanet, tcash
 * II. mandiriclickpay
 * III. briva, finpay021, finpaysyst021, vabni, vapermata, vastbni, vastpermata,vamandiri, vastmandiri
 */
function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay_Briva';
  $methods[] = 'WC_Gateway_Finpay_Finpay021';
  $methods[] = 'WC_Gateway_Finpay_Finpayst021';
  $methods[] = 'WC_Gateway_Finpay_Vabni';
  $methods[] = 'WC_Gateway_Finpay_Vastbni';
  $methods[] = 'WC_Gateway_Finpay_Vapermata';  
  $methods[] = 'WC_Gateway_Finpay_Vastpermata';
  $methods[] = 'WC_Gateway_Finpay_Vamandiri';
  $methods[] = 'WC_Gateway_Finpay_Vastmandiri';


  return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );
