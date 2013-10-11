<html>
<head>
  <script type='text/javascript' src='../js/jquery-1.4.2.min.js'></script>
  <script type='text/javascript'>
    jQuery('document').ready(function() {
      $.get('./estimate_shipping.php',
          {},
          function(result) {
            if (result != null) {
              // result should be an array of shipping methods
              var html = '';
              for (var i = 0; i < result.length; i++) {
                var method = result[i];
                html += "<input type='radio' name='shippingMethod' value='" + method.name + "'/> " + method.name + " - " + method.cost + "<br />";
              }
              $('#shippingMethods').html(html);
            }
          },
          "json");
    });
  </script>
  <style type='text/css'>
    * {
      font-family: Arial, serif;
      font-size: 11px;
    }
  </style>
</head>
<body>

<div id='shippingMethods'>
  Shipping methods will display here, but estimateShipping is takes a long time.
</div>

</body>
</html>
