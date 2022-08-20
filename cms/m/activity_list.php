<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/
//  06-02-2010 - caw - copied and pasted from activity.php for Pika Mobile 

/* These screens display case information. */
chdir('..');

require_once('pika-danio.php');
pika_init();



require_once('pikaCase.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');
require_once('pikaCaseTab.php');
require_once('plFlexList.php');


// VARIABLES
$main_html = array();  // Values for the main HTML template.
$base_url = pl_settings_get('base_url');
$screen = pl_grab_get('screen', 'act');
$clean_screen = pl_clean_file_name($screen);
$case_id = pl_grab_get('case_id', null, 'number');

// BEGIN MAIN CODE...

// first off, make sure there's a case_id
if (!is_numeric($case_id))
{
	trigger_error('No case_id was specified');
}

/* Get case record data (it'll be needed on every page is some form), store in $case_row. */
$case1 = new pikaCase($case_id);
$case_row = $case1->getValues();

// Prevent JS insertion attacks.
$dirty_case_row = $case_row;
$case_row = pl_clean_html_array($case_row);

// Do this after HTML tags are stripped out so the line break is preserved.
if (is_numeric($case1->getValue('client_id')))
{
	$case_row['client_address'] = nl2br(pl_text_address($case_row));
}


// ENFORCE PERMISSIONS
if (!pika_authorize('read_case', $dirty_case_row))
{
	// set up template, then display page
	$main_html['content'] = "This case is not viewable.";
	$default_template = new pikaTempLib('m/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}



// CASE TAB MODULE


// The HTML displayed by the case tab module.


//	include("modules/case-{$clean_screen}.php");

//**  06-02-2010 - caw - copied and pasted the below info from modules->case-act.php
// ** to support Pika Mobile

// CASE NOTES TAB

$hours_worked = 0;
$paging = $_SESSION['paging'];
$order = pl_grab_var('order');
$offset = pl_grab_get('offset', 0);

$menu_act_type = pl_menu_get('act_type');


$notes_form = array();

$notes_count = $hours_worked = 0;
$notes_form['act_date'] = date('m/d/Y');
$notes_form['act_time'] = pl_time_current_string(); //date('h:i A');
$notes_form['user_id'] = $auth_row['user_id'];
$notes_form['case_id'] = $case_row['case_id'];
$notes_form['number'] = $case_row['number'];

pl_menu_set_temp('user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('act_user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('act_pba_id', pikaMisc::fetchPbAttorneyArray());

//require_once('pikaCase.php');
//$case1 = new pikaCase($case_id);

$result = $case1->getNotes('DESC', $paging, $offset, $notes_count, $hours_worked);
// check if there are any case notes
/*if (mysql_num_rows($result) == 0)
{
	$notes_form['activity_list'] = "<p><em>No notes exist for this case.</em>\n";	
}
else  
{*/
	
	$activity_list = new plFlexList();
	$activity_list->template_file = 'm/activity_list.html';	
	
	$activity_list->table_url = "{$base_url}/case.php";
	$activity_list->get_url = "case_id={$case_id}&screen=act&";
	$activity_list->order_field = 'act_date';
	$activity_list->order = $notes_order;
	$activity_list->records_per_page = $paging;
	$activity_list->page_offset = $offset;
	
	while ($row = DBResult::fetchRow($result))
	{
		if (strlen($row['summary']) > 0 && strlen($row['notes']) > 0)
		{				
			$row['summary'] .= "\n";										
		}
		
		$row['type_desc'] = pl_array_lookup($row['act_type'], $menu_act_type);			
		$row['act_timestamp'] = pl_date_unmogrify($row["act_date"]) . ' ' . pl_time_unmogrify($row["act_time"]);
		

		$activity_list->addRow($row);
		$activity_list->total_records++;
	}	
	$notes_form['activity_list'] = $activity_list->draw();
//}


$template = new pikaTempLib('m/activity_list.html',$notes_form);
$main_html['content'] .= $template->draw();

//echo(print_r($main_html));

$default_template = new pikaTempLib('m/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>