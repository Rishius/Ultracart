<?php
require_once './UltraCart_v3.0.php';
define('CURRENCY_PREFIX', '$');  // could examine request and adjust dynamically if desired.
define('CURRENCY_SUFFIX', '');
function currency($num){
  return CURRENCY_PREFIX . (string)$num . CURRENCY_SUFFIX;
}

function saveFields(UltraCart $uc){
  if($uc->hasCart){
    $c = $uc->cart;

    $c->shipToAddress1 = clean("shippingAddress1");
    $c->shipToAddress2 = clean("shippingAddress2");
    $c->shipToCity = clean("shippingCity");
    $c->shipToCompany = clean("shippingCompany");
    $c->shipToCountry = clean("shippingCountry");
    $c->shipToEveningPhone = clean("shippingPhone");
    $c->shipToFirstName = clean("shippingFirstName");
    $c->shipToLastName = clean("shippingLastName");
    $c->shipToPhone = clean("shippingPhone");
    $c->shipToPostalCode = clean("shippingZip");
    $c->shipToResidential = clean("shippingResidential");
    $c->shipToState = clean("shippingState");
    $c->shipToTitle = clean("shippingTitle");
    $c->email = clean("email");
    $c->emailConfirm = clean("emailConfirm");

    $c->billToAddress1 = clean("billingAddress1",$c->shipToAddress1);
    $c->billToAddress2 = clean("billingAddress2",$c->shipToAddress2);
    $c->billToCity = clean("billingCity",$c->shipToCity);
    $c->billToCompany = clean("billingCompany",$c->shipToCompany);
    $c->billToCountry = clean("billingCountry",$c->shipToCountry);
    $c->billToDayPhone = clean("billingDayPhone",$c->shipToPhone);
    $c->billToEveningPhone = clean("billingEveningPhone",$c->shipToEveningPhone);
    $c->billToFirstName = clean("billingFirstName",$c->shipToFirstName);
    $c->billToLastName = clean("billingLastName",$c->shipToLastName);
    $c->billToPostalCode = clean("billingZip",$c->shipToPostalCode);
    $c->billToState = clean("billingState",$c->shipToState);
    $c->billToTitle = clean("billingTitle",$c->shipToTitle);

    $c->creditCardExpirationMonth = clean("creditCardExpMonth" );
    $c->creditCardExpirationYear = clean("creditCardExpYear" );
    $c->creditCardNumber = clean("creditCardNumber" );
    $c->creditCardType = clean("creditCardType" );
    $c->creditCardVerificationNumber = clean("creditCardVerificationNumber" );

    $c->mailingListOptIn = clean("mailingList");
  }
}

function clean($input, $valueIfNull = ''){
  if(!isset($_POST[$input])){
    return $valueIfNull;
  }
  return str_replace(chr(173), "", trim($_POST[$input]));
}

// only echos if the value is not null.
function echo_n($val){
  if(!is_null($val)){
    echo $val;
  }
}

function echo_selected($val, $cartval){
  if($val == $cartval){
    echo " selected='true' ";
  }
}

$merchantId = '59362';
$uc = new UltraCart($merchantId);
$result = null;
$msg = null;


if ($uc->hasCart && (isset($_POST['google']) || isset($_POST['google_x']))){
  saveFields($uc);
  // checkout will update cart, so no need to do it here.
  $result = $uc->checkoutGoogle();
  $msg = 'Executing Google Checkout, Status=' . $result->wasSuccessful . ', redirect=' . $result->returnValue;
  if($result->wasSuccessful && $result->returnValue){
    header("Location: " . $result->returnValue);
    exit;
  }
}


if ($uc->hasCart && (isset($_POST['paypal']) || isset($_POST['paypal_x']))){
  saveFields($uc);
  // checkout will update cart, so no need to do it here.
  $result = $uc->checkoutPayPal();
  if($result->wasSuccessful && $result->returnValue){
    header("Location: " . $result->returnValue);
    exit;
  }
}


if ($uc->hasCart && (isset($_POST['checkout']) || isset($_POST['checkout_x']))){
  saveFields($uc);
  // check for email confirmation here to speed things up.
  if($uc->cart->email != $uc->cart->emailConfirm){
    $result = new CartOperationResult();
    $result->wasSuccessful = false;
    array_push($result->errorMessages, 'The Email and Email Confirmation do not match.  Please re-enter your email address before continuing.');
  } else {
    $uc->cart->paymentMethod = 'Credit Card';
    // checkout will update cart, so no need to do it here.
    $result = $uc->checkout();
    if($result->wasSuccessful && $result->returnValue){
      header("Location: " . $result->returnValue);
      exit;
    }
  }
}

