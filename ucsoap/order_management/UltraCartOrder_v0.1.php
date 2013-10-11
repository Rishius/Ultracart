<?php

class OrderResult
{
  public $errorMessages = array();
  public $wasSuccessful = true;
  public $orderId = null;
  public $receiptText = null;
}

class UltraCartOrder{
  public $merchantId = null;
  public $login = null;
  public $password = null;
  public $ucServiceUrl = 'https://secure.ultracart.com/axis/services/OrderManagementServiceV1?wsdl';
  public $soapClient = null;
  public $credentials = null;

  public function __construct($merchantId, $login, $password)
  {
    $this->merchantId = $merchantId;
    $this->login = $login;
    $this->password = $password;

    $this->credentials = array('merchantId' => $merchantId, 'login' => $login, 'password' => $password);
    $this->soapClient = new SoapClient($this->ucServiceUrl);
  }


  public function printRawResult($result)
  {
    echo '<pre>';
    echo 'SOAP Result:<br />';
    echo "<hr />";
    ob_start();
    var_dump($result);
    $a = ob_get_contents();
    ob_end_clean();
    echo htmlspecialchars($a, ENT_QUOTES);
    echo "<hr />";
    echo '</pre>';
  }


  /**
   * @param  $customerOid customer profile single sign-on oid (string)
   * @param  $creditCardOid customer profile credit card single sign-on oid (string)
   * @param  $themeCode screen branding theme code (string)
   * @param array $items item ids (array of strings)
   * @param array $quantities (array of integers)
   * @param array $costs arbitrary unit costs (array of doubles)
   * @return OrderResult the result of the request
   */
  public function placeOrderWithArbitraryCosts($customerOid, $creditCardOid, $themeCode, array $items, array $quantities, array $costs)
  {
    $result = new OrderResult();
    $soapResult = $this->soapClient->placeSingleSignonOrder2($this->credentials, $customerOid, $creditCardOid, $items, $quantities, $themeCode, $costs);
    //$this->printRawResult($soapResult);


    if (!is_null($soapResult)) {
      if (property_exists($soapResult, 'errorMessages') && !is_null($soapResult->errorMessages)) {
        $result->wasSuccessful = false; // yeah, I know there's a property, but here's a double check.
        foreach ($soapResult->errorMessages as $err) {
          array_push($result->errorMessages, $err);
        }
      }
    }

    if(property_exists($soapResult, 'successful')){
      $result->wasSuccessful = $soapResult->successful;
    }


    if (property_exists($soapResult, 'orderId')) {
      $result->orderId = $soapResult->orderId;
    }

    if (property_exists($soapResult, 'receiptText')) {
      $result->receiptText = $soapResult->receiptText;
    }

    return $result;
  }



  /**
   * @param  $customerOid customer profile single sign-on oid (string)
   * @param  $creditCardOid customer profile credit card single sign-on oid (string)
   * @param  $themeCode screen branding theme code (string)
   * @param array $items item ids (array of strings)
   * @param array $quantities (array of integers)
   * @return OrderResult the result of the request
   */
  public function placeOrder($customerOid, $creditCardOid, $themeCode, array $items, array $quantities)
  {
    $result = new OrderResult();
    $soapResult = $this->soapClient->placeSingleSignonOrder($this->credentials, $customerOid, $creditCardOid, $items, $quantities, $themeCode);
    //$this->printRawResult($soapResult);

    if (!is_null($soapResult)) {
      if (property_exists($soapResult, 'errorMessages') && !is_null($soapResult->errorMessages)) {
        $result->wasSuccessful = false; // yeah, I know there's a property, but here's a double check.
        foreach ($soapResult->errorMessages as $err) {
          array_push($result->errorMessages, $err);
        }
      }
    }

    if(property_exists($soapResult, 'successful')){
      $result->wasSuccessful = $soapResult->successful;
    }


    if (property_exists($soapResult, 'orderId')) {
      $result->orderId = $soapResult->orderId;
    }

    if (property_exists($soapResult, 'receiptText')) {
      $result->receiptText = $soapResult->receiptText;
    }

    return $result;
  }


}


?>