<?php
define('PL_DISABLE_SECURITY', true);

chdir('../');
require_once('pika-danio.php');
pika_init();

function send_mail_notification($user_id, $case_id, $case_number, $sender_name)
{
	$safe_user_id = mysql_real_escape_string($user_id);
	
	if (is_numeric($safe_user_id) 
			&& strlen(pl_settings_get('smartpost_from_address')) > 0 
			&& strlen(pl_settings_get('smartpost_api_key')) > 0)
	{
		$result = mysql_query("SELECT email FROM users WHERE user_id = {$safe_user_id}");
		$row = mysql_fetch_assoc($result);
		
		// Send email via SparkPost.
		$to = $row['email'];
		$subject = "New SMS for {$case_number}";
		$message = "New SMS from {$sender_name}:  https://{$_SERVER['SERVER_NAME']}/{$base_url}/case.php?case_id={$safe_case_id}&screen=sms";
		
		$data_string = '{"options": {"sandbox": false}, "content": {"from": "' 
			. pl_settings_get('smartpost_from_address') 
			. '", "subject": "Curl API test", "text":"Testing SparkPost."}, "recipients": [{"address": "' . $to . '"}]}';
		
		$c = curl_init();
		curl_setopt($c, CURLOPT_URL, 'https://api.sparkpost.com/api/v1/transmissions');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($c, CURLOPT_TIMEOUT, 30);
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'Content-Type: application/json',
                                            'Authorization: ' . pl_settings_get('smartpost_api_key')
                                            ));
		//$status_code = curl_getinfo($c, CURLINFO_HTTP_CODE);
		$result=curl_exec($c);
		curl_close ($c);
		$result = json_decode($result);
	}
}

$number = $_POST['From'];
$body = $_POST['Body'];

$case_id = '';

$clean_number = mysql_real_escape_string($number);
$phone = substr($clean_number, 5, 3) . '-' . substr($clean_number, 8);
$area_code = substr($clean_number, 2, 3);

$result = mysql_query("SELECT conflict.case_id, first_name, middle_name, last_name, extra_name 
	FROM contacts 
	LEFT JOIN conflict ON contacts.contact_id = conflict.contact_id
	LEFT JOIN cases ON conflict.case_id = cases.case_id
	WHERE conflict.relation_code = 1
	AND ((area_code = '{$area_code}' AND phone = '{$phone}') 
	OR (area_code_alt = '{$area_code}' AND phone_alt = '{$phone}'))
	ORDER BY open_date DESC LIMIT 1");

while ($row = mysql_fetch_assoc($result))
{
	$case_id = $row['case_id'];
	$sender_name = pl_text_name($row);
}

if ($case_id != '')
{
	require_once('pikaActivity.php');
	$a = new pikaActivity();
	$a->act_type = 'S';
	$a->act_date = date('Y-m-d');
	$a->act_time = date('H:i:s');
	$a->notes = $body;
	$a->summary = "[SMS message from {$sender_name} at ({$area_code}) {$phone}]";
	$a->case_id = $case_id;
	$a->save();
	
	// Send mail notification to the case handlers.
	require_once('pikaCase.php');
	$c = new pikaCase($case_id);
	send_mail_notification($c->user_id);
	send_mail_notification($c->cocounsel1);
	send_mail_notification($c->cocounsel2);
}

else
{
	require_once('pikaActivity.php');
	$a = new pikaActivity();
	$a->act_type = 'S';
	$a->act_date = date('Y-m-d');
	$a->act_time = date('H:i:s');
	$a->notes = $body;
	$a->summary = "[SMS message from {$sender_name} at ({$area_code}) {$phone}]";
	$a->save();

	$case_id = 'undefined';
}


header('Content-Type: text/xml');
?>
 
<Response>
    <Message>
        Thanks!  Your message has been sent to case ID <?php echo $case_id ?>.
    </Message>
</Response>