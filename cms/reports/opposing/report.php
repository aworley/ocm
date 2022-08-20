<?php
/**********************************/
/* Pika CMS (C) 2013 			  */
/* Pika Software, LLC.	          */
/* http://pikasoftware.com        */
/**********************************/


chdir('../../');

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');
require_once('pikaCase.php');

$report_title = 'Opposing Party Report';
$report_name = "opposing";

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

$date_start = pl_grab_post('date_start');
$date_end = pl_grab_post('date_end');
$office = pl_grab_post('office');
$problem = pl_grab_post('problem');
$funding = pl_grab_post('funding');
$user_id = pl_grab_post('user_id');
$show_sql = pl_grab_post('show_sql');

$opposing_name = pl_grab_post('opposing_name');

$case_county = pl_grab_post('case_county');

$staff_array = pikaUser::getUserArray();
$office_array = pl_menu_get('office');
$funding_array = pl_menu_get('funding');
$problem_array = pl_menu_get('problem');

$order_by = pl_grab_post('order_by','open_date');
$order = pl_grab_post('order');

$report_format = pl_grab_post('report_format');

if ('csv' == $report_format)
{
	require_once ('plCsvReportTable.php');
	require_once ('plCsvReport.php');
	$t = new plCsvReport();
	$line_break = "\n\r";
}

else
{
	require_once ('plHtmlReportTable.php');
	require_once ('plHtmlReport.php');
	$t = new plHtmlReport();
	$line_break = "<br/>\n\r";
}




$safe_date_start = DB::escapeString(pl_date_mogrify($date_start));
$safe_date_end = DB::escapeString(pl_date_mogrify($date_end));

if($date_start && $date_end)
{
	$t->add_parameter('Case Opened Between',$date_start . " - " . $date_end);
	$where_sql .= " AND cases.open_date >= '{$safe_date_start}' AND cases.open_date <= '{$safe_date_end}' ";
}
elseif ($date_start)
{
	$t->add_parameter('Case Opened After',$date_start);	
	$where_sql .= " AND cases.open_date >= '{$safe_date_start}'";
}
elseif ($date_end)
{
	$t->add_parameter('Case Opened Before',$date_end);
	$where_sql .= " AND cases.open_date <= '{$safe_date_end}' ";
}
if(strlen($funding) > 0)
{
	$t->add_parameter('Funding',pl_array_lookup($funding,$funding_array) . " ({$funding})");
	$safe_funding = DB::escapeString($funding);
	$where_sql .= " AND funding = '{$safe_funding}'";
}
if(strlen($problem) > 0)
{
	$t->add_parameter('Problem',pl_array_lookup($problem,$problem_array));
	$safe_problem = DB::escapeString($problem);
	$where_sql .= " AND problem = '{$safe_problem}'";
}
if(strlen($office) > 0)
{
	$t->add_parameter('Office',pl_array_lookup($office,$office_array) . " ({$office})");
	$safe_office = DB::escapeString($office);
	$where_sql .= " AND office = '{$safe_office}'";
}
if(strlen($user_id) > 0)
{
	$t->add_parameter('Staff',pl_array_lookup($user_id,$staff_array) . " ({$user_id})");
	$safe_user_id = DB::escapeString($user_id);
	$where_sql .= " AND (user_id = '{$safe_user_id}' OR cocounsel1 = '{$safe_user_id}' OR cocounsel2 = '{$safe_user_id}')";
}
if(strlen($case_county) > 0)
{
	$t->add_parameter('Case County', $case_county);
	$safe_case_county = DB::escapeString($case_county);
	$safe_case_county = pl_process_comma_vals($safe_case_county);
        $where_sql .= " AND case_county IN {$safe_case_county}";
}
if(strlen($opposing_name) > 0)
{
	$opposing_last_name = trim($opposing_name);
	if(strpos($opposing_name,',') !== false)
	{
		$name_split = explode(',',$opposing_name);
		$opposing_last_name = trim($name_split[0]);
		$opposing_first_name = trim($name_split[1]);
	}
	$t->add_parameter('Opposing Name',$opposing_name);
	if(strlen($opposing_last_name) > 0)
	{
		$safe_opposing_last_name = DB::escapeString($opposing_last_name);
		$where_sql .= " AND opposing.last_name LIKE '{$safe_opposing_last_name}'";
	}
	if(strlen($opposing_first_name) > 0)
	{
		$safe_opposing_first_name = DB::escapeString($opposing_first_name);
		$where_sql .= " AND opposing.first_name LIKE '{$safe_opposing_first_name}'";
	}
}


if($order != 'ASC')
{
	$order = 'DESC';
}
$order_sql = "";
if(strlen($order_by) > 0)
{
	switch ($order_by)
	{
		case 'opposing_name':
			$t->add_parameter('Order By ', 'Opposing Party Name ' . $order);
			$order_sql = " ORDER BY opposing_last_name {$order}, opposing_first_name {$order}";
			break;
		case 'client_name':
			$t->add_parameter('Order By ', 'Primary Client Name ' . $order);
			$order_sql = " ORDER BY client_last_name {$order}, client_first_name {$order}";
			break;
		case 'open_date':
			$t->add_parameter('Order By ', 'Case Open Date ' . $order);
			$order_sql = " ORDER BY open_date {$order}";
			break;
		
	}
}
// TRIM(CONCAT(pri_client.last_name,', ',pri_client.first_name,' ',pri_client.middle_name)) AS client_name,
$sql = "SELECT
		cases.case_id,
		opposing.first_name AS opposing_first_name,
		opposing.last_name AS opposing_last_name,
		opposing.middle_name AS opposing_middle_name,
		TRIM(CONCAT(pri_client.last_name,', ',pri_client.first_name)) AS client_name,
		cases.number,
		cases.cause_action,
		date_filed,
		open_date,
		case_county
		FROM cases
		LEFT JOIN contacts AS pri_client ON cases.client_id = pri_client.contact_id
		JOIN conflict ON cases.case_id = conflict.case_id
		JOIN contacts AS opposing ON conflict.contact_id = opposing.contact_id
		WHERE 1
		AND conflict.relation_code = '2'{$where_sql}{$order_sql}";

$t->title = $report_title;
$t->display_row_count(true);
$t->set_header(array('Opposing Party','Client Name', 'Case Number', 'Cause of Action', 'Date Filed', 'Case Opened', 'Case County'));
	
$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());

// interogate resulting output for report
while ($row = DBResult::fetchRow($result))
{
	$rpt_row = array();	
	$rpt_row['opposing_name'] = pikaTempLib::plugin('text_name','',$row,'',array("last_name=opposing_last_name","first_name=opposing_first_name","middle_name=opposing_middle_name","order=last"));
	$rpt_row['client_name'] = $row['client_name'];
	$rpt_row['number'] = 'No Case #';
	if($row['number'])
	{
		$rpt_row['number'] = $row['number'];
		if ($report_format != 'csv')
		{
			$rpt_row['number'] = "<a href=\"{$base_url}/case.php?case_id={$row['case_id']}&screen=info\">{$row['number']}</a>";
		}
	}	
	$rpt_row['cause_action'] = $row['cause_action'];
	$rpt_row['date_filed'] = pikaTempLib::plugin('text_date','',$row['date_filed']);
	$rpt_row['open_date'] = pikaTempLib::plugin('text_date','',$row['open_date']);
	$rpt_row['case_county'] = $row['case_county'];
	
	$t->add_row($rpt_row);
}

if($show_sql) 
{
	$t->set_sql($sql);
}

$t->display();	
exit();

?>
