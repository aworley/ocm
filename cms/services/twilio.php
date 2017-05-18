<?php
define('PL_DISABLE_SECURITY', true);

chdir('../');
require_once('pika-danio.php');
pika_init();

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