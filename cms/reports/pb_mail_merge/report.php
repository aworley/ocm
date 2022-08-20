<?php

/**********************************/
/* Pika CMS (C) 2015 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaCase.php');
require_once('pikaMisc.php');

$report_title = "Pro Bono Mail Merge Report";
$report_name = "pb_mail_merge";

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

$report_format = 'csv';
$show_sql = pl_grab_post('show_sql');

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

$t->title = $report_title;
$t->set_header(array('First Name', 'Middle Name', 'Last Name', 'Extra Name', 
					'Firm', 'Address', 'Address 2',
					'City', 'State', 'ZIP Code', 'County', 'Last Case'));
$sql = "SELECT	first_name, middle_name, last_name, extra_name, firm, address, 
		address2, city, state, zip, county, last_case 
		FROM pb_attorneys
		ORDER BY last_case DESC";

// Custom code for WI Judicare
if ($base_url == '/judicare')
{
	$t->set_header(array('First Name', 'Middle Name', 'Last Name', 'Extra Name', 
					'PAI Number', 'Firm', 'Address', 'Address 2',
					'City', 'State', 'ZIP Code', 'County', 'Last Case'));
	$sql = "SELECT	first_name, middle_name, last_name, extra_name, pai_number, 
			firm, address, address2, city, state, zip, county, last_case 
			FROM pb_attorneys WHERE panel_status != 'Off'
			ORDER BY last_case DESC";
}

// run the report

$result = DB::query($sql) or trigger_error();
while ($row = DBResult::fetchRow($result))
{
	$row['last_case'] = pl_date_unmogrify($row['last_case']);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
