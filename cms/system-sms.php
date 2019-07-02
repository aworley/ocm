<?php

/**********************************/
/* Pika CMS (C) 2010			  */
/* http://pikasoftware.com		  */
/**********************************/

require_once('pika-danio.php');
pika_init();
require_once('pikaSettings.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');

$main_html = $html = array();
$base_url = pl_settings_get('base_url');

$main_html['page_title'] = $page_title = "SMS Settings";
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; 
						<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
						{$page_title}";

$action = pl_grab_post('action');

if (!pika_authorize('system',array()))
{
	$main_html['content'] = "Access denied";
	
	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

switch ($action)
{
	case 'update':

		pl_settings_set('twilio_account_sid', pl_grab_post('twilio_account_sid'));
		pl_settings_set('twilio_auth_token', pl_grab_post('twilio_auth_token'));
		pl_settings_set('twilio_number', pl_grab_post('twilio_number'));
		pl_settings_set('sparkpost_api_key', pl_grab_post('sparkpost_api_key'));
		pl_settings_set('sparkpost_from_address', pl_grab_post('sparkpost_from_address'));
		/*
		'twilio_account_sid'
		'twilio_auth_token'
		'twilio_number'
		'sparkpost_api_key'
		'sparkpost_from_address'
		*/
		pl_settings_save();
		
	default:

		$html['twilio_account_sid'] = pl_settings_get('twilio_account_sid');
		$html['twilio_auth_token'] = pl_settings_get('twilio_auth_token');
		$html['twilio_number'] = pl_settings_get('twilio_number');
		$html['sparkpost_api_key'] = pl_settings_get('sparkpost_api_key');
		$html['sparkpost_from_address'] = pl_settings_get('sparkpost_from_address');
		
		$template = new pikaTempLib('subtemplates/system-sms.html',$html);
		$main_html['content'] = $template->draw();
		
		break;
}


$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>