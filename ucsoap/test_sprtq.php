<?php
$url = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
$client = new SoapClient($url);
// $client = new SoapClient($url, array('trace' => TRUE)); // for verbosity, like __getFunctions() below
//print_r($client->__getFunctions());
$merchantId = 'SPRTQ';
if(!isset($_COOKIE["cartId"])){
  $cartChangeResult = $client->createCart($merchantId);
  $cart = $cartChangeResult->cart;
}else{
  $cartId=$_COOKIE["cartId"];
  $cartChangeResult = $client->getCart($merchantId,$cartId);
  $cart = $cartChangeResult->cart;
}

$itemIds = array('SKU-003');
$itemsResult = $client->getItems($merchantId, $itemIds);
$items = $itemsResult->items;

?>