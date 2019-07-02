<?php
// 08-05-2011 - caw - modified to run under v5
chdir('../../');

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');

$report_title = 'LSC Litigation Report';
$report_name = "lsc_lit";

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

// initialize variables
$report_format = pl_grab_post('report_format');
$filed_date_begin = pl_grab_post('filed_date_begin');
$filed_date_end = pl_grab_post('filed_date_end');
$office = pl_grab_post('office');
$show_protected = pl_grab_post('show_protected');
$program_filed = pl_grab_post('program_filed');
$lit_status = pl_grab_post('lit_status');
$funding = pl_grab_post('funding');
$user_id = pl_grab_post('user_id');
$show_sql = pl_grab_post('show_sql');

$menu_office = pl_menu_get('office');
$menu_lit_status = pl_menu_get('lit_status');
$menu_yes_no = pl_menu_get('yes_no');
$menu_relation_codes = pl_menu_get('relation_codes');
$menu_funding = pl_menu_get('funding');

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


// build the SQL statement, based on user input
$sql = "SELECT cases.number, cases.case_id, cases.number, cases.office, 
                               cases.cause_action, cases.court_address, cases.court_name, 
                               cases.court_address2, cases.court_city, 	cases.court_state, 
                               cases.court_zip, cases.docket_number, cases.date_filed, 
                               cases.protected, cases.why_protected, cases.lit_status, 
			contacts.first_name, contacts.middle_name, contacts.last_name, 
			contacts. extra_name, contacts.address, contacts.address2, 
			contacts.city, contacts.state, contacts.zip, contacts.area_code, 
			contacts.phone, contacts.phone_notes
		FROM cases 
		LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
		WHERE 1";

if($filed_date_begin && $filed_date_end) 
{
$safe_filed_date_begin = DB::escapeString(pl_date_mogrify($filed_date_begin));
$safe_filed_date_end = DB::escapeString(pl_date_mogrify($filed_date_end));

$t->add_parameter('Filed Between',$filed_date_begin . " - " . $filed_date_end);

 $sql .= " AND date_filed >= '{$safe_filed_date_begin}' 
				AND date_filed <= '{$safe_filed_date_end}'";
}

$sql .= " AND lit_status IS NOT NULL";
// program filed
if (2 == $program_filed)
{
	$t->add_parameter('Show Program Filed');
	$sql .= " AND program_filed=1";
}
else if (3 == $program_filed)
{
	$t->add_parameter('Show NOT Program Filed');
	$sql .= " AND program_filed=0";
}
// office
//$x = pl_process_comma_vals($office);
$x = DB::escapeString($office);
if ($x != false) 
{
	$t->add_parameter('Office',$office);
	$sql .= " AND office IN $x";
}
// litigation status
//$x = pl_process_comma_vals($lit_status);
$x = implode(',', $lit_status);
$x = DB::escapeString($x);
if ($x != false) 
{	
	$t->add_parameter('Litigation Status',$x);
	$sql .= " AND lit_status IN ($x)";
}

// show protected
if (2 == $show_protected)
{
	$t->add_parameter('Show Only Unprotected Info');
	$sql .= " AND (protected != 1 OR protected IS NULL)";
}
else if (3 == $show_protected)
{
	$t->add_parameter('Show Only Protected Info');
	$sql .= " AND protected=1";
}

$x = pl_process_comma_vals($funding);
if ($x != false)
{
        $t->add_parameter('Funding Code(s)',$funding);
        $sql .= " AND funding IN $x";
}

if($user_id)
{
        $t->add_parameter('Staff',pl_array_lookup($user_id,$staff_array) . " ({$user_id})");
        $safe_user_id = DB::escapeString($user_id);
        $sql .= " AND (user_id = '{$safe_user_id}' OR cocounsel1 = '{$safe_user_id}' OR cocounsel2 = '{$safe_user_id}')";
}

$sql .= " ORDER BY last_name";

