<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title></title>

	</head>
	<body>
		<?php

		class tier{};
		$aff = new tier;
		$aff->tierNumber = 1;
		$aff->affiliateOid = 4;  // this must be a real affiliate oid

		$client = new SoapClient('https://secure.ultracart.com/axis/services/AffiliateServiceV1?wsdl');


		$result = $client->createAffiliate(array(
			"login"=>"perry",
			"merchantId"=>"demo",
			"password"=>"passowrd"
		),array(
			"email"=>"perry" . rand() . "@test.com",
			"companyName"=>"TEST TIER",
			"affiliateOid"=> 0,   // make this zero when creating a new affiliate.
			"autoApproveCommissions"=>true,
			"emailPreference"=>0,
			"htmlPermitted"=>false,
			"payViaPaypal"=>true,
			"status"=>1,
			"usingAdNetwork"=>false,
			"usingAdware"=>false,
			"usingBlog"=>false,
			"usingPerAcquisition"=>false,
			"usingPpc"=>false,
			"usingSeo"=>false,
			"usingWebsite"=>false,
			"password"=>"123",
			"affiliateGroup"=>"Default",
			"relationships"=>array($aff) //array("tierNumber"=>1, "affiliateOid"=>70980
		));

		?>
  I hope everything went okay.
	</body>
</html>
