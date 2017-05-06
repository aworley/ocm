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

$report_title = "Issue by SponsorType Report";
$report_name = "issue_sponsor";

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
$sql = "SELECT SUM(nbr) AS total_nbr
		FROM ((SELECT COUNT(*) AS nbr
			FROM cases
			WHERE 1{$where_sql} AND (issue1 IS NOT NULL OR sub_issue1 IS NOT NULL) 
			) UNION (
			SELECT COUNT(*) AS nbr
			FROM cases
			WHERE 1{$where_sql} AND (issue2 IS NOT NULL OR sub_issue2 IS NOT NULL)
			) UNION (
			SELECT COUNT(*) AS nbr
			FROM cases
			WHERE 1{$where_sql} AND (issue3 IS NOT NULL OR sub_issue3 IS NOT NULL)
			)) AS tbl";

//echo $sql;

$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
$total_nbr = 0;
if(mysql_num_rows($result) == 1)
{
	$row = mysql_fetch_assoc($result);
	$total_nbr = $row['total_nbr'];
}

$sql = "SELECT IFNULL(menu_sponsor_type_1.label,'No Data') AS sponsor_type, IFNULL(menu_pension_issue.label,'No Data') AS issue_label, IFNULL(menu_pension_sub_issue.label,'No Data') AS sub_issue_label, SUM(nbr) AS total_nbr
		FROM ((SELECT sponsor_type_1, issue1 AS issue, sub_issue1 AS sub_issue, COUNT(*) AS nbr
			FROM cases
			LEFT JOIN pension_plans ON cases.pension_plan_id = pension_plans.pension_plan_id
			WHERE 1{$where_sql} AND (issue1 IS NOT NULL OR sub_issue1 IS NOT NULL) 
			GROUP BY sponsor_type_1, issue1, sub_issue1
			) UNION (
			SELECT sponsor_type_1, issue2 AS issue, sub_issue2 AS sub_issue, COUNT(*) AS nbr
			FROM cases
			LEFT JOIN pension_plans ON cases.pension_plan_id = pension_plans.pension_plan_id
			WHERE 1{$where_sql} AND (issue2 IS NOT NULL OR sub_issue2 IS NOT NULL)
			GROUP BY sponsor_type_1, issue2, sub_issue2
			) UNION (
			SELECT sponsor_type_1, issue3 AS issue, sub_issue3 AS sub_issue, COUNT(*) AS nbr
			FROM cases
			LEFT JOIN pension_plans ON cases.pension_plan_id = pension_plans.pension_plan_id
			WHERE 1{$where_sql} AND (issue3 IS NOT NULL OR sub_issue3 IS NOT NULL)
			GROUP BY sponsor_type_1, issue3, sub_issue3
			)) AS tbl
		LEFT JOIN menu_sponsor_type_1 ON menu_sponsor_type_1.value = tbl.sponsor_type_1
		LEFT JOIN menu_pension_issue ON menu_pension_issue.value = tbl.issue
		LEFT JOIN menu_pension_sub_issue ON menu_pension_sub_issue.value = tbl.sub_issue
		GROUP BY sponsor_type, issue, sub_issue";

//echo $sql;

$t->title = $report_title;


$sponsor_type_totals = array();
$issue_totals = array();

$sponsor_type_array = array();

$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	//print_r($row);
	$sponsor_type_array[$row['sponsor_type']][$row['issue_label']][$row['sub_issue_label']] = $row['total_nbr']; 
	$sponsor_type_totals[$row['sponsor_type']] += $row['total_nbr'];
	$issue_totals[$row['sponsor_type']][$row['issue_label']] += $row['total_nbr'];
}


foreach ($sponsor_type_array as $sponsor_type => $issue_array)
{
	$t->set_header(array('Issue','Sub Issue','#','%'));
	$t->set_table_title($sponsor_type);
	$t->display_row_count(false);
	//$t->add_row(array($sponsor_type,'','',$sponsor_type_totals[$sponsor_type]));
	foreach ($issue_array as $issue => $sub_issue_array)
	{
		$t->add_row(array($issue,'',$issue_totals[$sponsor_type][$issue],number_format(($issue_totals[$sponsor_type][$issue]/$total_nbr)*100,2)));
		foreach ($sub_issue_array as $sub_issue => $nbr)
		{
			$t->add_row(array('',$sub_issue,$nbr,number_format(($nbr/$total_nbr)*100,2)));
		}
	}
	$t->add_row(array('Total','',$sponsor_type_totals[$sponsor_type],number_format(($sponsor_type_totals[$sponsor_type]/$total_nbr)*100,2)));
	$t->add_table();
}

$t->display_row_count(false);

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
