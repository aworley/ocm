<?php 

/**********************************/
/* Pika CMS (C) 2008 Aaron Worley */
/* http://www.pikasoftware.com    */
/**********************************/

require_once('pika-danio.php');
pika_init();
require_once('pikaTempLib.php');
require_once('pikaCase.php');
require_once('pikaMisc.php');
require_once('pikaActivity.php');



// VARIABLES
$buffer = '';
$filter = array();
$base_url = pl_settings_get('base_url');
$base_directory = pl_settings_get('base_directory');

$action = pl_grab_get('action');
$case_id = pl_grab_get('case_id');
$act_id = pl_grab_get('act_id');
$act_type = pl_grab_get('act_type','C');
$act_date = pl_grab_get('act_date');
$act_url = pl_grab_get('act_url');

$funding = null;

if (pl_settings_get('autofill_time_funding') == 1)
{
	$funding = pl_grab_get('funding');
}


$menu_act_type = pl_menu_get('act_type');
$menu_staff = pikaMisc::fetchStaffArray();
$menu_pba = pikaMisc::fetchPbAttorneyArray();


// Attempt to determine act_url if not already provided
if (strlen($act_url) < 1) {
	$act_url = 'cal_day.php';
	
	if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']) {
		$http_referer = $_SERVER['HTTP_REFERER'];
		$qs_position = strpos($http_referer,'?');
		if($qs_position) { // Need to remove QS
			$http_referer = substr($http_referer,0,$qs_position);
		}
		if(strpos($http_referer,'/')) { // Linux
			$act_url_array = explode('/',$http_referer);
			$act_url_temp = array_pop($act_url_array);
			
			if(file_exists($base_directory . '/' . $act_url_temp)) {
				// Match found
				$act_url = $act_url_temp;
			}
		} elseif (strpos($http_referer,"\\")) { // Windows
			$act_url_array = explode("\\",$http_referer);
			$act_url_temp = array_pop($act_url_array);
			
			if(file_exists($base_directory . "\\" . $act_url_temp)) {
				// Match found
				$act_url = $act_url_temp;
			}
		}
	}
	
	if (strlen($act_url) < 1) {  // If act_url is still blank default to calendar
		$act_url = 'cal_day.php';
	}
}

// Load activity record or populate form to create new activity record
$act_row = array();
if(!is_numeric($act_id)) {
	if($act_date) {
		$act_row['act_date'] = $act_date;
	} else {
		$act_row['act_date'] = date('Y-m-d');
	}
	$act_row['case_id'] = $case_id;
	$act_row['funding'] = $funding;
	$act_row['act_type'] = $act_type;
	$act_row['act_time'] = pl_time_current_string();
	$act_row['user_id'] = pl_grab_get('user_id');
	$act_row['pba_id'] = pl_grab_get('pba_id');

	// AMW 2014-07-23 - Added for SMRLS.
	if ($act_type == 'T') 
	{
		$act_row['completed'] = 1;
	}	
} else {
	$activity = new pikaActivity($act_id);
	$act_row = $activity->getValues();
	$act_row['act_time'] = pl_time_unmogrify($act_row['act_time']);
	$act_type = $activity->act_type;
	$act_row['deleteActivity'] = "<img src=\"{$base_url}/images/point.gif\"> " .
		"<a href=\"{$base_url}/activity.php?act_id={$act_id}&action=confirm_delete\">Delete this record</a>";
}

// Dynamically determine Activity type based on act_type
$type_desc = pl_array_lookup($act_type,$menu_act_type);

// If attempting to delete record make user confirm action
if($action == 'confirm_delete') {
	$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; ";
	if(isset($act_row['case_id']) && is_numeric($act_row['case_id'])) {
		$case = new pikaCase($act_row['case_id']);
		$act_row['number'] = $case->number;
		if(!isset($act_row['number']) || !$act_row['number']) {$act_row['number'] = '(No Case #)';}
		$a['nav'] .= "<a href=\"{$base_url}/case.php?case_id={$act_row['case_id']}\">{$act_row['number']}</a> &gt; ";
	}
	$a['nav'] .= "Delete {$type_desc}";
	$sub_directive = 'access_denied';
	if((pl_settings_get('db_name') == 'legalaidnebraska' && pika_authorize('edit_act',$act_row))
	 	|| pika_authorize('delete_act',$act_row))
	{
		$sub_directive = 'confirm_delete';
	}
	$template = new pikaTempLib("subtemplates/activity.html",$act_row,$sub_directive);		
	$a['content'] = $template->draw();
	$a['page_title'] = "Confirm Delete {$type_desc}";
	$default_template = new pikaTempLib('templates/default.html',$a);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}


