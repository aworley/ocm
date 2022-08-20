<?php

require_once ('pika_cms.php'); 


// VARIABLES

$pk = new pikaCms($db);
$C = '';
$t = new plTable();  // completed calendar items
$p = new plTable();  // pending calendar items
$overdue = new plTable();  // overdue calendar items
$todo = new plTable();  // todo list

$fund_breakdown = new plTable();
$tbf = array();

$plMenus['user_id'] = $pk->fetchStaffArray();
$plMenus['funding'] = pl_table_array('funding');
pl_menu_init('category');
pl_menu_init('act_type');

// Gotta check strlen's on $cal_date, $user_it to prevent zero-length strings.
$cal_date = pl_grab_var('cal_date', 'GET', null, 'date');

if (strlen($cal_date) < 1)
{
	$cal_date = date('Y-m-d');
}

$user_id = pl_grab_var('user_id');

if (strlen($user_id) < 1)
{
	$user_id = $auth_row['user_id'];
}


$C = '';
$content = array();

// END VARIABLES



// Pending and Completed tables

$columns = array('Type', 'Time', 'Description', 'Case Info.', 'Cmplt, Hrs');

$t->assignLabels(array('Type', 'Time', 'Description', 'Case Info.', 'Hours'));
$t->sortable = FALSE;
$t->max_length = 1000;
$t->rowa_bg = 'row1';
$t->rowb_bg = 'row2';
$t->show_pager = FALSE;


$p->assignLabels($columns);
$p->sortable = FALSE;
$p->max_length = 1000;
$p->rowa_bg = 'row1';
$p->rowb_bg = 'row2';
$p->show_pager = FALSE;


$overdue->assignLabels($columns);
$overdue->sortable = FALSE;
$overdue->max_length = 1000;
$overdue->rowa_bg = 'row1';
$overdue->rowb_bg = 'row2';
$overdue->show_pager = FALSE;


$todo->assignLabels(array('Type', 'Description', 'Case Info.', 'Cmplt, Hrs'));
$todo->sortable = FALSE;
$todo->max_length = 1000;
$todo->rowa_bg = 'row1';
$todo->rowb_bg = 'row2';
$todo->show_pager = FALSE;

$filter["starting"] = $cal_date;
$filter["ending"] = $cal_date;
$filter["user_id"] = $user_id;

$total_time = 0;
$target = '';

$i = 0;

// START completed records
$result = $pk->getActivitiesCompleted($user_id, $cal_date);

while ($row = DBResult::fetchRow($result))
{
	$a = array();
	$target = '';
	$phone_str = '';
	$i++;

	if (pika_authorize('read_act', $row))
	{
		if ($pikaPopupGroupie == 1)
		{
			$target = ' target="_blank"';
		}
		
		$a[] = "<a href='activity.php?act_id={$row['act_id']}'>{$plMenus['act_type'][$row['act_type']]}</a>";
		
		// time of day
		$z = pl_unmogrify_time($row['act_time']);
		if (isset($row['act_end_time']) && $row['act_end_time'])
		{
			$z .= "<br/>- " . pl_unmogrify_time($row['act_end_time']);
		}
		
		$a[] = $z;
		
		// stuff
		// AMW - 2012-5-29 - Stop XSS.
		$a[] = pl_clean_html($row['summary']) . '<br/>' . substr(pl_array_lookup($row['category'], $plMenus['category']), 0, 10);

		// case information
		$case_cell = '';
		
		$case_cell .= "<a href='case.php?case_id={$row['case_id']}'$target>{$row['number']}</a>";
		
		if ($row['client_id'])
		{
			$case_cell .= "<br/>\n<a href='contact.php?contact_id={$row['client_id']}'>{$row['last_name']}, {$row['first_name']}</a>";
		}
		
		if ($row['area_code'] || $row['phone'] || $row['phone_notes'])
		{
			$case_cell .= "<br/>\n" . pl_format_phone($row);
		}
		
		if (isset($row['court_name']) && $row['court_name'])
		{
			$case_cell .= "<br/>\n" . $row['court_name'];
		}
		
		$case_cell .= '&nbsp;';
		
		$a[] = $case_cell;
		
		// hours
		if ($row['hours'])
		{
			$a[] = $row['hours'];
			$total_time += $row['hours'];
		}
	}

	else 
	{
		$a[] = "$z";
		$a[] = "&nbsp;";
		$a[] = $plMenus['user_id'][$row['user_id']];
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";		
		$a[] = $row['js'];
	}

	// AMW - 2012-5-29 - Stop XSS.
	//$a[''] = pl_clean_html($a[2]);
	
	$t->addRow($a);
	if (!isset($tbf[$row['funding']]))
	{
		$tbf[$row['funding']] = 0;
	}
	
	$tbf[$row['funding']] += $row['hours'];
}
// END completed records


