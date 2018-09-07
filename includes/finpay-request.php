<?php

function charge_payment($order) {
  $finpay_args = $this->get_finpay_args($order);

  $ch = curl_init($this->endpoint());

  $post_string = http_build_query($finpay_args, '', '&');

  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  # Get the response
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;


}

function get_finpay_args($order) {  

  //collect items
  $items = array();

  if( sizeof( $order->get_items() ) > 0 ) {
    foreach( $order->get_items() as $item ) {
      if ( $item['qty'] ) {
        $product = $order->get_product_from_item( $item );

        $finpay_item = array();

        $finpay_item['id']    = $item['product_id'];
        $finpay_item['price']      = $order->get_item_subtotal( $item, false );
        $finpay_item['quantity']   = $item['qty'];
        $finpay_item['name'] = $item['name'];

        $items[] = $finpay_item;
      }
    }
  }

  //shipping fee
  if( $order->get_total_shipping() > 0 ) {
    $items[] = array(
      'id' => 'shippingfee',
      'price' => $order->get_total_shipping(),
      'quantity' => 1,
      'name' => 'Shipping Fee',
    );
  }


  //get total amount
  $total_amount=0;
  foreach ($items as $item) {
    $total_amount+=($item['price']*$item['quantity']);
  }
  
  $add_info1    = $order->billing_city;
  $amount       = $order->get_total();
  $cust_email   = $order->billing_email;
  $cust_id      = $order->get_user_id();
  $cust_msisdn  = $order->billing_phone;
  $cust_name    = $order->billing_name;
  $invoice      = $order_id;
  $merchant_id  = $this->merchant_id;
  $return_url   = $this->get_return_url($order);
  $sof_id       = 'finpay021';
  $sof_type     = 'pay';
  $trans_date   = date('YYMMDDhis',$order->order_date);

  if($this->environment == 'sandbox'){
    $merchant_key = $this->auth_key_sandbox;
  }else{
    $merchant_key = $this->auth_key_production;
  }

  //mer_signature
  $mer_signature = $add_info1.'%'.$amount.'%'.$cust_email.'%'.$cust_id.'%'.$cust_msisdn.'%'.$cust_name.'%'.$invoice.'%'.$merchant_id.'%'.$return_url.'%'.$sof_id.'%'.$sof_type.'%'.$trans_date;
  $mer_signature = strtoupper($mer_signature).'%'.$merchant_key;
  $mer_signature = hash('sha256', $mer_signature);
  //data
  $data = array (
    'add_info1'	    => $order->billing_city,
    'amount'		    => $total_amount,
    'cust_email'    => $order->billing_email,
    'cust_id'       => $order->get_user_id(),
    'cust_msisdn'   => $order->billing_phone,
    'cust_name'     => $order->billing_name,
    'invoice'       => $order_id,
    'mer_signature' => $mer_signature,
    'merchant_id'   => $this->merchant_id,
    'return_url'    => $this->get_return_url($order),
    'sof_id'        => 'finpay021',
    'sof_type'      => 'pay',
    'trans_date'    => date('YYMMDDhis',$order->order_date)
  );
  return $data;
}

function endpoint() {
  if($this->environment == 'sandbox'){
    $url = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
  }else{
    $url = 'https://sandbox.finpay.co.id/servicescode/api/apiFinpay.php';
  }
  return $url;
}