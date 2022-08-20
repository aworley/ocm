<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'LSC Interim Case Services';
$report_name = "lsc_interim";

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

$report_format = pl_grab_get('report_format');
$close_date_begin = pl_grab_get('close_date_begin');
$close_date_end = pl_grab_get('close_date_end');
$open_on_date = pl_grab_get('open_on_date');
$funding = pl_grab_get('funding');
$office = pl_grab_get('office');
$status = pl_grab_get('status');
$county = pl_grab_get('county');
$gender = pl_grab_get('gender');
$undup = pl_grab_get('undup');
$calendar_year = pl_grab_get('calendar_year');
$clean_calendar_year = DB::escapeString($calendar_year);
$show_sql = pl_grab_get('show_sql');

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

$clb = $clean_calendar_year . "-01-01";
$cle = $clean_calendar_year . "-06-30";
$ood = $clean_calendar_year . "-06-30";

$eth_sql = "SELECT SUBSTRING(LPAD(problem, 2, '0'),1,1) AS category,
	SUM(IF(close_code IN ('A', 'B'), 1, 0)) AS 'Cases Closed after Limited Service',
	SUM(IF(close_code IN ('F', 'G', 'H', 'IA', 'IB', 'IC', 'K', 'L'), 1, 0)) AS 'Cases Closed after Extended Service',
	SUM(IF(ISNULL(close_date) OR close_date > '{$clean_calendar_year}-06-30', 1, 0)) AS 'Cases Remaining Open on June 30'
	FROM cases
	WHERE status='2'";



// handle the crazy date range selection
$range1 = $range2 = "";

$sql = '';

$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);
$safe_ood = DB::escapeString($ood);

if ($clb && $cle) 
{
	//$t->add_parameter('Closed Between', $safe_clb . " - " . $safe_cle);
	$range1 = "close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
}

elseif ($clb) 
{
	//$t->add_parameter('Closed After', $safe_clb);
	$range1 = "close_date >= '{$safe_clb}'";
}

elseif ($cle) 
{
	//$t->add_parameter('Closed Before', $safe_cle);
	$range1 = "close_date <= '{$safe_cle}'";
}

if ($ood) 
{
	//$t->add_parameter('Open On', $safe_ood);
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
	$sql .= " AND county IN $x";
}

if ($gender) 
{
	$t->add_parameter('Gender Code',$gender);
	$safe_gender = DB::escapeString($gender);
	$sql .= " AND gender='{$safe_gender}'";
}

if ($undup == 1 || ($undup == 0 && $undup != '')) 
{
		$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
		$safe_undup = DB::escapeString($undup);
        $sql .= " AND undup = '{$safe_undup}'";
}

$eth_sql .= $sql . " GROUP BY category";

$t->title = $report_title;
$t->set_table_title("Form G-1: Interim Case Services");
$t->display_row_count(false);
$t->set_header(array('Category','Cases Closed after Limited Service','Cases Closed after Extended Service','Cases Remaining Open on June 30'));
$t->add_parameter('Cases open between', $safe_clb . " - " . $safe_cle);
$t->add_parameter('Status Codes', "2, 5");
$t->add_parameter('Limited Service Closing Codes', 'A, B');
$t->add_parameter('Extended Service Closing Codes', 'F, G, H, IA, IB, IC, K, L');
$t->add_parameter('Funding', $funding);


$total = array();
$total['code'] = "";
$total['category'] = "";
	$total["A"]	= "0";
	$total["B"]	= "0";
	$total["C"]	= "0";
	$total["D"] = "0";
	$total["total"] = "0";
	
$result = DB::query($eth_sql) or trigger_error();
while ($row = DBResult::fetchRow($result))
{
	$t->add_row($row);

	$total["A"]	+= $row["Under 18"];
	$total["B"]	+= $row["18 to 59"];
	$total["C"]	+= $row["60 and Older"];
	$total["D"] += $row["No Age Data"];
	$total["total"] += $row["Total"];
}

//$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($eth_sql);
}




// Add the PAI table
$t->add_table();
$t->set_table_title("Form G-1(d): Interim Case Services (PAI)");
$t->display_row_count(false);
$t->set_header(array('Category', 'Cases Closed after Limited Service','Cases Closed after Extended Service','Cases Remaining Open on June 30'));

$pai_sql = "SELECT SUBSTRING(LPAD(problem, 2, '0'),1,1) AS category,
	SUM(IF(close_code IN ('A', 'B'), 1, 0)) AS 'Cases Closed after Limited Service',
	SUM(IF(close_code IN ('F', 'G', 'H', 'IA', 'IB', 'IC', 'K', 'L'), 1, 0)) AS 'Cases Closed after Extended Service',
	SUM(IF(ISNULL(close_date) OR close_date > '{$clean_calendar_year}-06-30', 1, 0)) AS 'Cases Remaining Open on June 30'
	FROM cases
	WHERE status='5'" . $sql . " GROUP BY category";
$result = DB::query($pai_sql) or trigger_error();

while ($row = DBResult::fetchRow($result))
{
	$t->add_row($row);
}

//$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($pai_sql);
}

$t->display();	
exit();

?>
