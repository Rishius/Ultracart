<?php
require_once './UltraCart_v3.0.php';


function clean($input, $valueIfNull = '')
{
  if (!isset($_POST[$input])) {
    return $valueIfNull;
  }
  return str_replace(chr(173), "", trim($_POST[$input]));
}

// only echos if the value is not null.
function echo_n($val)
{
  if (!is_null($val)) {
    echo $val;
  }
}

function echo_selected($val, $cartVal)
{
  if ($val == $cartVal) {
    echo " selected='true' ";
  }
}

$merchantId = '59362';
$uc = new UltraCart($merchantId);
$result = null;
$msg = null;

// most of the code blocks below begin with a check for a cart.  when the UltraCart object is created,
// it will connect to the server and begin a session.  The cart *is* the session, so it's not just about
// a bunch of items.  It's the entire conversation state in one object, so it needs to exist.
if ($uc->hasCart && isset($_POST['registerCustomer'])) {

  if (!isset($_POST['registerEmail']) || !isset($_POST['registerPassword'])) {
    $msg = "Error.  Missing an email or password.  Please enter an email and password to register a customer.  Nothing done.";
  } else {
    $email = $_POST['registerEmail'];
    $password = $_POST['registerPassword'];
    // checkout will update cart, so no need to do it here.
    $result = $uc->registerCustomer($email, $password);
    $msg = 'Registering Customer, Status=' . (bool)$result->wasSuccessful;

  }
}

if ($uc->hasCart && isset($_POST['loginCustomer'])) {
  if (!isset($_POST['loginEmail']) || !isset($_POST['loginPassword'])) {
    $msg = "Error.  Missing an email or password.  Please enter an email and password to login a customer.  Nothing done.";
  } else {
    $email = $_POST['loginEmail'];
    $password = $_POST['loginPassword'];
    // checkout will update cart, so no need to do it here.
    $result = $uc->loginCustomer($email, $password);
    $msg = 'Logging In Customer, Status=' . (bool)$result->wasSuccessful;

  }
}


if ($uc->hasCart && isset($_POST['logoutCustomer'])) {
  $result = $uc->logoutCustomer();
  $msg = 'Logging Out Customer, Status=' . (bool)$result->wasSuccessful;
}


if (is_null($result) && isset($_GET['checkout_err'])) {
  $checkoutErrors = $_GET['checkout_err'];
  $result = new CartOperationResult();
  $result->wasSuccessful = false;
  foreach ($checkoutErrors as $err) {
    array_push($result->errorMessages, $err);
  }
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <title>Customer Profile Example - UltraCart PHP Cart</title>
  <link href="css/cart_1.0.css" rel="stylesheet" type="text/css"/>
  <script type='text/javascript' src='js/jquery-1.4.2.min.js'></script>
  <script type='text/javascript'>

    /**
     * hides the error messages when the user acknowledges them.  This isn't really a render function, but
     * it's tied to the renderErrors function so it's included here to ensure it's not missed.
     */
    function hideError() {
      jQuery('#error_container').hide();
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
      <em>Demo Instructions</em>:<br/>
      Register a customer by adding email and password.<br/>
      Use the other buttons to test login and logout.<br/>
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




  <?php if ($uc->loggedIn) { ?>
  <?php $profile = $uc->cart->customerProfile; ?>
  <form method='post' id='registerForm' action='./customer_profile_3.0.php'>
    <input type='submit' name='logoutCustomer' value='logout customer'/>
  </form>

  <table>
    <tbody>
    <tr>
      <td>First Name</td>
      <td><?php echo $profile->firstName;  ?></td>
    </tr>
    <tr>
      <td>Last Name</td>
      <td><?php echo $profile->lastName;  ?></td>
    </tr>
    <tr>
      <td>Address 1</td>
      <td><?php echo $profile->address1;  ?></td>
    </tr>
    <tr>
      <td>Address 2</td>
      <td><?php echo $profile->address2;  ?></td>
    </tr>
    <tr>
      <td>City</td>
      <td><?php echo $profile->city;  ?></td>
    </tr>
    <tr>
      <td>State</td>
      <td><?php echo $profile->state;  ?></td>
    </tr>
    </tbody>
  </table>


  <?php } else { ?>


  <form method='post' id='registerForm' action='./customer_profile_3.0.php'>
    <div id='registerCustomerDiv' class='section'>
      <div id='registerCustomerHeader'>Register New Customer</div>
      <div>
        <label for='registerEmail'>Email:</label><input id='registerEmail' name='registerEmail' type='text' size='30' maxlength='50'/>
      </div>
      <div>
        <label for='registerPassword'>Password:</label><input id='registerPassword' name='registerPassword' type='text' size='20' maxlength='20'/>
      </div>
      <input type='submit' name='registerCustomer' value='register customer'/>
    </div>
  </form>

  <br/>
  <br/>
  <form method='post' id='loginForm' action='./customer_profile_3.0.php'>
    <div id='loginCustomerDiv' class='section'>
      <div id='loginCustomerHeader'>Login Existing Customer</div>
      <div>
        <label for='loginEmail'>Email:</label><input id='loginEmail' name='loginEmail' type='text' size='30' maxlength='50'/>
      </div>
      <div>
        <label for='loginPassword'>Password:</label><input id='loginPassword' name='loginPassword' type='text' size='20' maxlength='20'/>
      </div>
      <input type='submit' name='loginCustomer' value='login customer'/>
    </div>
  </form>



  <?php } ?>

  <br/>
  <br/>
  <?php $uc->printRawCart(); ?>

  <div id='spacer'></div>

    </div>
  </div>
</div>

</body>
</html>