// START Pending records
if ($cal_date == date('Y-m-d'))
{
	// 20130103 MDF
	$day_cal_time = date("H:i:00");
}
else 
{
	$day_cal_time = null;
}
$result = $pk->getActivitiesPending($user_id, $cal_date, $day_cal_time);

while ($row = DBResult::fetchRow($result))
{
	$a = array();
	$target = '';
	$phone_str = '';
	$i++;

	if (pika_authorize('read_act', $row))
	{
		if ($pikaPopupGroupie == 1)
		{
			$target = ' target="_blank"';
		}

		// act type
		$a[] = "<a href='activity.php?act_id={$row['act_id']}'>{$plMenus['act_type'][$row['act_type']]}</a>";
		
		// time of day
		$z = pl_unmogrify_time($row['act_time']);
		if (isset($row['act_end_time']) && $row['act_end_time'])
		{
			$z .= "<br/>- " . pl_unmogrify_time($row['act_end_time']);
		}
		if ($row['act_date'] != $cal_date && $row['act_date'])
		{
			$z = pl_date_unmogrify($row['act_date']) . "<br/>" . $z;
		}
		if ('' == ltrim($z) || is_null($z))
		{
			$z = "[edit]";
		}
		
		$a[] = $z;
		
		// Description
		$row['summary'] . '<br/>';
		if(isset($plMenus['category'][$row['category']])) {
			$row['summary'] .=  substr($plMenus['category'][$row['category']], 0, 10);
		}
		$a[] = $row['summary'];
		// case information
		$case_cell = '';
		
		$case_cell .= "<a href=\"case.php?case_id={$row['case_id']}\"$target>{$row['number']}</a>";
		
		if ($row['client_id'])
		{
			$case_cell .= "<br/>\n<a href='contact.php?contact_id={$row['client_id']}'>{$row['last_name']}, {$row['first_name']}</a>";
		}
		
		if ($row['area_code'] || $row['phone'] || $row['phone_notes'])
		{
			$case_cell .= "<br/>\n<span style=\"white-space: nowrap;\">" . pl_format_phone($row) . "</span>\n";
		}
		
		if (isset($row['court_name']) && $row['court_name'])
		{
			$case_cell .= "<br/>\n" . $row['court_name'];
		}
		
		$a[] = $case_cell;
		
		
		// Closing Fields
		$a[] = "<span style=\"white-space: nowrap;\">
			<input type=\"checkbox\" name=\"completed[{$row['act_id']}]\"/>
			<input type=\"text\" name=\"hours[{$row['act_id']}]\" size=\"3\" maxlength=\"3\"/></span>";
	}

	else 
	{
		$a[] = "$z";
		$a[] = "&nbsp;";
		$a[] = $plMenus['user_id'][$row['user_id']];
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = $row['summary'];
	}

	$p->addRow($a);
}
// END pending records


// START overdue records
$result = $pk->getActivitiesOverdue($user_id);

