<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaMisc.php');

$report_title = "Daily Intake Report";
$report_name = "daily_intake";

$office = pl_grab_post('office');
$date = pl_grab_post('date');
$report_format = pl_grab_post('report_format');
$show_sql = pl_grab_post('show_sql');

$staff = pikaMisc::fetchStaffArray();
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


// run the report
$sql = "SELECT	case_id,
				open_date, 
				last_name, first_name,
				status, 
				number, 
				user_id, 
				cocounsel1, 
				cocounsel2, 
				office 
		FROM cases LEFT JOIN contacts ON cases.client_id=contacts.contact_id
		WHERE 1";

if ($office)
{
	$t->add_parameter('Office Code',$office);
	$safe_office = DB::escapeString($office);
	$sql .= " AND office='{$safe_office}'";
}

if ($date)
{

	$t->add_parameter('Date',$date);
	$safe_date = DB::escapeString(pl_date_mogrify($date));
	$sql .= " AND open_date='{$safe_date}'";
}

$sql .= " ORDER BY last_name ASC, first_name ASC";


$t->title = $report_title;
$t->set_header(array('Open Date', 'Name', 'Status', 'Case Number', 'Counsel',
					'Co-counsel', 'Co-counsel', 'Office', 'Case Notes'));
$result = DB::query($sql);

while ($row = DBResult::fetchRow($result))
{
	$notes = '';
	$sql2 = "SELECT act_date, notes FROM activities 
				WHERE case_id = '{$row['case_id']}'
				ORDER BY act_date DESC, act_time DESC, act_id DESC";
	$resultb = DB::query($sql2) or trigger_error();
	
	while ($rb = DBResult::fetchRow($resultb))
	{
		if ($rb['notes'])
		{
			$notes .= pl_date_unmogrify($rb['act_date']) . pl_clean_html(": {$rb['notes']}\n");
		}
	}
	
	// limit notes size
	if (strlen($notes) > 500)
	{
		$notes = substr($notes, 0, 499) . " ...";
	}
	
	$row['notes'] = $notes;
	
	$row['open_date'] = pl_date_unmogrify($row['open_date']);
	$row['user_id'] = pl_array_lookup($row['user_id'], $staff);
	$row['cocounsel1'] = pl_array_lookup($row['cocounsel1'], $staff);
	$row['cocounsel2'] = pl_array_lookup($row['cocounsel2'], $staff);

	unset($row['case_id']);
	unset($row['first_name']);

	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
