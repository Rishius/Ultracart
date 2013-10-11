<?php
  require_once './UltraCart_v1.1.php';
  header("Content-Type: text/json");
  $merchantId = 'DEMO';
  $uc = new UltraCart($merchantId);

  if($uc->hasCart && isset($_POST['shippingMethod'])){
    $uc->cart->shippingMethod = $_POST['shippingMethod'];

    // since this method is called via ajax async in the background of the web page
    // I don't check the result of the update or take any actions.  There's nothing
    // to do if it fails.  But it shouldn't fail.  :)  I encode the result just for debugging.
    $result = $uc->updateCart();
  } else {
    echo json_encode("no cart was found.  could not update shipping methods");
  }
?>