// table object used to display the query results
$cols = array('Client Name & Contact Info','Case #','Staff','Cause of Action','Docket #','Office','Date Filed','Court Name & Address','Protected','Protected Reason');

$t->set_title($report_title);
$t->display_row_count(false);
$t->set_header($cols);

// execute the SQL statement, format the results, and add to the table object	
$result = DB::query($sql) or trigger_error();
while ($row = DBResult::fetchRow($result))
{
	$r = array();
// the client name column displays more than just client info	
	$r['client_name'] = pikaTempLib::plugin('text_name','',$row);
	if (true == $row['protected'])
	{
		$r['client_name'] .= "<br/><em>address info. protected</em>";
	}
	else
	{
		$r['client_name'] .= "<br>" . $row['address'];
		if ($row['address2']) 
		{
			$r['client_name'] .= "<br>" . $row['address2'];
		}
		$r['client_name'] .= "<br>" . $row['city'];
		$r['client_name'] .= ", " . $row['state'];
		$r['client_name'] .= " " . $row['zip'];
		$r['client_name'] .= "<br>(" . $row['area_code'] . ")";
		$r['client_name'] .= " " . $row['phone'];
	}
// client name column also holds opposing party info	
//	$r['client_name'] .= "<br><br><b>Adverse Parties</b>";
	
	$r['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}\" target=\"_blank\">{$row['number']}</a>";
	$r['user_id'] = pl_array_lookup($row['user_id'],$staff_array) . " ({$row['user_id']})";
	$r['cause_action'] = $row['cause_action'];
	$r['docket_number'] = $row['docket_number'];
	$r['office'] = pl_array_lookup($row['office'],$menu_office);
	$r['date_filed'] = pikaTempLib::plugin('text_date','',$row['date_filed']);
	$r['court_name'] = $row['court_name'];
	$r['court_name'] .= "<br>" . $row['court_address'];
	if ($row['court_address2']) 
	{
		$r['court_name'] .= "<br>" . $row['court_address2'];
	}
	$r['court_name'] .= "<br>" . $row['court_city'];
	$r['court_name'] .= ", " . $row['court_state'];
	$r['court_name'] .= " " . $row['court_zip'];		
	$r['protected'] = pl_array_lookup($row['protected'],$menu_yes_no);
	$r['why_protected'] = $row['why_protected'];
	
// go get opposing party info for this case
	unset($sql_con);
	$sql_con = "SELECT conflict.*, contacts.*
				FROM conflict LEFT JOIN contacts 
				ON conflict.contact_id = contacts.contact_id
				WHERE 1";
	$x = $row['case_id'];
	$sql_con .= " AND conflict.case_id = $x";
	$sql_con .= " AND conflict.relation_code !=1";

// execute opposing party search
$result_con = DB::query($sql_con) or trigger_error();
while ($row_con = DBResult::fetchRow($result_con))
{
		$r['client_name'] .= "<br><b>" .pl_array_lookup($row_con['relation_code'],$menu_relation_codes) ."</b>";
		$r['client_name'] .= "<br>" .pikaTempLib::plugin('text_name','',$row_con);
		if($row_con['address']){
			$r['client_name'] .= "<br>" .$row_con['address']; }
		if($row_con['address2'])	{
			$r['client_name'] .= "<br>" .$row_con['address2']; }
		If($row_con['city']){
			$r['client_name'] .= "<br>" .$row_con['city']; }
		if($row_con['state']) {
			$r['client_name'] .= ", " .$row_con['state']; }
		if($row_con['zip']) {
			$r['client_name'] .= "  " .$row_con['zip']; }
		if($row_con['area_code']) {
			$r['client_name'] .= "<br>(" .$row_con['area_code'] .")"; }
		if($row_con['phone']) {
			$r['client_name'] .= $row_con['phone']; }				
}
	
	$t->add_row($r);
}

$t->add_row($total);

if($show_sql) {
	$t->set_sql($sql . $sql_con);
//	$t->set_sql($sql_con);
}

$t->display();	
exit();

?>
