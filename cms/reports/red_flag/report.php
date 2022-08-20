<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');
require_once('pikaFlags.php');

$report_title = 'Flagged Case Report';
$report_name = "red_flag";

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
$open_date_begin = pl_grab_post('open_date_begin');
$open_date_end = pl_grab_post('open_date_end');
$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$limit = pl_grab_post('limit');

$menu_case_status = pl_menu_get('case_status');
$menu_office = pl_menu_get('office');
$menu_funding = pl_menu_get('funding');
$staff_array = pikaUser::getUserArray();
$menu_limit = array('100'=>'100','500'=>'500','1000'=>'1000');

$base_url = pl_settings_get('base_url');

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

$sql = "SELECT cases.*, contacts.*
		FROM cases
		LEFT JOIN contacts ON cases.client_id = contacts.contact_id
		WHERE 1";



$safe_open_date_begin = DB::escapeString(pl_date_mogrify($open_date_begin));
$safe_open_date_end = DB::escapeString(pl_date_mogrify($open_date_end));

if ($open_date_begin && $open_date_end) {
	$t->add_parameter('Cases Opened Between',$open_date_begin . " - " . $open_date_end);
	$sql .= " AND open_date >= '{$safe_open_date_begin}' AND open_date <= '{$safe_open_date_end}'";
} elseif ($open_date_begin) {
	$t->add_parameter('Opened After',$open_date_begin);
	$sql .= " AND open_date >= '{$safe_open_date_begin}'";
} elseif ($open_date_end) {
	$t->add_parameter('Opened Before',$open_date_end);
	$sql .= " AND open_date <= '{$safe_open_date_end}'";
}





$clb = pl_date_mogrify($close_date_begin);
$cle = pl_date_mogrify($close_date_end);
$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);

if ($clb) 
{
        $t->add_parameter('Closed On or After', $close_date_begin);
        $sql .= " AND close_date >= '{$safe_clb}'";
}

if ($cle) 
{
        $t->add_parameter('Closed On or Before', $close_date_end);
        $sql .= " AND close_date <= '{$safe_cle}'";
}








// Other filters
$x = pl_process_comma_vals($funding);
if ($x != false) {
	$t->add_parameter('Funding Code(s)',$funding);
	$sql .= " AND funding IN $x";
}

$x = pl_process_comma_vals($office);
if ($x != false) {
	$t->add_parameter('Office Code(s)',$office);
	$sql .= " AND office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false) {
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND status IN $x";
}

$x = pl_process_comma_vals($county);
if ($x != false) {
	$t->add_parameter('Counties',$county);
	$sql .= " AND case_county IN $x";
}

$sql .= " ORDER BY open_date ASC";

if (!is_numeric($limit) || !in_array($limit,$menu_limit)) {
	$limit = '1000';
}
$t->add_parameter('Limit Returned Rows',$limit);

$t->title = $report_title;
$t->display_row_count(true);
$t->set_header(array('Number','Client','Status','Office','Atty','Open Date','Close Date','Flags'));

$limit_count = 0;
$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
while ($row = DBResult::fetchRow($result))
{
	$flags_array = pikaFlags::generateFlags($row['case_id']);
	if(count($flags_array) > 0) {
		
		$rpt_row = array();
		$rpt_row['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}\" target=\"_blank\">{$row['number']}</a>";
		$rpt_row['client_name'] = pikaTempLib::plugin('text_name','',$row);
		$rpt_row['status'] = pl_array_lookup($row['status'],$menu_case_status);
		$rpt_row['office'] = pl_array_lookup($row['office'],$menu_office);
		$rpt_row['user_id'] = pl_array_lookup($row['user_id'],$staff_array);
		$rpt_row['open_date'] = pikaTempLib::plugin('text_date','',$row['open_date']);
		$rpt_row['close_date'] = pikaTempLib::plugin('text_date','',$row['close_date']);
		$tmp_flag_array = array();
		foreach ($flags_array as $key => $flag_array) {
			$tmp_flag_array[] = $flag_array['name'];
		}
		$rpt_row['flags'] = implode(",\n",$tmp_flag_array);
		$t->add_row($rpt_row);
		$limit_count++;	
	}
}


if($show_sql) {
	$t->set_sql($sql);
}

$t->display();	
exit();

?>
