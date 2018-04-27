<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'Outcomes Report';
$report_name = "outcomes";

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
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$county = pl_grab_post('county');
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
$columns = array('Problem Code', 'Goal', 'Result', 'Yes', 'No', 'NA', 'Case Number', 'Close Date', 'Funding', 'Office', 'Case Status', 'Undup.', 'County', 'ZIP', 'Persons Helped');


$clb = pl_date_mogrify($close_date_begin);
$cle = pl_date_mogrify($close_date_end);

$sql = "SELECT label AS problem_code, goal, result, IF(result = 1, 1, 0) AS yes_outcome,
		IF(result = 0, 1, 0) AS no_outcome, IF(result = 2, 1, 0) AS na_outcome,
		number, close_date, cases.funding, office, status, undup, case_county, case_zip, persons_helped
	FROM outcomes
	LEFT JOIN outcome_goals USING(outcome_goal_id)
	LEFT JOIN cases ON outcomes.case_id = cases.case_id
	LEFT JOIN menu_problem ON cases.problem = menu_problem.value
	WHERE 1";

$range1 = $range2 = "";
$safe_clb = mysql_real_escape_string($clb);
$safe_cle = mysql_real_escape_string($cle);

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


$x = pl_process_comma_vals($funding);
if ($x != false)
{
	$t->add_parameter('Funding Code(s)',$funding);
	$sql .= " AND funding IN $x";
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

$x = pl_process_comma_vals($county);
if ($x != false)
{
	$t->add_parameter('Counties',$county);
	$sql .= " AND case_county IN $x";
}

$safe_undup = mysql_real_escape_string($undup);
if ($undup == 1 || ($undup == 0 && $undup != ''))
{
	$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
	$sql .= " AND undup = '{$safe_undup}'";
}

$sql .= " ORDER BY problem_code ASC, goal ASC, result ASC, close_date ASC";


$t->title = $report_title;
$t->display_row_count(false);
$t->set_header($columns);


$result = mysql_query($sql) or trigger_error();
while ($row = mysql_fetch_assoc($result))
{
	if ('1' == $row['result'])
	{
		$row['result'] = 'Yes';
	}
	
	else if ('0' == $row['result'])
	{
		$row['result'] = 'No';
	}
	
	else if ('2' == $row['result'])
	{
		$row['result'] = 'NA';
	}
	
	$row['close_date'] = pl_date_unmogrify($row['close_date']);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
