<?php
require('twilio-php/Twilio/autoload.php');
use Twilio\Rest\Client;

$AccountSid = pl_settings_get('twilio_account_sid');
$AuthToken = pl_settings_get('twilio_auth_token');
$from = pl_settings_get('twilio_number');
$cell = pl_grab_post('cell');
$message = pl_grab_post('message');
$send_sms = pl_grab_post('send_sms');


function format_us_mobile($area_code, $phone)
{
	return "1" . $area_code . substr($phone, 0, 3) . substr($phone, 4);
}

function pretty_format_us_mobile($area_code, $phone)
{
	return "(" . $area_code . ") " . $phone;
}

// Based on sample code from Twilio.
// Get the PHP helper library from twilio.com/docs/php/install 
// Loads the library require "vendor/autoload.php"; 
// A function that takes a phone number and 
// returns true if the number is valid and false otherwise. 

	/*	
	$client = new Lookups_Services_Twilio($sid, $token); 
	// Try performing a carrier lookup and return true if successful. 
	try { 
		$number = $client->phone_numbers->get($number, array("CountryCode" => "US", "Type" => "carrier"));
        $number->carrier->type; // Should throw an exception if the number doesn't exist.
        return true;
    } catch (Exception $e) {
        // If a 404 exception was encountered return false.
        if($e->getStatus() == 404) {
            return false;
        } else {
            throw $e;
        }
    }
    */

function is_valid_number($number, $sid, $token) 
{
	if (strlen($number) == 11)
	{
		return true;
	}
	
	return false;
}

/*	This duplicates pl_mysql_column_exists because some older versions of 
		v6 don't have it, and we can't guarantee it'll be available everywhere.
*/
function mysql_column_exists($table, $column)
{
	$clean_table = DB::escapeString($table);
	$clean_column = DB::escapeString($column);
	
	$result = DB::query("SHOW COLUMNS FROM {$clean_table} LIKE '{$clean_column}'");
	
	if (DBResult::numRows($result) == 1)
	{
		return true;
	}
	
	return false;
}


// Main code
if ($send_sms == 'Send SMS')
{
	if (is_valid_number($cell, $AccountSid, $AuthToken)) 
	{
		$client = new Client($AccountSid, $AuthToken);
		$sms = $client->account->messages->create($cell, array('from' => $from, 'body' => $message));
		
		require_once('pikaActivity.php');
		$a = new pikaActivity();
		$a->act_type = 'S';
		$a->act_date = date('Y-m-d');
		$a->act_time = date('H:i:s');
		$a->notes = $message;
		$a->summary = "[SMS message to {$cell} from {$from}]";
		$a->case_id = $case_id;
		$a->sms_count = 1;
		$a->save();
		
		$C .= "<div class='well'>Sent message to $cell</div>";
	} 
	
	else 
	{
    	$C .= "<div class='well'>Sorry, but that is not a valid cell number.</div>";
	}
}

$safe_case_id = $case_id;
$mobile_options = '';
$ok_to_text_sql = '1 AS ok_to_text, 1 AS ok_to_text_alt';

if (mysql_column_exists('contacts', 'ok_to_text'))
{
	$ok_to_text_sql = 'ok_to_text, ok_to_text_alt';
}

$sql = "SELECT first_name, middle_name, last_name, extra_name,
	area_code, phone, area_code_alt, phone_alt, {$ok_to_text_sql} 
	FROM conflict LEFT JOIN contacts USING(contact_id)
	WHERE conflict.case_id = {$safe_case_id}
	AND conflict.relation_code = 1
	ORDER BY last_name ASC, first_name ASC, extra_name ASC";
$result = DB::query($sql);

while ($row = DBResult::fetchRow($result))
{
	if (strlen($row['area_code']) == 3 && strlen($row['phone']) == 8 &&
			$row['ok_to_text'] == 1)
	{
		$mobile_options .= "<option value=\"" 
			. format_us_mobile($row['area_code'], $row['phone']) . "\">"
			. pl_text_name($row) . ' '
			. pretty_format_us_mobile($row['area_code'], $row['phone'])
			. "</option>\n";
	}
	
	if (strlen($row['area_code_alt']) == 3 && strlen($row['phone_alt']) == 8 &&
			$row['ok_to_text_alt'] == 1)
	{
		$mobile_options .= "<option value=\"" 
			. format_us_mobile($row['area_code_alt'], $row['phone_alt']) . "\">"
			. pl_text_name($row) . ' '
			. pretty_format_us_mobile($row['area_code_alt'], $row['phone_alt'])
			. "</option>\n";
	}
}

$form_url = $base_url . "/case.php?case_id={$case_id}&screen=sms";
$C .= "
<br>
<h4>Send a SMS message to a case contact</h4>
<form method='POST' action='".$form_url."'>
Cell Number:<br>
<select name='cell'>{$mobile_options}</select><br>
Message:<br>
<textarea name=\"message\" rows=\"8\" maxlength=\"1600\" placeholder=\"Please enter your message here.\"></textarea><br>
<input type='submit' name='send_sms' value='Send SMS' class='btn btn-primary'>
</form>";

$save_case_changes = false;

if ($case1->unread_sms > 0)
{
	$save_case_changes = true;
}

// Start SMS listing
$sql = "SELECT act_date, act_time, summary, notes FROM activities WHERE act_type = 'S'"
	. " AND case_id = {$safe_case_id} ORDER BY act_date DESC, act_time DESC";
$result = DB::query($sql);

$sms_listing_rows = '';

while ($row = DBResult::fetchRow($result))
{
	$from_text = substr(htmlentities($row['summary']), 13);
	$from_text = substr($from_text, 0, -1);
	
	/*	Look for unread messages in the case record counter.  If present,
			mark this row as New and de-increment the counter by one.
			*/
	
	if ($case1->unread_sms > 0)
	{
		$from_text = '<span class="badge badge-info">New</span> ' . $from_text;
		$case1->unread_sms--;
	}
		
	$sms_listing_rows .= "<tr><td><p>{$from_text}</p><p>" 
		. pl_date_unmogrify($row['act_date']) . " "
		. pl_time_unmogrify($row['act_time']) . "</p></td><td><strong>" 
		. nl2br(htmlentities($row['notes']), false)
		. "</strong></td></tr>";
}

if (strlen($sms_listing_rows) == 0)
{
	$C .= "<p>No SMS messages exist for this case.</p>";
}

else 
{
	$C .= "<table class=\"table table-striped\">{$sms_listing_rows}</table>";
}
// End SMS listing

if ($save_case_changes)
{
	$case1->save();
}
