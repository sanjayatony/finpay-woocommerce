<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Finnet

this plugin written on https://docs.woocommerce.com/document/payment-gateway-api/

*/


add_action( 'plugins_loaded', 'finpay_gateway_init');

function finpay_gateway_init() {
  if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
    return;
  }
  DEFINE ('FP_PLUGIN_DIR', plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) . '/' );
  require_once dirname( __FILE__ ) . '/class/class.finpay-gateway.php';
  
}

function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay';
  return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );
