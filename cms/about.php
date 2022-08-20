<?php

/**********************************/
/* Pika CMS (C) 2008 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika-danio.php');
pika_init();

$result = DB::query("SELECT VERSION() AS mysql_version") or trigger_error();
$a = DBResult::fetchRow($result);
$a['pika_version'] = PIKA_VERSION;
$a['pika_revision'] = PIKA_REVISION;
$a['pika_patch_level'] = PIKA_PATCH_LEVEL;
$a['pika_code_name'] = PIKA_CODE_NAME;
$a['config_date'] = date('m/d/Y h:i A', filemtime(pl_custom_directory() . '/config/settings.php'));
$a['install_date'] = date('m/d/Y h:i A', filemtime('pika-danio.php'));
$a['server_name'] = $_SERVER['SERVER_NAME'];
$a['php_version'] = phpversion();
$a['apache_version'] = $_SERVER["SERVER_SOFTWARE"];

$a['base_url'] = $base_url = pl_settings_get('base_url');


$plTemplate["content"] = pl_template($a, 'subtemplates/about.html');
$plTemplate["page_title"] = "About Pika CMS";
$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a>
						&gt; <a href=\"{$base_url}/site_map.php\">Site Map</a>
						&gt; About Pika";


$buffer = pl_template($plTemplate, 'templates/default.html');
pika_exit($buffer);

?>