if (isset($_POST['add'])) {

  if ($uc->hasCart && isset($_POST['item'])) {
    $newItems = $_POST['item'];
    $quantities = $_POST['quantity'];

    for ($i = count($newItems) - 1; $i >= 0; $i--) {
      if (is_null($newItems[$i]) || strlen(trim($newItems[$i])) == 0) {
        unset($newItems[$i]);
        unset($quantities[$i]);
      }
    }

    $msg = 'Adding ' . count($newItems) . ' Item(s)';
    $result = $uc->addItems($newItems, $quantities);
  }
}


if (isset($_POST['deleteItem'])) {
  if ($uc->hasCart && isset($_POST['item'])) {
    $deleteItem = $_POST['item'];
    $msg = 'Deleted Item ' . $deleteItem;
    $result = $uc->deleteItem($deleteItem);
  }
}


if (isset($_POST['apply_coupon']) || isset($_POST['apply_coupon_x'])) {
  if ($uc->hasCart && isset($_POST['couponCode'])) {
    $msg = 'Applying Coupon Code ' . $_POST['couponCode'];
    $result = $uc->applyCoupon($_POST['couponCode']);
  }
}

if ($uc->hasCart && isset($_POST['deleteCoupon'])) {
    $msg = 'Removed Coupon Code ' . $_POST['deleteCouponCode'];
    $result = $uc->removeCoupon($_POST['deleteCouponCode']);
}


if (isset($_POST['update']) || isset($_POST['update_x'])) {
  if ($uc->hasCart && isset($_POST['item'])) {
    saveFields($uc);
    $updateItems = $_POST['item'];
    $updateQuantities = $_POST['quantity'];

    $updateCount = 0;
    $updateArray = array();
    if ($uc->hasItems) {
      foreach ($uc->getItems() as $cartItem) {
        for ($i = 0; $i < count($updateItems); $i++) {
          if ($updateItems[$i] == $cartItem->itemId && $updateQuantities[$i] != $cartItem->quantity) {
            $cartItem->quantity = $updateQuantities[$i];
            $updateCount++;
          }
        }
        array_push($updateArray, $cartItem);
      }
    }

    $msg = 'Updated ' . $updateCount . ' Item(s)';
    $result = $uc->updateItems($updateArray);
  }
}


if (isset($_POST['clearCart'])) {
  $msg = 'Cleared Cart';
  if (!is_null($cart)) {
    $result = $uc->clearCart();
  }
}

if(is_null($result) && isset($_GET['checkout_err'])){
  $checkoutErrors = $_GET['checkout_err'];
  $result = new CartOperationResult();
  $result->wasSuccessful = false;
  foreach($checkoutErrors as $err){
    array_push($result->errorMessages, $err);
  }
}

