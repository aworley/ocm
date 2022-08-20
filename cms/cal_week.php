<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika_cms.php'); 

$pk = new pikaCms($db);
unset($C);
$t = new plTable();
$t->table_class = 'whitebg';
$fund_breakdown = new plTable();

$plMenus['user_id'] = $pk->fetchStaffArray();
$plMenus['funding'] = pl_table_array('funding');
$short_user_list = array();
$z = 4;
foreach($plMenus['user_id'] as $key => $val)
{
	if (strlen($val) > $z)
	{
		$short_user_list[$key] = substr($val, 0, $z) . '...';
	}
	
	else 
	{
		$short_user_list[$key] = $val;
	}
}

$tbf = array();  // Time By Funding code


// VARIABLES

$C = '';
$screen = pl_grab_var('screen', 'one');

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

$custom = pl_grab_var('custom', 0, 'GET', 'boolean');
/*
if (is_array($_REQUEST['user_list']))
{
	$user_list = $_REQUEST['user_list'];
}

else 
{
	$user_list = array();
}
*/
$user_list = pl_grab_var('user_list', null, 'REQUEST', 'array');

if ($user_id == 'mine')
{
	$user_id = $auth_row['user_id'];
}

// END VARIABLES


if('day' == $screen)
{
	header("Location: cal_day.php?cal_date=$cal_date&user_id=$user_id");
	exit();
}




function print_calendar_item($a)
{
	global $plUserId, $auth_row, $short_user_list;
	
	$C = '';
	$title = " title='(no summary)'";
	
	$tmp = pl_unmogrify_time($a["act_time"]);
	if (strlen($a['act_end_time']) > 0)
	{
		$tmp .= ' - ' . pl_unmogrify_time($a['act_end_time']);
	}

	if($a["user_id"] == $plUserId)
	{
		// $style = 'style="font-size: 10px;"';
		$style = 'class="mycal"';
	}
	
	else
	{
		// $style = 'style="font-size: 10px; color: #555555;"';
		$style = 'class="othercal"';
	}
	$client_name = $court_name = '';
	if (($a["user_id"] == $plUserId) || ($auth_row['read_all'] == true))
	{
		if ($a['summary'])
		{
			$title = ' title="' . htmlspecialchars(substr($a['summary'], 0, 25)) . '"';
		}
		
		if ($a['last_name'])
		{
			$client_name = "<span $style><br/>&nbsp; * {$a['last_name']}</span>";
		}
		
		if ($a['location'])
		{
			$court_name = "<span $style><br/>&nbsp; * {$a['location']}</span>";
		}

		$C .= "<a href=activity.php?act_id={$a["act_id"]} $style$title>
				{$tmp}</a>$client_name$court_name";

		if($a["completed"])
		{
			$C .= '<img width=6 height=6 src="images/mini_check.gif" alt=""/>';
		}
	}
	
	else
	{
		$C .= "<span $style>{$tmp}</span>";	
	}

	$C .= '<br/>';
	return $C;
}


/* generate HTML to display one calendar week.  Each day stored in an array 
   item.It will display the week of the date specified, and will show only 
   activities owned by the user_id (if user_id is NULL, all activities are 
   shown).
   */
