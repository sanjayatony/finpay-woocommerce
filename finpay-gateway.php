<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Finnet

this plugin written on https://docs.woocommerce.com/document/payment-gateway-api/

*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', 'finpay_gateway_init');

function finpay_gateway_init() {  
  class WC_Gateway_Finpay extends WC_Payment_Gateway {
    /**
    * constructor
    */
    function __construct() {
      $this->id									= 'finpay';
      $this->has_fields					= false;
      $this->method_title				= __('Finpay', 'woocommerce');
      $this->method_description = __('Allows payments using Finpay.', 'woocommerce');
  
      //load the settings
      $this->init_form_fields();
      $this->init_settings();
  
      $this->enabled 			= $this->get_option('enabled');
      $this->title 				= $this->get_option('title');
      $this->description 	= $this->get_option('description');
      $this->environment	= $this->get_option('environment');
      if($this->environment == 'sandbox'){
        $this->merchant_id			= $this->get_option('merchant_id_sandbox');
        $this->merchant_key	= $this->get_option('merchant_key_sandbox');
        $this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
      }else{
        $this->merchant_id			= $this->get_option('merchant_id_production');
        $this->merchant_key	= $this->get_option('merchant_key_production');
        $this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
      }
      
  
      $this->enabled_cc		= $this->get_option('cc');
      $this->enabled_finpay021		= $this->get_option('finpay021');
  
      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'finpay_admin_scripts' ));
    
    }
  
    /**
     * Add JS
     */
    function finpay_admin_scripts() {
      wp_enqueue_script( 'admin-finpay',  plugin_dir_url( __FILE__ ). 'js/admin.js', array('jquery') );
    }
  
    /**
     * Setting admin page
     */
    function init_form_fields() {
      $this->form_fields = include 'includes/settings-finpay.php';
    }

  
    /**
     * Process the payment and return the result
     */
    function process_payment( $order_id ) {
      global $woocommerce;
      $order = new WC_Order($order_id);

      $result = $this->generate_request($order);
  
      return array(
        'result'  => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }

    function generate_request($order_id){
      global $woocommerce;
      $order = new WC_Order( $order_id );

      $add_info1    = $order->billing_postal_code;
      $amount       = $order->get_total();
      $cust_email   = $order->billing_email;
      $cust_id      = $order->get_user_id();
      $cust_msisdn  = $order->billing_phone;
      $cust_name    = $order->billing_first_name;
      $invoice      = $order->get_id();
      $merchant_id  = $this->merchant_id;
      $return_url   = $this->get_return_url($order);
      $sof_id       = 'finpay021';
      $sof_type     = 'pay';
      $trans_date   = strtotime($order->order_date);

      //mer_signature
      $mer_signature = $add_info1.'%'.$amount.'%'.$cust_email.'%'.$cust_id.'%'.$cust_msisdn.'%'.$cust_name.'%'.$invoice.'%'.$merchant_id.'%'.$return_url.'%'.$sof_id.'%'.$sof_type.'%'.$trans_date;
      $mer_signature = strtoupper($mer_signature).'%'.$this->merchant_key;
      $mer_signature = hash('sha256', $mer_signature);
      //data
      $data = array (
        'add_info1'	    => $add_info1,
        'amount'		    => $amount,
        'cust_email'    => $cust_email,
        'cust_id'       => $cust_id,
        'cust_msisdn'   => $cust_msisdn,
        'cust_name'     => $cust_name,
        'invoice'       => $invoice,
        'mer_signature' => $mer_signature,
        'merchant_id'   => $merchant_id,
        'return_url'    => $return_url,
        'sof_id'        => $sof_id,
        'sof_type'      => $sof_type,
        'trans_date'    => $trans_date
      );
      $logger = wc_get_logger();
      $logger->log( 'DATA send', wc_print_r($data, true) );
      $response = $this->api_request($data);
      return $response;

    }

    /**
     * API REQUEST
     */
    function api_request($data){
      $ch = curl_init($this->api_endpoint);

      $post_string = http_build_query($data, '', '&');

      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      # Get the response
      $response = curl_exec($ch);
      $logger = wc_get_logger();
      $logger->log( 'API response', $response);

      curl_close($ch);
      return json_decode($response, true);
    }
  
  }
  
}

function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay';
  return $methods;
}

add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );
