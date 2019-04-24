<?php

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaCase.php');
require_once('pikaMisc.php');

$report_title = "Quick Docket Report";
$report_name = "quick_docket";

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

$user_id = pl_grab_post('user_id');
if(is_null($user_id) || !$user_id) {  // Need UserID to continue
	echo "Notice:  No Attorney was selected!";
	exit();
}


$report_format = pl_grab_post('report_format');
$show_sql = pl_grab_post('show_sql');


$staff = pikaMisc::fetchStaffArray();



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
$safe_user_id = DB::escapeString($user_id);
$t->add_parameter('User',$user_id);

$sql = "SELECT	case_id, open_date, last_name, status, number, user_id, 
				cocounsel1, cocounsel2, office 
		FROM cases LEFT JOIN contacts ON cases.client_id=contacts.contact_id
		WHERE (user_id='{$safe_user_id}' OR cocounsel1='{$safe_user_id}' OR cocounsel2='{$safe_user_id}')
		AND (close_date IS NULL OR close_date='0000-00-00')";

$sql .= " ORDER BY last_name ASC";


$t->title = $report_title;
$t->set_header(array('Open Date', 'Last Name', 'Status', 'Case Number', 'Counsel',
					'Co-counsel', 'Co-counsel', 'Office', 'Case Notes'));

$result = DB::query($sql) or trigger_error();
while ($row = DBResult::fetchArray($result))
{
	$notes = '';
	$case = new pikaCase($row['case_id']);
	$notesdb = $case->getNotes('DESC');
	while ($rb = DBResult::fetchArray($notesdb))
	{
		$notes .= pl_date_unmogrify($rb['act_date']);
		$notes .= ": " . $rb['summary'];
		$notes .= "  " . $rb['notes'] . "\n";
	}
	
	// limit notes size
	if (strlen($notes) > 500)
	{
		$notes = substr($notes, 0, 499) . " ...";
	}
	
	$notes = pl_html_text($notes);
	
	// Return Friendly names for User/Co-counsels
	if(isset($row['user_id']) && $row['user_id']) {
		if(isset($staff[$row['user_id']]) && $staff[$row['user_id']]) {
			$row['user_id'] = $staff[$row['user_id']];
		}
	}
	if(isset($row['cocounsel1']) && $row['cocounsel1']) {
		if(isset($staff[$row['cocounsel1']]) && $staff[$row['cocounsel1']]) {
			$row['cocounsel1'] = $staff[$row['cocounsel1']];
		}
	}
	if(isset($row['cocounsel2']) && $row['cocounsel2']) {
		if(isset($staff[$row['cocounsel2']]) && $staff[$row['cocounsel2']]) {
			$row['cocounsel2'] = $staff[$row['cocounsel2']];
		}
	}
	$row['notes'] = $notes;
	$row['open_date'] = pl_date_unmogrify($row['open_date']);
	unset($row['case_id']);

	$t->add_row($row);
}




if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