$subtotal = $uc->hasCart ? $uc->cart->subtotal : 0;
$subtotalWithDiscount = $uc->hasCart ? $uc->cart->subtotalWithDiscount : 0;
$discount = $uc->hasCart ? $uc->cart->subtotalDiscount : 0;
$tax = $uc->hasCart ? $uc->cart->tax : 0;
$shippingMethod = $uc->hasCart ? $uc->cart->shippingMethod : '';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>UltraCart PHP Cart</title>
  <link href="css/cart_1.0.css" rel="stylesheet" type="text/css"/>
  <script type='text/javascript' src='js/jquery-1.4.2.min.js'></script>
  <script type='text/javascript' src='js/NumberFormat.js'></script>
  <script type='text/javascript'>

    var nf = new NumberFormat(0);
    var nfSuffix = '';
    // USD configuration
    nf.setCurrency(true);
    nf.setCurrencyPosition(nf.LEFT_INSIDE);
    nf.setCurrencyValue('$');
    nf.setNegativeFormat(nf.LEFT_DASH);
    nf.setPlaces(2, true);
    nf.setSeparators(true, nf.COMMA, nf.PERIOD);

    function nfFormat(v) {
      nf.setNumber(v);
      return nf.toFormatted() + nfSuffix;
    }

    /**
     * hides the error messages when the user acknowledges them.  This isn't really a render function, but
     * it's tied to the renderErrors function so it's included here to ensure it's not missed.
     */
    function hideError(){
      jQuery('#error_container').hide();
    }

    var shippingMethods = null;
    var subtotal = <?php echo "$subtotalWithDiscount"; ?>;
    var discount = <?php echo "$discount"; ?>;
    var tax = <?php echo "$tax"; ?>;
    var shippingChoice = '<?php echo "$shippingMethod"; ?>';
    var shipping = 0; // determined later via ajax

    var lastZip = '<?php echo_n($uc->cart->shipToPostalCode) ?>';
    var lastCity = '<?php echo_n($uc->cart->shipToCity) ?>';
    var lastState = '<?php echo_n($uc->cart->shipToState) ?>';

    function updateSummary() {
      //TODO
      // if there are shipping methods,
      // loop through and find the shippingChoice
      // if found, then update the summary
      // will need the total,subtotal, and tax as variables above.
    }



    <?php if ($uc->hasItems) { ?>
      jQuery('document').ready(function() {
        showHide(document.getElementById('billingDifferent'), 'billingTable');
        estimateShipping();

        jQuery('#shippingZip').bind('blur', blurShippingField);
        jQuery('#shippingCity').bind('blur', blurShippingField);
        jQuery('#shippingState').bind('blur', blurShippingField);

      });
    <?php } ?>


    function blurShippingField(){
      var zip = jQuery('#shippingZip').val();
      var city = jQuery('#shippingCity').val();
      var state = jQuery('#shippingState').val();

      if(zip && city && state){
        if(zip != lastZip || city != lastCity || state != lastState){
          lastZip = zip;
          lastCity = city;
          lastState = state;

          estimateShipping({city:city,state:state,zip:zip});
        }
      }

    }

    /**
     * updates the shipping.  if location information is passed in, the cart is updated first to give more accurate results.
     * @param cityStateZip hash (zip, city, state)
     */
    function estimateShipping(cityStateZip){
      var params = {};
      if(cityStateZip){
        params = cityStateZip;
      }

      jQuery.get('http://localhost:8888/ultracart3/estimate_shipping.php',
        params,
        function(result) {
          if (result != null) {

            // unbind any existing options before overwriting to avoid leaks.
            jQuery('[name=shippingMethod]').unbind('.ultraCart');

            // result should be an array of shipping methods
            window.shippingMethods = result;

            // if there isn't a shipping choice, select the first one.
            if(!shippingChoice && window.shippingMethods && window.shippingMethods.length > 0){
              shippingChoice = window.shippingMethods[0].name;
              updateShippingOnServer();
            }

            var html = '';
            for (var i = 0; i < result.length; i++) {
              var method = result[i];
              var checked = (shippingChoice && method.name && shippingChoice == method.name);

              html += "<div class='shippingMethod'>";
              html += "<div class='shippingName'>";
              html += "<input class='shippingField' name='shippingMethod' type='radio' value='" + method.name + "' " + (checked ? "checked='checked'" : "") + " />";
              html += method.displayName;
              html += "<\/div><div class='shippingPrice'>";
              html += nfFormat(method.cost);
              html += "<\/div><div style='clear:both'></div></div>";
            }
            jQuery('#shippingMethods').html(html);
            jQuery('[name=shippingMethod]').bind('click.ultraCart', chooseShipping);
            chooseShipping();
          }
        },
        "json");
    }


    function chooseShipping(){
      var newChoice = jQuery('[name=shippingMethod]:checked').val();
      var updateServer =  newChoice != shippingChoice;
      shippingChoice = newChoice;

      var shippingMethod = null;
      if(shippingMethods){
        for(var i = 0; i < shippingMethods.length; i++){
          if(shippingMethods[i].name == shippingChoice){
            shippingMethod = shippingMethods[i];
            break;
          }
        }
      }

      if(!shippingMethod){
        return;
      }

      var totalTax = tax + parseFloat(shippingMethod.tax);
      var shippingTotal = '&nbsp;';
      if(shippingMethod.cost == 0){
        shippingTotal = '<strong>FREE Shipping!</strong>';
      } else {
        shippingTotal = nfFormat(shippingMethod.cost);
      }

      var total = subtotal + totalTax + parseFloat(shippingMethod.cost);

      jQuery('#summarySubtotal').html("<div class='summaryLabel'>Subtotal:<\/div><div class='summaryField'>" + nfFormat(subtotal) + "<\/div>");
      jQuery('#summaryTax').html("<div class='summaryLabel'>Tax:<\/div><div class='summaryField'>" + (totalTax == 0 ? "<span class='tax'>No Sales Tax!</span>" : nfFormat(totalTax)) + "<\/div>");
      jQuery('#summaryShipping').html("<div class='summaryLabel'>Shipping:<\/div><div class='summaryField'>" + shippingTotal + "<\/div>");
      jQuery('#summaryTotal').html("<div class='summaryLabel'>Total:<\/div><div class='summaryField'>" + nfFormat(total) + "<\/div>");

      if(updateServer){
        updateShippingOnServer();
      }

    }

    function updateShippingOnServer(){
      jQuery.ajax({
            type: "POST",
            url: './update_shipping.php',
            data: "shippingMethod=" + shippingChoice,
            dataType: 'json',
            success: function(result) {
            // not doing anything here.
            }});
    }

    function increaseBy2() {
      var quantityFields = document.getElementsByClassName('qty');
      if (quantityFields) {
        for (var i = 0; i < quantityFields.length; i++) {
          quantityFields[i].value = parseInt(quantityFields[i].value) + 2;
        }
      }
    }

    function blankAddFields() {
      var fields = document.getElementsByClassName('addText');
      if (fields) {
        for (var i = 0; i < fields.length; i++) {
          fields[i].value = '';
        }
      }
    }

    function deleteAnItem(itemId) {
      if (confirm("Please confirm you wish to delete " + itemId + '.')) {
        var itemField = document.getElementById('deleteItem');
        itemField.value = itemId;
        var deleteForm = document.getElementById('deleteForm');
        deleteForm.submit();
      }
    }

    function removeCoupon(couponCode) {
      var couponCodeField = document.getElementById('deleteCouponCode');
      couponCodeField.value = couponCode;
      var deleteForm = document.getElementById('deleteCouponForm');
      deleteForm.submit();
    }

    function showHide(checkbox, divId) {
        jQuery('#' + divId).toggle(checkbox.checked);
    }
  </script>
