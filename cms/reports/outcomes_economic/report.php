<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'Outcomes - Economic Report';
$report_name = "outcomes_economic";

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
$clb = pl_date_mogrify($close_date_begin);
$cle = pl_date_mogrify($close_date_end);

if (true == pl_settings_get('ca_iolta_outcomes'))
{
	$columns = array('Problem Code', 'Back Awards and Lump-Sum Settlement', 
		'Monthly Benefits Obtained', 'Reduction or Elimination of Claimed Amounts',
		'Monthly Cost Savings and Payment Reductions', 'Other Significant Outcome',
		'Case Number', 'Close Date', 'Funding', 'Office', 'Case Status', 'Undup.', 
		'County', 'ZIP');
	$sql = "SELECT label AS problem_code, 
			ca_outcome_amount_obtained, ca_outcome_monthly_obtained, 
			ca_outcome_amount_reduced, ca_outcome_monthly_reduced,
			outcome_notes,
			number, close_date, cases.funding, office, status, undup, case_county, 
			case_zip
		FROM cases
		LEFT JOIN menu_problem ON cases.problem = menu_problem.value
		WHERE 1";
}

else 
{
	$columns = array('Problem Code', 'Income After Served', 'Income If Not Served', 'Change in Income',
		'Assets After Served', 'Assets If Not Served', 'Change in Assets',
		'Debt After Served', 'Debt If Not Served', 'Debt Reduced',
		'Other Significant Outcome',
		'Case Number', 'Close Date', 'Funding', 'Office', 'Case Status', 'Undup.', 'County', 'ZIP');
	$sql = "SELECT label AS problem_code, 
			outcome_income_after_service, outcome_income_no_service, 
			(outcome_income_after_service - outcome_income_no_service) AS income_delta, 
			outcome_assets_after_service, outcome_assets_no_service,
			(outcome_assets_after_service - outcome_assets_no_service) AS assets_delta,
			outcome_debt_after_service, outcome_debt_no_service,
			(outcome_debt_no_service - outcome_debt_after_service) AS debt_improvement,
			outcome_notes,
			number, close_date, cases.funding, office, status, undup, case_county, case_zip
		FROM cases
		LEFT JOIN menu_problem ON cases.problem = menu_problem.value
		WHERE 1";	
}

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

$safe_undup = DB::escapeString($undup);
if ($undup == 1 || ($undup == 0 && $undup != ''))
{
	$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
	$sql .= " AND undup = '{$safe_undup}'";
}

$sql .= " ORDER BY problem_code ASC, close_date ASC";


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
