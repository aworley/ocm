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

$report_title = "Activity Type Report";
$report_name = "activity_type";

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


$date_start = pl_grab_post('date_start');
$date_end = pl_grab_post('date_end');

$show_sql = pl_grab_post('show_sql');


$safe_ds = mysql_real_escape_string(pl_date_mogrify($date_start));
$safe_de = mysql_real_escape_string(pl_date_mogrify($date_end));


$where_sql = '';

if ($date_start && $date_end) {
	$t->add_parameter('Date Between',$date_start . " - " . $date_end);
}
elseif (strtotime($date_start) > strtotime($date_end)) 
{
	echo "Invalid Date range";
	exit();
}
else
{
	echo "You must select the beginning and ending date for the reporting period";
	exit();
}




// run the report


$sql = "SELECT
		'category' AS category,
		IFNULL(SUM(IF(pension_services = '01',1,0)),0) AS quick_answer,
		IFNULL(SUM(IF(pension_services = '02',1,0)),0) AS brief_service,
		IFNULL(SUM(IF(pension_services = '03',1,0)),0) AS referral,
		IFNULL(SUM(IF(pension_services = '04',1,0)),0) AS locate_lost,
		IFNULL(SUM(IF(pension_services = '05',1,0)),0) AS obtain_info,
		IFNULL(SUM(IF(pension_services = '06',1,0)),0) AS research,
		IFNULL(SUM(IF(pension_services = '07',1,0)),0) AS claim_appeal,
		IFNULL(SUM(IF(pension_services = '08',1,0)),0) AS appear_hearing,
		SUM(IF(pension_services IS NULL,1,0)) AS no_data
		FROM cases 
		WHERE 1";


$t->title = $report_title;
$t->set_table_title('Opened Matters');
$t->display_row_count(false);

// Build Columns
$cols = array(	'Category',
				'01 - Quick Ans',
				'02 - Brief Svc', 
				'03 - Referral', 
				'04 - Locate Lost', 
				'05 - Obtain Info', 
				'06 - Research', 
				'07 - Claim or Appeal', 
				'08 - Appear at Hearing', 
				'No Data', 
				'Total');


// Build Header

$t->set_header($cols);


// Run the Opened Before
$ob_where_sql = " AND open_date <= '{$safe_ds}' AND (cases.close_date IS NULL OR (cases.close_date <= '{$safe_de}' AND cases.close_date >= '{$safe_ds}'))";

$result = mysql_query($sql . $ob_where_sql) or trigger_error('SQL:' . $sql . $ob_where_sql . ' Error: ' . mysql_error());

$total_open = $total_closed = array(
'category' => '',
'quick_answer' => 0,
'brief_service' => 0,
'referral' => 0,
'locate_lost' => 0,
'obtain_info' => 0,
'research' => 0,
'claim_appeal' => 0,
'appear_hearing' => 0,
'no_data' => 0,
'total' => 0
);

$total = 0;

while($row = mysql_fetch_assoc($result))
{
	
	foreach ($row as $col => $nbr)
	{   
		$total += $row[$col];
		$total_open[$col] += $row[$col];
	}
	$row['category'] = 'Opened Before';
	$row['total'] = $total_open['total'] = $total;
	$t->add_row($row);
}


// Run the Opened During
$od_where_sql = " AND open_date >= '{$safe_ds}' AND open_date <= '{$safe_de}' AND ('close_date' IS NULL OR close_date = '0000-00-00' OR (close_date <= '{$safe_de}' AND close_date >= '{$safe_ds}'))";

$result = mysql_query($sql . $od_where_sql) or trigger_error('SQL:' . $sql . $od_where_sql . ' Error: ' . mysql_error());

$total = 0;

while($row = mysql_fetch_assoc($result))
{
	
	foreach ($row as $col => $nbr)
	{
		$total += $row[$col];
		$total_open[$col] += $row[$col];
	}
	$row['category'] = 'Opened During';
	$row['total'] = $total;
	$total_open['total'] += $total;
	$t->add_row($row);
}
$total_open['category'] = 'Total';
$t->add_row($total_open);


if($show_sql) {
	$t->set_sql($sql . $ob_where_sql . PHP_EOL . $sql . $od_where_sql);
}


$t->add_table();
$t->display_row_count(false);
$t->set_table_title('Closed Matters');

$t->set_header($cols);

// Run the Closed During
$cd_where_sql = " AND open_date <= '{$safe_de}' AND (close_date >= '{$safe_ds}' AND close_date <= '{$safe_de}')";

$result = mysql_query($sql . $cd_where_sql) or trigger_error('SQL:' . $sql . $cd_where_sql . ' Error: ' . mysql_error());

$total = 0;

while($row = mysql_fetch_assoc($result))
{
	
	foreach ($row as $col => $nbr)
	{
		$total += $row[$col];
		$total_closed[$col] += $row[$col];
	}
	$row['category'] = 'Closed During';
	$row['total'] = $total;
	$total_closed['total'] += $total;
	$t->add_row($row);
}


// Run the Still Open
$so_where_sql = " AND open_date <= '{$safe_de}' AND (close_date IS NULL OR close_date = '0000-00-00')";

$result = mysql_query($sql . $so_where_sql) or trigger_error('SQL:' . $sql . $so_where_sql . ' Error: ' . mysql_error());

$total = 0;

while($row = mysql_fetch_assoc($result))
{
	$row['category'] = 'Still Open';
	foreach ($row as $col => $nbr)
	{
		$total += $row[$col];
		$total_closed[$col] += $row[$col];
	}
	$row['total'] = $total;
	$total_closed['total'] += $total;
	$t->add_row($row);
}
$total_closed['category'] = 'Total';
$t->add_row($total_closed);


if($show_sql) {
	$t->set_sql($sql . $cd_where_sql . PHP_EOL . $sql . $so_where_sql);
}

$t->display();
exit();

?>
