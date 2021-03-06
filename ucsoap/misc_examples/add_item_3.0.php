<?php
  $url = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
  $client = new SoapClient($url);
  //$client = new SoapClient($url, array('trace' => TRUE));  // for verbosity, like __getFunctions() below
  //print_r($client->__getFunctions());
  $merchantId = '59362';


// =====================================================================
// Standard cart retrieval, which would probably be in a reusable function
// check for a cookie.
// if no cookie, create the cart.
if (!isset($_COOKIE["cartId"])) {
  $cartChangeResult = $client->createCart($merchantId);
  if (property_exists($cartChangeResult, 'cart')) {
    $cart = $cartChangeResult->cart;
  }
  if (!is_null($cart)) {
    setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
  }
  // found a cookie, get the cart.
} else {
  $cartId = $_COOKIE["cartId"];
  $cartChangeResult = $client->getCart($merchantId, $cartId);
  if (property_exists($cartChangeResult, 'cart')) {
    $cart = $cartChangeResult->cart;
  }

  // if that didn't work for some reason, then the cookie is bad/stale, so just create the cart
  if (!isset($cart) || is_null($cart)) {
    $cartChangeResult = $client->getCart($merchantId, $cartId);
    if (property_exists($cartChangeResult, 'cart')) {
      $cart = $cartChangeResult->cart;
      if (!is_null($cart)) {
        setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
      }
    }
  }
}
// =====================================================================


  // if we have a cart and an item, add the item.
  if(!is_null($cart) && isset($_POST['item'])){
    $newItem = $_POST['item'];
    $qty = 1;
    if(isset($_POST['quantity'])){
      $qty = intval($_POST['quantity']);  // not checking for valid integer...
    }

    $cartChangeResult = $client->addItemByItemId($merchantId, $cart, $newItem, $qty);
    if(!is_null($cartChangeResult)){
      if(property_exists($cartChangeResult, 'cart')){
        $cart = $cartChangeResult->cart;
      }
    }
  }

$items = $cart->items;
?>
<html>
  <head>
    <style type='text/css'>
      * {
        font-family: Verdana, serif;
        font-size: 11px;
      }
      body{
        margin:30px;
      }
      th, td{
        padding: 3px 5px;
      }
    </style>
  </head>
  <body>
  <?php if(isset($cartChangeResult) && property_exists($cartChangeResult, 'errors') && count($cartChangeResult->errors) > 0){ ?>
    <div>Errors from previous operation:</div>
    <ul>
    <?php foreach($cartChangeResult->errors as $err){
      echo "<li>$err</li>";
    } ?>
    </ul>
    <br />
    <br />
    <br />
  <?php } ?>

  <div>Enter an item to add to the cart.  Try BONE, TSHIRT, PDF, item, P0975</div>
  <form method='POST' action='./add_item_3.0.php'>
    <table>
      <tr>
        <td><label for='item'>Item ID:</label></td>
        <td><input type='text' id='item' name='item' size='20' maxlength="20" /></td>
      </tr>
      <tr>
        <td><label for='quantity'>Quantity:</label></td>
        <td><input type='text' id='quantity' name='quantity' size='5' maxlength="5" /></td>
      </tr>
      <tr>
        <td colspan='2'><input type='submit' value='add' /></td>
      </tr>
    </table>
  </form>
  <br />
  <br />
  <h1>Items in Cart</h1>

  <table>
    <thead>
      <tr>
        <th>Thumbnail</th>
        <th>Item</th>
        <th>Description</th>
        <th align='right'>Cost</th>
        <th align='right'>Discounted Cost</th>
        <th align='right'>Quantity</th>
      </tr>
    </thead>
    <tbody>
    <?php
      if(!is_null($items)){
        foreach($items as $item){
          $cost = sprintf("%01.2f", $item->unitCost);
          $unitCostWithDiscount = sprintf("%01.2f", $item->unitCostWithDiscount);
          echo "<tr>";
          echo "<td><img src='$item->defaultThumbnailUrl' alt='thumbnail'/></td>";
          echo "<td>$item->itemId</td>";
          echo "<td>$item->description</td>";
          echo "<td align='right'>$cost</td>";
          echo "<td align='right'>$unitCostWithDiscount</td>";
          echo "<td align='right'>$item->quantity</td>";
          echo '</tr>';
        }
      }
    ?>
    </tbody>
  </table>
  <br/>
  <br/>
  </body>
</html>
