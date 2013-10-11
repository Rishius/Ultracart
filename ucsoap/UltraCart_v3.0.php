<?php
// this class provides a robust interface hiding the soap implementation and
// making it so my editor quits yelling about the unknown methods and properties

function ucCompareItemsById($a, $b)
{
  if (strtoupper($a->itemId) == strtoupper($b->itemId)) {
    return 0;
  }
  return (strtoupper($a->itemId) < strtoupper($b->itemId)) ? -1 : 1;
}

// this only works for Apache.  If you're using IIS, you'll need to come up with something else.
function ucCurrentUrl()
{
  $pageURL = 'http';
  if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    $pageURL .= "s";
  }
  $pageURL .= "://";
  if (isset($_SERVER["SERVER_PORT"]) && $_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
  } else {
    $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
  }
  return $pageURL;
}


class CartOperationResult
{
  public $errorMessages = array();
  public $wasSuccessful = true;
  public $returnValue = null;
}

class CartGoogleInfo
{
  public $enabled = false;
  public $imageUrl = '';
  public $imageAltText = '';
}

class CartPayPalInfo
{
  public $enabled = false;
  public $imageUrl = '';
  public $imageAltText = '';
}

class UltraCart
{
  public $cart = null;
  public $merchantId = null;
  public $hasCart = false;
  public $hasItems = false;
  public $ucServiceUrl = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
  public $soapClient = null;
  public $messages = array();
  public $loggedIn = false;

  public function __construct($merchantId)
  {
    $this->merchantId = $merchantId;
    $this->soapClient = new SoapClient($this->ucServiceUrl);
    /*** Check if there's already a cart ***/
    if (!isset($_COOKIE["cartId"])) {
      $cartChangeResult = $this->soapClient->createCart($merchantId);
      $cart = $cartChangeResult->cart;
      if (!is_null($cart)) {
        $this->cart = $cart;
        setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
        $this->hasCart = true;
        $this->updateItemState();

      }
    } else {
      $cartId = $_COOKIE["cartId"];
      $cartChangeResult = $this->soapClient->getCart($merchantId, $cartId);
      $cart = $cartChangeResult->cart;

      // after placing an order, the cartId cookie will still be present, but will no be valid.  so if the $cart object
      // is null, then create the cart explicitly without cookie.
      if (is_null($cart)) {
        $cartChangeResult = $this->soapClient->createCart($merchantId);
        $cart = $cartChangeResult->cart;
        if (!is_null($cart)) {
          setcookie('cartId', $cart->cartId, time() + 60 * 60 * 24 * 30);
        }
      }

      $this->hasCart = !is_null($cart);
      if ($this->hasCart) {
        $this->cart = $cart;
        $this->updateItemState();
      }
    }

    $this->updateLoginState();
    //var_dump($cartChangeResult);

  }

  public function printRawCart()
  {
    echo '<pre>';
    echo 'Cart Object:<br />';
    echo "<hr />";
    ob_start();
    var_dump($this->cart);
    $a = ob_get_contents();
    ob_end_clean();
    echo htmlspecialchars($a, ENT_QUOTES);
    echo "<hr />";
    echo '</pre>';
  }


  // updates the hasItems flag, and re-sorts the items.  this is a good practice as many soap calls affect
  // items indirectly.  And it's fast enough to call.
  private function updateItemState()
  {
    $this->hasItems = $this->hasCart && property_exists($this->cart, 'items') && count($this->cart->items) > 0;
    if ($this->hasItems) {
      usort($this->cart->items, "ucCompareItemsById");
    }
  }

  private function updateLoginState()
  {
    if ($this->hasCart && property_exists($this->cart, 'loggedIn')) {
      $this->loggedIn = $this->cart->loggedIn;
    }
  }