function draw_week($cal_date='', $user_id='', $user_list)
{
	// Variables
	global $pk, $tbf;
	
	$activities = array();
	$hours = array();
	
	// extract day, month and year from cal_date, to make things easier
	list ($cal_year, $cal_month, $cal_day) = explode("-", $cal_date);

	// Determine the Monday of the current week
	$date_tmp = getdate(mktime(0, 0, 0, $cal_month, $cal_day, $cal_year));
	// kludge it if today's a sunday...
	if($date_tmp['wday'] == 0)
		$monday_tmp = $cal_day - 6;
	else
		$monday_tmp	= $cal_day - $date_tmp['wday'] + 1;

	// store each day this week in standard pika internal format
	$cal_ts[0] = date('Y-m-d', mktime(0, 0, 0, $cal_month, $monday_tmp, $cal_year));

	for($n = 1; $n < 7; $n++)
	{
		$cal_ts[$n] = pl_date_add('d', 1, $cal_ts[$n-1]);
	}

	$cal_next_week = pl_date_add('ww', 1, $cal_date);
	$cal_previous_week = pl_date_add('ww', -1, $cal_date);
	
	// End Variables


	for ($i = 0; $i < 7; $i++)
	{
		$d = $cal_ts[$i];

		$e = date('M j', strtotime($cal_ts[$i]));
		if (date('Y-m-d') == $d)
		{
			$e .= '&nbsp;<i>TODAY</i>';
		}

		if (isset($activities[$d]))
		{
			$activities[$d] .= "<a href='cal_day.php?cal_date=$d&user_id=$user_id'><b>$e</b></a><br/>";
		}
		
		else // this suppresses PHP warnings
		{
			$activities[$d] = "<a href='cal_day.php?cal_date=$d&user_id=$user_id'><b>$e</b></a><br/>";
		}
	}
	
	$filter["starting"] = $cal_ts[0];
	$filter["ending"] = $cal_ts[6];
	
	if('office_' == substr($user_id, 0, 7))
	{
		$filter['office'] = substr($user_id, 7);
	}
	
	else if (sizeof($user_list) > 0)
	{
		$filter['user_list'] = $user_list;
	}
	
	else 
	{
		$filter["user_id"] = $user_id;
	}
	
	// Get activities for this week
	$result = $pk->fetchActivitiesCaseClient($filter, $tmp, 'user_id, act_time', 'ASC', 0, 1000);

	while ($row = DBResult::fetchRow($result))
	{
		if (1 == $row["completed"])
		{
			if (isset($hours[$row["act_date"]]))
			{
				$hours[$row["act_date"]] += $row["hours"];
			}
			
			else // supresses PHP warnings
			{
				$hours[$row["act_date"]] = $row["hours"];
			}
			
			if (isset($tbf["{$row['funding']}"]))
			{
				$tbf["{$row['funding']}"] += $row['hours'];
			}
			
			else
			{
				$tbf["{$row['funding']}"] = $row['hours'];
			}
		}
		
		else 
		{
			$activities[$row["act_date"]] .= print_calendar_item($row);
		}
	}
	
	// Done getting activities

	/* Also show daily sum of hours, unless multiple users are shown.  Don't show sum for
       a particular day if there are no hours logged
       */
    $trash = array('user_id' => $user_id);
    
	if ($user_id && pika_authorize('read_act', $trash))
	{
		for($i = 0; $i < 7; $i++)
		{
			if (isset($hours[$cal_ts[$i]]) && $hours[$cal_ts[$i]] > 0)
			{
				$activities[$cal_ts[$i]] .= "<b>{$hours[$cal_ts[$i]]}</b> hrs.";
			}
		}
	}
	
	return $activities;
}


/*
if ('add_activity' == $action)
{
	$a = pl_grab_vars('activities');
	
	// these should override what got picked up by pl_grab_vars()
	$a["act_date"] = pl_date_mogrify($act_date);
	$a["act_time"] = pl_mogrify_time($act_time);
	$a["user_id"] = $plUserId;
	
	// Damn checkboxes
	if ($a["completed"] != 1)
		$a["completed"] = 0;
	if ($a["good_story"] != 1)
		$a["good_story"] = 0;

	$act_id = $pk->newActivity($a);
	
	$C .= 'New activity added';
}
*/

$plTemplate['nav'] = "<a href=\".\" class=light>$pikaNavRootLabel</a> &gt; <a href=cal_day.php class=light>Calendar</a> &gt; " . pl_date_unmogrify($cal_date);

		// <a href='activity_batch.php' class=light>Time Batch</a> -


// draw calendar controls
/*
$C .= '<table width="100%" summary="">';

$C .= '<tr><td><strong>';


if ('four' == $screen)
{
	$z = pl_date_add('ww', -4, $cal_date);
	$C .= "<a href='cal_week.php?cal_date=$z&screen=four&user_id=$user_id'>
			&lt;&lt;&lt;&lt; Previous 4 Weeks</a> &nbsp; &nbsp; ";
}

if ('two' == $screen)
{
	$z = pl_date_add('ww', -2, $cal_date);
	$C .= "<a href='cal_week.php?cal_date=$z&screen=two&user_id=$user_id'>
			&lt;&lt; Previous 2 Weeks</a> &nbsp; &nbsp; ";
}

$z = pl_date_add('ww', -1, $cal_date);
$C .= "<a href='cal_week.php?cal_date=$z&screen=$screen&user_id=$user_id'>
		&lt; Previous Week</a>";


$C .= '</strong></td><td align=center>';

$C .= "<form action=cal_week.php method=GET name=form1><input type=hidden name=cal_date value='$cal_date'>";
$C .= "<input type=\"hidden\" name=\"screen\" value=\"day\">";

$staff_menu = $pk->fetchEnabledStaffArray();

$C .= pl_array_menu($staff_menu, 'user_id', $user_id, false);

$C .= '<input type=submit name="UPDATE" value="Show"></form>';


$C .= pika_calendar_tabs($screen, $cal_date, $user_id);

$C .= '</td><td align=right><strong>';

$z = pl_date_add('ww', 1, $cal_date);
$C .= "<a href='cal_week.php?cal_date=$z&screen=$screen&user_id=$user_id'>
		Next Week &gt;</a>";

if ('two' == $screen)
{
	$z = pl_date_add('ww', 2, $cal_date);
	$C .= "&nbsp; &nbsp; <a href='cal_week.php?cal_date=$z&screen=two&user_id=$user_id'>
			Next 2 Weeks &gt;&gt;</a>";
}

if ('four' == $screen)
{
	$z = pl_date_add('ww', 4, $cal_date);
	$C .= "&nbsp; &nbsp; <a href='cal_week.php?cal_date=$z&screen=four&user_id=$user_id'>
			Next 4 Weeks &gt;&gt;&gt;&gt;</a>";
}

$C .= '</strong></td></tr></table>';
*/
// end calendar controls
$content['screen'] = $screen;
$content['prev_week'] = pl_date_add('ww', -1, $cal_date);
$content['next_week'] = pl_date_add('ww', 1, $cal_date);

