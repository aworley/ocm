<?php 

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaMisc.php');


$report_title = "Time Report";
$report_name = "time";

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

pl_menu_get('yes_no');
pl_menu_get('funding');
pl_menu_get('office');

$staff_array = pikaMisc::fetchStaffArray();
$pba_array = pikaMisc::fetchPbAttorneyArray();

// initialize variables
$category = pl_grab_post('category');
$office = pl_grab_post('office');
$user_id = pl_grab_post('user_id');
$pba_id = pl_grab_post('pba_id');
$date_start = pl_grab_post('date_start');
$date_end = pl_grab_post('date_end');
$funding = pl_grab_post('funding'); 
$report_format = pl_grab_post('report_format');
$show_sql = pl_grab_post('show_sql');
$sort_order = pl_grab_post('sort_order');



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

$t->set_title($report_title);




// build the SQL statement, based on user input
$sql = "SELECT act_date, act_time, hours, completed,
				activities.user_id, activities.pba_id, summary,
				cases.number, cases.office, activities.funding 
				FROM activities
				LEFT JOIN cases ON activities.case_id=cases.case_id 
				WHERE 1";

if ($date_start)
{
	$t->add_parameter('Date Start',$date_start);
	$safe_date_start = mysql_real_escape_string(pl_date_mogrify($date_start));
	$sql .= " AND act_date >= '{$safe_date_start}'";
}

if ($date_end)
{
	$t->add_parameter('Date End',$date_end);
	$safe_date_end = mysql_real_escape_string(pl_date_mogrify($date_end));
	$sql .= " AND act_date <= '{$safe_date_end}'";
}

if ($number) {
	$t->add_parameter('Case Number',$number);
	$safe_number = mysql_real_escape_string($number);
	$sql .= " AND number='{$safe_number}'";
}

if ($funding) {
	$t->add_parameter('Funding Code',$funding);
	$safe_funding = mysql_real_escape_string($funding);
	$sql .= " AND activities.funding='{$safe_funding}'";
}

if ($office) {
	$t->add_parameter('Office Code',$office);
	$safe_office = mysql_real_escape_string($office);
	$sql .= " AND office='{$safe_office}'";
}


if ($category)
{
	$t->add_parameter('Categories',$category);
	$sql_category = pl_process_comma_vals($category);
	$sql .= " AND category IN {$sql_category}";
}
$by_pba = false;
if ($user_id)
{
	$t->add_parameter('User',$user_id);
	$safe_user_id = mysql_real_escape_string($user_id);
	$sql .= " AND activities.user_id = '{$safe_user_id}'";
} else if ($pba_id) {
	$by_pba = true;
	$t->add_parameter('PBA',$pba_id);
	$safe_pba_id = mysql_real_escape_string($pba_id);
	$sql .= " AND activities.pba_id = '{$safe_pba_id}'";
}

if ($sort_order == 'ASC') {$sort_order = 'ASC';}
else { $sort_order = 'DESC';}
$t->add_parameter('Sort Order',$sort_order);

$sql .= " ORDER BY 	activities.user_id $sort_order, 
					pba_id $sort_order, 
					act_date $sort_order, 
					act_time $sort_order, 
					act_id $sort_order";


// table object used to display the query results
$cols = array('Date', 'Time', 'Hours', 'Completed', 'Staff', 'Vol. Atty.', 
				'Summary', 'Case Number', 'Office', 'Funding Source');
				
// execute the SQL statement, format the results, and add to the table object
$result = mysql_query($sql) or trigger_error();

while ($row = mysql_fetch_assoc($result)) {
	if(!$by_pba) {$act_user["{$row['user_id']}"][] = $row; }// Searching by Staff Atty
	else {$act_user["{$row['pba_id']}"][] = $row; } // Searching by Pro Bono Atty
}
$first_table = true;
$report_sum = 0;
foreach ($act_user as $uid => $act_rows)
{
	if($first_table) {
		$first_table = false;
	} else {
		$t->add_table();
	}
	$t->set_header($cols);
	$user_name = '';
	if(!$by_pba) {$user_name = pl_array_lookup($uid,$staff_array);}
	else {$user_name = pl_array_lookup($uid,$pba_array);}
	$user_sum = 0;
	foreach ($act_rows as $act) {
		$r = array();
		$r['act_date'] = pl_date_unmogrify($act['act_date']);
		$r['act_time'] = pl_time_unmogrify($act['act_time']);
		$r['hours'] = $act['hours'];
		$r['completed'] = pl_array_lookup($act['completed'],$plMenus['yes_no']);
		$r['user_id'] = pl_array_lookup($act['user_id'],$staff_array);
		$r['pba_id'] = pl_array_lookup($act['pba_id'],$pba_array);
		$r['summary'] = $act['summary'];
		$r['number'] = $act['number'];
		$r['office'] = pl_array_lookup($act['office'],$plMenus['office']);
		$r['funding'] = pl_array_lookup($act['funding'],$plMenus['funding']);
		if ($act['completed'])
		{
			$report_sum += $act['hours'];
			$user_sum += $act['hours'];
		}
		$t->add_row($r);
	}
	
	$t->set_table_title("{$user_name} - $user_sum hours");
}

$t->add_table();
$t->set_table_title('Total Report Hours');
$t->set_header(array('All Hours'));
$t->display_row_count(false);
$t->add_row(array($report_sum));

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
