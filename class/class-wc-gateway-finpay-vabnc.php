<?php
/**
 * Sof vabnc / VA BNC
 */

class WC_Gateway_Finpay_Vabnc extends WC_Payment_Gateway {

	/**
	 * constructor
	 */
	private $sof_id   = 'vabnc';
	private $sof_desc = 'BNC Virtual Account';

	public function __construct() {
		$this->id                 = $this->sof_id;
		$this->has_fields         = false;
		$this->method_title       = 'BNC Virtual Account';
		$this->method_description = 'Allows payments using BNC Virtual Account';
		$this->icon               = plugins_url( 'img/bnc.png', dirname( __FILE__ ) );

		// load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled       = $this->get_option( 'enabled' );
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->instructions  = $this->get_option( 'instructions' );
		$this->timeout       = $this->get_option( 'timeout' );
		$this->environment   = $this->get_option( 'environment' );
		$this->merchant_name = $this->get_option( 'merchant_name' );
		if ( 'sandbox' === $this->environment ) {
			$this->merchant_id  = $this->get_option( 'merchant_id_sandbox' );
			$this->merchant_key = $this->get_option( 'merchant_key_sandbox' );
			$this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
		} else {
			$this->merchant_id  = $this->get_option( 'merchant_id_production' );
			$this->merchant_key = $this->get_option( 'merchant_key_production' );
			$this->api_endpoint = 'https://billhosting.finpay.id/prepaidsystem/api/apiFinpay.php';
		}

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_print_scripts-woocommerce_page_wc-settings', array( &$this, 'finpay_admin_scripts' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) ); // custom thankyou page
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );

	}


	/**
	 * Process the payment and return the result
	 */
	public function process_payment( $order_id ) {
		global $woocommerce, $wpdb;
		$order = new WC_Order( $order_id );

		$response = $this->generate_request( $order_id );

		$logger = wc_get_logger();
		$logger->log( 'response generate', wc_print_r( $response, true ) );

		if ( '00' === $response->status_code ) {
			// update  order status to on-hold
			$order->update_status( 'on-hold', 'Awaiting payment via ' . $this->sof_desc);

			// update order note with payment code
			$order->add_order_note( 'Your ' . $this->sof_desc . ' payment code is <b>' . $response->payment_code . '</b>' );

			// Save payment code to post meta.
			add_post_meta( $order_id, '_payment_code', $response->payment_code, true );

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order ),
			);

		} else {
			wc_add_notice( $this->sof_desc . '  error:'. $response->status_code, 'error' );
			return;
		}
	}

	/**
	 * Prosessing the request
	 */
	public function generate_request( $order_id ) {
		global $woocommerce;
		$logger	= wc_get_logger();
		$order 	= new WC_Order( $order_id );

		$items = array();

		foreach ( $order->get_items() as $item_id => $item ) {
   		$product_id = $item->get_product_id();
			$product_name = $item->get_name();
			$quantity = $item->get_quantity();
			$item_subtotal = $item->get_subtotal();
  	}

		array_push( $items, array(
		  $product_id,
			$product_name,
			$quantity,
			$item->get_subtotal()
    ) );

		$logger->log( 'ITEMS', wc_print_r( $items, true ) );

		$data = array (
			"amount"      => round( $order->get_total(), 0 ),			
			"cust_email"  => $order->billing_email,
			"cust_id"     => $order->billing_phone,
			"cust_msisdn" => $order->billing_phone,
			"cust_name"   => $order->billing_first_name . ' ' . $order->billing_last_name,
			"invoice"     => $order->get_order_number(),
			"merchant_id" => $this->merchant_id,
			"items"				=> array(array("Kura-kura Terbang",100000,1)),
			"return_url"  => get_site_url() . '/wc-api/' . strtolower( get_class( $this ) ) . '/?id=' . $order_id,
			"timeout"     => $this->timeout,
			"trans_date"  => gmdate( 'Ymdhis', strtotime( $order->order_date ) ),
			"add_info1"   => $this->merchant_name . '-' . $order->billing_first_name,
			"sof_id"      => $this->id,
			"sof_type"    => 'pay'
		);

		$exceedlen = false;
		$field_var = '';
		if ( strlen( $data['add_info1'] ) > 150 ) {
			$field_var .= 'add_info1';
			$exceedlen  = true;
		} elseif ( strlen( $data['add_info5'] ) > 150 ) {
			$field_var .= 'add_info5';
			$exceedlen  = true;
		} elseif ( strlen( $data['amount'] ) > 12 ) {
			$field_var .= 'amount';
			$exceedlen  = true;
		} elseif ( strlen( $data['cust_email'] ) > 50 ) {
			$field_var .= 'cust_email';
			$exceedlen  = true;
		} elseif ( strlen( $data['cust_msisdn'] ) > 32 ) {
			$field_var .= 'cust_msisdn';
			$exceedlen  = true;
		} elseif ( strlen( $data['cust_name'] ) > 50 ) {
			$field_var .= 'cust_name';
			$exceedlen  = true;
		}
		if ( $exceedlen ) {
			wc_add_notice( __( 'This field(s) have exceed the limit : ', 'woocommerce' ) . $field_var, 'error' );
			return;
		}

		if ( ! preg_match( '/^[0-9]+$/', $data['cust_msisdn'] ) ) {
			wc_add_notice( __( 'Phone number only accept number value. ', 'woocommerce' ), 'error' );
			return;
		}

		$signature   = $this->generate_signature( $data, $this->merchant_key );
		$finpay_args = array_merge( $data, array( 'mer_signature' => $signature ) );

		
		$logger->log( 'DATA send', wc_print_r( $finpay_args, true ) );

		$response = wp_remote_retrieve_body( wp_remote_post( $this->api_endpoint, array( 'body' => $finpay_args ) ) );
		$logger->log( 'Response', $response );
		return json_decode( $response );
	}

	
	public function generate_signature( $data_array, $merchant_key ){
		$logger	= wc_get_logger();
		ksort($data_array, 0);

		if(is_array($data_array['items'])){
			$data_array['items'] = json_encode($data_array['items']);
		}

		$data = strtoupper( implode('%', $data_array) ) . '%' . $merchant_key;
		$logger->log( 'DATA IMPLODE', $data );

		$signature = hash( 'sha256', $data );
		return strtoupper( $signature );
	}


	/**
	 * Show the code in thankyou page
	 */
	public function thankyou_page( $order_id ) {
		global $wpdb;
		echo '<div style="text-align:center">';
		echo esc_html( wpautop( wptexturize( $this->instructions ) ) );
		echo '<h4>' . esc_html( get_post_meta( $order_id, '_payment_code', true ) ) . '</h4>';
		echo '</div>';
	}
	/**
	 * add instrctions and payment code in email
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo '<div style="text-align:center">';
			echo esc_html(wpautop( wptexturize( $this->instructions ) ));
			echo '<h4>' . esc_html( get_post_meta( $order_id, '_payment_code', true ) ) . '</h4>';
			echo '</div>';
		}
	}

	public function callback_handler() {
		global $woocommerce;
		$order = new WC_ORDER( $_GET['id'] );
		$order->add_order_note( __( 'Your payment have been received', 'woocommerce' ) );
		$order->payment_complete();
		$order->reduce_order_stock();
		update_option( 'webhook_debug', $_GET );
	}


	/**
	 * Add JS to admin page
	 */
	public function finpay_admin_scripts() {
		wp_enqueue_script( 'admin-finpay', plugin_dir_url( __FILE__ ) . '../js/admin.js', array( 'jquery' ), '0.1', true );
	}

	/**
	 * Settings in Admin page
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'                 => array(
				'title'   => __( 'Enabled/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => 'Enable BNC Virtual Account',
				'default' => 'no',
			),
			'title'                   => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'BNC Virtual Account',
				'desc_tip'    => true,
			),
			'description'             => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'instructions'            => array(
				'title'       => __( 'Instructions', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the instruction which the user sees in thankyou page', 'woocommerce' ),
				'default'     => __( 'Please pay with Finpay Code below' ),
				'desc_tip'    => true,
			),
			'timeout'                 => array(
				'title'       => __( 'Code Timeout (minutes)', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the the payment code timeout ', 'woocommerce' ),
				'default'     => 100000,
				'desc_tip'    => true,
			),
			'merchant_name'           => array(
				'title'       => __( 'Merchant Name', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your Merchant Name.', 'woocommerce' ),
				'default'     => '',
			),
			'environment'             => array(
				'title'       => __( 'Environment', 'woocommerce' ),
				'type'        => 'select',
				'default'     => 'sandbox',
				'description' => __( 'Select the Environment', 'woocommerce' ),
				'options'     => array(
					'sandbox'    => __( 'Sandbox', 'woocommerce' ),
					'production' => __( 'Production', 'woocommerce' ),
				),
				'class'       => 'finpay_environment',
			),
			'merchant_id_sandbox'     => array(
				'title'       => __( 'Merchant ID', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Finpay Merchant ID.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'merchant_key_sandbox'    => array(
				'title'       => __( 'Merchant Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Sandbox</b> Finpay Authentification key', 'woocommerce' ),
				'default'     => '',
				'class'       => 'sandbox_settings sensitive',
			),
			'merchant_id_production'  => array(
				'title'       => __( 'Merchant ID', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Finpay Merchant ID.', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
			'merchant_key_production' => array(
				'title'       => __( 'Merchant Key', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your <b>Production</b> Finpay Authentification key', 'woocommerce' ),
				'default'     => '',
				'class'       => 'production_settings sensitive',
			),
		);
	}
}