// Generate user_id menus
if(isset($act_row['pba_id']) && is_numeric($act_row['pba_id'])) {
	$owner = $act_row['pba_id'];
	$filter['pba_id'] = $owner;
	$act_row['owner_menu'] = "Pro Bono Attorney:<br>\n";
	$act_row['owner_menu'] .= pikaTempLib::plugin('menu','pba_id',$owner,$menu_pba);
	//$act_row['staff_menu'] = pikaTempLib::plugin('menu','user_id',$owner,$menu_pba);
} elseif(isset($act_row['user_id']) && is_numeric($act_row['user_id'])) {
	$owner = $act_row['user_id'];
	$filter['user_id'] = $owner;
	$act_row['owner_menu'] = "Staff:<br>\n";
	$act_row['owner_menu'] .= pikaTempLib::plugin('menu','user_id',$owner,$menu_staff);
	//$act_row['staff_menu'] = pikaTempLib::plugin('menu','user_id',$owner,$menu_staff);
} elseif(isset($auth_row['pba_id']) && is_numeric($auth_row['pba_id'])) {
	$owner = $auth_row['pba_id'];
	$filter['pba_id'] = $owner;
	$act_row['owner_menu'] = "Pro Bono Attorney:<br>\n";
	$act_row['owner_menu'] .= pikaTempLib::plugin('menu','pba_id',$owner,$menu_pba);
	//$act_row['staff_menu'] = pikaTempLib::plugin('menu','user_id',$owner,$menu_pba);
} else {
	$owner = $auth_row['user_id'];
	$filter['user_id'] = $owner;
	$act_row['owner_menu'] = "Staff:<br>\n";
	$act_row['owner_menu'] .= pikaTempLib::plugin('menu','user_id',$owner,$menu_staff);
	//$act_row['staff_menu'] = pikaTempLib::plugin('menu','user_id',$owner,$menu_staff);
}
$act_row['owner_menu'] .= "<br/>";
// Generate cases menu
$filter['show_cases'] = '0';
$row_count = 0;
$open_cases_result = pikaMisc::getCases($filter,$row_count,'client_name','ASC',0,500);
$open_case_menu_array = array();
while($row = mysql_fetch_assoc($open_cases_result)) {
	$open_case_menu_array[$row['case_id']] = $row;
}

$case_menu_args = array();

if (pl_settings_get('autofill_time_funding') == 1)
{
	$case_menu_args = array('onchange=setFunding(this.value);');
}

$act_row['new_case_menu'] = pikaTempLib::plugin('case_menu', 'case_id', 
	$act_row['case_id'], $open_case_menu_array, $case_menu_args);
$act_row['lsc_problem_menu'] = pikaTempLib::plugin('lsc_problem','problem',$act_row);
//$act_row['act_date'] = pl_date_unmogrify($act_row['act_date']);
if(isset($act_row['act_end_date'])) {
	$act_row['act_end_date'] = pl_date_unmogrify($act_row['act_end_date']);
}

$act_row['act_url'] = $act_url;
		
if (!pika_authorize('read_act', $act_row)) {
	// set up template, then display page
	$a['content'] = 'Access Denied';
	$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
	Edit Activity";
	$a['page_title'] = "Edit Activity";
	$default_template = new pikaTempLib('templates/default.html',$a);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}
		
//print_r($act_row);
$textformat = new pikaTempLib('subtemplates/textFormat.html',array());
$act_row['textFormat'] = $textformat->draw();

require_once('pikaInterview.php');
$result = pikaInterview::getInterviewsDB(1);
$menu_interviews = array();
while($row = mysql_fetch_assoc($result)) {
        $menu_interviews[$row['interview_id']] = $row['name'];
}

$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; ";
if(isset($act_row['case_id']) && is_numeric($act_row['case_id'])) {
	$case = new pikaCase($act_row['case_id']);
	
	// If funding is blank populate from case
	if(!isset($act_row['funding']) && !$act_row['funding'] 
		&& !is_numeric($act_id) && pl_settings_get('autofill_time_funding') == 1) 
	{
		$act_row['funding'] = $case->funding;
	}
	
	$act_row['number'] = $case->number;
	if(!isset($act_row['number']) || !$act_row['number']) {$act_row['number'] = '(No Case #)';}
	$a['nav'] .= "<a href=\"{$base_url}/case.php?case_id={$act_row['case_id']}\">{$act_row['number']}</a> &gt; ";
}
$a['nav'] .= "Edit {$type_desc}";
// Act lookup - check for legacy templates
if (file_exists(pl_custom_directory() . "/subtemplates/activity{$act_type}.html")){ // -custom/subtemplates/activityX.html
	$template = new pikaTempLib("{$custom_dir}/subtemplates/activity{$act_type}.html",$act_row);
}elseif (file_exists("subtemplates/activity{$act_type}.html")){	 // subtemplates/activityX.html
	$template = new pikaTempLib("subtemplates/activity{$act_type}.html",$act_row);
}else { // Standard location (Pika 4.0+)
	$template = new pikaTempLib("subtemplates/activity.html",$act_row,$act_type);	
}

$template->addMenu('interviews',$menu_interviews);  

$a['content'] = $template->draw();

/*	The activity lock setting prevents the date, funding, and hours from being
		edited if 'x' number of days have passed since the record act_date.
		Enforce this setting here.
		
		TODO:  also put to code in ops/update_activity.php to help enforce this.
		*/

$lock_max_days = pl_settings_get('activity_lock_max_days');

if ($lock_max_days > 0 && $auth_row['group_id'] != 'system')
{
	$act_buffer = $a['content'];
	
	if ((time() - strtotime($act_row['act_date']))  / (60 * 60 * 24) > $lock_max_days)
	{
		$act_buffer = str_replace('onclick="openCalendar(', 'disabled onclick="openCalendar(', $act_buffer);
		$act_buffer = str_replace('name="act_date"', 'disabled name="act_date"', $act_buffer);
		$act_buffer = str_replace('name="funding"', 'disabled name="funding"', $act_buffer);
		$act_buffer = str_replace('name="hours"', 'disabled name="hours"', $act_buffer);
	}

	$a['content'] = $act_buffer;
}

/*	End of the activity lock section. */

// If this is a new record, 'created' won't be present, so check if it exists first.
if (isset($act_row['created']) && $act_row['created'])
{
	$c_date = pl_date_unmogrify(substr($act_row['created'], 0, 10));
	$c_time = substr($act_row['created'], 10);
	$a['content'] .= '<p class="muted">Record created on ' . $c_date . $c_time . '</p>';
}

$a['page_title'] = "Activity Screen";
		
$default_template = new pikaTempLib('templates/default.html',$a);
$buffer = $default_template->draw();


pika_exit($buffer);


?>
