<?php


class ItemResult
{
  public $errorMessages = array();
  public $wasSuccessful = true;
}

class UltraCartItem{
  public $merchantId = null;
  public $login = null;
  public $password = null;
  public $ucServiceUrl = 'https://secure.ultracart.com/axis/services/ItemManagementServiceV1?wsdl';
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


  public function createSimpleKit($kitItemId, $description, $cost, array $components, array $componentQuantities)
  {

    $result = new ItemResult();
    $result->wasSuccessful = false;
    try{
      $soapResult = $this->soapClient->createSimpleKit($this->credentials, $kitItemId, $description, $cost, $components, $componentQuantities);

      // comment this out if you don't want to see the details.
      $this->printRawResult($soapResult);

      if (!is_null($soapResult)) {
        $result->wasSuccessful = (bool) $soapResult;
      }

    } catch (SoapFault $e) {
      $result->wasSuccessful = false;

      $knownError = false;
      $pos = strpos($e->getMessage(), 'Invalid Login');
      if ($pos !== false) {
        array_push($result->errorMessages, 'Invalid Login. Please verify credentials.');
        $knownError = true;
      }

      // I could check for the other errors, but it's easy enough just to check the logs.
      // Possible Exception Messages:
      //kitItemId can not be null
      //description can not be null
      //cost can not be null
      //componentItemIds can not be null
      //componentQuantities can not be null
      //componentItemIds must contain one or more elements
      //Array length for componentItemIds and componentQuantities must match
      //A kit or item with the same item id already exists
      //Item XXYYZZ does not exist in your UltraCart account   (invalid kit component)
      //Kits can not have components that are also kits

      if(!$knownError) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'An unexpected error prevented item creation.  Please contact support.');
        error_log('SoapFault during UltraCartItem->createSimpleKit:' . $e->getMessage());
      }
    }



    return $result;
  }



  public function createSimpleKit2($kitItemId, $description, $cost, array $components, array $componentQuantities, $quickbooksCode, $quickbooksClass, array $componentUnitValues)
  {

    $result = new ItemResult();
    $result->wasSuccessful = false;
    try{
      $soapResult = $this->soapClient->createSimpleKit2($this->credentials, $kitItemId, $description, $cost, $components, $componentQuantities, $quickbooksCode, $quickbooksClass, $componentUnitValues);

      // comment this out if you don't want to see the details.
      $this->printRawResult($soapResult);

      if (!is_null($soapResult)) {
        $result->wasSuccessful = (bool) $soapResult;
      }

    } catch (SoapFault $e) {
      $result->wasSuccessful = false;

      $knownError = false;
      $pos = strpos($e->getMessage(), 'Invalid Login');
      if ($pos !== false) {
        array_push($result->errorMessages, 'Invalid Login. Please verify credentials.');
        $knownError = true;
      }

      // I could check for the other errors, but it's easy enough just to check the logs.
      // Possible Exception Messages:
      //kitItemId can not be null
      //description can not be null
      //cost can not be null
      //componentItemIds can not be null
      //componentQuantities can not be null
      //componentItemIds must contain one or more elements
      //Array length for componentItemIds and componentQuantities must match
      //A kit or item with the same item id already exists
      //Item XXYYZZ does not exist in your UltraCart account   (invalid kit component)
      //Kits can not have components that are also kits

      if(!$knownError) {
        $result->wasSuccessful = false;
        array_push($result->errorMessages, 'An unexpected error prevented item creation.  Please contact support.');
        error_log('SoapFault during UltraCartItem->createSimpleKit:' . $e->getMessage());
      }
    }



    return $result;
  }

}


?>