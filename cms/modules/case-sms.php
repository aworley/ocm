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
		$a->save();
		
		$C .= "<div class='well'>Sent message to $cell</div>";
	} 
	
	else 
	{
    	$C .= "<div class='well'>Sorry, but that is not a valid cell number.</div>";
	}
}

$mobile_options = '';
$sql = "SELECT first_name, middle_name, last_name, extra_name,
	area_code, phone, area_code_alt, phone_alt 
	FROM conflict LEFT JOIN contacts USING(contact_id)
	WHERE conflict.case_id = {$case_id}
	AND conflict.relation_code = 1
	ORDER BY last_name ASC, first_name ASC, extra_name ASC";
$result = mysql_query($sql);

while ($row = mysql_fetch_assoc($result))
{
	if (strlen($row['area_code']) == 3 && strlen($row['phone']) == 8)
	{
		$mobile_options .= "<option value=\"" 
			. format_us_mobile($row['area_code'], $row['phone']) . "\">"
			. pl_text_name($row) . ' '
			. format_us_mobile($row['area_code'], $row['phone'])
			. "</option>\n";
	}
	
	if (strlen($row['area_code_alt']) == 3 && strlen($row['phone_alt']) == 8)
	{
		$mobile_options .= "<option value=\"" 
			. format_us_mobile($row['area_code_alt'], $row['phone_alt']) . "\">"
			. pl_text_name($row) . ' '
			. format_us_mobile($row['area_code_alt'], $row['phone_alt'])
			. "</option>\n";
	}
}

$form_url = $base_url . "/case.php?case_id={$case_id}&screen=sms";
$C .= "
<form method='POST' action='".$form_url."'>
Cell Number:<br>
<select name='cell'>{$mobile_options}</select><br>
Message:<br>
<textarea name=\"message\" rows=\"8\" maxlength=\"1600\" placeholder=\"Please enter your message here.\"></textarea><br>
<input type='submit' name='send_sms' value='Send SMS'>
</form>";