</head>
<body>


<div id='content'>
<div id='banner'>
  <img src='images/uclogo.png' alt='logo'/>
  <div style='float:right;font-family:Arial,serif;font-size:10px;'>
    <em>Demo Instructions</em>:<br />
    Items to Add: BONE, TSHIRT, PDF, item, P0975<br />
    Invalid Items (try): INVALIDITEM<br />
    Coupons to Add: AFA, 5OFF<br />
    Invalid Coupons (try): INVALIDCOUPON<br />
    Test Credit Card: Visa, 4444333322221111 (Any future exp date, CVV 123)<br />
    Please use the UltraCart forums to suggest improvements and contribute bug fixes!
  </div>
  <div style='clear:both'></div>
</div>

<?php if (!is_null($result) && count($result->errorMessages) > 0) { ?>
  <div id='error_container'>
    <div id='error_container_wrapper'>
      <img src='images/info.gif' alt='info'/>
      <span class='error_title'>Important Message(s):</span>
      <div id='error_messages'>
        <ul>
          <?php foreach ($result->errorMessages as $err) {
            echo "<li>$err</li>";
          } ?>
        </ul>
      </div>
      <div id='error_footer'>
        <span onclick='hideError()' class='acknowledge_link'>[acknowledge]</span>
      </div>
    </div>
  </div>
<?php } ?>



