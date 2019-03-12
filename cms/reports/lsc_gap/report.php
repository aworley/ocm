<?php

chdir('../../');

require_once ('pika-danio.php');
pika_init(); 

// VARIABLES

$report_title = 'LSC Justice Gap Report';
$report_name = "lsc_gap";

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

$output_format = pl_grab_post('output_format');

$open_date_begin = pl_grab_post('open_date_begin');
$open_date_end = pl_grab_post('open_date_end');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$undup = pl_grab_post('undup');

$office_menu = pl_menu_get('office');
$menu_undup = pl_menu_get('undup');

// OBJECTS
if ('csv' == $report_format)
{
	require_once ('plCsvReportTable.php');
	require_once ('plCsvReport.php');
	$t = new plCsvReport();
}

else
{
	require_once ('plHtmlReportTable.php');
	require_once ('plHtmlReport.php');
	$t = new plHtmlReport();
}


$sql = "select 
if(length(problem) < 1 || ISNULL(problem), 'blank', concat(substring(lpad(problem, 2, '0'), 1, 1), '0s')) as category, 
sum(IF(lsc_justice_gap = 1, 1, 0)) AS a,
sum(IF(lsc_justice_gap = 2, 1, 0)) AS b,
sum(IF(lsc_justice_gap = 3, 1, 0)) AS c,
sum(IF(lsc_justice_gap = 4, 1, 0)) AS d,
sum(IF(lsc_justice_gap = 5, 1, 0)) AS e,
sum(IF(lsc_justice_gap = 6, 1, 0)) AS f,
sum(IF(lsc_justice_gap = 7, 1, 0)) AS g,
sum(IF(lsc_justice_gap = 12, 1, 0)) AS m,
sum(IF(lsc_justice_gap = 8, 1, 0)) AS h,
sum(IF(lsc_justice_gap = 9, 1, 0)) AS i,
sum(IF(lsc_justice_gap = 10, 1, 0)) AS j,
sum(IF(lsc_justice_gap = 11, 1, 0)) AS k,
sum(IF(ISNULL(lsc_justice_gap) || lsc_justice_gap < 1 || lsc_justice_gap > 12, 1, 0)) as l
from cases where 1";


// Filters
$odb = pl_date_mogrify($open_date_begin);
$ode = pl_date_mogrify($open_date_end);
$safe_odb = mysql_real_escape_string($odb);
$safe_ode = mysql_real_escape_string($ode);

if ($odb && $ode) 
{
	$t->add_parameter('Opened Between',$open_date_begin . " - " . $open_date_end);
	$sql .= " AND open_date >= '{$safe_odb}' AND open_date <= '{$safe_ode}'";
}

else
{
	trigger_error('Both a start date and an end date are needed.'); exit();
}

if ($undup == 1 || ($undup == 0 && $undup != '')) {
	$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
	$safe_undup = mysql_real_escape_string($undup);
	$sql .= " AND undup = '{$safe_undup}'";
}

// Other filters
$x = pl_process_comma_vals($funding);
if ($x != false) {
	$t->add_parameter('Funding Code(s)',$funding);
	$sql .= " AND funding IN $x";
}

$x = pl_process_comma_vals($office);
if ($x != false) {
	$t->add_parameter('Office Code(s)',$office);
	$sql .= " AND office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false) {
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND status IN $x";
}

$sql .= " GROUP BY category ASC WITH ROLLUP";

$t->set_title($report_title);
$t->display_row_count(false);
$t->set_header(array('Category',
	'Unable to Serve - <br>Ineligible',
	'Unable to Serve - <br>Conflict of Interest',
	'Unable to Serve - <br>Outside of <br>Program Priorities or <br>Case Acceptance <br>Guidelines',
	'Unable to Serve - <br>Insufficient <br>Resources',
	'Unable to Serve - <br>Other Reasons',
	'Unable to Serve Fully - <br>Insufficient Resources - <br>Provision of Legal <br>Information or Pro Se <br>Resources',
	'Unable to Serve Fully - <br>Insufficient Resources - <br>Provided Limited <br>Service',
	'Unable to Serve Fully - <br>Insufficient Resources - <br>Provided Some Extended <br>Service',
	'Fully Served - <br>Provision of Legal <br>Information or <br>Pro Se Resources',
	'Fully Served - <br>Provision of Limited <br>Services',
	'Fully Served - <br>Extended Service <br>Case Accepted',
	'Pending',
	'blank'));

// RUN Report

$result = mysql_query($sql) or trigger_error('');

while($row = mysql_fetch_assoc($result)) 
{
	if (strlen($row['category']) < 1)
	{
		$row['category'] = 'Totals';
	}

	$t->add_row($row);
}

if($show_sql) {
        $t->set_sql($sql);
}

$t->display();
exit();
