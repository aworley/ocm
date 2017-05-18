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

$report_title = "Demographics Report";
$report_name = "demographics";

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


$safe_clb = mysql_real_escape_string(pl_date_mogrify($close_date_begin));
$safe_cle = mysql_real_escape_string(pl_date_mogrify($close_date_end));


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


$t->title = $report_title;

// Determine total cases in period

$sql = "SELECT COUNT( * ) as total FROM cases WHERE 1{$where_sql};";
$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
if(mysql_num_rows($result) == 1)
{
	$row = mysql_fetch_assoc($result);
	$total = $row['total'];
}

$t->set_table_title('Client Matters Closed');
$t->set_header(array('#'));
$t->add_row(array($total));
$t->display_row_count(false);

// Start Demographics

$t->add_table();
$t->set_table_title('Gender');
$t->set_header(array('Gender', '#', '%'));
$t->display_row_count(false);

$sql = "SELECT IFNULL(label,IFNULL(gender,'No Data')), COUNT(*) AS nbr, (COUNT(*)/{$total})*100 AS prc 
		FROM cases 
		LEFT JOIN contacts ON cases.client_id = contacts.contact_id 
		LEFT JOIN menu_gender ON menu_gender.value = contacts.gender 
		WHERE 1{$where_sql} 
		GROUP BY gender 
		ORDER BY menu_order ASC;";


$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	$row['prc'] = number_format($row['prc'],2);
	$t->add_row($row);
}

$t->add_row(array('Total',$total,'100'));


if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();
$t->set_table_title('Income');
$t->set_header(array('Income', '#', '%'));
$t->display_row_count(false);

$sql = "SELECT TRUNCATE(income/10000,0) as income_category, COUNT(*) AS nbr, (COUNT(*)/(SELECT COUNT(*) FROM cases WHERE 1 {$where_sql}))*100 AS prc 
		FROM cases 
		WHERE 1{$where_sql} 
		GROUP BY income_category 
		ORDER BY income_category ASC;";


$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	
	if(is_numeric($row['income_category']))
	{
		$lower = "$" . number_format($row['income_category'] * 10000);
		$upper = "$" . number_format((($row['income_category'] + 1) * 10000) - 1);
		$row['income_category'] = $lower . " - " . $upper;
	}
	else 
	{
		$row['income_category'] = 'No Data';
	}
	$row['prc'] = number_format($row['prc'],2);
	$t->add_row($row);
}

$t->add_row(array('Total',$total,'100'));


if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();
$t->set_table_title('Age');
$t->set_header(array('Age', '#', '%'));
$t->display_row_count(false);

$sql = "SELECT TRUNCATE(client_age/10,0) as age_category, COUNT(*) AS nbr, (COUNT(*)/(SELECT COUNT(*) FROM cases WHERE 1 {$where_sql}))*100 AS prc 
		FROM cases 
		WHERE 1{$where_sql} 
		GROUP BY age_category 
		ORDER BY age_category ASC;";


$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	
	if(is_numeric($row['age_category']))
	{
		$lower = $row['age_category'] . "0";
		$upper = ((($row['age_category'] + 1) * 10) - 1);
		$row['age_category'] = $lower . " - " . $upper;
	}
	else 
	{
		$row['age_category'] = 'No Data';
	}
	$row['prc'] = number_format($row['prc'],2);
	$t->add_row($row);
}

$t->add_row(array('Total',$total,'100'));


if($show_sql) {
	$t->set_sql($sql);
}


$t->add_table();
$t->set_table_title('Marital Status');
$t->set_header(array('Marital Status', '#', '%'));
$t->display_row_count(false);

$sql = "SELECT IFNULL(label,IFNULL(marital,'No Data')), COUNT(*) AS nbr, (COUNT(*)/{$total})*100 AS prc 
		FROM cases 
		LEFT JOIN contacts ON cases.client_id = contacts.contact_id 
		LEFT JOIN menu_marital ON menu_marital.value = contacts.marital 
		WHERE 1{$where_sql} 
		GROUP BY marital 
		ORDER BY menu_order ASC;";


$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	$row['prc'] = number_format($row['prc'],2);
	$t->add_row($row);
}

$t->add_row(array('Total',$total,'100'));


if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();
$t->set_table_title('Ethnicity');
$t->set_header(array('Ethnicity', '#', '%'));
$t->display_row_count(false);

$sql = "SELECT IFNULL(label,IFNULL(ethnicity,'No Data')), COUNT(*) AS nbr, (COUNT(*)/{$total})*100 AS prc 
		FROM cases 
		LEFT JOIN contacts ON cases.client_id = contacts.contact_id 
		LEFT JOIN menu_ethnicity ON menu_ethnicity.value = contacts.ethnicity 
		WHERE 1{$where_sql} 
		GROUP BY ethnicity 
		ORDER BY menu_order ASC;";


$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
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
