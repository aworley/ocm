<?php

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
$notes_form['action_name'] = 'add_activity';
$notes_form['act_url'] = "case.php?case_id={$case_row['case_id']}&screen=act";

$notes_form['funding'] = null;

if (pl_settings_get('autofill_time_funding') == 1)
{
	$notes_form['funding'] = $case_row['funding'];
}

// Hack, for LASGC...
if (isset($case_row['project'])) 
{
	$notes_form['project'] = $case_row['project'];
}

if ($order == 'asc')
{
	$notes_order = 'ASC';
}

else
{
	$notes_order = 'DESC';
}

pl_menu_set_temp('user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('act_user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('act_pba_id', pikaMisc::fetchPbAttorneyArray());


$notes_header = new pikaTempLib('subtemplates/case_act.html', $case_row, 'notes_header');
$notes_form['notes_header'] = $notes_header->draw();

require_once('pikaCase.php');
$case1 = new pikaCase($case_id);

$result = $case1->getNotes($notes_order, $paging, $offset, $notes_count, $hours_worked);


// A link to reverse the current display order of activities
// Note:  Show this only if there are cases to display


if (0 == $notes_count)
{
	$notes_form['notes_list'] = "<p><em>No notes exist for this case.</em>\n";
}

else  // only show these if there are actually activities to sort...
{
	require_once('plFlexList.php');
	$notes_list = new plFlexList();
	$notes_list->template_file = 'subtemplates/case_act.html';
	$notes_list->table_url = "{$base_url}/case.php";
	$notes_list->get_url = "case_id={$case_id}&screen=act&";
	$notes_list->order_field = 'act_date';
	$notes_list->order = $notes_order;
	$notes_list->records_per_page = $paging;
	$notes_list->page_offset = $offset;

	
	if (1 < $notes_count)
	{
		$case_row['next_order'] = 'desc';
		
		if ($notes_order == 'DESC')
		{
			$case_row['next_order'] = 'asc';
		}
		$notes_order = new pikaTempLib('subtemplates/case_act.html', $case_row, 'notes_order');
		$notes_form['notes_order'] = $notes_order->draw();
	}
	
	while ($row = DBResult::fetchRow($result))
	{
		if (strlen($row['summary']) > 0 && strlen($row['notes']) > 0)
		{
			$row['summary'] .= "\n";
		}
		
		if (strlen($row['hours']) < 1) 
		{
			$row['hours'] = "0";
		}
		$row['type_desc'] = pl_array_lookup($row['act_type'], $menu_act_type);
		$row['act_timestamp'] = pl_date_unmogrify($row["act_date"]) . ' ' . pl_time_unmogrify($row["act_time"]);
		$notes_list->addFancyTextRow($row);
		
	}
	$notes_list->total_records = $notes_count;
	$notes_form['notes_list'] = $notes_list->draw();
}

if($hours_worked > 0) {
	$case_row['hours_worked'] = $hours_worked;
} else {$case_row['hours_worked'] = "0";}

if (1 == $hours_worked) 
{
	$case_row['hours_label'] = 'hour';
}

else 
{
	$case_row['hours_label'] = 'hours';
}

$notes_footer = new pikaTempLib('subtemplates/case_act.html', $case_row, 'notes_footer');
$notes_form['notes_footer'] = $notes_footer->draw();
$text_format = new pikaTempLib('subtemplates/textFormat.html',array());
$notes_form['textFormat'] = $text_format->draw();

$notes_form['owner_menu'] = "Staff:<br>\n";
$notes_form['owner_menu'] .= pikaTempLib::plugin('menu','user_id',$notes_form['user_id'],$plMenus['user_id']);
$notes_form['owner_menu'] .= "<br/>";

$default_template = new pikaTempLib('subtemplates/activity.html', $notes_form, 'N');

require_once('pikaInterview.php');
$result = pikaInterview::getInterviewsDB(1);
$menu_interviews = array();

while($row = DBResult::fetchRow($result))
{
	$menu_interviews[$row['interview_id']] = $row['name'];
}

$default_template->addMenu('interviews',$menu_interviews);

$C .= $default_template->draw();

/*

NE program had the following code on their site.  I don't think 
$auth_row['pba_id'] will ever be present, so I did not merge this into the
main code base.

---
> $menu_staff = pikaMisc::fetchStaffArray();
> $menu_pba = pikaMisc::fetchPbAttorneyArray();
> // Generate user_id menus
> if(isset($auth_row['pba_id']) && is_numeric($auth_row['pba_id'])) {
> 	$owner = $auth_row['pba_id'];
> 	$notes_form['owner_menu'] = "Pro Bono Attorney:<br>\n";
> 	$notes_form['owner_menu'] .= pikaTempLib::plugin('menu','pba_id',$owner,$menu_pba);
> } else {
> 	$owner = $auth_row['user_id'];
> 	$notes_form['owner_menu'] = "Staff:<br>\n";
> 	$notes_form['owner_menu'] .= pikaTempLib::plugin('menu','user_id',$owner,$menu_staff);
> }
128,130c145,157
< $notes_form['owner_menu'] = "Staff:<br>\n";
< $notes_form['owner_menu'] .= pikaTempLib::plugin('menu','user_id',$notes_form['user_id'],$plMenus['user_id']);
< $notes_form['owner_menu'] .= "<br/>";
---
*/

?>