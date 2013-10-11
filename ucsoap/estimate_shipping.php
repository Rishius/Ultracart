<?php
  require_once './UltraCart_v3.0.php';
  header("Content-Type: application/json");
  $merchantId = '59362';
  $uc = new UltraCart($merchantId);

  if($uc->hasCart){
    if(isset($_GET['zip']) && isset($_GET['city']) && isset($_GET['state'])){
      error_log("updating location information");
      $uc->cart->shipToPostalCode = $_GET['zip'];
      $uc->cart->shipToCity = $_GET['city'];
      $uc->cart->shipToState = $_GET['state'];
      $uc->updateCart();
    }

    $ucShippingEstimate = $uc->estimateShipping();
    echo $ucShippingEstimate;
    if(!is_null($ucShippingEstimate)){
      // convert object array to simple array of arrays for clean json output
      $shippingEstimate = array();
      foreach($ucShippingEstimate as $ucEstimate){
        $estimate = array(
          'comment' => $ucEstimate->comment,
          'cost' => $ucEstimate->cost,
          'costBeforeDiscout' => $ucEstimate->costBeforeDiscount,
          'discount' => $ucEstimate->discount,
          'displayName' => $ucEstimate->displayName,
          'estimatedDelivery' => $ucEstimate->estimatedDelivery,
          'name' => $ucEstimate->name,
          'tax' => $ucEstimate->tax
          );
        array_push($shippingEstimate, $estimate);
      }

      echo json_encode($shippingEstimate);
    } else {
      echo json_encode("no shipping methods found");
    }
  } else {
    echo json_encode("no cart was found.  could not get shipping methods");
  }
?>