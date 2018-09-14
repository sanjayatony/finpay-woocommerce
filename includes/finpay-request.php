<?php
/**
 * Finpay request
 */
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
$return_url   = get_site_url().'/wc-api/finpay';
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

$response = wp_remote_retrieve_body(wp_remote_post( $this->api_endpoint, array('body' => $finpay_args )));
$logger->log( 'Response', $response );
return json_decode($response);

?>