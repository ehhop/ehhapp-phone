<?php

$path = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/services.php?WSDL';
$client = new SoapClient('http://' . $_SERVER['HTTP_HOST'] . $path);

// var_dump($client->get_oncall_CM_phone('9/14/2013'));
if (isset($_POST['submit'])) {
  var_dump($client->voicemail_alert(1, '2125551234', '9/14/2013', 'http://example.com'));
}

?>
<html>
<head><title>Test EHHOP phone-related webservices</title></head>
<body>
  <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
    <input type="submit" name="submit" value="Test voicemail alert"/>
  </form>
</body>
</html>