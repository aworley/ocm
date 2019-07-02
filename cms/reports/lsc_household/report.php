<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'LSC Household Report (Form G-2)';
$report_name = "lsc_household";

$base_url = pl_settings_get('base_url');

if(!pika_report_authorize($report_name)) 
{
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
$gender = pl_grab_post('gender');
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

// handle the crazy date range selection
$range1 = $range2 = "";

$sql = '';

$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);
$safe_ood = DB::escapeString($ood);

if ($clb && $cle) 
{
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$range1 = "close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
}

elseif ($clb) 
{
	$t->add_parameter('Closed After',$close_date_begin);
	$range1 = "close_date >= '{$safe_clb}'";
}

elseif ($cle) 
{
	$t->add_parameter('Closed Before',$close_date_end);
	$range1 = "close_date <= '{$safe_cle}'";
}

if ($ood) 
{
	$t->add_parameter('Open On',$open_on_date);
	$range2 = "(open_date <= '{$safe_ood}' AND (close_date IS NULL OR close_date > '{$safe_ood}'))";
}

if ($ood) 
{
	if ($clb || $cle) 
	{
		$sql .= " AND (($range1) OR $range2)";
	} 
	
	else 
	{
		$sql .= " AND $range2";
	}
} 

else 
{
	if ($clb || $cle) 
	{
		$sql .= " AND $range1";
	}
}


// Other filters
// 20111213 MDF - Changed to NOT
$x = pl_process_comma_vals($funding);
if ($x != false)
{
	$t->add_parameter('Funding Code(s)',$funding);
	//$sql .= " AND funding IN $x";
	$sql .= " AND funding IN $x";
}
// 20111213 MDF - END

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

if ($undup == 1 || ($undup == 0 && $undup != '')) 
{
		$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
		$safe_undup = DB::escapeString($undup);
        $sql .= " AND undup = '{$safe_undup}'";
}


// Build the report.
$t->title = $report_title;
$t->display_row_count(false);


$h_sql = "SELECT SUM(children) AS t_children, SUM(adults) AS t_adults,
			SUM(persons_helped) AS t_persons_helped FROM cases WHERE 1" . $sql;
$result = DB::query($h_sql) or trigger_error('This report has an error.');
$row = DBResult::fetchRow($result);

$t->add_row(array('1) Total number of Persons in all Households Served', '&nbsp;'));
$t->add_row(array('Total Number of Adults', $row['t_adults']));
$t->add_row(array('Total Number of Children', $row['t_children']));
$t->add_row(array('Total Number of all Persons Served', $row['t_persons_helped']));

// Blank row
$t->add_row(array('', ''));

$dv_sql = "SELECT SUM(dom_viol) AS t FROM cases WHERE 1" . $sql;
$result = DB::query($dv_sql) or trigger_error();
$row = DBResult::fetchRow($result);
$t->add_row(array('2) Total Number of Cases Involving Domestic Violence', $row['t']));


if($show_sql) 
{
	$t->set_sql($h_sql . " " . $dv_sql);
}

$t->display();	
exit();

?>
