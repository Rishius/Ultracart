<?php

$url = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
$client = new SoapClient($url);
//$client = new SoapClient($url, array('trace' => TRUE));  // for verbosity, like __getFunctions() below
//print_r($client->__getFunctions());
$merchantId = 'DEMO';
$msg = null;

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


if (isset($_POST['add'])) {

  if (!is_null($cart) && isset($_POST['item'])) {
    $newItems = $_POST['item'];
    $qtys = $_POST['quantity'];

    for ($i = count($newItems) - 1; $i >= 0; $i--) {
      if (is_null($newItems[$i]) || strlen(trim($newItems[$i])) == 0) {
        unset($newItems[$i]);
        unset($qtys[$i]);
      }
    }

    $msg = 'Adding ' . count($newItems) . ' Item(s)';

    $errors = null;
    $cartChangeResult = $client->addItemByItemIds($merchantId, $cart, $newItems, $qtys);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $errors = $cartChangeResult->errors;
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $cart = $cartChangeResult->cart;
      }
    }
  }
}


if (isset($_POST['deleteItem'])) {

  if (!is_null($cart) && isset($_POST['item'])) {
    $deleteItem = $_POST['item'];
    $msg = 'Deleted Item ' . $deleteItem;
    $errors = null;
    $cartChangeResult = $client->removeItem($merchantId, $cart, $deleteItem);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $errors = $cartChangeResult->errors;
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $cart = $cartChangeResult->cart;
      }
    }
  }
}


if (isset($_POST['update'])) {

  if (!is_null($cart) && isset($_POST['item'])) {
    $updateItems = $_POST['item'];
    $updateQuantities = $_POST['quantity'];

    $updateCount = 0;
    $updateArray = array();
    foreach ($cart->items as $cItem) {
      for ($i = 0; $i < count($updateItems); $i++) {
        if ($updateItems[$i] == $cItem->itemId && $updateQuantities[$i] != $cItem->quantity) {
          $cItem->quantity = $updateQuantities[$i];
          $updateCount++;
        }
      }
      array_push($updateArray, $cItem);
    }

    $msg = 'Updated ' . $updateCount . ' Item(s)';

    $errors = null;
    $cartChangeResult = $client->updateItems($merchantId, $cart, $updateArray);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $errors = $cartChangeResult->errors;
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $cart = $cartChangeResult->cart;
      }
    }
  }
}


if (isset($_POST['clearCart'])) {
  $msg = 'Cleared Cart';
  if (!is_null($cart)) {
    $errors = null;
    $cartChangeResult = $client->clearItems($merchantId, $cart);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $errors = $cartChangeResult->errors;
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $cart = $cartChangeResult->cart;
      }
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

    body {
      margin: 30px;
    }

    th, td {
      padding: 3px 5px;
    }

    .msg {
      border-top: 1px solid black;
      border-bottom: 1px solid black;
      width: 600px;
      padding: 10px;
      margin-top: 10px;
      margin-bottom: 10px;
      background-color: #E0E0E0;
      font-weight: bold;
    }

    #shippingMethods {
      width: 300px;
      padding: 10px;
      border: 1px solid black;
    }

    #shippingMethodsContainer {
      width: 300px;
      vertical-align: top;
    }
  </style>
  <script type='text/javascript' src='../js/jquery-1.4.2.min.js'></script>
  <script type='text/javascript'>
    jQuery('document').ready(function() {
      $.get('/estimate_shipping.php',
          {},
          function(result) {
            if (result != null) {
              // result should be an array of shipping methods
              var html = '';
              for (var i = 0; i < result.length; i++) {
                var method = result[i];
                html += "<input type='radio' name='shippingMethod' value='" + method.name + "'/> " + method.name + " - " + method.cost + "<br />";
              }
              $('#shippingMethods').html(html);
            }
          },
          "json");
    });
  </script>
  <script type='text/javascript'>
    function increaseBy2() {
      var qfields = document.getElementsByClassName('qty');
      if (qfields) {
        for (var i = 0; i < qfields.length; i++) {
          qfields[i].value = parseInt(qfields[i].value) + 2;
        }
      }
    }

    function blankAddFields() {
      var fields = document.getElementsByClassName('addText');
      if (fields) {
        for (var i = 0; i < fields.length; i++) {
          fields[i].value = '';
        }
      }
    }

    function deleteAnItem(itemId) {
      if (confirm("Please confirm you wish to delete " + itemId + '.')) {
        var itemField = document.getElementById('deleteItem');
        itemField.value = itemId;
        var deleteForm = document.getElementById('deleteForm');
        deleteForm.submit();
      }
    }
  </script>
