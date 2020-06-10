<?php
/**
 * tcash / TCASH
 */

class WC_Gateway_Finpay_Tcash extends WC_Payment_Gateway {

	/**
	 * constructor
	 */
	private $sof_id   = 'tcash';
	private $sof_desc = 'TCash';

	public function __construct() {
		$this->id                 = $this->sof_id;
		$this->has_fields         = false;
		$this->method_title       = 'TCash';
		$this->method_description = 'Allows payments using TCash';
		$this->icon               = plugins_url( 'img/tcash.png', dirname( __FILE__ ) );

		// load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled       = $this->get_option( 'enabled' );
		$this->title         = $this->get_option( 'title' );
		$this->description   = $this->get_option( 'description' );
		$this->instructions  = $this->get_option( 'instructions' );
		$this->timeout       = $this->get_option( 'timeout' );
		$this->environment   = $this->get_option( 'environment' );
		$this->merchant_code = $this->get_option( 'merchant_code' );
		if ( 'sandbox' === $this->environment ) {
			$this->merchant_id  = $this->get_option( 'merchant_id_sandbox' );
			$this->merchant_key = $this->get_option( 'merchant_key_sandbox' );
			$this->api_endpoint = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
		} else {
			$this->merchant_id  = $this->get_option( 'merchant_id_production' );
			$this->merchant_key = $this->get_option( 'merchant_key_production' );
			$this->api_endpoint = 'https://billhosting.finnet-indonesia.com/prepaidsystem/api/apiFinpay.php';
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
			$order->update_status( 'on-hold', __( 'Awaiting payment via ' . $this->sof_desc, 'woocommerce' ) );

			return array(
				'result'   => 'success',
				'redirect' => $response->redirect_url, // to payment page
			);

		} else {
			wc_add_notice( $this->sof_desc . '  error:' . $response->status_code, 'error' );
			return;
		}
	}

