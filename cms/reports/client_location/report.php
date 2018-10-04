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

$report_title = "Client Location";
$report_name = "client_location";

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
	$t->add_parameter('Closing Date Between',$date_start . " - " . $date_end);
	$where_sql = " AND cases.close_date >= '{$safe_ds}' AND cases.close_date <= '{$safe_de}'";
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
$t->title = $report_title;
$t->set_table_title('Callers by ZIP Code');
$t->set_header(array("ZIP Code", "Callers"));
$t->display_row_count(false);

$sql = "SELECT zip, COUNT(*) as callers FROM cases";
$sql .= " LEFT JOIN contacts ON client_id = contact_id WHERE 1{$where_sql} group BY zip";
$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while ($row = mysql_fetch_assoc($result))
{
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();
$t->set_header(array("City", "State", "Callers"));
$t->display_row_count(false);

$sql = "SELECT city, state, COUNT(*) as callers FROM cases";
$sql .= " LEFT JOIN contacts ON client_id = contact_id WHERE 1{$where_sql} GROUP BY state, city";
$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while ($row = mysql_fetch_assoc($result))
{
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

/*
$sql = "SELECT
		IFNULL(pension_services,'No Data') AS pension_services,
		IFNULL(SUM(annuity_interest),0) AS annuity_interest,
		IFNULL(SUM(annuity_not_recovered),0) AS annuity_not_recovered,
		IFNULL(SUM(annuity_retro_payment),0) AS annuity_retro_payment,
		IFNULL(SUM(annuity_lump_sum),0) AS annuity_lump_sum,
		IFNULL(SUM(annuity_cash_accumulated),0) AS annuity_cash_accumulated,
		IFNULL(SUM(annuity_total_cash_accumulated),0) AS annuity_total_cash_accumulated
		FROM cases 
		WHERE 1{$where_sql}
		GROUP BY pension_services";


$t->add_table();
$t->set_table_title('Cash Accumulated');
$t->display_row_count(false);

// Build Columns
$cols = array(	'Service',
				'Interest',
				'Not Recovered', 
				'Retroactive Payment', 
				'Lump Sum', 
				'Annuity', 
				'Total');


// Build Header

$t->set_header($cols);


// Run the Report

$pension_services = pl_menu_get('pension_services');


$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while($row = mysql_fetch_assoc($result))
{
	$row['pension_services'] = pl_array_lookup($row['pension_services'],$pension_services);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();


$sql = "SELECT
		IFNULL(pension_services,'No Data') AS pension_services,
		IFNULL(SUM(annuity_interest),0) AS annuity_interest,
		IFNULL(SUM(annuity_not_recovered),0) AS annuity_not_recovered,
		IFNULL(SUM(annuity_retro_payment),0) AS annuity_retro_payment,
		IFNULL(SUM(annuity_lump_sum),0) AS annuity_lump_sum,
		IFNULL(SUM(annuity_present_value),0) AS annuity_present_value,
		IFNULL(SUM(annuity_total_present_value),0) AS annuity_total_present_value
		FROM cases 
		WHERE 1{$where_sql}
		GROUP BY pension_services";


$t->title = $report_title;
$t->set_table_title('Present Value');
$t->display_row_count(false);

// Build Columns
$cols = array(	'Service',
				'Interest',
				'Not Recovered', 
				'Retroactive Payment', 
				'Lump Sum', 
				'Annuity', 
				'Total');


// Build Header

$t->set_header($cols);


// Run the Report

$pension_services = pl_menu_get('pension_services');


$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while($row = mysql_fetch_assoc($result))
{
	$row['pension_services'] = pl_array_lookup($row['pension_services'],$pension_services);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}
*/
$t->display();
exit();

?>
