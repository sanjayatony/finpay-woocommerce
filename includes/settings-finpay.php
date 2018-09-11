<?php
/**
 * Settings for Finpay gateway
 */
return array(
  'enabled' => array(
    'title' => __('Enabled/Disable', 'woocommerce'),
    'type' => 'checkbox',
    'label' => __('Enable Finpay Payment', 'woocommerce'),
    'default' => 'no'
  ),
  'title' => array(
    'title' => __( 'Title', 'woocommerce' ),
    'type' => 'text',
    'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
    'default' => __( 'Finpay', 'woocommerce' ),
    'desc_tip'    => true
  ),
  'description' => array(
    'title' => __( 'Description', 'woocommerce' ),
    'type' => 'textarea',
    'description' => __( 'This controls the description which the user sees during checkout', 'woocommerce' ),
    'default' => '',
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
    )
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
  ),
  'cc' => array(
    'title' => __( 'Enable credit card', 'woocommerce' ),
    'type' => 'checkbox',
    'label' => __( 'Enable Credit card?', 'woocommerce' ),
    'description' => __( 'Please contact us if you wish to enable this feature in the Production environment.', 'woocommerce' ),
    'default' => 'no'
  ),
  'finpay021' => array(
    'title' => __( 'Enable Felisa 021 Closed', 'woocommerce' ),
    'type' => 'checkbox',
    'label' => __( 'Enable Felisa 021 Closed', 'woocommerce' ),
    'description' => __( 'Please contact us if you wish to enable this feature in the Production environment.', 'woocommerce' ),
    'default' => 'no'
  ),
);