	/**
	 * Prosessing the request
	 */
	public function generate_request( $order_id ) {
		global $woocommerce;
		$order = new WC_Order( $order_id );

		$items_arr = array();
		if ( count( $order->get_items() ) > 0 ) {
			foreach ( $order->get_items() as $item ) {
				if ( $item['qty'] ) {
					$product = $order->get_product_from_item( $item );

					$fn_item = array();

					$fn_item['name']  = $item['name'];
					$fn_item['price'] = $order->get_item_subtotal( $item, false );
					$fn_item['qty']   = $item['qty'];

					$items_arr[] = array_values( $fn_item );
				}
			}
		}
		$logger = wc_get_logger();
		$logger->log( 'items', wc_print_r( $items, true ) );
		$items = wp_json_encode( $items_arr );

		$add_info1   = $this->merchant_code . '-' . $order->billing_first_name; // code+fname
		$add_info5   = $order->billing_phone;
		$amount      = round( $order->get_total(), 0 );
		$cust_email  = $order->billing_email;
		$cust_id     = $order->billing_phone;
		$cust_msisdn = $order->billing_phone;
		$cust_name   = $order->billing_first_name . ' ' . $order->billing_last_name; // fname+lname
		$failed_url  = $order->get_checkout_payment_url( false );
		$invoice     = $order->get_order_number();
		$items       = $items;
		$merchant_id = $this->merchant_id;
		$return_url  = get_site_url() . '/wc-api/' . strtolower( get_class( $this ) ) . '/?id=' . $invoice;  // callback
		$sof_id      = $this->id;
		$sof_type    = 'pay';
		$success_url = $this->get_return_url( $order ); // after payment done
		$timeout     = $this->timeout;
		$trans_date  = gmdate( 'Ymdhis', strtotime( $order->order_date ) );

		$exceedlen = false;
		$field_var = '';
		if ( strlen( $add_info1 ) > 150 ) {
			$field_var .= 'add_info1';
			$exceedlen  = true;
		} elseif ( strlen( $add_info5 ) > 150 ) {
			$field_var .= 'add_info5';
			$exceedlen  = true;
		} elseif ( strlen( $amount ) > 12 ) {
			$field_var .= 'amount';
			$exceedlen  = true;
		} elseif ( strlen( $cust_email ) > 50 ) {
			$field_var .= 'cust_email';
			$exceedlen  = true;
		} elseif ( strlen( $cust_msisdn ) > 32 ) {
			$field_var .= 'cust_msisdn';
			$exceedlen  = true;
		} elseif ( strlen( $cust_name ) > 50 ) {
			$field_var .= 'cust_name';
			$exceedlen  = true;
		}
		if ( $exceedlen ) {
			wc_add_notice( __( 'This field(s) have exceed the limit : ', 'woocommerce' ) . $field_var, 'error' );
			return;
		}

		if ( ! preg_match( '/^[0-9]+$/', $cust_msisdn ) ) {
			wc_add_notice( __( 'Phone number only accept number value. ', 'woocommerce' ), 'error' );
			return;
		}

		// mer_signature
		$mer_signature = $add_info1 . '%' . $add_info5 . '%' . $amount . '%' . $cust_email . '%' . $cust_id . '%' . $cust_msisdn . '%' . $cust_name . '%' . $failed_url . '%' . $invoice . '%' . $items . '%' . $merchant_id . '%' . $return_url . '%' . $sof_id . '%' . $sof_type . '%' . $success_url . '%' . $timeout . '%' . $trans_date;

		$logger        = wc_get_logger();
		$mer_signature = strtoupper( $mer_signature ) . '%' . $this->merchant_key;
		$logger->log( 'MER1', $mer_signature );
		$mer_signature = strtoupper( hash( 'sha256', $mer_signature ) );
		$logger->log( 'MER2', $mer_signature );

		// data
		$finpay_args = array(
			'add_info1'     => $add_info1,
			'add_info5'     => $add_info5,
			'amount'        => $amount,
			'cust_email'    => $cust_email,
			'cust_id'       => $cust_id,
			'cust_msisdn'   => $cust_msisdn,
			'cust_name'     => $cust_name,
			'failed_url'    => $failed_url,
			'invoice'       => $invoice,
			'items'         => $items,
			'mer_signature' => $mer_signature,
			'merchant_id'   => $merchant_id,
			'return_url'    => $return_url,
			'sof_id'        => $sof_id,
			'sof_type'      => $sof_type,
			'success_url'   => $success_url,
			'timeout'       => $timeout,
			'trans_date'    => $trans_date,
		);

		$logger->log( 'DATA send', wc_print_r( $finpay_args, true ) );

		$response = wp_remote_retrieve_body( wp_remote_post( $this->api_endpoint, array( 'body' => $finpay_args ) ) );
		$logger->log( 'Response', $response );
		return json_decode( $response );
	}

	/**
	 * Show the code in thankyou page
	 */

	public function thankyou_page( $order_id ) {
		echo esc_html(wpautop( wptexturize( $this->instructions ) ));
	}
	/**
	 * add instrctions and payment code in email
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo esc_html(wpautop( wptexturize( $this->instructions ) ));
		}
	}

	public function callback_handler() {
		$logger = wc_get_logger();
		$logger->log( 'finrc', wc_print_r( $_POST, true ) );
		global $woocommerce;
		$order = new WC_ORDER( $_GET['id'] );
		if ( '00' === $_POST['result_code'] ) {
			$order->add_order_note( __( 'Your payment have been received', 'woocommerce' ) );
			$order->payment_complete();
			$order->reduce_order_stock();
		} else {
			$order->add_order_note( __( 'Your payment failed, please contact Finpay.', 'woocommerce' ) );
			$order->update_status( 'failed', __( 'Your payment failed, please try again.', 'woocommerce' ) );
		}

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
				'label'   => 'Enable TCash',
				'default' => 'no',
			),
			'title'                   => array(
				'title'       => __( 'Title', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => 'TCash',
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
			'merchant_code'           => array(
				'title'       => __( 'Merchant Code', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Enter your Merchant Code.', 'woocommerce' ),
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
