<?php
// modified during 2012 point release 
chdir('../../');

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');

$report_title = 'Inactive Case Report';
$report_name = "inactive_case";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) {
	$main_html = array();
	$main_html['base_url'] = $base_url;
	$main_html['page_title'] = $report_title;
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
    				  &gt; <a href=\"{$base_url}/reports/\">Reports</a> 
    				  &gt; $report_title";
	$main_html['content'] = "You are not authorized to run this report";
	$default_template = new pikaTempLib('templates/default.html', $main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$report_format = pl_grab_post('report_format');
$open_date_begin = pl_grab_post('open_date_begin');
$open_date_end = pl_grab_post('open_date_end');
$inactive_date_begin = pl_grab_post('inactive_date_begin');
$user_id = pl_grab_post('user_id');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$limit = pl_grab_post('limit');

$menu_case_status = pl_menu_get('case_status');
$menu_office = pl_menu_get('office');
$menu_funding = pl_menu_get('funding');
$staff_array = pikaUser::getUserArray();


if ('csv' == $report_format)
{
	require_once ('plCsvReportTable.php');
	require_once ('plCsvReport.php');
	$t = new plCsvReport();
}

else
{
	require_once ('plHtmlReportTable.php');
	require_once ('plHtmlReport.php');
	$t = new plHtmlReport();
}


// run the report


$sql = "SELECT cases.*, contacts.*, MAX(activities.act_date) as last_act
		FROM cases
		LEFT JOIN activities ON cases.case_id = activities.case_id
		LEFT JOIN contacts ON cases.client_id = contacts.contact_id
		WHERE 1";


$safe_open_date_begin = DB::escapeString(pl_date_mogrify($open_date_begin));
$safe_open_date_end = DB::escapeString(pl_date_mogrify($open_date_end));
$safe_inactive_date_begin = DB::escapeString(pl_date_mogrify($inactive_date_begin));

if ($open_date_begin && $open_date_end) {
	$t->add_parameter('Cases Opened Between',$open_date_begin . " - " . $open_date_end);
	$sql .= " AND open_date >= '{$safe_open_date_begin}' AND open_date <= '{$safe_open_date_end}' AND close_date IS NULL";
} elseif ($open_date_begin) {
	$t->add_parameter('Opened After',$open_date_begin);
	$sql .= " AND open_date >= '{$safe_open_date_begin}' AND close_date IS NULL";
} elseif ($open_date_end) {
	$t->add_parameter('Opened Before',$open_date_end);
	$sql .= " AND open_date <= '{$safe_open_date_end}' AND close_date IS NULL";
}


// Other filters
if(is_numeric($user_id)) {
	$safe_user_id = DB::escapeString($user_id);
	$t->add_parameter('User(s)',pl_array_lookup($user_id,$staff_array));
	$sql .= " AND (cases.user_id='{$safe_user_id}' OR cases.cocounsel1='{$safe_user_id}' OR cases.cocounsel2='{$safe_user_id}')";
}


$x = pl_process_comma_vals($funding);
if ($x != false) {
	$t->add_parameter('Funding Code(s)',$funding);
	$sql .= " AND cases.funding IN $x";
}

$x = pl_process_comma_vals($office);
if ($x != false) {
	$t->add_parameter('Office Code(s)',$office);
	$sql .= " AND cases.office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false) {
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND cases.status IN $x";
}

$x = pl_process_comma_vals($county);
if ($x != false) {
	$t->add_parameter('Counties',$county);
	$sql .= " AND county IN $x";
}

if ($gender) {
	$t->add_parameter('Gender Code',$gender);
	$safe_gender = DB::escapeString($gender);
	$sql .= " AND gender='{$safe_gender}'";
}

$sql .= " GROUP BY cases.case_id";

if ($inactive_date_begin) {
	$t->add_parameter('Inactive After',$inactive_date_begin);
	$sql .= " HAVING (MAX(act_date) <= '{$safe_inactive_date_begin}' OR MAX(act_date) IS NULL)";
}

if($limit)
{
	$t->add_parameter('Limit Results',$limit . " Row(s)");
	$safe_limit = DB::escapeString($limit);
	$sql .= " LIMIT {$safe_limit};";
}

$t->title = $report_title;
$t->display_row_count(true);
$t->set_header(array('Number','Client','Status','Office','Funding','Atty','Open Date','Last Activity'));
	
$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
while ($row = DBResult::fetchRow($result))
{
	$rpt_row = array();
		
	if ($report_format != 'csv')
	{
		$case_number_label = 'No Case #';
		
		if ($row['number'])
		{
			$case_number_label = $row['number'];
		}
		
		$rpt_row['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}&screen=info\" target=\"_blank\">{$case_number_label}</a>";
	}
	
	else 
	{
		$rpt_row['number'] = $row['number'];
	}
	
	$rpt_row['client_name'] = pikaTempLib::plugin('text_name','',$row);
	$rpt_row['status'] = pl_array_lookup($row['status'],$menu_case_status);
	$rpt_row['office'] = pl_array_lookup($row['office'],$menu_office);
	$rpt_row['funding'] = pl_array_lookup($row['funding'],$menu_funding);
	$rpt_row['user_id'] = pl_array_lookup($row['user_id'],$staff_array);
	$rpt_row['open_date'] = pikaTempLib::plugin('text_date','',$row['open_date']);
	$rpt_row['last_act'] = pikaTempLib::plugin('text_date','',$row['last_act']);
	$t->add_row($rpt_row);
}


if($show_sql) {
	$t->set_sql($sql);
}

$t->display();	
exit();

?>
