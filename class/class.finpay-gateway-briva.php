<?php
/**
* briva / Felisa BRI Closed
*/

class WC_Gateway_Finpay_Briva extends WC_Payment_Gateway {

  /**
  * constructor
  */
  private $sof_id  	= 'briva';
  private $sof_desc = 'BRI Virtual Account - Close Payment';

  public function __construct () {
    $this->id									= $this->sof_id;
    $this->has_fields					= false;
    $this->method_title				= __($this->sof_desc, 'woocommerce');
    $this->method_description = __('Allows payments using '.$this->sof_desc, 'woocommerce');
    $this->icon = plugins_url( 'img/bri.png', dirname(__FILE__) ) ;

    //load the settings
    $this->init_form_fields();
    $this->init_settings();

    $this->enabled = $this->get_option('enabled');
    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->instructions = $this->get_option('instructions');
    $this->timeout = $this->get_option('timeout');
    $this->environment = $this->get_option('environment');
    if($this->environment == 'sandbox'){
      $this->merchant_id = $this->get_option('merchant_id_sandbox');
      $this->merchant_key	= $this->get_option('merchant_key_sandbox');
      $this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
    }else{
      $this->merchant_id = $this->get_option('merchant_id_production');
      $this->merchant_key	= $this->get_option('merchant_key_production');
      $this->api_endpoint = 'https://billhosting.finnet-indonesia.com/prepaidsystem/api/apiFinpay.php';
    }

    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'finpay_admin_scripts' ));
    add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) ); //custom thankyou page
    add_action( 'woocommerce_api_'. strtolower( get_class($this) ), array( $this, 'callback_handler' ) );

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
      $order->update_status('on-hold', __('Awaiting payment via '. $this->sof_desc, 'woocommerce'));

      //update order note with payment code
      $order->add_order_note( __('Your '.$this->sof_desc.' payment code is <b>'.$response->payment_code.'</b>', 'woocommerce') );

      //use post excerpt to save payment code. GENIUS!!
      $rs = $wpdb->update($wpdb->prefix. 'posts', array ('post_excerpt' => $response->payment_code), array ('ID' => $order_id) );

      return array(
        'result'  => 'success',
        'redirect' => $this->get_return_url( $order )
      );

    }else{
      wc_add_notice( __($this->sof_desc.'  error:', 'woocommerce') . $response->status_code, 'error' );
      return;
    }
  }

  /**
   * Prosessing the request
   */
  function generate_request ($order_id) {
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
    $return_url   = get_site_url().'/wc-api/'.strtolower( get_class($this)).'/?id='.$invoice;
    $sof_id       = $this->id;
    $sof_type     = 'pay';
    $timeout      = $this->timeout;
    $trans_date   = date('Ymdhis',strtotime($order->order_date));

    //mer_signature
    $mer_signature = $add_info1.'%'.$amount.'%'.$cust_email.'%'.$cust_id.'%'.$cust_msisdn.'%'.$cust_name.'%'.$invoice.'%'.$merchant_id.'%'.$return_url.'%'.$sof_id.'%'.$sof_type.'%'.$timeout.'%'.$trans_date;
    $mer_signature = strtoupper($mer_signature).'%'.$this->merchant_key;
    $mer_signature = hash('sha256', $mer_signature);

    //data
    $finpay_args = array (
      'add_info1'     => $add_info1,
      'amount'        => $amount,
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
      'timeout'       => $timeout,
      'trans_date'    => $trans_date
    );
    $logger = wc_get_logger();
    $logger->log( 'DATA send', wc_print_r($finpay_args, true) );

    $response = wp_remote_retrieve_body(wp_remote_post( $this->api_endpoint, array('body' => $finpay_args )));
    $logger->log( 'Response', $response );
    return json_decode($response);
  }

  /**
   * Show the code in thankyou page
   */

  public function thankyou_page ($order_id) {
    global $wpdb;
    $payment_code = $wpdb->get_row( 'SELECT * FROM '. $wpdb->prefix. 'posts WHERE ID = '.$order_id );
    echo '<div style="text-align:center">';
    echo wpautop( wptexturize( $this->instructions ) );
    echo '<h4>'.$payment_code->post_excerpt.'</h4>';
    echo '</div>';
  }
  /**
  * add instrctions and payment code in email
  */
  function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
      echo '<div style="text-align:center">';
      echo wpautop( wptexturize( $this->instructions ) );
      echo '<h4>'.$payment_code->post_excerpt.'</h4>';
      echo '</div>';
    }
  }

  public function callback_handler() {
    global $woocommerce;
    $order = new WC_ORDER( $_GET['id'] );
    $order->add_order_note( __('Your payment have been received', 'woocommerce') );
    $order->payment_complete();
    $order->reduce_order_stock();
    update_option('webhook_debug', $_GET);
  }


  /**
   * Add JS to admin page
   */
  public function finpay_admin_scripts () {
    wp_enqueue_script( 'admin-finpay',  plugin_dir_url( __FILE__ ). '../js/admin.js', array('jquery') );
  }

  /**
   * Settings in Admin page
   */
  public function init_form_fields () {
    $this->form_fields = array(
		  	'enabled' => array(
		    'title' => __('Enabled/Disable', 'woocommerce'),
		    'type' => 'checkbox',
		    'label' => __('Enable '.$this->sof_desc, 'woocommerce'),
		    'default' => 'no'
		  ),
		  'title' => array(
		    'title' => __( 'Title', 'woocommerce' ),
		    'type' => 'text',
		    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		    'default' => __( $this->sof_desc, 'woocommerce' ),
		    'desc_tip'    => true
		  ),
		  'description' => array(
		    'title' => __( 'Description', 'woocommerce' ),
		    'type' => 'textarea',
		    'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
		    'default' => '',
		    'desc_tip'    => true
		  ),
		  'instructions' => array(
		    'title' => __( 'Instructions', 'woocommerce' ),
		    'type' => 'textarea',
		    'description' => __( 'This controls the instruction which the user sees in thankyou page', 'woocommerce' ),
		    'default' => __('Please pay with Finpay Code below'),
		    'desc_tip'    => true
		  ),
		  'timeout' => array(
		    'title' => __( 'Code Timeout (minutes)', 'woocommerce' ),
		    'type' => 'text',
		    'description' => __( 'This controls the the payment code timeout ', 'woocommerce' ),
		    'default' => __(100000),
		    'desc_tip'    => true
		  ),
		  'environment' => array(
		    'title' => __( 'Environment', 'woocommerce' ),
		    'type' => 'select',
		    'default' => 'sandbox',
		    'description' => __( 'Select the Environment', 'woocommerce' ),
		    'options'   => array(
		      'sandbox'    => __( 'Sandbox', 'woocommerce' ),
		      'production'   => __( 'Production', 'woocommerce' ),
		    ),
		    'class'	=> 'finpay_environment'
		  ),
		  'merchant_id_sandbox' => array(
		    'title'		=> __('Merchant ID', 'woocommerce'),
		    'type'		=> 'text',
		    'description'	=> __('Enter your <b>Sandbox</b> Finpay Merchant ID.', 'woocommerce'),
		    'default'	=> '',
		    'class' => 'sandbox_settings sensitive'
		  ),
		  'merchant_key_sandbox' => array(
		    'title'		=> __('Merchant Key', 'woocommerce'),
		    'type'		=> 'text',
		    'description'	=> __('Enter your <b>Sandbox</b> Finpay Authentification key', 'woocommerce'),
		    'default'	=> '',
		    'class'	=> 'sandbox_settings sensitive'
		  ),
		  'merchant_id_production' => array(
		    'title'		=> __('Merchant ID', 'woocommerce'),
		    'type'		=> 'text',
		    'description'	=> __('Enter your <b>Production</b> Finpay Merchant ID.', 'woocommerce'),
		    'default'	=> '',
		    'class' => 'production_settings sensitive'
		  ),
		  'merchant_key_production' => array(
		    'title'		=> __('Merchant Key', 'woocommerce'),
		    'type'		=> 'text',
		    'description'	=> __('Enter your <b>Production</b> Finpay Authentification key', 'woocommerce'),
		    'default'	=> '',
		    'class'	=> 'production_settings sensitive'
		  )
		);
  }
}




