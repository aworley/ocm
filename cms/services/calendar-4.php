<?php

/*	AMW 2018-12-24 This is the iCal service for organizations still running v.4
 		or lower, the original file in v.4 had an issue related to the function
		unserialize, and this version contains the fix.
		*/ 

// Token Based Authorization - Optional
// For clients w/o HTTP authorization built-in

$auth = '';
$auth_array = array();

if(isset($_GET['token']) && $_GET['token']) {
	$auth = base64_decode($_GET['token']);
	
	$auth_array = array();
	$x = explode("\"", $auth);
	$auth_array[] = $x[1];
	$auth_array[] = $x[3];
		
	$_SERVER['PHP_AUTH_USER'] = $auth_array[0];
	$_SERVER['PHP_AUTH_PW'] = $auth_array[1];
}

// Libraries
chdir("../");
require_once ('pika-danio.php');
pika_init();
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

$user_id = $auth_row['user_id'];

pl_menu_get('act_type');
pl_menu_get('category');
pl_menu_get('funding');
pl_menu_get('yes_no');

$interval = 30;
if(isset($_SESSION['def_ical_interval']) && is_numeric($_SESSION['def_ical_interval'])) {
	$interval = $_SESSION['def_ical_interval'];
}
$current_date = date('U');
$end_date = $current_date + ($interval * 24 * 60 * 60); // End Date range
$current_date = $current_date - (2*24*60*60); // Show 2 days prior
$current_date = date('Y-m-d',$current_date);
$end_date = date('Y-m-d',$end_date);


// Main Code
$sql = "SELECT activities.*, cases.number
		FROM activities
		LEFT JOIN cases ON activities.case_id = cases.case_id
		WHERE 1 
		AND activities.user_id='{$user_id}'
		AND act_date >= '{$current_date}'
		AND act_date <= '{$end_date}'
		ORDER BY act_date DESC, act_time DESC 
		LIMIT 1000;";

$result = mysql_query($sql) or trigger_error(mysql_error());
//echo $sql;
$ical_list = new plFlexList();
$ical_list->template_file = "subtemplates/ical/{$time_zone}/ical.txt";
$counter = 0;
while ($row = mysql_fetch_assoc($result))
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
$file_size = strlen($buffer);

header("Content-Type: text/Calendar");
header("Content-Disposition: attachment; filename=\"pika.ics\"");
header("Content-Length: {$file_size}");

pika_exit($buffer);

?>
