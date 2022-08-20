<?php
// 06-22-2011 - caw - created new report
chdir('../../');

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');

$report_title = 'Inactive Staff Member Report';
$report_name = "inactive_user";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) {
	$main_html = array();
	$main_html['base_url'] = $base_url;
	$main_html['page_title'] = $report_title;
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
    				  &gt; <a href=\"{$base_url}/reports/\">Reports</a> 
    				  &gt; $report_title";
	$main_html['content'] = "You are not authorized to run this report";
	$default_template = new pikaTempLib('templates/default.html', $main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$report_format = pl_grab_post('report_format');
$inactive_date_begin = pl_grab_post('inactive_date_begin');
$limit = pl_grab_post('limit');

$menu_act_type = pl_menu_get('act_type');
$staff_array = pikaUser::getUserArray();
$menu_act_type = pl_menu_get('act_type');
// enable_link so if user selects csv file no link code is included  1 = yes, 0 = no  	
$enable_link = 1;

if ('csv' == $report_format)
{
	require_once ('plCsvReportTable.php');
	require_once ('plCsvReport.php');
	$t = new plCsvReport();
	$enable_link = 0;
}

else
{
	require_once ('plHtmlReportTable.php');
	require_once ('plHtmlReport.php');
	$t = new plHtmlReport();
}

$safe_inactive_date_begin = DB::escapeString(pl_date_mogrify($inactive_date_begin));

// build the sql using UNION to combine the different selects into a derived table 
// sort the derived table creating a 2nd derived table that can then be grouped
// to obtain the MAX date per user
$sql_derived_table_select = "SELECT * FROM( ";

 $sql_1 = "(SELECT users.user_id as staff,
				FROM_UNIXTIME(MAX(user_sessions.last_updated)) as action_date, 
				'Login' as description,
				' ' as link,
				' ' as link_name
				FROM  users
				LEFT JOIN user_sessions ON users.user_id = user_sessions.user_id
				WHERE 1
				AND (FROM_UNIXTIME(user_sessions.last_updated) < '{$safe_inactive_date_begin}'
				OR user_sessions.last_updated IS NULL)
				GROUP BY users.user_id
				HAVING (MAX(user_sessions.last_updated) 
				OR MAX(user_sessions.last_updated) IS NULL))";

$sql_2 = "(SELECT activities.user_id as staff,
				MAX(activities.last_changed) as action_date,
				'Activity' as description,
				activities.act_id as link,
				activities.act_type as link_name 
				FROM activities
				WHERE 1
				AND activities.last_changed < '{$safe_inactive_date_begin}'
				AND activities.user_id IS NOT NULL 
				GROUP BY activities.user_id
				HAVING MAX(activities.last_changed))";

$sql_3 = "(SELECT cases.user_id as staff,
				MAX(cases.last_changed) as action_date,
				'Counsel' as description,
				cases.case_id as link,
				cases.number as link_name 	
				FROM cases 
				WHERE 1
				AND cases.last_changed < '{$safe_inactive_date_begin}'
				AND cases.user_id IS NOT NULL
				GROUP BY cases.user_id 
				HAVING MAX(cases.last_changed))";

$sql_4 = "(SELECT cases.intake_user_id as staff,
				MAX(cases.created) as action_date,
				'Intake' as description,
				cases.case_id as link,
				cases.number as link_name
				FROM cases
				WHERE 1
				AND cases.created < '{$safe_inactive_date_begin}'
				AND cases.intake_user_id IS NOT NULL
				GROUP BY cases.intake_user_id
				HAVING MAX(cases.created))";
				
$sql_5 = "(SELECT cases.cocounsel1 as staff,
				MAX(cases.last_changed) as action_date,
				'1st CoCounsel' as description,
				cases.case_id as link,
				cases.number as link_name 
				FROM cases
				WHERE 1
				AND cases.last_changed < '{$safe_inactive_date_begin}'
				AND cases.cocounsel1 IS NOT NULL 
				GROUP BY cases.cocounsel1
				HAVING MAX(cases.last_changed))";
				
$sql_6 = "(SELECT cases.cocounsel2 as staff,
				MAX(cases.last_changed) as action_date,
				'2nd CoCounsel' as description,
				cases.case_id as link,
				cases.number as link_name 
				FROM cases
				WHERE 1
				AND cases.last_changed < '{$safe_inactive_date_begin}'
				AND cases.cocounsel2 IS NOT NULL 
				GROUP BY cases.cocounsel2
				HAVING MAX(cases.last_changed))";
				
$sql_derived_table1 = ") as t1 ORDER BY staff, action_date DESC";
$sql_derived_table2 = ") as t2 GROUP BY staff 
                 							HAVING (MAX(action_date) 
                 							OR MAX(action_date) IS NULL)";

if ($inactive_date_begin)
{	
	$t->add_parameter('Activity Prior To ',$inactive_date_begin);	

	$sql = $sql_derived_table_select;
	$sql .= $sql_derived_table_select;
	$sql .= $sql_1;
	$sql .= " UNION ";
	$sql .= $sql_2;
	$sql .= " UNION ";
	$sql .= $sql_3;
	$sql .= " UNION ";
	$sql .= $sql_4;
	$sql .= " UNION ";
	$sql .= $sql_5;
	$sql .= " UNION ";
	$sql .= $sql_6;
	$sql .= $sql_derived_table1;
	$sql .= $sql_derived_table2;
}


if($limit)
{
	$t->add_parameter('Limit Results',$limit . " Row(s)");
	$safe_limit = DB::escapeString($limit);
	$sql .= " LIMIT {$safe_limit};";
}

$t->title = $report_title;
$t->display_row_count(true);
$t->set_header(array('Staff Name','Date','Action','Link'));
	
$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());

// interogate resulting output for report
while ($row = DBResult::fetchRow($result))
{	
	$rpt_row = array();	
	$rpt_row['staff'] = pl_array_lookup($row['staff'],$staff_array);
	$rpt_row['action_date'] = pikaTempLib::plugin('text_date','',$row['action_date']);
//	$rpt_row['description'] = $row['description'];
	if($row['description']=="Activity")
		{
			$rpt_row['description'] = $row['description'];
			$hold_type = pl_array_lookup($row['link_name'],$menu_act_type);
			if($enable_link==1)
			{				
				$rpt_row['link'] = "<a href=\"{$base_url}/activity.php?act_id={$row['link']}
				\"target=\"_blank\">{$hold_type}</a>";
			}
			else
			{
				$rpt_row['description'] = $row['description'];
				$rpt_row['link'] = $hold_type;
			}	
		}
	elseif ($row['description']=='Login')
		{
			if(!$row['action_date'])
			{
				$rpt_row['description'] = "No Activity Detected";
				$rpt_row['action_link'] = " ";
			}
			else
			{
				$rpt_row['description'] = $row['description'];
				$rpt_row['action_link'] = " ";
//				$rpt_row['action_link'] = "Login Detected";
			}
		}
	else	
		{
			if($enable_link==1)
			{
				$rpt_row['description'] = $row['description'];
				$rpt_row['link'] = "<a href=\"{$base_url}/case.php?case_id={$row['link']}
				\"target=\"_blank\">{$row['link_name']}</a>";	
			}
			else
			{
				$rpt_row['description'] = $row['description'];
				$rpt_row['link'] = $row['link_name'];
			}	
		}	
	$t->add_row($rpt_row);
}

if($show_sql) 
{
	$t->set_sql($sql);
}

$t->display();	
exit();

?>