<div id='shoppingCart'>
  <div id='shoppingCartWrapper'>

  <?php
  // you probably don't want this in a production environment
  if (!is_null($msg)) {
    echo "<div class='msg'>$msg</div>";
  }
  ?>


  <script type='text/javascript'>
    function hideQuickAdd(){
      jQuery('#quickadd').hide();
      return false;
    }
  </script>
  <div id='quickadd' class='section'>
    Quick Add Form.  <a href='#' onclick='return hideQuickAdd()'>[Hide Me]</a>
    <form method='post' action='./cart.php'>
      <table>
        <thead>
        <tr>
          <th>No.</th>
          <th>Item</th>
          <th>Quantity</th>
        </tr>
        </thead>
        <tfoot>
        <tr>
          <td colspan='2'>
            <input type='submit' name='add' value='add'/><input type='button' name='blankFields' value='blank fields' onclick='blankAddFields()'/>
          </td>
        </tr>
        </tfoot>
        <tbody>
        <tr>
          <td>1.</td>
          <td><label><input type='text' name='item[]' class='addText' size='20' value='BONE'/></label></td>
          <td><label><input type='text' name='quantity[]' class='addText' size='5' value='1'/></label></td>
        </tr>
        <tr>
          <td>2.</td>
          <td><label><input type='text' name='item[]' class='addText' size='20' value='PDF'/></label></td>
          <td><label><input type='text' name='quantity[]' class='addText' size='5' value='2'/></label></td>
        </tr>
        <tr>
          <td>3.</td>
          <td><label><input type='text' name='item[]' class='addText' size='20' value='TSHIRT'/></label></td>
          <td><label><input type='text' name='quantity[]' class='addText' size='5' value='3'/></label></td>
        </tr>
        <tr>
          <td>4.</td>
          <td><label><input type='text' name='item[]' class='addText' size='20' value='item'/></label></td>
          <td><label><input type='text' name='quantity[]' class='addText' size='5' value='4'/></label></td>
        </tr>
        <tr>
          <td>5.</td>
          <td><label><input type='text' name='item[]' class='addText' size='20' value=''/></label></td>
          <td><label><input type='text' name='quantity[]' class='addText' size='5'/></label></td>
        </tr>
        </tbody>

      </table>
    </form>
    </div>


    <form method='post' id='deleteCouponForm' action='./cart.php'>
      <input type='hidden' name='deleteCouponCode' id='deleteCouponCode' value=''/>
      <input type='hidden' name='deleteCoupon' value='yes'/>
    </form>

    <form method='post' id='deleteForm' action='./cart.php'>
      <input type='hidden' name='item' id='deleteItem' value=''/>
      <input type='hidden' name='deleteItem' value='yes'/>
    </form>

    <?php if ($uc->hasItems) { ?>
      <form method='post' action='./cart.php'>

      <div id='cartItemsContainer'>
        <table id='cartItemsTable' summary='cart' cellspacing='0' cellpadding='0'>
          <thead>
          <tr>
            <th>&nbsp;</th>
            <th align='left'><!-- Image Column--></th>
            <th align='left'>Item</th>
            <th align='left'>Description</th>
            <th align='right'>Quantity</th>
            <th align='right'>Unit Price</th>
            <th align='right'>Amount</th>
            <th>&nbsp;</th>
          </tr>
          </thead>
          <tfoot>
          <tr>
            <td colspan='4'>
              <span id='continueShoppingContainer' title='Continue Shopping'><a href='index.html' title='Continue Shopping'><img src='images/continue.gif' alt='continue shopping' style='border:0'/></a></span>
              <span id='updateQuantityContainer' title='Update Quantity' class='update_link'><input type='image' name='update' src='images/update.gif' value='update' alt='update quantity'/></span>
            </td>
            <td class='subtotal_label'>
              <?php if($discount > 0){ ?>
                <div class='subtotal' id='subtotal_label1'>Subtotal before discounts:</div>
                <div class='discount' id='discount_label1'>Discount:</div>
              <?php } ?>
              <div class='subtotal' id='subtotal_label2'>Subtotal:</div>
            </td>
            <td>
              <?php if($discount > 0){ ?>
                <div class='subtotal' id='subtotal1'><?php echo currency(sprintf("%01.2f", $subtotal)); ?></div>
                <div class='discount' id='discount1'><?php echo currency(sprintf("%01.2f", $discount)); ?></div>
              <?php } ?>
              <div class='subtotal' id='subtotal2'><?php echo currency(sprintf("%01.2f", $subtotalWithDiscount)); ?></div>
            </td>
            <td>&nbsp;</td>
          </tr>
          </tfoot>
          <tbody>
            <?php
            foreach ($uc->getItems() as $item) {
              $unitCostWithDiscount = currency(sprintf("%01.2f", $item->unitCostWithDiscount));
              $amount = currency(sprintf("%01.2f", $item->unitCostWithDiscount * $item->quantity));
              echo "<tr>";
              echo "<td>";



              if($item->defaultThumbnailUrl){
                echo "<img src='$item->defaultThumbnailUrl' alt=''/>";
              }
              echo "</td>";
              echo "<td><input type='hidden' name='item[]' value='$item->itemId'/>$item->itemId</td>";
              echo "<td>";
              if($item->viewUrl){
                echo "<a href='" . $item->viewUrl . "'>";
              }
              echo $item->description;
              if($item->viewUrl){
                echo "</a>";
              }
              echo "</td>";
              echo "<td align='right'><input type='text' size='4' name='quantity[]' value='$item->quantity' class='qty'/></td>";
              echo "<td align='right'>$unitCostWithDiscount</td>";
              echo "<td align='right'>$amount</td>";
              echo "<td><span class='remove_link' title='remove item' onclick='deleteAnItem(\"$item->itemId\")'><img src='images/trash.png' alt='remove item'/></span></td>";
              echo '</tr>';

              ?><!--
                <?php var_dump($item);?>
              --><?php
            }
            ?>
          </tbody>
        </table>
      </div>


      <div id="couponContainer">
        <div class='couponWrapper'>
          <table summary=''>
            <tbody>
            <tr>
              <td>
                <div id='couponsApplied'>
                <?php
                  if($uc->hasCart){
                    $coupons = $uc->getCoupons();
                    if(count($coupons) > 0){
                      echo "<div class='couponHeader'>Applied Coupons:</div>";
                      foreach($coupons as $coupon){
                        echo "<div><span style='float:left;vertical-align:middle'>";
                        echo $coupon->couponCode;
                        echo "</span><span class='coupon_link' onclick='removeCoupon(\"";
                        echo $coupon->couponCode;
                        echo "\")'><img src='images/delete_coupon.png' alt='remove coupon' style='float:left;vertical-align:middle' /></span></div>";
                      }
                    }
                  }
                ?>
                </div>
              </td>
            </tr>
            <tr>
              <td><span class="couponHeader"><label for='couponCode'>Enter coupon code:</label></span></td>
            </tr>
            <tr>
              <td><input name="couponCode" id="couponCode" size="21" class="couponField" type="text"/></td>
            </tr>
            <tr>
              <td>
                <div class="applyCouponButton">
                  <input type='image' name='apply_coupon' src='images/applyCoupon.gif' alt="apply coupon"/>
                </div>
              </td>
            </tr>
            </tbody>
          </table>
        </div>
      </div>

    <?php
      $googleInfo = $uc->getGoogleInfo();
      if($googleInfo->enabled){
        $googleImgSrc = $googleInfo->imageUrl ? $googleInfo->imageUrl : 'images/google_checkout.gif';
        $googleImgAlt = $googleInfo->imageAltText ? $googleInfo->imageAltText : 'Google Checkout';

        echo "<div id='ucGoogleCheckoutSection' class='colorSubHeader'>";
        echo "<input name='google' type='image' id='googleImage' src='$googleImgSrc' alt='$googleImgAlt'/>";
        echo "<span id='ucGoogleConjunction1'><br/> - or use - <br/><br/></span>";
        echo "</div>";
      }
    ?>

    <?php
      $payPalInfo = $uc->getPayPalInfo();
      if($payPalInfo->enabled){
        $payPalImgSrc = $payPalInfo->imageUrl ? $payPalInfo->imageUrl : 'images/paypal.gif';
        $payPalImgAlt = $payPalInfo->imageAltText ? $payPalInfo->imageAltText : 'PayPal Checkout';

        echo "<div id='ucPayPalCheckoutSection' class='colorSubHeader'>";
        echo "<input name='paypal' type='image' id='paypalImage' src='$payPalImgSrc' alt='$payPalImgAlt'/>";
        echo "<span id='ucPayPalConjunction'><br/> - or use our secure order form below. -<br/><br/></span>";
        echo "</div>";
      }
    ?>

    <div id='ucUltraCartCheckoutSection'>
    <!--Above Shipping/Billing Address-->
    <!--/Above Shipping/Billing Address-->

    <table summary='' border="0" cellpadding="10" cellspacing="0">
      <tbody>
      <tr>
        <td valign="top">

          <div id="shippingTable">
            <div class="colorSubHeader">SHIPPING ADDRESS</div>
            <div id='shippingTitleContainer' class="ucFormLabel">
              <label for='shippingTitle'>Title:</label><span class="fieldNote" id="shippingTitleNote"></span><br/>
              <input name='shippingTitle' id="shippingTitle" size="12" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToTitle) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingFirstName'>First
                Name:</label><span class="fieldNote" id="shippingFirstNameNote"></span><br/>
              <input name='shippingFirstName' id="shippingFirstName" size="12" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToFirstName) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingLastName'>Last Name:</label><span class="fieldNote" id="shippingLastNameNote"></span><br/>
              <input name='shippingLastName' id="shippingLastName" size="16" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToLastName) ?>"/>
            </div>

            <div class="ucFormLabel">
              <label for='shippingCompany'>Company:</label><span class="fieldNote" id="shippingCompanyNote"></span><br/>
              <input name='shippingCompany' id="shippingCompany" size="30" maxlength="50" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToCompany) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingAddress1'>Address 1:</label><span class="fieldNote" id="shippingAddress1Note"></span><br/>
              <input name='shippingAddress1' id="shippingAddress1" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToAddress1) ?>"/>
            </div>

            <div class="ucFormLabel">
              <label for='shippingAddress2'>Address 2:</label><span class="fieldNote" id="shippingAddress2Note"></span><br/>
              <input name='shippingAddress2' id="shippingAddress2" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToAddress2) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingCity'>City:</label><span class="fieldNote" id="shippingCityNote"></span><br/>
              <input name='shippingCity' id="shippingCity" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToCity) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingState'>State/Province/Region:</label><span class="fieldNote" id="shippingStateNote"></span><br/>
              <input name='shippingState' id="shippingState" size="10" maxlength="32" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToState) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingZip'>Zip/Postal Code:</label><span class="fieldNote" id="shippingZipNote"></span><br/>
              <input name='shippingZip' id="shippingZip" size="10" maxlength="20" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToPostalCode) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='shippingCountry'>Country:</label><span class="fieldNote" id="shippingCountryNote"></span><br/>
              <select name='shippingCountry' id="shippingCountry" style="width: 315px;" class="ucFormField">
                <option value='United States' <?php echo_selected('United States', $uc->cart->shipToCountry) ?>>United States</option>
              </select>
            </div>

            <div class="ucFormLabel">
              <label for='shippingPhone'>Daytime Phone:</label><span class="fieldNote" id="shippingPhoneNote"></span><br/>
              <input name='shippingPhone' id="shippingPhone" size="14" maxlength="25" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->shipToPhone) ?>"/>
            </div>

            <div class="ucFormLabel" id='emailContainer'>
              <label for='email'>Email Address:</label><span class="fieldNote" id="emailNote"></span><br/>
              <input name='email' id="email" size="30" maxlength="100" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->email) ?>"/>
            </div>

            <div class="ucFormLabel" id='emailConfirmContainer'>
              <span class="required">*</span>
              <label for='emailConfirm'>Email Address
                (Confirm):</label><span class="fieldNote" id="emailConfirmNote"></span><br/>
              <input name='emailConfirm' id="emailConfirm" size="30" maxlength="100" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->emailConfirm) ?>"/>
            </div>

            <div class="ucFormLabel">
              <input name="mailingList" id="mailingList" checked="checked" type="checkbox"/>&nbsp;<label for='mailingList'>Please
              send me email updates of news and special offers</label>
            </div>

          </div>


          <!-- End of Shipping Table -->

        </td>
        <!-- billing cell -->
        <td id="billingTableCell" valign="top">
          <!-- Billing Table -->

          <div id="billingTable">
            <div class="colorSubHeader">BILLING ADDRESS</div>
            <div id='billingTitleContainer' class="ucFormLabel">
              <label for='billingTitle'>Title:</label><span class="fieldNote" id="billingTitleNote"></span><br/>
              <input name='billingTitle' id="billingTitle" size="12" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToTitle) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingFirstName'>First
                Name:</label><span class="fieldNote" id="billingFirstNameNote"></span><br/>
              <input id="billingFirstName" size="12" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToFirstName) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingLastName'>Last Name:</label><span class="fieldNote" id="billingLastNameNote"></span><br/>
              <input id="billingLastName" size="16" maxlength="30" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToLastName) ?>"/>
            </div>

            <div class="ucFormLabel">
              <label for='billingCompany'>Company:</label><span class="fieldNote" id="billingCompanyNote"></span><br/>
              <input id="billingCompany" size="30" maxlength="50" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToCompany) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingAddress1'>Address 1:</label><span class="fieldNote" id="billingAddress1Note"></span><br/>
              <input id="billingAddress1" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToAddress1) ?>"/>
            </div>

            <div class="ucFormLabel">
              <label for='billingAddress2'>Address 2:</label><span class="fieldNote" id="billingAddress2Note"></span><br/>
              <input id="billingAddress2" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToAddress2) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingCity'>City:</label><span class="fieldNote" id="billingCityNote"></span><br/>
              <input id="billingCity" size="30" maxlength="32" style="width: 315px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToCity) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingState'>State/Province/Region:</label><span class="fieldNote" id="billingStateNote"></span><br/>
              <input id="billingState" size="10" maxlength="32" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToState) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingZip'>Zip/Postal Code:</label><span class="fieldNote" id="billingZipNote"></span><br/>
              <input id="billingZip" size="10" maxlength="20" style="width: 190px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->billToPostalCode) ?>"/>
            </div>

            <div class="ucFormLabel">
              <span class="required">*</span>
              <label for='billingCountry'>Country:</label><span class="fieldNote" id="billingCountryNote"></span><br/>
              <select id="billingCountry" style="width: 315px;" class="ucFormField">
                <option value='United States' <?php echo_selected('United States', $uc->cart->shipToCountry) ?>>United States</option>
              </select>
            </div>

          </div>
          <!-- /Billing Table -->
        </td>
      </tr>
      </tbody>
    </table>

    <div class="ucFormLabel" id='billingDifferentContainer'>
      <input name='billingDifferent' value='on' id="billingDifferent" type="checkbox" onclick="showHide(this, 'billingTable')" checked="<?php if(isset($_POST['billingDifferent']) && $_POST['billingDifferent'] == 'on'){ echo " checked='true' "; } ?>"/>
      <label for='billingDifferent'>Check here if billing information is <strong>different</strong> from shipping
        information.</label>
    </div>

    <!--Below Shipping/Billing Address-->
    <!--/Below Shipping/Billing Address-->
    <div id='shipFrom'>
      <!--Ship From-->
      <!--/Ship From-->
    </div>

    <!-- Shipping and summary table -->
    <table summary='' border="0" cellpadding="0" cellspacing="10">
      <tbody>
      <tr>
        <td valign="top">
          <!-- Shipping methods -->
          <div id='shippingContainer'>
            <div id='shippingHeader' class='colorSubHeader'>SHIPPING PREFERENCE</div>
            <div id='shippingMethods'>Loading...</div>
          </div>
          <!-- /shipping methods -->
        </td>
        <td valign="top">
          <!-- summary -->
          <?php
          $total = $subtotalWithDiscount + $tax;
          echo "<div id='summaryContainer'>";
          echo "<div id='summaryHeader' class='colorSubHeader'>SUMMARY</div>";
          echo "<div id='summarySubtotal'><div class='summaryLabel'>Subtotal:</div><div class='summaryField'>"; echo currency(sprintf("%01.2f", $subtotalWithDiscount)); echo "</div></div>";
          echo "<div id='summaryTax'><div class='summaryLabel'>Tax:</div><div class='summaryField'>";
          if($tax == 0){
            echo "<span class='tax'>No Sales Tax!</span>";
          } else {
           echo currency(sprintf("%01.2f", $tax));
          }
          echo "</div></div>";
          echo "<div id='summaryShipping'><div class='summaryLabel'>Shipping:</div><div class='summaryField'>calculating...</div></div>";
          echo "<div id='summaryTotal'><div class='summaryLabel'>Total:</div><div class='summaryField'>";
          echo currency(sprintf("%01.2f", $total));
          echo "</div></div>";
          echo "</div>";
          ?>
          <!-- /summary -->
        </td>
      </tr>
      </tbody>
    </table>
    <br/>

    <!--Above Payment Information-->
    <!--/Above Payment Information-->
    <div id='creditCardContainer'>
      <div id='creditCardHeader'><span class="colorSubHeader">CREDIT CARD PAYMENT</span></div>
      <div id='creditCardTypeLabel'>
        <div class="ucFormLabel"><span class="required">*</span><label for='creditCardType'>Credit Card Type:</label></div>
      </div>
      <div id='creditCardTypeField'>
        <select name='creditCardType' id="creditCardType" style="width: 250px;" class="ucFormField">
          <?php
            if($uc->hasCart){
              $cartCreditCardType = $uc->cart->creditCardType;
              foreach($uc->cart->creditCardTypes as $ccType){
                echo "<option value='$ccType' ";
                echo_selected($ccType, $cartCreditCardType);
                echo ">$ccType</option>";
              }
            }
          ?>
        </select>
      </div>
      <div id='creditCardNumberLabel'>
        <div class="ucFormLabel"><span class="required">*</span><label for='creditCardNumber'>Credit Card #:</label></div>
      </div>
      <div id='creditCardNumberField'>
        <input name='creditCardNumber' id="creditCardNumber" size="30" maxlength="20" style="width: 250px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->creditCardNumber) ?>"/>
      </div>
      <div id='creditCardExpLabel'>
        <div class="ucFormLabel"><span class="required">*</span>Expiration Date:</div>
      </div>
      <div id='creditCardExpField'>
        <label>
          <select name='creditCardExpMonth' id="creditCardExpMonth" style="width: 125px;" class="ucFormField">
            <?php $cartCreditCardExpMonth = $uc->cart->creditCardExpirationMonth; ?>
            <option value="1" <?php echo_selected("1", $cartCreditCardExpMonth) ?>>01 - January</option>
            <option value="2" <?php echo_selected("2", $cartCreditCardExpMonth) ?>>02 - February</option>
            <option value="3" <?php echo_selected("3", $cartCreditCardExpMonth) ?>>03 - March</option>
            <option value="4" <?php echo_selected("4", $cartCreditCardExpMonth) ?>>04 - April</option>
            <option value="5" <?php echo_selected("5", $cartCreditCardExpMonth) ?>>05 - May</option>
            <option value="6" <?php echo_selected("6", $cartCreditCardExpMonth) ?>>06 - June</option>
            <option value="7" <?php echo_selected("7", $cartCreditCardExpMonth) ?>>07 - July</option>
            <option value="8" <?php echo_selected("8", $cartCreditCardExpMonth) ?>>08 - August</option>
            <option value="9" <?php echo_selected("9", $cartCreditCardExpMonth) ?>>09 - September</option>
            <option value="10" <?php echo_selected("10", $cartCreditCardExpMonth) ?>>10 - October</option>
            <option value="11" <?php echo_selected("11", $cartCreditCardExpMonth) ?>>11 - November</option>
            <option value="12" <?php echo_selected("12", $cartCreditCardExpMonth) ?>>12 - December</option>
          </select>
        </label>
        <label><select name='creditCardExpYear' id="creditCardExpYear" style="width: 120px;" class="ucFormField">
          <?php
            $yr = date('Y');
            $cartCreditCardExpYear = $uc->cart->creditCardExpirationYear;
            for($yr_i = 0; $yr_i < 25; $yr_i++){
              echo "<option value='$yr' ";
              echo_selected($yr, $cartCreditCardExpYear);
              echo ">$yr</option>";
              $yr++;
            }
          ?>
        </select></label>
      </div>
      <div id='creditCardVerificationLabel'>
        <div class="ucFormLabel"><span class="required">*</span><label for='creditCardVerificationNumber'>Card Verification
          #:</label></div>
      </div>
      <div id='creditCardVerificationField'>
        <input name='creditCardVerificationNumber' id="creditCardVerificationNumber" size="30" maxlength="4" style="width: 100px;" class="ucFormField" type="text" value="<?php echo_n($uc->cart->creditCardVerificationNumber) ?>"/>
        <span class="ucSmall">&nbsp;
          <a href="https://secure.ultracart.com/checkout/cvv2/both.jsp">help finding this number</a>
        </span>
      </div>
    </div>

    <!--Below Payment Information-->
    <!--/Below Payment Information-->
    <input type='image' name='checkout' src='images/finalizeOrder.gif' alt='Finalize Order'/>
    </div>

      </form>
    <?php } else { ?>

      <div id='emptyCart'>
        Your cart is currently empty.  Add items to display checkout.
      </div>

    <?php } ?>

    <br />
    <br />
    <?php var_dump($_COOKIE); ?>
    <br />
    <?php $uc->printRawCart(); ?>

    <div id='spacer'></div>

    </div>
  </div>
</div>

</body>
</html>
