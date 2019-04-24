<?php

/***************************/
/* Pika CMS (C) 2011       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

ini_set("zlib.output_compression", "On");
ini_set("zlib.output_compression_level", 9);

chdir('../');

define('PL_HTTP_SECURITY',true);

require_once ('pika-danio.php');
pika_init();
require_once('pikaCase.php');
require_once('pikaContact.php');
require_once('pikaActivity.php');

$auth_row = pikaAuthHttp::getInstance()->getAuthRow();

if ('system' != $auth_row['group_id'])
{
	echo "This action is not available.  Please log in under an account with system privileges.";
	exit();
}

$tables = array();
$result = DB::query("SHOW TABLES");

while ($row= DBResult::fetchArray($result))
{
	if ($row[0] != 'doc_storage')
	{
		$tables[] = $row[0];
	}
}

echo json_encode($tables);
exit();