</head>
<body>
<?php
    if (!is_null($msg)) {
  echo "<div class='msg'>$msg</div>";
}
?>

<?php if (isset($cartChangeResult) && property_exists($cartChangeResult, 'errors') && count($cartChangeResult->errors) > 0) { ?>
<div>Errors from previous operation:</div>
<ul>
  <?php foreach ($cartChangeResult->errors as $err) {
  echo "<li>$err</li>";
} ?>
</ul>
<br/>
<br/>
<br/>
  <?php } ?>

<div>
  Enter an item to add to the cart. Try BONE, TSHIRT, PDF, item, Baseball, Baseball Bat, PrinterLaser<br/>
  Try entering a large quantity of BONE items, like a quantity of 55 ... see what happens.
</div>
<form method='POST' action='./update_items_3.0.php'>
  <table>
    <thead>
    <tr>
      <th>No.</th>
      <th>Item</th>
      <th>Quantity</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <td colspan='2'>
        <input type='submit' name='add' value='add'/><input type='button' name='blankFields' value='blank fields' onclick='blankAddFields()'/>
      </td>
    </tr>
    </tfoot>
    <tbody>
    <tr>
      <td>1.</td>
      <td><input type='text' name='item[]' class='addText' size='20' value='BONE'/></td>
      <td><input type='text' name='quantity[]' class='addText' size='5' value='1'/></td>
    </tr>
    <tr>
      <td>2.</td>
      <td><input type='text' name='item[]' class='addText' size='20' value='PDF'/></td>
      <td><input type='text' name='quantity[]' class='addText' size='5' value='2'/></td>
    </tr>
    <tr>
      <td>3.</td>
      <td><input type='text' name='item[]' class='addText' size='20' value='TSHIRT'/></td>
      <td><input type='text' name='quantity[]' class='addText' size='5' value='3'/></td>
    </tr>
    <tr>
      <td>4.</td>
      <td><input type='text' name='item[]' class='addText' size='20' value='item'/></td>
      <td><input type='text' name='quantity[]' class='addText' size='5' value='4'/></td>
    </tr>
    <tr>
      <td>5.</td>
      <td><input type='text' name='item[]' class='addText' size='20' value=''/></td>
      <td><input type='text' name='quantity[]' class='addText' size='5'/></td>
    </tr>
    </tbody>

  </table>
</form>
<br/>
<br/>

<h1>Items in Cart</h1>

<form method='POST' id='deleteForm' action='./update_items_3.0.php'>
  <input type='hidden' name='item' id='deleteItem' value=''/>
  <input type='hidden' name='deleteItem' value='yes'/>
</form>

<form method='POST' action='./update_items_3.0.php'>
  <table>
    <thead>
    <tr>
      <th>Thumbnail</th>
      <th>Item</th>
      <th>Description</th>
      <th align='right'>Cost</th>
      <th align='right'>Discounted Cost</th>
      <th align='right'>Quantity</th>
      <th>&nbsp;</th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <td colspan='2'>
        <input type='button' onclick='increaseBy2()' name='increment' value='increment by 2'/><input type='submit' name='update' value='update'/><input type='submit' name='clearCart' value='clear cart'/>
      </td>
    </tr>
    </tfoot>
    <tbody>
<?php
        if (!is_null($items)) {
  usort($items, "compareItemsById");
  foreach ($items as $item) {
    $cost = sprintf("%01.2f", $item->unitCost);
    $unitCostWithDiscount = sprintf("%01.2f", $item->unitCostWithDiscount);
    echo "<tr>";
    echo "<td><img src='$item->defaultThumbnailUrl' alt='thumbnail'/></td>";
    echo "<td><input type='hidden' name='item[]' value='$item->itemId'/>$item->itemId</td>";
    echo "<td>$item->description</td>";
    echo "<td align='right'>$cost</td>";
    echo "<td align='right'>$unitCostWithDiscount</td>";
    echo "<td align='right'><input type='text' size='4' name='quantity[]' value='$item->quantity' class='qty'/></td>";
    echo "<td><input type='button' value='delete' onclick='deleteAnItem(\"$item->itemId\")'/></td>";
    echo '</tr>';
  }
}
?>
    </tbody>
  </table>
</form>
<br/>

<table>
  <tr>
    <td id='shippingMethodsContainer'></td>
  </tr>
</table>
<div id='shippingMethods'>
  Shipping methods will display here if the cart has items, but estimateShipping() takes a long time.
</div>

<br/>
<br/>
</body>
</html>
