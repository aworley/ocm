<?php

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaCase.php');
require_once('pikaTempLib.php');

$report_title = "Cases by Plan Type";
$report_name = "case_plan_type";

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

$report_format = pl_grab_post('report_format');


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

$open_date_begin = pl_grab_post('open_date_begin');
$open_date_end = pl_grab_post('open_date_end');

$redact_client = pl_grab_post('redact_client');
$show_sql = pl_grab_post('show_sql');

$safe_odb = DB::escapeString(pl_date_mogrify($open_date_begin));
$safe_ode = DB::escapeString(pl_date_mogrify($open_date_end));

$where_sql = '';

if ($open_date_begin && $open_date_end) {
	$t->add_parameter('Opened Between',$open_date_begin . " - " . $open_date_end);
	$where_sql .= " AND open_date >= '{$safe_odb}' AND open_date <= '{$safe_ode}'";
} elseif ($close_date_begin) {
	$t->add_parameter('Opened After',$open_date_begin);
	$where_sql .= " AND open_date >= '{$safe_odb}'";
} elseif ($close_date_end) {
	$t->add_parameter('Opened Before',$open_date_end);
	$where_sql .= " AND open_date <= '{$safe_ode}'";
}

// run the report

$t->title = $report_title;
$t->set_header(array('Case Number','Client Name','Open Date','Plan Type 1','Plan Type 2'));

$sql = "SELECT case_id, number, primary_client.first_name, primary_client.last_name, open_date, 
		IFNULL(menu_plan_type_1.label,'No Data') AS plan_type_1, IFNULL(menu_plan_type_2.label,'No Data') AS plan_type_2
		FROM cases
		LEFT JOIN contacts AS primary_client ON primary_client.contact_id = cases.client_id
		LEFT JOIN pension_plans ON cases.pension_plan_id = pension_plans.pension_plan_id
		LEFT JOIN menu_plan_type_1 ON pension_plans.plan_type_1 = menu_plan_type_1.value
		LEFT JOIN menu_plan_type_2 ON pension_plans.plan_type_2 = menu_plan_type_2.value
		WHERE 1{$where_sql}
		ORDER BY open_date ASC";

$result = DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
while ($row = DBResult::fetchRow($result))
{
	$r = array();
	if (strlen($row['number']) < 1)
	{
		$row['number'] = "No Case #";
	}
	$r['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}\">". $row['number']."</a>";
	$r['client_name'] = 'Xxxxxxxxx, Xxxxxx';
	if($redact_client != '1')
	{
		$r['client_name'] = pikaTempLib::plugin('text_name','',$row);
	}
	$r['open_date'] = pl_date_unmogrify($row['open_date']);
	$r['plan_type_1'] = $row['plan_type_1'];
	$r['plan_type_2'] = $row['plan_type_2'];
	$t->add_row($r);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
