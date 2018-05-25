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
pika_init();

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
$sql .= "WHERE sms_send_time < {$ut_safe} AND sms_send_failures != 1 ";
$sql .= "AND sms_act_id is NULL";
$result = mysql_query($sql);

while ($row = mysql_fetch_assoc($result))
{
  $sms_date = pl_date_unmogrify($row['act_date']);
  $sms_time = pl_time_unmogrify($row['act_time']);
  $message = $message_text . "\n\nDATE: {$sms_date}\nTIME: {$sms_time}\n\nThank you!";
  
  $cal = new pikaActivity($act_id);
  
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
   
  $sms = $client->account->messages->create($cell, array('from' => $from, 'body' => $message));
  $sms_result = json_decode($sms);
  
  // If successful, record the SMS as an activity record, then NULL out sms_send
  // and record sms_act_id.
  if ($sms_result->status == 'sent')
  {
    $a = new pikaActivity();
    $a->act_type = 'S';
    $a->act_date = date('Y-m-d');
    $a->act_time = date('H:i:s');
    $a->notes = $message;
    $a->summary = "[SMS message to {$cell} from {$from}]";
    $a->case_id = $case_id;
    $a->save();
    
    $cal->sms_send_time = null;
    $cal->sms_act_id = $a->act_id;
    $cal->save();
  }
  
  else 
  {
    /*
    $cal->sms_send_failure++;
    
    if ($cal->sms_send_failures > 2)
    {
      $cal->sms_send = null;
    }
    */
    
    $cal->sms_send_failure = true;
    $cal->sms_send_time = null;
    $cal->save();
    return false;
  }
}

pika_exit();
