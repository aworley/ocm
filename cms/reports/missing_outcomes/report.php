<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'Missing Outcomes Report';
$report_name = "missing_outcomes";

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
$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');
$open_on_date = pl_grab_post('open_on_date');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$undup = pl_grab_post('undup');
$show_sql = pl_grab_post('show_sql');

$menu_undup = pl_menu_get('undup');

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
$columns = array('Case Number', 'Staff ID', 'Close Date',  'Office', 'Case Status', 'Undup.');


$clb = pl_date_mogrify($close_date_begin);
$cle = pl_date_mogrify($close_date_end);


$sql = "SELECT number, user_id, close_date, office, status, undup
	FROM cases
	WHERE 1";

$range1 = $range2 = "";
$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);

if ($clb && $cle) {
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$range1 = "close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
} elseif ($clb) {
	$t->add_parameter('Closed After',$close_date_begin);
	$range1 = "close_date >= '{$safe_clb}'";
} elseif ($cle) {
	$t->add_parameter('Closed Before',$close_date_end);
	$range1 = "close_date <= '{$safe_cle}'";
}


	if ($clb || $cle) {
		$sql .= " AND $range1";
	}



$x = pl_process_comma_vals($office);
if ($x != false)
{
	$t->add_parameter('Office Code(s)',$office);
	$sql .= " AND office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false)
{
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND status IN $x";
}

$safe_undup = DB::escapeString($undup);
if ($undup == 1 || ($undup == 0 && $undup != ''))
{
	$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
	$sql .= " AND undup = '{$safe_undup}'";
}

$sql .= " AND NOT EXISTS (SELECT 1 FROM `outcomes` WHERE outcomes.case_id = cases.case_id)";

$sql .= " ORDER BY number ASC";


$t->title = $report_title;
$t->display_row_count(false);
$t->set_header($columns);



$result = DB::query($sql) or trigger_error();
while ($row = DBResult::fetchRow($result))
{
	$row['close_date'] = pl_date_unmogrify($row['close_date']);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
