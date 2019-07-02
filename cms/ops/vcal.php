<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/*
This file contains the code to compose new activity records, edit existing records, and to
warn the user before deleting records.
*/
chdir("..");

require_once ('pika_cms.php');

$pk = new pikaCms;

// VARIABLES
$C = '';
$screen = pl_grab_var('screen');
$a = pl_grab_vars('activities');
$act_url = pl_grab_var('act_url');

// Verify that act_type is initialized and clean
if (!isset($a['act_type']))
{
	$a['act_type'] = 'C';
}

else
{
	pl_menu_init('act_type');
	
	if (!array_key_exists($a['act_type'], $plMenus['act_type']))
	{
		pika_error_notice('Invalid act_type', "'{$a['act_type']}' is not a valid act_type.");
		exit();
	}
}

// act_url defines where to redirect the user after composing/editing records
$a['act_url'] = pl_grab_var('act_url');

if (null == $a['act_url'])
{
	if (!$_SERVER['HTTP_REFERER'])
	{
		$a['act_url'] = 'calendar.php';
	}
	
	else
	{
		$a['act_url'] = $_SERVER['HTTP_REFERER'];
	}
}

$dummy = '';

// end VARIABLES

// FUNCTIONS

function nl2encbr($string) {

	$maclinebreak = chr(13);
	$linlinebreak = chr(10);
	$winlinebreak = chr(13) . chr(10);
	$newlinebreak = '=0D=0A=' . $winlinebreak . ' ';
	$placeholder = '<br />';
	$string = str_replace($winlinebreak,$placeholder,$string);
	$string = str_replace($linlinebreak,$placeholder,$string);
	$string = str_replace($maclinebreak,$placeholder,$string);
	$string = str_replace($placeholder,$newlinebreak,$string);
	$string = "=$winlinebreak " . $string;
	
	return $string;
}

// This takes un-mogrified date and time to make a timestamp
// suitable for vCalendar format
function vc_timestamp($date, $time) {
	if (!$time) {
		$time='00:00:00';
	}
	
	$timestamp = str_replace('-','',$date) . 'T' . str_replace(':','',$time);
	return $timestamp;
}

function vc_duration($start_time, $hours) {
	if (!$hours) {
		$hours = 1;
	}
	$decimal_pos = strpos($hours,'.');
	if ($decimal_pos) {
		$minutes = substr($hours,$decimal_pos+1,-1);
		$minutes = $minutes*6;
		$hours = substr($hours,0,$decimal_pos);
	}
	$hours_field = str_pad(substr($start_time, 9, 2) + $hours, 2, "0", STR_PAD_LEFT);
	if ($decimal_pos) {
		$minutes_field = str_pad(substr($start_time, 11, 2) + $minutes, 2, "0", STR_PAD_LEFT);
	}
	$time = substr_replace($start_time,$hours_field,9,2);
	if ($decimal_pos) {
		$time = substr_replace($time,$minutes_field,11,2);
	}
	return $time;
}


// MAIN CODE
	// It's an existing activity... fetch its record
	$result = $pk->fetchActivity("$act_id");
	$b = DBResult::fetchRow($result);  // there should be only one record
	$a = array_merge($a, $b);
	/* this will clobber any same-keyed elements in the $a array (initialized
	at the top of the screen. */
	
	// enforce security level permissions
	if (!pika_authorize('read_act', $a))
	{
		// set up template, then display page
		$plTemplate["page_title"] = "Case: {$num}";
		$plTemplate["content"] = 'access denied';
		
		echo pl_template($plTemplate, 'templates/default.html');
		echo pl_bench('results');
		exit();
	}
/*	
	if (isset($a['pba_id']))
	{
		$a['owner_menu'] = 'Pro Bono Attorney:<br>' . pl_array_menu($pk->fetchPbAttorneyArray(), 'pba_id', $a['pba_id']);
	}
	
	else
	{
		$a['owner_menu'] = 'Staff:<br>' . pl_array_menu($pk->fetchStaffArray(), 'user_id', $a['user_id']);
	}
*/	
	if (!isset($a['case_id']))
	{
		// initialize case_id
		$a['case_id'] = null;
	}
	
	$a['new_case_menu'] = pika_act_case_menu($auth_row['user_id'], $a['case_id']);
	
	if ($a['case_id'])
	{
		$result = $pk->fetchCaseList(array('case_id' => $a['case_id']), $dummy);
		$case_row = DBResult::fetchRow($result);
	}
	
	if (isset($a["act_date"]))
	{
		$nav_date = $a["act_date"];  // save for later use
		$a["act_date"] = pl_unmogrify_date($a["act_date"]);
	}
	
	if (isset($a["act_time"]))
	{
		$nav_time = $a["act_time"];  // save for later use
		$a["act_time"] = pl_unmogrify_time($a["act_time"]);
	}
	
	if ($a['act_end_date'])
	{
		$nav_end_date = $a["act_end_date"];  // save for later use
		$a['act_end_date'] = pl_unmogrify_date($a["act_end_date"]);
	}

	if (isset($a["act_end_time"]))
	{
		$nav_end_time = $a["act_end_time"];  // save for later use
		$a["act_end_time"] = pl_unmogrify_time($a["act_end_time"]);
	}
	
	
	if (isset($a['number']))
	{
		// add a hyperlink to the case number
		$a['number_link'] = "<a href=\"contact.php?contact_id={$case_row['client_id']}\">{$case_row['contacts.last_name']}, {$case_row['contacts.first_name']} {$case_row['contacts.middle_name']} {$case_row['contacts.extra_name']}</a>,
			<a href=\"case.php?case_id={$a['case_id']}\">{$a['number']}</a>";
	}
	
	// type_desc is used to describe the act. type in the nav bar
	switch ($a['act_type'])
	{
		case 'K':
		
		$type_desc = 'Tickle';
		
		break;
		
		case 'T':
		
		$type_desc = 'Time Slip';
		
		break;
		
		case 'L':
		
		$type_desc = 'LSC Other Matters Record';
		
		break;
		
		case 'N':
		
		$type_desc = 'Case Note';
		
		break;
		
		case 'C':
		
		$type_desc = 'Appointment';
		
		break;
	}
	
	$a['form_action'] = 'dataops.php?screen=edit';
	$a['action_name'] = 'update_activity';
	$a['submit_button'] = "<input type=submit name='close_act' value='Save and Close' tabindex=1>";
	
	$a['vc_date_start'] = vc_timestamp($nav_date, $nav_time);
	if ($nav_end_date) {
		$a['vc_date_end'] = vc_timestamp($nav_end_date, $nav_end_time);
	} else {
		$a['vc_date_end'] = vc_duration($a['vc_date_start'],$a['hours']);
	}
	$a['vc_summary'] = nl2encbr($a['summary']);
	$a['vc_description'] = nl2encbr($a['notes']);
	$a['uid'] = md5(uniqid(rand(), true));
	
	/* SpellCheck??
	if ($sc)
	{
	$a["notes"] = stripslashes($sc);
	}
	*/
	
//	$C .= pl_template($a, "subtemplates/activity{$a['act_type']}.html");
	header("Content-type:text/calendar");
	header("Content-Disposition:filename=export.vcf");
	
	echo pl_template($a, "templates/vcal.txt");
//echo pl_template($plTemplate, 'templates/default.html');
echo pl_bench('results');
exit();

?>
