<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'LSC CSR Report';
$report_name = "lsc_csr";

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
$ood = pl_date_mogrify($open_on_date);

$sql = "SELECT label as 'problem_label', problem,
	SUM(IF(close_code = 'A', 1, 0)) AS 'A',
	SUM(IF(close_code = 'B', 1, 0)) AS 'B',
	SUM(IF(close_code = 'C', 1, 0)) AS 'C',
	SUM(IF(close_code = 'D', 1, 0)) AS 'D',
	SUM(IF(close_code = 'E', 1, 0)) AS 'E',
	SUM(IF(close_code = 'F', 1, 0)) AS 'F',
	SUM(IF(close_code = 'G', 1, 0)) AS 'G',
	SUM(IF(close_code = 'H', 1, 0)) AS 'H',
	SUM(IF(close_code = 'I', 1, 0)) AS 'I',
	SUM(IF(close_code = 'J', 1, 0)) AS 'J',
	SUM(IF(close_code = 'K', 1, 0)) AS 'K',
	SUM(IF(close_code IS NULL OR close_code NOT IN ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'), 1, 0)) AS 'Z',
	SUM(1) AS total
	FROM cases
	LEFT JOIN menu_problem_2007 ON cases.problem=menu_problem_2007.value
	WHERE 1";
$columns = array('Problem Code', 'Code #', 'A', 'B', 'C', 'D', 'E', 'F', 
				'G', 'H', 'I', 'J', 'K', 'No Code', 'Total');
$total = array('A'=>'0','B'=>'0','C'=>'0','D'=>'0','E'=>'0','F'=>'0','G'=>'0',
				'H'=>'0','I'=>'0','J'=>'0','K'=>'0','Z'=>'0','total'=>'0');

$sql2008 = "SELECT label as 'problem_label', problem,
	SUM(IF(close_code = 'A', 1, 0)) AS 'A',
	SUM(IF(close_code = 'B', 1, 0)) AS 'B',
	SUM(IF(close_code = 'F', 1, 0)) AS 'F',
	SUM(IF(close_code = 'G', 1, 0)) AS 'G',
	SUM(IF(close_code = 'H', 1, 0)) AS 'H',
	SUM(IF(close_code = 'IA', 1, 0)) AS 'IA',
	SUM(IF(close_code = 'IB', 1, 0)) AS 'IB',
	SUM(IF(close_code = 'IC', 1, 0)) AS 'IC',
	SUM(IF(close_code = 'K', 1, 0)) AS 'K',
	SUM(IF(close_code = 'L', 1, 0)) AS 'L',
	SUM(IF(close_code IS NULL OR close_code NOT IN ('A','B','F','G','H','IA','IB','IC','K','L'), 1, 0)) AS 'Z',
	SUM(1) AS total
	FROM cases
	LEFT JOIN menu_problem_2008 ON cases.problem=menu_problem_2008.value
	WHERE 1";
$columns2008 = array('Problem Code','Code #','A','B','F','G','H','IA','IB','IC','K','L','No Code','Total');
$total2008 = array('A'=>'0','B'=>'0','F'=>'0','G'=>'0','H'=>'0','IA'=>'0','IB'=>'0',
					'IC'=>'0','K'=>'0','L'=>'0','Z'=>'0','total'=>'0');

if(strtotime($cle) >= strtotime('1/1/2008')) {
	$sql = $sql2008;
	$columns = $columns2008;
	$total = $total2008;
}
					
// handle the crazy date range selection
$range1 = $range2 = "";
$safe_clb = mysql_real_escape_string($clb);
$safe_cle = mysql_real_escape_string($cle);
$safe_ood = mysql_real_escape_string($ood);

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

if ($ood) {
	$t->add_parameter('Open On',$open_on_date);
	$range2 = "(open_date <= '{$safe_ood}' AND (close_date IS NULL OR close_date > '{$safe_ood}'))";
}

if ($ood) {
	if ($clb || $cle) {
		$sql .= " AND (($range1) OR $range2)";
	} else { $sql .= " AND $range2"; }
} else {
	if ($clb || $cle) {
		$sql .= " AND $range1";
	}
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

$sql .= " GROUP BY problem ORDER BY problem ASC";


$t->title = $report_title;
$t->display_row_count(false);
//$t->set_table_title('Table 1: Ethnicity by Age Category');
$t->set_header($columns);


$result = mysql_query($sql) or trigger_error();
while ($row = mysql_fetch_assoc($result))
{
	$t->add_row($row);
	unset($row['problem_label']);
	unset($row['problem']);
	foreach ($row as $key => $val) {
		$total[$key] += $val;
	}
}

$r = array_merge(array('','Totals'), array_values($total));


$t->add_row($r);

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
