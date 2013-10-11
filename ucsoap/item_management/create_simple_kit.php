<?php

require './UltraCartItem_v0.2.php';
set_time_limit(300);

$merchantId = '59362';
$login = 'geeklytech';
$password = 'G33klydev';


$kitItem = 'PERRYTEST';
$description = 'This is a test kit created via SOAP interface';
$cost = 64.23;
$items = array('Bone', 'TSHIRT', 'PDF');
$quantities = array(3, 4, 5);

$kitItem2 = 'PERRYTEST2';
$description2 = 'This is a test kit created via SOAP interface using createSimpleKit2';
$quickbooksCode = 'MYCODE';
$quickbooksClass = 'MyQBClass';
$componentUnitValues = array( 18.00, 18.00, 20.23 );


$uc = new UltraCartItem($merchantId, $login, $password);
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
      // I make the calls down here so I can dump raw results, if desired.
$result1 = $uc->createSimpleKit($kitItem, $description, $cost, $items, $quantities);
?>
<strong>Result of Create Simple Kit</strong><br/>
<?php echo $result1->wasSuccessful; ?><br/>
Any errors will print below:<br/>
<?php foreach ($result1->errorMessages as $err) {
  echo "$err<br />";
} ?>


<br />
<br />
<br />


<?php
      // I make the calls down here so I can dump raw results, if desired.
$result1 = $uc->createSimpleKit2($kitItem2, $description2, $cost, $items, $quantities, $quickbooksCode, $quickbooksClass, $componentUnitValues);
?>
<strong>Result of Create Simple Kit2</strong><br/>
<?php echo $result1->wasSuccessful; ?><br/>
Any errors will print below:<br/>
<?php foreach ($result1->errorMessages as $err) {
  echo "$err<br />";
} ?>

</body>
</html>