  public function registerCustomer($email, $password)
  {
    $result = new CartOperationResult();
    try {
      $cartChangeResult = $this->soapClient->establishCustomerProfileImmediately($this->merchantId, $this->cart, $email, $password);
      $cart = $cartChangeResult->cart;
      if (!is_null($cart)) {
        $this->cart = $cart;
        $this->hasCart = true;
      } else {
        $result->wasSuccessful = false;
        $result->errorMessages = $cartChangeResult->errors;
      }

      $this->updateLoginState();
      $this->updateItemState();

      // to be successful, the loggedIn property should be true.
      if ($this->loggedIn) {
        $result->wasSuccessful = true;
      } else {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'Registration was successful, but customer is not logged in.  Strange.');
      }


    } catch (SoapFault $e) {
      $pos = strpos($e->getMessage(), 'A customer profile already exists');
      if ($pos !== false) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'Registration failed. This email address is already registered.');
      } else {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'An unexpected error prevented customer registration.  Please contact support.');
        error_log('SoapFault during UltraCart->registerCustomer:' . $e->getMessage());
      }
    }
    return $result;
  }

  public function loginCustomer($email, $password)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->loginCustomer($this->merchantId, $this->cart, $email, $password);
    $cart = $cartChangeResult->cart;
    if (!is_null($cart)) {
      $this->cart = $cart;
      $this->hasCart = true;

      $this->updateLoginState();
      $this->updateItemState();

      // to be successful, the loggedIn property should be true.
      if ($this->loggedIn) {
        $result->wasSuccessful = true;
      } else {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'Login was successful, but customer is not logged in.  Strange.');
      }

    } else {
      $result->wasSuccessful = false;
      $result->errorMessages = $cartChangeResult->errors;
      array_push($result->errorMessages, 'Login failed.  Invalid email address or password.');
    }

    return $result;
  }

  public function logoutCustomer()
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->logoutCustomer($this->merchantId, $this->cart);
    $cart = $cartChangeResult->cart;
    if (!is_null($cart)) {
      $this->cart = $cart;
      $this->hasCart = true;
    } else {
      $result->wasSuccessful = false;
      $result->errorMessages = $cartChangeResult->errors;
    }

    $this->updateLoginState();
    $this->updateItemState();

    // to be successful, the loggedIn property should now be false
    if (!$this->loggedIn) {
      $result->wasSuccessful = true;
    } else {
      $result->wasSuccessful = false;
      array_push($result->errorMessages, 'Logout was successful, but customer is still showing as logged in.  Strange.');
    }

    return $result;
  }


  public function addItems(array $items, array $quantities)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->addItemByItemIds($this->merchantId, $this->cart, $items, $quantities);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
    }
    if (property_exists($cartChangeResult, 'cart')) {
      $this->cart = $cartChangeResult->cart;
      $this->hasCart = true;
    }
    $this->updateItemState();

    return $result;
  }


  public function deleteItem($item)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->removeItem($this->merchantId, $this->cart, $item);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }
    $this->updateItemState();

    return $result;
  }


  public function applyCoupon($couponCode)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->applyCoupon($this->merchantId, $this->cart, $couponCode);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }
    $this->updateItemState();

    return $result;
  }


  public function removeCoupon($couponCode)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->removeCoupon($this->merchantId, $this->cart, $couponCode);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }
    $this->updateItemState();

    return $result;
  }


  public function updateItems(array $cartItems)
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->updateItems($this->merchantId, $this->cart, $cartItems);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }
    $this->updateItemState();

    return $result;
  }


  public function updateCart()
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->updateCart($this->merchantId, $this->cart);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }
    $this->updateItemState();
  }

  public function validate($checks = array())
  {
    $result = new CartOperationResult();
    $errors = null;
    if (count($checks) > 0) {
      $validationResult = $this->soapClient->validate($this->merchantId, $this->cart, $checks);
    } else {
      $validationResult = $this->soapClient->validate($this->merchantId, $this->cart);
    }

    if (count($validationResult->validationFailures) > 0) {
      $result->wasSuccessful = false;
      foreach ($validationResult->validationFailures as $err) {
        array_push($result->errorMessages, $err);
      }
    }
    if (property_exists($validationResult, 'errors')) {
      $result->wasSuccessful = false;
      foreach ($validationResult->errors as $err) {
        array_push($result->errorMessages, $err);
      }
    }


    return $result;
  }


  public function estimateShipping()
  {
    $shippingEstimateResult = $this->soapClient->estimateShipping($this->merchantId, $this->cart);
    return $shippingEstimateResult->estimates;
    // not looking at the errors here. might want to.
  }


  public function clearCart()
  {
    $result = new CartOperationResult();
    $cartChangeResult = $this->soapClient->clearItems($this->merchantId, $this->cart);
    if (!is_null($cartChangeResult)) {
      if (property_exists($cartChangeResult, 'errors')) {
        $result->wasSuccessful = false;
        foreach ($cartChangeResult->errors as $err) {
          array_push($result->errorMessages, $err);
        }
      }
      if (property_exists($cartChangeResult, 'cart')) {
        $this->cart = $cartChangeResult->cart;
        $this->hasCart = true;
      }
    }

    $this->updateItemState();
    return $result;
  }

  public function getItems()
  {
    $dummy = array();
    if (is_null($this->cart)) {
      return $dummy;
    }

    if (!property_exists($this->cart, 'items')) {
      return $dummy;
    }

    return $this->cart->items;
  }

  public function getCoupons()
  {
    $dummy = array();
    if (is_null($this->cart)) {
      return $dummy;
    }

    if (!property_exists($this->cart, 'coupons')) {
      return $dummy;
    }

    return $this->cart->coupons;
  }


  public function getGoogleInfo()
  {
    $googleInfo = new CartGoogleInfo();
    if ($this->hasCart) {
      if (property_exists($this->cart, 'googleCheckoutCompatible')) {
        $googleInfo->enabled = $this->cart->googleCheckoutCompatible;
      }

      if (property_exists($this->cart, 'googleCheckoutButtonUrl')) {
        $googleInfo->imageUrl = $this->cart->googleCheckoutButtonUrl;
      }
      if (property_exists($this->cart, 'googleCheckoutButtonAltText')) {
        $googleInfo->imageAltText = $this->cart->googleCheckoutButtonAltText;
      }
    }
    return $googleInfo;
  }


  public function getPayPalInfo()
  {
    $payPalInfo = new CartPayPalInfo();
    if ($this->hasCart) {
      if (property_exists($this->cart, 'hasPayPal')) {
        $payPalInfo->enabled = $this->cart->hasPayPal;
      }

      if (property_exists($this->cart, 'payPalButtonUrl')) {
        $payPalInfo->imageUrl = $this->cart->payPalButtonUrl;
      }
      if (property_exists($this->cart, 'payPalButtonAltText')) {
        $payPalInfo->imageAltText = $this->cart->payPalButtonAltText;
      }
    }
    return $payPalInfo;
  }

  public function checkoutGoogle()
  {
    $result = new CartOperationResult();
    $result->wasSuccessful = false;
    if ($this->hasCart) {
      $this->updateCart();
      $handOffResult = $this->soapClient->googleCheckoutHandoff($this->merchantId, $this->cart, ucCurrentUrl(), 'checkout_err[]');
      if (is_null($handOffResult)) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'Google Checkout is unavailable at this time.  Sorry.');
      } else {
        if (property_exists($handOffResult, 'errors') && count($handOffResult->errors) > 0) {
          $result->wasSuccessful = false;
          foreach ($handOffResult->errors as $err) {
            array_push($result->errorMessages, $err);
          }
        } else {
          $result->wasSuccessful = true;
          $result->returnValue = $handOffResult->redirectToUrl;
        }
      }
    }
    return $result;
  }


  public function checkoutPayPal()
  {
    $result = new CartOperationResult();
    $result->wasSuccessful = false;
    if ($this->hasCart) {
      $this->updateCart();
      $handOffResult = $this->soapClient->paypalHandoff($this->merchantId, $this->cart, ucCurrentUrl(), 'checkout_err[]');
      if (is_null($handOffResult)) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'PayPal Checkout is unavailable at this time.  Sorry.');
      } else {
        if (property_exists($handOffResult, 'errors') && count($handOffResult->errors) > 0) {
          $result->wasSuccessful = false;
          foreach ($handOffResult->errors as $err) {
            array_push($result->errorMessages, $err);
          }
        } else {
          $result->wasSuccessful = true;
          $result->returnValue = $handOffResult->redirectToUrl;
        }
      }
    }
    return $result;
  }


  public function checkout()
  {
    $result = new CartOperationResult();
    $result->wasSuccessful = false;
    if ($this->hasCart) {
      $this->updateCart();
      $handOffResult = $this->soapClient->checkoutHandoff($this->merchantId, $this->cart, ucCurrentUrl(), 'checkout_err[]');
      if (is_null($handOffResult)) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'Checkout is unavailable at this time.  Sorry.');
      } else {
        if (property_exists($handOffResult, 'errors') && count($handOffResult->errors) > 0) {
          $result->wasSuccessful = false;
          foreach ($handOffResult->errors as $err) {
            array_push($result->errorMessages, $err);
          }
        } else {
          $result->wasSuccessful = true;
          $result->returnValue = $handOffResult->redirectToUrl;
        }
      }
    }
    return $result;
  }


}

?>