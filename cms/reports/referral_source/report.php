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

$report_title = "Referral Source Report";
$report_name = "referral_source";

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


$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');

$show_sql = pl_grab_post('show_sql');


$safe_clb = DB::escapeString(pl_date_mogrify($close_date_begin));
$safe_cle = DB::escapeString(pl_date_mogrify($close_date_end));


$where_sql = '';

if ($close_date_begin && $close_date_end) {
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$where_sql .= " AND close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
} elseif ($close_date_begin) {
	$t->add_parameter('Closed After',$close_date_begin);
	$where_sql .= " AND close_date >= '{$safe_clb}'";
} elseif ($close_date_end) {
	$t->add_parameter('Closed Before',$close_date_end);
	$where_sql .= " AND close_date <= '{$safe_cle}'";
}




// run the report


$sql = "SELECT COUNT( * ) as total FROM cases WHERE 1{$where_sql};";
$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
if(DBResult::numRows($result) == 1)
{
	$row = DBResult::fetchRow($result);
	$total = $row['total'];
}

$sql = "SELECT label, COUNT(*) AS nbr, (COUNT(*)/{$total})*100 AS prc 
		FROM cases 
		LEFT JOIN menu_referred_by ON menu_referred_by.value = cases.referred_by 
		WHERE 1{$where_sql} 
		GROUP BY referred_by 
		ORDER BY menu_order ASC;";


$t->title = $report_title;
$t->set_header(array('Referral Source', '#', '%'));

$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
while ($row = DBResult::fetchRow($result))
{
	$row['prc'] = number_format($row['prc'],2);
	$t->add_row($row);
}

$t->add_row(array('Total',$total,'100'));


if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
