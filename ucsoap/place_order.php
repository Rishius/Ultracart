<?php

  require './UltraCartOrder_v0.1.php';
  set_time_limit(300);

  $merchantId = 'DEMO';
  $login = 'perry';
  $password = 'passwordgoeshere';
  $themeCode = '';
  $ssoOid = 'F105C77E970FD801301E3DF870051700';
  $ccOid = '49475FE5A35EE201301E3DF879051700';

  $items = array('Bone', 'TSHIRT', 'PDF');
  $quantities = array(3, 4, 5);
  $costs = array(20.54, 2.99, 19.50);

  $uc = new UltraCartOrder($merchantId, $login, $password);
?>
<html>
  <head>
    <style type='text/css'>
      * { font-family: tahoma, serif; font-size:12px;}
    </style>
  </head>
  <body>
    <?php
      // I make the calls down here so I can dump raw results, if desired.
      $result1 = $uc->placeOrderWithArbitraryCosts($ssoOid, $ccOid, $themeCode, $items, $quantities, $costs);
      $result2 = $uc->placeOrder($ssoOid, $ccOid, $themeCode, $items, $quantities);
    ?>


      <strong>Result of Place Order with Arbitrary Costs</strong><br />
      Success: <?php echo $result1->wasSuccessful; ?><br />
      Order Id: <?php echo $result1->orderId; ?><br />
      Receipt Text:<br />
      <pre style='font-family: courier, serif; color:blue'>
<?php echo $result1->receiptText; ?>
      </pre>
      Any errors will print below:<br />
      <?php foreach ($result1->errorMessages as $err) { echo "$err;<br />"; } ?>

      <br />
      <br />
      <hr />
      <br />
      <br />

      <strong>Result of Place Order</strong><br />
      Success: <?php echo $result2->wasSuccessful; ?><br />
      Order Id: <?php echo $result2->orderId; ?><br />
    Receipt Text:<br />
    <pre style='font-family: courier, serif; color:blue'>
<?php echo $result2->receiptText; ?>
    </pre>
      Any errors will print below:<br />
      <?php foreach ($result2->errorMessages as $err) { echo "$err;<br />"; } ?>

  </body>
</html>