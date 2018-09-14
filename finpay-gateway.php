<?php
/*
Plugin Name: Finpay - WooCommerce Payment Gateway Plugin
Version: 1.0.0
Author: Tony S

this plugin written based on https://docs.woocommerce.com/document/payment-gateway-api/

*/


add_action( 'plugins_loaded', 'finpay_gateway_init');
function finpay_gateway_init() {
  class WC_Gateway_Finpay extends WC_Payment_Gateway {

    /**
    * constructor
    */
    public function __construct () {
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
      $this->instructions = $this->get_option('instructions');
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

      $this->enabled_cc		= $this->get_option('cc');
      $this->enabled_finpay021		= $this->get_option('finpay021');

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
      add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'finpay_admin_scripts' ));
      add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) ); //custom thankyou page
      add_action( 'woocommerce_api_finpay', array( $this, 'webhook' ) ); //webhook after payment success

    }
    

    /**
     * Process the payment and return the result
     */
    public function process_payment( $order_id ) {
      global $woocommerce, $wpdb;
      $order = new WC_Order($order_id);

      $response = $this->generate_request($order_id);

      $logger = wc_get_logger();
      $logger->log( 'response generate', wc_print_r($response, true));

      if($response->status_code == '00'){
        //update  order status to on-hold
        $order->update_status('on-hold', __('Awaiting payment via Finpay', 'woocommerce'));

        //update order note with payment code
        $order->add_order_note( __('Your Finpay payment code is <b>'.$response->payment_code.'</b>', 'woocommerce') );

        //use post excerpt to save payment code. GENIUS!!
        $rs = $wpdb->update($wpdb->prefix. 'posts', array ('post_excerpt' => $response->payment_code), array ('ID' => $order_id) );

        return array(
          'result'  => 'success',
          'redirect' => $this->get_return_url( $order )
        );

      }else{
        wc_add_notice( __('Finpay Payment error:', 'woocommerce') . $response->status_code, 'error' );
        return;
      }
    }

    /**
     * Prosessing the request
     */
    function generate_request ($order_id) {
      include 'includes/finpay-request.php';
    }

    /**
     * Show the code in thankyou page
     */

    public function thankyou_page ($order_id) {
      global $wpdb;
      $payment_code = $wpdb->get_row( 'SELECT * FROM '. $wpdb->prefix. 'posts WHERE ID = '.$order_id );
      echo wpautop( wptexturize( $this->instructions ) );
      echo '<p><strong>'.$payment_code->post_excerpt.'</strong></p>';
    }

    /**
     * webhook, to update order status if payment success
     */
    public function webhook() { 
      $order = wc_get_order( $_GET['id'] );
      $order->payment_complete(); // set order status to paid
      $order->reduce_order_stock(); // reduce stock
    }

    /**
     * Add JS to admin page
     */
    public function finpay_admin_scripts () {
      wp_enqueue_script( 'admin-finpay',  plugin_dir_url( __FILE__ ). 'js/admin.js', array('jquery') );
    }

    /**
     * Settings in Admin page
     */
    public function init_form_fields () {
      $this->form_fields = include 'includes/settings-finpay.php';
    }



  }





}

/**
 * add finpay to payment gateway
 */
function add_finpay_payment_gateway( $methods ) {
  $methods[] = 'WC_Gateway_Finpay';
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

