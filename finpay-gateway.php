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
global $payment_code;
$pyament_code = "";
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

      $this->enabled = $this->get_option('enabled');
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->environment = $this->get_option('environment');
      if($this->environment == 'sandbox'){
        $this->merchant_id = $this->get_option('merchant_id_sandbox');
        $this->merchant_key	= $this->get_option('merchant_key_sandbox');
        $this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
      }else{
        $this->merchant_id = $this->get_option('merchant_id_production');
        $this->merchant_key	= $this->get_option('merchant_key_production');
        $this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
      }

      $this->payment_code = '';

      $this->enabled_cc		= $this->get_option('cc');
      $this->enabled_finpay021		= $this->get_option('finpay021');

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'finpay_admin_scripts' ));
      add_action( 'woocommerce_thankyou_finpay', 'thankyou_page', 10);


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
      global $woocommerce, $payment_code;

      $order = new WC_Order($order_id);

      $response = $this->generate_request($order_id);

      $logger = wc_get_logger();
      $logger->log( 'response generate', wc_print_r($response, true));

      if($response->status_code == '00'){
        //update  order status to on-hold
        $order->update_status('on-hold', __('Awaiting payment via Finpay', 'woocommerce'));

        //update order note with payment code
        $order->add_order_note( __('Your payment code is <b>'.$response->payment_code.'</b>', 'woocommerce') );

        $payment_code = $response->payment_code;

      }else{
        wc_add_notice( __('Finpay Payment error:', 'woocommerce') . $response->status_code, 'error' );
        return;
      }

      return array(
        'result'  => 'success',
        'redirect' => $this->get_return_url( $order )
      );
    }
    /**
     * Prosessing the request
     * return : array
     */
    function generate_request($order_id){
      global $woocommerce;
      $order = new WC_Order( $order_id );

      $add_info1    = $order->billing_last_name;
      $amount       = round($order->get_total(), 0);
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
      $finpay_args = array (
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
      $logger->log( 'DATA send', wc_print_r($finpay_args, true) );

      //$response = $this->api_request($data);
      $response = wp_remote_retrieve_body(wp_remote_post( $this->api_endpoint, array('body' => $finpay_args )));
      $logger->log( 'Response', $response );
      return json_decode($response);
    }

    public function get_payment_code($payment_code){
      return $payment_code;
    }

  }

}

function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay';
  return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_finpay_payment_gateway' );


/**
 * Output for the order received page.
 */
add_action( 'woocommerce_thankyou_finpay', 'finpay_thankyou_page', 10);

function finpay_thankyou_page() {
  global $payment_code;
  echo '<p>Lorem ipsum dolor si amett '.$payment_code.'</p>';
}



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

