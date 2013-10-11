<html>
<body>
<pre>
<?php
    require_once './UltraCart_v3.0.php';

    $merchantId = 'DEMO';
    $uc = new UltraCart($merchantId);

    $items[] = 'BONE';
    $quantities[] = 1;

    $uc->addItems($items, $quantities);
    $cartChangeResult = $uc->getRelatedItems();
    echo 'getRelatedItems():';
    var_dump($cartChangeResult);

    $cartChangeResult = $uc->getRelatedItems2('BONE');
    echo 'getRelatedItems2():';
    var_dump($cartChangeResult);

    ?>
  </pre>
</body>
</html>