while ($row = DBResult::fetchRow($result))
{
	$a = array();
	$target = '';
	$phone_str = '';
	$i++;

	if (pika_authorize('read_act', $row))
	{
		if ($pikaPopupGroupie == 1)
		{
			$target = ' target="_blank"';
		}

		// act type
		$a[] = "<a href='activity.php?act_id={$row['act_id']}'>{$plMenus['act_type'][$row['act_type']]}</a>";
		
		// time of day
		$z = pl_unmogrify_time($row['act_time']);
		if (isset($row['act_end_time']) && $row['act_end_time'])
		{
			$z .= "<br/>- " . pl_unmogrify_time($row['act_end_time']);
		}
		if ($row['act_date'] != $cal_date && $row['act_date'])
		{
			$z = pl_date_unmogrify($row['act_date']) . "<br/>" . $z;
		}
		if ('' == ltrim($z) || is_null($z))
		{
			$z = "[edit]";
		}
		
		if ($row['act_date'] == $cal_date)
		{
			$a[] = "<span class=\"warn\">Today<br/>$z</span>";
		}
		
		else 
		{
			$a[] = $z;
		}
		
		// Description
		// AMW - 2012-5-29 - Stop XSS.
		$a[] = pl_clean_html($row['summary']) . '<br/>' . substr(pl_array_lookup($row['category'], $plMenus['category']), 0, 10);
		// case information
		$case_cell = '';
		
		$case_cell .= "<a href='case.php?case_id={$row['case_id']}'$target>{$row['number']}</a>";
		
		if ($row['client_id'])
		{
			$case_cell .= "<br/>\n<a href='contact.php?contact_id={$row['client_id']}'>{$row['last_name']}, {$row['first_name']}</a>";
		}
		
		if ($row['area_code'] || $row['phone'] || $row['phone_notes'])
		{
			$case_cell .= "<br/>\n<span style=\"white-space: nowrap;\">" . pl_format_phone($row) . "</span>\n";
		}
		
		if (isset($row['court_name']) && $row['court_name'])
		{
			$case_cell .= "<br/>\n" . $row['court_name'];
		}
		
		$a[] = $case_cell;
		
		
		// Closing Fields
		$a[] = "<span style=\"white-space: nowrap;\">
			<input type=\"checkbox\" name=\"completed[{$row['act_id']}]\"/>
			<input type=\"text\" name=\"hours[{$row['act_id']}]\" size=\"3\" maxlength=\"3\"/></span>";
	}

	else 
	{
		$a[] = "$z";
		$a[] = "&nbsp;";
		$a[] = $plMenus['user_id'][$row['user_id']];
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";		
		$a[] = $row['summary'];
	}

	$overdue->addRow($a);
}
// END overdue records


// START to do records
$result = $pk->getActivitiesTodo($user_id);

while ($row = DBResult::fetchRow($result))
{
	$a = array();
	$target = '';
	$phone_str = '';
	$i++;

	if (pika_authorize('read_act', $row))
	{
		if ($pikaPopupGroupie == 1)
		{
			$target = ' target="_blank"';
		}
		
		$a[] = "<a href='activity.php?act_id={$row['act_id']}'>{$plMenus['act_type'][$row['act_type']]}</a>";
		
		// Description
		$a[] = pl_clean_html($row['summary']);

		// case information
		$case_cell = '';
		
		$case_cell .= "<a href='case.php?case_id={$row['case_id']}'$target>{$row['number']}</a>";
		
		if ($row['client_id'])
		{
			$case_cell .= "<br/>\n<a href='contact.php?contact_id={$row['client_id']}'>{$row['last_name']}, {$row['first_name']}</a>";
		}
		
		if ($row['area_code'] || $row['phone'] || $row['phone_notes'])
		{
			$case_cell .= "<br/>\n" . pl_format_phone($row);
		}
		
		if (isset($row['court_name']) && $row['court_name'])
		{
			$case_cell .= "<br/>\n" . $row['court_name'];
		}
		
		$a[] = $case_cell;
		
		// Closing Fields
		$a[] = "<span style=\"white-space: nowrap;\">
			<input type=\"checkbox\" name=\"completed[{$row['act_id']}]\"/>
			<input type=\"text\" name=\"hours[{$row['act_id']}]\" size=\"3\" maxlength=\"3\"/></span>";
	}
	
	else
	{
		$a[] = "$z";
		$a[] = "&nbsp;";
		$a[] = $plMenus['user_id'][$row['user_id']];
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = "&nbsp;";
		$a[] = $row['summary'];
	}

	$todo->addRow($a);
}
// END to do records


// Used to generate the javascript "check all" button
$content['i_value'] = $i;


// Draw the overdue table
if (0 == sizeof($overdue->rows))
{
	$content['overdue_table'] = "";
}

