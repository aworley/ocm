<?php

/***************************/
/* Pika CMS (C) 2015       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

chdir('../');

define('PL_HTTP_SECURITY',true);

require_once ('pika-danio.php');
pika_init();
/*
require_once('pikaCase.php');
require_once('pikaContact.php');
require_once('pikaActivity.php');
*/
require_once('pikaLSXML_V2.php');

$auth_row = pikaAuthHttp::getInstance()->getAuthRow();
$lsxml = $_POST['lsxml'];
$tx = new pikaLSXML($lsxml);
$case_id = $tx->importXML();
$case = new pikaCase($case_id);
$case->intake_user_id = $auth_row['user_id'];
$case->save();

echo "[{$case_id}] ";
print_r($case);

exit();
