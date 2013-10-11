<html>
<body>
<pre>
<?php
  $url = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
  $client = new SoapClient($url, array('trace' => TRUE));  // for verbosity, like __getFunctions() below
  print_r($client->__getFunctions());
?>
</pre>
</body>
</html>
