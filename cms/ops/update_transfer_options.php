<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/*
This code adds/updates case transfer options in the transfer_options table in Pika.
*/
chdir("..");
require_once ('pika-danio.php'); 
pika_init();


// Variables
// probably should be an array at this point
$base_url = pl_settings_get('base_url');
$action = pl_grab_post('action');
$id = pl_grab_post('id');
$safe_id = DB::escapeString($id);
$label = pl_grab_post('label');
$safe_label = DB::escapeString($label);
$url = pl_grab_post('url');
$safe_url = DB::escapeString($url);
$transfer_mode = pl_grab_post('transfer_mode');
$safe_transfer_mode = DB::escapeString($transfer_mode);
$dummy = null;

if (!pika_authorize("system", $dummy))
{
	$plTemplate["content"] = "Access denied";
	$plTemplate["nav"] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; CaseQ Transfer Options Manager";

	$buffer = pl_template($plTemplate, 'templates/default.html');
	pika_exit($buffer);
}

switch ($action) {
	
	case 'add':
		
		$new_id = pl_mysql_next_id('transfer_option');
		$sql = "INSERT INTO transfer_options 
				SET id='{$new_id}', label='{$safe_label}', 
				url='{$safe_url}', transfer_mode='{$safe_transfer_mode}';";
		
		
		DB::query($sql) or trigger_error();
		
		header("Location: {$base_url}/transfer_options.php");	
		break;
	
	case 'update':
		
		$sql = "UPDATE transfer_options 
				SET label='{$safe_label}', url='{$safe_url}',
				transfer_mode='{$safe_transfer_mode}'
				WHERE id='{$safe_id}'
				LIMIT 1;";
		
		
		DB::query($sql) or trigger_error("");
		
		header("Location: {$base_url}/transfer_options.php");	
		break;
		
	case 'delete':
	
		$sql = "DELETE FROM transfer_options
				WHERE id='{$safe_id} 
				LIMIT 1'";
		
		DB::query($sql) or trigger_error("");
		
		header("Location: {$base_url}/transfer_options.php");
		break;
		
	default:
		
		trigger_error("Unknown Action Selected!");
		break;
}

pika_exit();







?>
