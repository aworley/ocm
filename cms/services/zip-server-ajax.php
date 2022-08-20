<?php

/**********************************/
/* Pika CMS (C) 2008 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

define('PL_DISABLE_SECURITY',true);

chdir('..');
require_once('pika-danio.php');
pika_init();



$zip = pl_grab_get('zip');
$zip = substr($zip, 0, 5);
$safe_zip = DB::escapeString($zip);

$buffer = '';
$city = '';
$state = '';
$county = '';
$doc = new DOMDocument();
$zipcode = $doc->createElement('zipcode');
$zipcode = $doc->appendChild($zipcode);

if (strlen($zip) == 5)
{
	$result = DB::query("SELECT city, state, county, zip FROM zip_codes WHERE zip='{$safe_zip}' LIMIT 1");
	$row = DBResult::fetchRow($result);
	$city = $row['city'];
	$state = $row['state'];
	$county = $row['county'];
	
	foreach ($row as $key => $val) {
		$node = $doc->createElement($key,$val);
		$zipcode->appendChild($node);
	}
	
}


$buffer = $doc->saveXML();
header('Content-type: text/xml');
exit($buffer);
?>
