<?php
 require('./UltracartLegacyAPI_v0.1.php');
?>
<html>
<head>
  <style type='text/css'>
    * {
      font-family: tahoma, serif;
      font-size: 12px;
    }
  </style>
</head>
<body>

<?php
$merchantId = '59362';
$login = 'geeklytech';
$password = 'G33klydev';
$uc = new UltraCartLegacyAPI($merchantId, $login, $password);
$originalItem = 'BONE';

//$merchantId = 'XDWEX';
//$login = 'perry';
//$password = 'password';
//$uc = new UltraCartLegacyAPI($merchantId, $login, $password);
//$originalItem = 'TEST1';


$newItem = 'TEST' . rand();

// Step 1. Get the new item
$item = $uc->queryItem($originalItem);

//  Step 2. Pull out the XML and replace the merchant_item_id
$itemXml = $item->asXML();
$newItemXml = preg_replace("#<merchant_item_id>" . $originalItem . "</merchant_item_id>#imU", '<merchant_item_id>' . $newItem . '</merchant_item_id>', $itemXml);

// Step 3. Create the new item.
$itemXml2 = 'No item created.';
$createResult = $uc->createItem($newItemXml);

?>


<h1><label for='orig'>XML for original item</label></h1>
<textarea id='orig' rows="20" cols="150">
  <?php print_r($itemXml); ?>
</textarea>
<br/>
<br/>

<h1><label for='orig'>XML for new item (going to be passed to CreateItem)</label></h1>
<textarea id='orig' rows="20" cols="150">
  <?php print_r($newItemXml); ?>
</textarea>
<br/>
<br/>


<?php if ($createResult) { ?>
// Step 4. If the item was created, query the new item.
$itemXml2 = $uc->queryItem($newItem);
?>
<h1><label for='orig'>XML for new item (returned from QueryItem)</label></h1>
<textarea id='orig' rows="20" cols="150">
  <?php print_r($itemXml2->asXML()); ?>
</textarea>
<br/>
<br/>

  <?php } else {
  echo 'create failed, see logs';
}?>

</body>
</html>