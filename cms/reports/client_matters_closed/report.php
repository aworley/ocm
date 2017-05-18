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

$report_title = "Client Matters Closed Report";
$report_name = "client_matters_closed";

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

$redact_client = pl_grab_post('redact_client');
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


$sql = "SELECT	case_id, number, close_date, pension_case_closure_notes, primary_client.first_name, primary_client.last_name
		FROM cases 
		LEFT JOIN contacts AS primary_client ON cases.client_id=primary_client.contact_id
		WHERE 1{$where_sql}
		ORDER BY close_date DESC";


$t->title = $report_title;
$t->set_header(array('Case Number', 'Client Name', 'Closing Date', 'Closure Notes'));

$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
while ($row = mysql_fetch_assoc($result))
{
	$r = array();
	if (strlen($row['number']) < 1)
	{
		$row['number'] = "No Case #";
	}
	$r['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}\">". $row['number']."</a>";
	$r['client_name'] = 'Xxxxxxxxx, Xxxxxx';
	if($redact_client != '1')
	{
		$r['client_name'] = pikaTempLib::plugin('text_name','',$row);
	}
	$r['close_date'] = pl_date_unmogrify($row['close_date']);
	$r['pension_case_closure_notes'] = pl_html_text($row['pension_case_closure_notes']);
	$t->add_row($r);
}




if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
