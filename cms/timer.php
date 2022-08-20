<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.net        */
/**********************************/


require_once ('pika-danio.php');
pika_init();
require_once('pikaActivity.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');
require_once('pikaCase.php');


// VARIABLES
$buffer = '';

$case_id = pl_grab_get('case_id');
$action = pl_grab_get('action');
$elapsed_mins = pl_grab_get('elapsed_mins');
$end_butt = pl_grab_get('end');
$pause_butt = pl_grab_get('pause');

$act_row = pl_clean_form_input($_GET);

if (pl_settings_get('autofill_time_funding') == 0)
{
	$act_row['funding'] = null;
}

$act_interval = pl_settings_get('act_interval');

// Generate cases menu
$filter['show_cases'] = '0';
$filter['user_id'] = $auth_row['user_id'];
$row_count = 0;
$open_cases_result = pikaMisc::getCases($filter,$row_count);
$open_case_menu_array = array();
while($row = DBResult::fetchRow($open_cases_result)) {
	$open_case_menu_array[$row['case_id']] = $row;
}

$case_menu_args = array();

if (pl_settings_get('autofill_time_funding') == 1)
{
	$case_menu_args = array('onchange=setFunding(this.value);');
}

$act_row['new_case_menu'] = pikaTempLib::plugin('case_menu', 'case_id',
	$act_row['case_id'], $open_case_menu_array, $case_menu_args);

if(is_numeric($act_row['case_id'])) {
	$case = new pikaCase($act_row['case_id']);
	$act_row['number'] = $case->number;
	
	if (!isset($act_row['funding']) && !$act_row['funding'] 
		&& pl_settings_get('autofill_time_funding') == 1) 
	{
		$act_row['funding'] = $case->funding;
	}
}
if(!isset($act_row['number']) || !$act_row['number']) {$act_row['number'] = '(No Case #)';}
$textformat = new pikaTempLib('subtemplates/textFormat.html',array());
$act_row['textFormat'] = $textformat->draw();

if (!is_null($end_butt))
{
	if($elapsed_mins >= 0)
	{
		$hours = $elapsed_mins / 60;	
	}
	else 
	{
		$hours = 0;
	}
	$act_row['act_date'] = date('Y-m-d');
	$act_row['hours'] = $hours;
	$act_row['user_id'] = $auth_row['user_id'];
	$act_row['completed'] = 1;
	$activity = new pikaActivity();
	if (isset($a['act_id'])) { unset($a['act_id']); }
	$activity->setValues($act_row);
	$activity->hours = $activity->roundHoursByInterval($hours,$act_interval);
	$activity->save();
	$template = new pikaTempLib('subtemplates/timer.html',array(),'timer-close');
} elseif (!is_null($pause_butt)) {
	$template = new pikaTempLib('subtemplates/timer.html',$act_row,'timer-pause');
} else {
	$act_row['act_time'] = pl_time_current_string();
	$template = new pikaTempLib('subtemplates/timer.html',$act_row,'timer');
}







$a['content'] = $template->draw();
$a['page_title'] = "Pop-Up Timer";

$default_template = new pikaTempLib('templates/empty.html',$a);
$buffer = $default_template->draw();
pika_exit($buffer);
?>
