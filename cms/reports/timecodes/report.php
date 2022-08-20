<?php

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaMisc.php');

$report_title = "Time Codes Report";
$report_name = "timecodes";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) {
	$main_html = array();
	$main_html['base_url'] = $base_url;
	$main_html['page_title'] = $report_title;
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
    				  &gt; <a href=\"{$base_url}/reports/\">Reports</a> 
    				  &gt; $report_title";
	$main_html['content'] = "You are not authorized to run this report";

	$buffer = pl_template('templates/default.html', $main_html);
	pika_exit($buffer);
}

$user_list = pl_grab_post('user_list');
$date_start = pl_grab_post('date_start');
$date_end = pl_grab_post('date_end');

$holidaycode = pl_grab_post('holidaycode');
$lunchcode = pl_grab_post('lunchcode');
$vacationcode = pl_grab_post('vacationcode');
$compcode = pl_grab_post('compcode');
$sickcode = pl_grab_post('sickcode');

$report_format = pl_grab_post('report_format');
$show_sql = pl_grab_post('show_sql');



$staff = pikaMisc::fetchStaffArray();

if ('csv' == $report_format)
{
	require_once ('app/lib/plCsvReportTable.php');
	require_once ('app/lib/plCsvReport.php');
	$t = new plCsvReport();
}

else
{
	require_once ('app/lib/plHtmlReportTable.php');
	require_once ('app/lib/plHtmlReport.php');
	$t = new plHtmlReport();
}


// Run the Report
// Check the time codes and ensure at least default values
if(!$holidaycode) {$safe_holidaycode = 'SK';}
else {$safe_holidaycode = DB::escapeString($holidaycode);}
if(!$lunchcode) {$safe_lunchcode = 'SL'; }
else {$safe_lunchcode = DB::escapeString($lunchcode);}
if(!$vacationcode) {$safe_vacationcode = 'SV'; }
else {$safe_vacationcode = DB::escapeString($vacationcode);}
if(!$compcode) {$safe_compcode = 'SM'; }
else {$safe_compcode = DB::escapeString($compcode);}
if(!$sickcode) {$safe_sickcode = 'SS'; }
else {$safe_sickcode = DB::escapeString($sickcode);}

$sql = "SELECT	act_date, activities.user_id,
		SUM(IF(category NOT IN 
		('{$safe_holidaycode}', '{$safe_lunchcode}', '{$safe_vacationcode}',
		 '{$safe_compcode}', '{$safe_sickcode}'), hours, 0)) AS work,
		SUM(IF(category = '{$safe_holidaycode}', hours, 0)) AS holiday,
		SUM(IF(category = '{$safe_lunchcode}', hours, 0)) AS lunch,
		SUM(IF(category = '{$safe_compcode}', hours, 0)) AS comp,
		SUM(IF(category = '{$safe_sickcode}', hours, 0)) AS sick,
		SUM(IF(category = '{$safe_vacationcode}', hours, 0)) AS vacation,
		SUM(IF(category IS NULL, hours, 0)) AS nocode,
		SUM(hours) AS total
		FROM activities LEFT JOIN users ON activities.user_id=users.user_id
		WHERE 1";

$safe_user_list = pl_process_comma_vals($user_list);
if ($safe_user_list != false) {
	$t->add_parameter('User ID(s)',$user_list);
	$sql .= " AND activities.user_id IN {$safe_user_list}";
}

if ($date_start)
{
	$t->add_parameter('Date Start',$date_start);
	$safe_date_start = DB::escapeString(pl_date_mogrify($date_start));
	$sql .= " AND act_date >= '{$safe_date_start}'";
}

if ($date_end)
{
	$t->add_parameter('Date End',$date_end);
	$safe_date_end = DB::escapeString(pl_date_mogrify($date_end));
	$sql .= " AND act_date <= '{$safe_date_end}'";
}

$sql .= " GROUP BY activities.user_id, act_date ORDER BY activities.user_id ASC, act_date ASC";

$cols = array('Date', 'User ID', 'Name',
			 'Work', 'Holiday', 'Lunch',
			 'Comp', 'Sick', 'Vacation',
			 'No Code', 'Daily Total');

$t->set_title($report_title);
$totals = array('worktotal' => 0,
				'holidaytotal' => 0,
				'lunchtotal' => 0,
				'comptotal' => 0,
				'sicktotal' => 0,
				'vacationtotal' => 0,
				'nocodetotal' => 0,
				'dailytotal' => 0);

$act_user = array();

$result = DB::query($sql) or trigger_error();

while ($row = DBResult::fetchRow($result)) {
	$act_user[$row['user_id']][] = $row;
}

$first_table = true;
// Format the output into the report tables
foreach ($act_user as $user => $act_row) {
	if($first_table) {
		$first_table = false;
	} else {
		$t->add_table();
	}
	$t->set_header($cols);
	$t->set_table_title(pl_array_lookup($user,$staff));
	$tmp_totals = $totals; // Make a new copy of totals array template
	foreach ($act_row as $act) {
		$r = array();
		$r['act_date'] = pl_date_unmogrify($act['act_date']);
		$r['user_id'] = $act['user_id'];
		$r['name'] = pl_array_lookup($act['user_id'],$staff);
		$tmp_totals['worktotal'] += $r['work'] = number_format($act['work'],2);
		$tmp_totals['holidaytotal'] += $r['holiday'] = number_format($act['holiday'],2);
		$tmp_totals['lunchtotal'] += $r['lunch'] = number_format($act['lunch'],2);
		$tmp_totals['comptotal'] += $r['comp'] = number_format($act['comp'],2);
		$tmp_totals['sicktotal'] += $r['sick'] = number_format($act['sick'],2);
		$tmp_totals['vacationtotal'] += $r['vacation'] = number_format($act['vacation'],2);
		$tmp_totals['nocodetotal'] += $r['nocode'] = number_format($act['nocode'],2);
		$tmp_totals['dailytotal'] += $r['total'] = number_format($act['total'],2);
		$t->add_row($r);
	}
	// Display the totals for this user_id
	$r = array ('', '', 'Totals:');
	foreach ($tmp_totals as $key => $val) {
		$r[$key] = number_format(round($val,2),2);
	}
	$t->add_row($r);	
}

$footer =	"Date: _____________________  &nbsp; &nbsp; Signed: _________________________________________<br/>\n<br/>\n
			 Approved: ____________________________________________________________________\n";
$t->set_footer($footer); 


if($show_sql) {
	$t->set_sql($sql);
}

$t->display();	
exit();

?>