$columns = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 
		'Saturday', 'Sunday');

$t->assignLabels($columns);
$t->sortable = FALSE;
$t->show_pager = FALSE;
$t->max_length = $pikaDefPaging;
$t->nav_url = "cal_week.php?cal_mode=weekly&user_id=$user_id&";
$t->rowa_bg = 'calrow';
$t->rowb_bg = 'calrow';
$t->td_style = 'calrow';
$t->min_row_height = 100;


switch ($screen)
{
	case 'one':

	$t->addRow(draw_week($cal_date, $user_id, $user_list));
	break;

	case 'two':

	$t->addRow(draw_week($cal_date, $user_id, $user_list));
	$t->addRow(draw_week(pl_date_add('ww', 1, $cal_date), $user_id, $user_list));
	break;

	case 'four':

	$t->addRow(draw_week($cal_date, $user_id, $user_list));
	$t->addRow(draw_week(pl_date_add('ww', 1, $cal_date), $user_id, $user_list));
	$t->addRow(draw_week(pl_date_add('ww', 2, $cal_date), $user_id, $user_list));
	$t->addRow(draw_week(pl_date_add('ww', 3, $cal_date), $user_id, $user_list));
	break;

	default:

	$C .= '<p>Error: undefined screen mode</p>';
	break;
}


	
$content['calendar_table'] = $t->draw();



$fund_breakdown->assignLabels(array("Funding Code", "Hours"));
$fund_breakdown->sortable = FALSE;
$fund_breakdown->show_pager = FALSE;
$fund_breakdown->width = "200";


while(list($key, $val) = each($tbf))
{
	if (!$key)
	{
		$fund_breakdown->addRow(array("No Funding Code", "<b>$val</b> hours<br/>"));
	}
	
	else 
	{
		$fund_breakdown->addRow(array($plMenus['funding'][$key], "<b>$val</b> hours<br/>"));
	}
}

if (sizeof($fund_breakdown->rows) < 1)
{
	$fund_breakdown->addRow(array('No entries'));
}
/*
$C .= '<br><table align="right"><tr valign=top><td>';
$C .= "<ul>\n
	<li><a href='activity.php?screen=compose&act_date=$cal_date&completed=1' class=light>Add New Time Slip</a>\n
	<li><a href='activity.php?screen=compose&act_date=$cal_date&completed=0' class=light>Add New Appointment</a>\n
	</ul>\n";
$C .= '</td><td>';
$C .= $fund_breakdown->draw();
$C .= '</td></tr></table>';

if ('one' == $screen)
{
	$C .= '<p> &nbsp; <p> &nbsp;';
}
*/

$content['funding_table'] = $fund_breakdown->draw();

$content['cal_tabs'] = pika_calendar_tabs($screen, $cal_date, $user_id);
$content['user_id'] = $user_id;
$content['cal_date'] = $cal_date;

$tmp_date = pl_date_unmogrify($cal_date);

$plTemplate["content"] = pl_template($content, 'subtemplates/cal_week.html');
$plTemplate["page_title"] = "Calendar";
$plTemplate['nav'] = "<a href=\".\">$pikaNavRootLabel</a> &gt;
	<a href='cal_day.php'>Calendar</a> &gt; 
	<a href='cal_day.php?cal_date=$cal_date'>$tmp_date</a> &gt;
	Week View &nbsp; ";


echo pl_template($plTemplate, 'templates/default.html');
echo pl_bench('results');
exit();


?>
