<?php

/***************************/
/* Pika CMS (C) 2011       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

chdir('../');

require_once ('pika-danio.php');
pika_init();
require_once('pikaCase.php');
require_once('pikaMisc.php');
require_once('pikaSettings.php');

require_once('pikaTempLib.php');

// VARIABLES

$case_id = pl_grab_post('case_id');
$action = pl_grab_post('action');
$transfer_option_id = pl_grab_post('transfer_option_id');

$base_url = pl_settings_get('base_url');
$owner_name = pl_settings_get('owner_name');


// Menus

$staff_array = pikaMisc::fetchStaffArray();

function post_transfer($url = null,$data = null,$optional_headers = null)
{
	
	$params = array('http' => array(
					'method' => 'POST',
					'content' => $data
	));
	if ($optional_headers !== null) 
	{
		$params['http']['header'] = $optional_headers;
	}
	$ctx = stream_context_create($params);
	$fp = @fopen($url, 'rb', false, $ctx);
	if (!$fp) 
	{
		$msg = "Problem connecting to receiving server.  Please verify that the URL and Authentication credentials supplied are correct.\n<br/>URL: {$url}";
		throw new Exception($msg);
	}
	$response = @stream_get_contents($fp);
	if ($response === false) {
		throw new Exception("Problem reading data from stream at $url");
	}
	return $response;
}

function pika_transfer($data,$transfer_option_id)
{
	$response = false;
	
	require_once('pikaTransferOption.php');
	$tx = new pikaTransferOption($transfer_option_id);
	$user = $tx->user;
	$pass = $tx->password;
	$url = $tx->url;

	$auth = base64_encode($user.':'.$pass);
	$auth_header = 	"Content-type: application/x-www-form-urlencoded\r\n" .
					"Authorization: Basic {$auth}\r\n";
	
	// Pika CMS v5 needs a valid MD5 checksum otherwise the transfer op will fail.
	$data['checksum'] = md5(var_export(unserialize($data['payload']), true));
	
	$data = http_build_query($data);
	
	try {
		$response = post_transfer($url,$data,$auth_header);
		return $response;
	} 
	catch (Exception $e)
	{
		trigger_error($e->getMessage());
	}
	
}


function transfer_error($msg = null,$line = 0,$case_id = null)
{
	require_once('pikaSettings.php');
	$base_url = pl_settings_get('base_url');
	
	$main_html = array();
	$main_html['content'] = "There was a problem during the case transfer process.  <em>The case has not been transferred correctly.</em><br/>\n".
							"Message: {$msg}".
							"Line No: {$line}<br/>\n". 
							"<a href=\"{$base_url}/case.php?case_id={$case_id}\">Return to this case</a>";
	$main_html['page_title'] = 'Case Transfer';
	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; Case Transfer";
	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}


// BEGIN MAIN CODE...

// ENFORCE PERMISSIONS
$case = new pikaCase($case_id);
$case_row = $case->getValues();
if (!pika_authorize('edit_case', $case_row))
{
	// set up template, then display page
	$main_html['content'] = "Access Denied - You do not have the necessary permissions to transfer this case.";
	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}



// cases record.

unset($case_row['user_id']);
unset($case_row['cocounsel1']);
unset($case_row['cocounsel2']);
unset($case_row['intake_user_id']);

$client_id = $case_row['client_id'];
unset($case_row['client_id']);


$data = array(	'action' => 'newCase',
				'payload' => serialize($case_row)				
);

$response = pika_transfer($data,$transfer_option_id);

$tx_case_id = $response;
if (!is_numeric($response))
{
	$msg = 'Action: newCase<br/>\nError: Unable to transfer case data.<br/>\nResponse: ' . $response;
	transfer_error($msg,__LINE__,$case_id);
}

// contacts and conflicts.
$stack = array();
$result = $case->getContactsDb();
while ($contact_row = DBResult::fetchRow($result))
{
	$data = array(	'action' => 'newContact',
					'payload' => serialize($contact_row)				
	);
	
	$response = pika_transfer($data,$transfer_option_id);
	
	$tx_contact_id = $response;
	if (!is_numeric($response))
	{
		$msg = 'Action: newContact<br/>\nError: Unable to Add Case Contact.<br/>\nResponse: ' . $response;
		transfer_error($msg,__LINE__,$case_id);
	}
	
	if ($contact_row['contact_id'] == $client_id) 
	{
		array_unshift($stack, array('0' => $tx_case_id, '1' => $tx_contact_id, '2' => $contact_row['relation_code']));
	}
	else 
	{
		array_push($stack, array('0' => $tx_case_id, '1' => $tx_contact_id, '2' => $contact_row['relation_code']));
	}
}

while (sizeof($stack) > 0) 
{
	$data = array(	'action' => 'addCaseContact',
					'payload' => serialize(array_shift($stack))				
	);
	
	$response = pika_transfer($data,$transfer_option_id);
	if (!is_numeric($response))
	{
		$msg = 'Action: addCaseContact<br/>\nError: Unable to associate contact with transferred case.<br/>\nResponse: ' . $response;
		transfer_error($msg,__LINE__,$case_id);
	}	
}

// activities - notes and timekeeping.
$result = $case->getNotes('ASC',10000);
while ($notes = DBResult::fetchRow($result))
{
	$notes['case_id'] = $tx_case_id;
	
	$atty_name = pl_array_lookup($notes['user_id'], $staff_array);
	$notes['notes'] .= "\n\n===\nEntered by {$atty_name}, {$owner_name}";
	$notes['notes'] .= ", {$case_row['number']}";
	unset($notes['user_id']);
	unset($notes['act_id']);

	$data = array(	'action' => 'newActivity',
					'payload' => serialize($notes)
	);
	$response = pika_transfer($data,$transfer_option_id);
	if (!is_numeric($response))
	{
		$msg = 'Action: newActivity<br/>\nError: Unable to associate case note with transferred case.<br/>\nResponse: ' . $response;
		transfer_error($msg,__LINE__,$case_id);
	}	
}

// Set the original case to transferred status.
$case->status = 4;
$case->save();

$number = $case->number;
if(strlen($number) < 1)
{
	$number = 'No Case #';
}
$case_url = "<a href=\"{$base_url}/case.php?case_id={$case_id}\">{$case->number}</a>";

$main_html = array();
$main_html['content'] = "Transfer of case # ".
						$case_url . " ".
						"Complete, transferred case reference number # is '{$tx_case_id}'.";
$main_html['page_title'] = $page_title = 'Case Transfer';
$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; {$case_url} &gt; {$page_title}";
$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);
