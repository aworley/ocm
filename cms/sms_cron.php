<?php

/**********************************/
/* Pika CMS (C) 2018 Aaron Worley */
/* https://pikasoftware.com       */
/**********************************/

if (php_sapi_name() != "cli")
{
  trigger_error('This script can only be run from the command line.');
  exit();
}

/*  This file will normally be called from the cron process.  Change to the
    correct working directory here, because we're not sure what cron is going
    to give us. */
chdir(__DIR__);

require_once ('pika-danio.php');

$include_str = './app/lib' . PATH_SEPARATOR . './app/extralib' 
	. PATH_SEPARATOR . ini_get('include_path');
ini_set('include_path', $include_str);

// Now that the include_path is set, load the danio pl.php library.
//TODO fix this
require_once('pl.php');
define('PL_SETTINGS_FILE', pl_custom_directory() . '/config/settings.php');
define('PL_DEFAULT_PREFS_FILE', pl_custom_directory() . '/config/default_prefs.php');
if(!defined('PL_DISABLE_MYSQL'))
{
	pl_mysql_init() or trigger_error('Could not connect to MySQL server.  Please check PikaCMS database connection settings and/or verify that an instance of MySQL is running on the specified host.  ERROR # ' . mysql_errno());
}
$time_zone = pl_settings_get('time_zone');

if (function_exists('date_default_timezone_set')) 
{
	if (!$time_zone)
	{
		$time_zone='America/New_York';
	}
	
	date_default_timezone_set($time_zone);
}

$auth_row = array('user_id' => 0);

require_once('pikaActivity.php');

require('twilio-php/Twilio/autoload.php');
use Twilio\Rest\Client;

function format_us_mobile($area_code, $phone)
{
	return "1" . $area_code . substr($phone, 0, 3) . substr($phone, 4);
}

$AccountSid = pl_settings_get('twilio_account_sid');
$AuthToken = pl_settings_get('twilio_auth_token');
$from = pl_settings_get('twilio_number');

// Add 90 seconds in case cron does not fire off exactly on the dot.
$ut_safe = mysql_real_escape_string(time() + 90);

/*  Notes on SMS reminder status

    No reminder needed:  sms_send_time is NULL
    Reminder requested, not sent yet:  sms_send_time is not NULL
      and sms_send_failures != 1 and sms_act_id is NULL
    Reminder requested and sent:  sms_send_time is not NULL 
      and sms_act_id is not NULL
    Reminder requested but failed to send:  sms_send_time is not NULL
      and sms_send_failures == 1
    
    */

$sql = "SELECT act_id, act_date, act_time, label AS message_text, area_code, ";
$sql .= "phone, area_code_alt, phone_alt, sms_mobile ";
$sql .= "FROM activities LEFT JOIN menu_sms_messages ON sms_message_id = value ";
$sql .= "LEFT JOIN cases ON activities.case_id = cases.case_id ";
$sql .= "LEFT JOIN contacts ON cases.client_id = contacts.contact_id ";
$sql .= "WHERE sms_send_time < {$ut_safe} AND (sms_send_failures != 1 OR sms_send_failures IS NULL) ";
$sql .= "AND sms_act_id is NULL";
$result = mysql_query($sql);

while ($row = mysql_fetch_assoc($result))
{
  $sms_date = pl_date_unmogrify($row['act_date']);
  $sms_time = pl_time_unmogrify($row['act_time']);
  $message = $row['message_text'] . "\n\nDATE: {$sms_date}\nTIME: {$sms_time}\n\nThank you!";
  
  $cal = new pikaActivity($row['act_id']);
  $sms_status = null;
  $sms_error = '';
  
  // Attempt to send the SMS.
  $client = new Client($AccountSid, $AuthToken);
  
  if ($row['sms_mobile'] == 'p')
  {
    $cell = format_us_mobile($row['area_code'], $row['phone']);
  }
  
  else
  {
    $cell = format_us_mobile($row['area_code_alt'], $row['phone_alt']);
  }
   
  if (strlen(trim($row['message_text'])) == 0)
  {
    $sms_status = 'failed';
    $sms_error .= 'no message selected';
  }

  if (strlen($cell) != 11)
  {
    $sms_status = 'failed';
    $sms_error .= 'invalid mobile phone number for recipient';
  }

  if ($sms_status != 'failed')
  {
    try
    {
      $sms = $client->account->messages->create($cell, array('from' => $from, 'body' => $message));
      $sms_status = $sms->status;
    }

    catch (Exception $e)
    {
      $sms_error = $e->getMessage();
      $sms_status = 'failed';
    }
  }
  
  // If successful, record the SMS as an activity record, then NULL out sms_send
  // and record sms_act_id.
  if ($sms_status == 'queued')
  {
    echo "SMS sent\n";
    $a = new pikaActivity();
    $a->act_type = 'S';
    $a->act_date = date('Y-m-d');
    $a->act_time = date('H:i:s');
    $a->notes = $message;
    $a->summary = "[SMS message to {$cell} from {$from}]";
    $a->case_id = $cal->case_id;
    $a->sms_count = 1;
    $a->save();
    
    $cal->sms_act_id = $a->act_id;
    $cal->save();
  }
  
  else 
  {
    echo "SMS send failed: $sms_error\n";
    /*
    $cal->sms_send_failure++;
    
    if ($cal->sms_send_failures > 2)
    {
      $cal->sms_send = null;
    }
    */
    
    $cal->sms_send_failures = "1";
    $cal->save();
    return false;
  }
}

exit();

