<?php


defined('UC_SUCCESS') or define('UC_SUCCESS', 0);
defined('UC_UNKNOWN') or define('UC_UNKNOWN', 1);
defined('UC_ERROR_MISSING_PARAMETER') or define('UC_ERROR_MISSING_PARAMETER', 2);
defined('UC_ERROR_INVALID_LOGIN') or define('UC_ERROR_INVALID_LOGIN', 3);
defined('UC_ERROR_INADEQUATE_PERMISSIONS') or define('UC_ERROR_INADEQUATE_PERMISSIONS', 4);
defined('UC_ERROR_INVALID_PARAMETER_VALUE') or define('UC_ERROR_INVALID_PARAMETER_VALUE', 5);
defined('UC_ERROR_INTERNAL') or define('UC_ERROR_INTERNAL', 6);
defined('UC_ERROR_HTTPS_REQUIRED') or define('UC_ERROR_HTTPS_REQUIRED', 7);

class ApiResponse
{
  var $resultCode = -1;
  var $resultMessage = '';
  var $resultData = '';
  var $data = null;
  var $validData = true;

  function __construct($data)
  {
    $this->data = $data;
    $xml = simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);

    if (!$xml) {
      $this->validData = false;
      return;
    }

    $resultCode = (int)$xml->ResultCode;
    $resultMessage = $xml->ResultMessage;
    $resultData = $xml->ResultData;

    // check to see if resultData is encoded
    if (base64_decode($resultData, true)) {
      $resultData = base64_decode($resultData, true);
    }

    // check to see if the resultData is an xml chunk.  if so, convert to an xml object.
    if (simplexml_load_string($resultData, 'SimpleXMLElement', LIBXML_NOCDATA)) {
      $resultData = simplexml_load_string($resultData, 'SimpleXMLElement', LIBXML_NOCDATA);

    }

    $this->resultCode = $resultCode;
    $this->resultMessage = $resultMessage;
    $this->resultData = $resultData;

  }

}

class UltraCartLegacyAPI
{

  var $xmlResult;
  var $merchantId = '';
  var $login = '';
  var $password = '';
  var $baseUrl = '';

  function __construct($merchantId, $login, $password)
  {
    $this->merchantId = $merchantId;
    $this->login = $login;
    $this->password = $password;
    $this->baseUrl = "https://secure.ultracart.com/cgi-bin/UCApi?merchantId=$merchantId&login=$login&password=$password";
  }


/* gets the data from a URL and returns the raw result */
  function make_call($url)
  {
    echo "API Call:<br /><textarea id='orig' rows='4' cols='150'>$url</textarea><br />";
    $ch = curl_init();
    $timeout = 20;
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


    $data = curl_exec($ch);

    if (curl_errno($ch)) {
      print curl_error($ch);
    } else {
      curl_close($ch);
    }
        echo htmlentities($data) . "<br />";
    return $data;
  }

  public function queryItem($itemId)
  {
    $data = $this->make_call($this->baseUrl . "&function=QueryItem&ItemId=" . $itemId);
    $response = new ApiResponse($data);

    if ($response->validData) {
      return $response->resultData;
    } else {
      return false;
    }
  }

  public function createItem($itemXml)
  {
    $data = $this->make_call($this->baseUrl . "&function=CreateItem&Item=" . rawurlencode($itemXml));
    $response = new ApiResponse($data);
    if ($response->resultCode == UC_SUCCESS) {
      return true;
    } else {
      error_log('UltraCartLegacyAPI->createItem result:' . $response->resultMessage . " for this xml: " . $itemXml);
      return false;
    }
  }
}