else 
{
	$content['overdue_table'] = "<h2 class=\"hdt\">Overdue Items</h2>\n<p>" . $overdue->draw() . "<br/>\n";
}

// Draw the pending table
if (0 == sizeof($p->rows))
{
	$content['pending_table'] = "<em>No entries</em>";
}

else 
{
	$content['pending_table'] = $p->draw();
}

// Draw the todo table
if (0 == sizeof($todo->rows))
{
	$content['todo_table'] = "<em>No entries</em>";
}

else 
{
	$content['todo_table'] = $todo->draw();
}

// Draw the completed table
if (0 == sizeof($t->rows))
{
	$content['completed_table'] = "<em>No entries</em>";
}

else 
{
	$content['completed_table'] = $t->draw();
}

if (0 == sizeof($p->rows))
{
	$p->addRow(array('No entries'));
}


// Funding breakdown table
$fund_breakdown->assignLabels(array("Funding Code", "Hours"));
$fund_breakdown->sortable = FALSE;
$fund_breakdown->show_pager = FALSE;
$fund_breakdown->width = "200";


while(list($key, $val) = each($tbf))
{
	if (!$key)
	{
		$fund_breakdown->addRow(array("No Funding Code", "<strong>$val</strong> hours<br/>"));
	}
	
	else 
	{
		$fund_breakdown->addRow(array(pl_array_lookup($key, $plMenus['funding']), "<strong>$val</strong> hours<br/>"));
	}
}

if (sizeof($fund_breakdown->rows) < 1)
{
	$content['funding_table'] = "<em>No time slips</em>";
}

else 
{
	$content['funding_table'] = $fund_breakdown->draw();
}

/*
if (TRUE == $plSettings['enable_graphics'])
{
	$C .= '<table cellspacing="0" cellpadding="2" summary=""><tr valign=top><td>'; 

	$C .= "<img src=\"daily.php?current_date=$cal_date&user_id=$user_id\" 
		HEIGHT=252 WIDTH=200 border=3 alt=\"today's calendar\" class=thinborder>";
	
	$C .= '</td><td width="100%">';
}

if ($total_time > 0)
{
	$total_time_label .= " ($total_time hours)";
}

$C .= '<table width="100%"><tr valign="top"><td width="50%"><form>';
$C .= pika_heading('Pending') . '';
$C .= $p->draw() . '<input type="submit" value="Update"></form></td><td>&nbsp;</td><td width="50%">';
$C .= pika_heading("Completed $total_time_label");
$C .= $t->draw();
$C .= '</td></tr></table>';



$C .= "<br/><table align=\"right\"><tr valign=\"top\"><td>";

$C .= "</td><td>";

$C .= $fund_breakdown->draw();

$C .= "</td></tr></table>";


if (TRUE == $plSettings['enable_graphics'])
{
	$C .= "</td></tr></table>\n";
}
*/
$tmp = pl_date_unmogrify($cal_date);
$content['prev_date'] = pl_date_add('d', -1, $cal_date);
$content['cal_date'] = $cal_date;
$content['next_date'] = pl_date_add('d', 1, $cal_date);
$content['pretty_cal_date'] = $tmp;
$content['total_hours'] = $total_time;
$content['cal_tabs'] = pika_calendar_tabs('day', $cal_date, $user_id);
$content['user_id'] = $user_id;

$plTemplate["content"] = pl_template($content, 'subtemplates/cal_day.html');
$plTemplate["page_title"] = "Calendar";
$plTemplate['nav'] = "<a href=\".\">$pikaNavRootLabel</a> &gt;
	<a href='cal_day.php'>Calendar</a> &gt; 
	<a href='cal_day.php?cal_date=$cal_date'>$tmp</a> &gt;
	Day View &nbsp; ";

$base_url = pl_settings_get('base_url');
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE) {
	$cal_url= "https://".$_SERVER['HTTP_HOST'].$base_url;
}else { $cal_url= "http://".$_SERVER['HTTP_HOST'].$base_url; }

$plTemplate['rss'] = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\" href=\"{$cal_url}/services/cal-rss.php?user_id={$user_id}\" />";

echo pl_template($plTemplate, 'templates/default.html');
echo pl_bench('results');
exit();


?>
