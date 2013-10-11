<?php
  $url = 'https://secure.ultracart.com/axis/services/CheckoutAPIV3?wsdl';
  $client = new SoapClient($url);
  //$client = new SoapClient($url, array('trace' => TRUE));  // for verbosity, like __getFunctions() below
  //print_r($client->__getFunctions());
  $merchantId = 'DEMO';
  $itemIds = array('Bone', 'Hunter', 'PDF');
  $itemsResult = $client->getItems($merchantId, $itemIds);
  $items = $itemsResult->items;
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

  <?php if(isset($itemsResult) && property_exists($itemsResult, 'errors') && count($itemsResult->errors) > 0){ ?>
    <div>Errors from previous operation:</div>
    <ul>
    <?php foreach($itemsResult->errors as $err){
      echo "<li>$err</li>";
    } ?>
    </ul>
    <br />
    <br />
    <br />
  <?php } ?>

  <div>Here's a list of Items.  Not all attributes are shown.</div>
  <table>
    <thead>
      <tr>
        <th>Thumbnail</th>
        <th>Item</th>
        <th align='right'>Oid</th>
        <th>Description</th>
        <th align='right'>Cost</th>
        <th>In Stock</th>
        <th align='right'>Avail. Qty</th>
      </tr>
    </thead>
    <tbody>
    <?php
      if(!is_null($items)){
        foreach($items as $item){
          $cost = sprintf("%01.2f", $item->cost);
          echo "<tr>";
          echo "<td><img src='$item->defaultThumbnailUrl' alt='thumbnail'/></td>";
          echo "<td>$item->itemId</td>";
          echo "<td align='right'>$item->itemOid</td>";
          echo "<td>$item->description</td>";
          echo "<td align='right'>$cost</td>";
          echo "<td align='center'>$item->inStock</td>";
          echo "<td align='right'>$item->availableQuantity</td>";
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