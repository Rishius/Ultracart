<html>
<body>
<pre>
<?php
$merchantId = '59362';

  $soapClient = new SoapClient('https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl');
  /*** Check if there's already a cart ***/
  if (!isset($_COOKIE["cartId"])) {
    $cart = $soapClient->createCart($merchantId);
    if (!is_null($cart)) {
      setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
    }
  } else {
    $cartId = $_COOKIE["cartId"];
    $cart = $soapClient->getCart($merchantId, $cartId);

    // after placing an order, the cartId cookie will still be present, but will no be valid.  so if the $cart object
    // is null, then create the cart explicitly without cookie.
    if (is_null($cart)) {
      $cart = $soapClient->createCart($merchantId);
      if (!is_null($cart)) {
        setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
      }
    }
  }

  $items = array();
  $items[] = 'SF';
  $quantities = array();
  $quantities[] = 1;
  $cartChangeResult = $soapClient->addItemByItemIds($merchantId, $cart, $items, $quantities);
  $cart = $cartChangeResult->cart;

  $cart->shipToPostalCode = '90038';
  $cart->shipToCity = 'Hollywood';
  $cart->shipToState = 'CA';
  $cart->shipToCountry = "United States";

  $cart = $soapClient->updateCart($merchantId, $cart);

  echo "Tax is: " . $cart->tax . "\n\n\n";

  var_dump($cart);

?>
  </pre>
</body>
</html>