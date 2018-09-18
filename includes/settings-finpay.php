<?php

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
  'timeout' => array(
    'title' => __( 'Code Timeout (minutes)', 'woocommerce' ),
    'type' => 'text',
    'description' => __( 'This controls the the payment code timeout ', 'woocommerce' ),
    'default' => __('100000'),
    'desc_tip'    => true
  ),
  'instructions' => array(
    'title' => __( 'Instructions', 'woocommerce' ),
    'type' => 'textarea',
    'description' => __( 'This controls the instruction which the user sees in thankyou page', 'woocommerce' ),
    'default' => __('Please pay with Finpay Code below'),
    'desc_tip'    => true
  ),
  'Timeout' => array(
    'title' => __( 'Code Timeout (minutes)', 'woocommerce' ),
    'type' => 'text',
    'description' => __( 'This controls the the payment code timeout ', 'woocommerce' ),
    'default' => __('100000'),
    'desc_tip'    => true
  ),
  'environment' => array(
    'title' => __( 'Environment', 'woocommerce' ),
    'type' => 'select',
    'default' => 'sandbox',
    'description' => __( 'Select the Environment', 'woocommerce' ),
    //'class' => 'finpay_environment'
    'options'   => array(
      'sandbox'    => __( 'Sandbox', 'woocommerce' ),
      'production'   => __( 'Production', 'woocommerce' ),
    )
  ),
  'merchant_id_sandbox' => array(
    'title'   => __('Merchant ID', 'woocommerce'),
    'type'    => 'text',
    'description' => __('Enter your <b>Sandbox</b> Finpay Merchant ID.', 'woocommerce'),
    'default' => '',
    'class' => 'sandbox_settings sensitive'
  ),
  'merchant_key_sandbox' => array(
    'title'   => __('Merchant Key', 'woocommerce'),
    'type'    => 'text',
    'description' => __('Enter your <b>Sandbox</b> Finpay Authentification key', 'woocommerce'),
    'default' => '',
    'class' => 'sandbox_settings sensitive'
  ),
  'merchant_id_production' => array(
    'title'   => __('Merchant ID', 'woocommerce'),
    'type'    => 'text',
    'description' => __('Enter your <b>Production</b> Finpay Merchant ID.', 'woocommerce'),
    'default' => '',
    'class' => 'production_settings sensitive'
  ),
  'merchant_key_production' => array(
    'title'   => __('Merchant Key', 'woocommerce'),
    'type'    => 'text',
    'description' => __('Enter your <b>Production</b> Finpay Authentification key', 'woocommerce'),
    'default' => '',
    'class' => 'production_settings sensitive'
  )
);
