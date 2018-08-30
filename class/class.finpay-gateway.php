<?php

class WC_Gateway_Finpay extends WC_Payment_Gateway {
	/**
	* constructor
	*/
	function __construct() {
		$this->id									= 'finpay';
		$this->has_fields					= false;
		$this->method_title				= __('Finpay', 'woocommerce');
		$this->method_description = __('Finpay Checkout', 'finpay-for-woocommerce');

		//load the settings
		$this->init_form_fields();
		$this->init_settings();

		$this->enabled 			= $this->get_option('enabled');
		$this->title 				= $this->get_option('title');
		$this->description 	= $this->get_option('description');
		$this->store_id			= $this->get_option('store_id');
		$this->store_secret	= $this->get_option('store_secret');
		$this->environment	= $this->get_option('environment');

		$this->log					= new WC_Logger();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	
	}

	/**
	 * Setting admin page
	 */
	function init_form_fields() {
		$sandbox_url = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enabled/Disable', 'woocommerce'),
				'type' => 'checkbox',
				'label' => __('Enable Finpay Payment', 'woocommerce'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Title', 'woocommerce' ),
				'type' => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default' => __( 'Credit Card (VISA / MasterCard)', 'woocommerce' ),
				'desc_tip'      => true,
			),
			'description' => array(
				'title' => __( 'Customer Message', 'woocommerce' ),
				'type' => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
				'default' => ''
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
			),
			'store_id_sandbox' => array(
				'title'		=> __('Store ID', 'woocommerce'),
				'type'		=> 'text',
				'description'	=> __('Enter your <b>Sandbox</b> Finpay Store ID.', 'woocommerce'),
				'default'	=> '',
				'class' => 'sandbox_settings'
			),
			'auth_key_sandbox' => array(
				'title'		=> __('Authentication Key', 'woocommerce'),
				'type'		=> 'text',
				'description'	=> __('Enter your <b>Sandbox</b> Finpay Authentification key', 'woocommerce'),
				'default'	=> '',
				'class'	=> 'sandbox_settings'
			),
			'store_id_production' => array(
				'title'		=> __('Store ID', 'woocommerce'),
				'type'		=> 'text',
				'description'	=> __('Enter your <b>Production</b> Finpay Store ID.', 'woocommerce'),
				'default'	=> '',
				'class' => 'sandbox_settings'
			),
			'auth_key_production' => array(
				'title'		=> __('Authentication Key', 'woocommerce'),
				'type'		=> 'text',
				'description'	=> __('Enter your <b>Production</b> Finpay Authentification key', 'woocommerce'),
				'default'	=> '',
				'class'	=> 'sandbox_settings'
			)

		);
	}

}
