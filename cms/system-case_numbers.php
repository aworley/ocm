<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once('pika-danio.php');

pika_init();

$a = array();
$a['base_url'] = $base_url = pl_settings_get('base_url');


// SECURITY
if (!pika_authorize('system', array()))
{
	$main_html = array();
	$main_html["page_title"] = 'Case Numbering';
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt;
						<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
	 					Case Numbering";
	$main_html["content"] = 'Access Denied';

	$buffer = pl_template('templates/default.html',$main_html);
	pika_exit($buffer);
}

// VARIABLES

$action = pl_grab_post('action');
$case_number = pl_grab_post('case_number');


if($action == 'update') {
	if($case_number >= 0 && is_numeric($case_number)) {
		
		$safe_case_number = DB::escapeString($case_number);
		DB::query("LOCK TABLES counters WRITE") or trigger_error('counters table lock failed');
		DB::query("UPDATE counters SET count='{$safe_case_number}' WHERE id='case_number' LIMIT 1");
		DB::query("UNLOCK TABLES") or trigger_error('error');
	}
}

$current_year = date('Y');
$sql = "SELECT COUNT(number) AS nbr
		FROM cases
		WHERE 1 
		AND open_date >= '{$current_year}-01-01' 
		AND open_date <= '{$current_year}-12-31'";
$result = DB::query($sql);
$row = DBResult::fetchRow($result);
$a['cases_ytd'] = $row['nbr'];

$sql = "SELECT count FROM counters WHERE id = 'case_number' LIMIT 1";
$result = DB::query($sql);
$row = DBResult::fetchRow($result);
$a['new_number'] = $row['count'] + 1;
$a['new_number_ex'] = "X-" . date('y') . "-" . str_pad(sprintf("%s", ($row['count'] + 1)), 5, '0', STR_PAD_LEFT);
$a['case_number'] = $row['count'];



$main_html = array();
$main_html["page_title"] = 'Case Numbering';
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> 
					&gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> 
					&gt; Case Numbering";
$main_html["content"] = pl_template('subtemplates/system-case_numbers.html', $a);


$buffer = pl_template('templates/default.html', $main_html);
pika_exit($buffer);

?>
