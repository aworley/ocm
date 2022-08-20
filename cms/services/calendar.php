<?php

// Libraries
chdir("../");
require_once ('pika-danio.php');

// Token Based Authorization - Optional
// For clients w/o HTTP authorization built-in
if(isset($_GET['token']) && $_GET['token']) {
	$auth = base64_decode($_GET['token']);
	
	$auth_array = array();
	$x = explode("\"", $auth);
	$auth_array[] = $x[1];
	$auth_array[] = $x[3];
	
	$_SERVER['PHP_AUTH_USER'] = $auth_array[0];
	$_SERVER['PHP_AUTH_PW'] = $auth_array[1];
	define('PL_DISABLE_SECURITY',true);
	pika_init();
	require_once('app/lib/pikaAuthHttp.php');
	require_once('app/lib/pikaAuthDb.php');
	$auth = pikaAuthHttp::getInstance();
	$authdb = new pikaAuthDb('users','username','password');
	$auth->authenticate($authdb);
	$auth_row = pikaAuthHttp::getInstance()->getAuthRow();
}
else {
	define('PL_HTTP_SECURITY',true);
	pika_init();
}



$auth = '';
$auth_array = array();




require_once ('plFlexList.php');



// Functions
function ical_text_mogrify($x)
{
	return str_replace("\n", "\\n", str_replace("\r","",$x));
}

function ical_datetime_mogrify($d, $t)
{
	if (is_null($d) || is_null($t)) 
	{
		return "";
	}
	return date("Ymd", strtotime($d)) . "T" . date("His", strtotime($t));
}
// Variables
$base_url = pl_settings_get('base_url');
$time_zone = pl_settings_get('time_zone');
//$time_zone = 'America/New_York'; // America/New_York, America/Chicago, America/Denver, America/Phoenix, America/Los_Angeles

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE) {
	$cal_url= "https://".$_SERVER['HTTP_HOST'].$base_url;
}else { $cal_url= "http://".$_SERVER['HTTP_HOST'].$base_url; }

require_once('pikaAuth.php');
$auth_row = pikaAuthHttp::getInstance()->getAuthRow();
$user_id = $auth_row['user_id'];

pl_menu_get('act_type');
pl_menu_get('category');
pl_menu_get('funding');
pl_menu_get('yes_no');

// AMW - 2012-1-24 - Show appointments out through 18 months.
// AMW - 2012-1-25 - Show all future appointments until you hit the record limit.
$current_date = date('U');
// AMW - 2012-1-24 - Show 60 days prior.
$current_date = $current_date - (60*24*60*60);
$current_date = date('Y-m-d',$current_date);

// Main Code
$sql = "SELECT activities.*, cases.number
		FROM activities
		LEFT JOIN cases ON activities.case_id = cases.case_id
		WHERE activities.user_id='{$user_id}'
		AND act_date >= '{$current_date}'
		AND act_type IN ('C', 'K')
		ORDER BY act_date ASC, act_time ASC 
		LIMIT 2000;";

$result = DB::query($sql) or trigger_error(DB::error());
//echo $sql;
$ical_list = new plFlexList();
$ical_list->template_file = "subtemplates/ical/{$time_zone}/ical.txt";
$counter = 0;
while ($row = DBResult::fetchRow($result))
{
	$temp_description = "";
	$row['notes'] = ical_text_mogrify($row['notes']);  //str_replace("\r", "=0D=0A=", stripslashes($row['notes']))
	// Assemble the description field
	if(isset($row['notes']) && $row['notes']) {
		$temp_description .= "Notes: ".$row['notes'] . "\\n\\n";
	}
	if(isset($row['hours'])) {
		$temp_description .= "Hours: " . ($row['hours']+0) . "\\n";
	}
	if(isset($row['completed'])) {
		$temp_description .= "Completed: " . pl_array_lookup($row['completed'], $plMenus['yes_no']) . "\\n";
	}
	if(isset($row['act_type']) && $row['act_type']) {
		$temp_description .= "Activity Type: " . pl_array_lookup($row['act_type'],$plMenus['act_type']) . "\\n";
	}
	if(isset($row['category']) && $row['category']) {
		$temp_description .= "Category: " . pl_array_lookup($row['category'], $plMenus['category']) . "\\n";
	}
	if(isset($row['funding']) && $row['funding']) {
		$temp_description .= "Funding: " . pl_array_lookup($row['funding'],$plMenus['funding']) . "\\n";
	}
	if(isset($row['case_id']) && $row['case_id']) {
		$temp_description .= "Case: " . $row['number'] . "\\n";
	}
	$row['cal_url'] = $cal_url . "/activity.php?act_id={$row['act_id']}";
	$temp_description .= $row['cal_url'];
	
	$row['ical_description'] = $temp_description;
	$row['summary'] = ical_text_mogrify($row['summary']);  
	$row['start'] = ical_datetime_mogrify($row['act_date'], $row['act_time']);
	if(!$row['act_end_time']) {
		$row['end'] = $row['start'];
	}else {
		$row['end'] = ical_datetime_mogrify($row['act_date'], $row['act_end_time']);
	}
	$row['time_zone'] = $time_zone;
	$row['alarm'] = ical_datetime_mogrify($row['act_date'], $row['act_time']).";P1D;7;TICKLE - " .stripslashes($row['summary']);
	
	if (!is_null($row['act_date'])) {
		// TODO doesn't work
		//$row['ical_text'] = trim(pl_template('subtemplates/ical.txt', $row,'todo'));
		$row['ical_text'] = trim(pl_template("subtemplates/ical/{$time_zone}/ical.txt", $row, 'calendar'));
		$ical_list->addHtmlRow($row);
		$counter++;
	}
	
}
if($counter == 0) {
	$buffer = trim(pl_template("subtemplates/ical/{$time_zone}/ical.txt",array(),'flex_header') . pl_template("subtemplates/ical/{$time_zone}/ical.txt",array(),'flex_footer'));	
}else {
	$buffer = trim($ical_list->draw());
}

if(!isset($_GET['debug']))
{
	header("Content-Type: text/Calendar");
	header("Content-Disposition: attachment; filename=\"pika.ics\"");
}
exit($buffer);

?>
