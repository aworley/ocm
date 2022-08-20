<?php
// 2012 point release modifications to accomodate null om_codes 
chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

function lsc_problem_category($problem_code = null) {
	$label = '';
	$lsc_categories = array('Consumer/Finance', 
							'Education',
							'Employment',
							'Family',
							'Juvenile',
							'Health',
							'Housing',
							'Income Maintenance',
							'Individual Rights',
							'Miscellaneous');
	if(is_null($problem_code) || !$problem_code) 
	{
		// Blank pcode's
		$label = "No Problem Code Entered";
	} 
	
	else if(strlen($problem_code) == 1) 
	{
		// Deals with single char/int strings for 1-9 pcodes's
		$label = pl_array_lookup('0',$lsc_categories);	
	} 
	
	else if(strlen($problem_code) == 2)
	{
		// Substring of 2 character problem code 
		$label = pl_array_lookup(substr($problem_code,0,1),$lsc_categories);
	}
	
	if(!$label) 
	{
		// Assuming all else fails toss into unknown pile
		$label = "Unknown Code - " . $problem_code;
	}
	
	return $label;
}

$report_title = 'LSC Other Services Report (Form M)';
$report_name = "lsc_services";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) 
{
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
$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$county = pl_grab_post('county');
$show_sql = pl_grab_post('show_sql');

pl_menu_get('lsc_other_services');
pl_menu_get('problem_2007');
pl_menu_get('problem_2008');

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

$extra_sql = '';

/*
$x = pl_process_comma_vals($funding);
if ($x != false)
{
	$extra_sql .= " AND funding IN $x";
}

$x = pl_process_comma_vals($office);
if ($x != false)
{
	$extra_sql .= " AND office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false)
{
	$extra_sql .= " AND status IN $x";
}

$x = pl_process_comma_vals($county);
if ($x != false)
{
	$extra_sql .= " AND case_county IN $x";
}
*/


if($close_date_begin && $close_date_end) 
{
	$safe_close_date_begin = pl_date_mogrify(DB::escapeString($close_date_begin));
	$safe_close_date_end = pl_date_mogrify(DB::escapeString($close_date_end));
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$extra_sql .= " AND act_date >= '{$safe_close_date_begin}'" .
				  " AND act_date <= '{$safe_close_date_end}'";
}

// Run this report's SQLs

$models = array();
$sql_models_used = "SELECT om_code, SUM(ph_measured) AS model_measured, SUM(ph_estimated) AS model_estimated
					FROM activities 
					WHERE 1 {$extra_sql} 
					GROUP BY om_code
					ORDER BY om_code ASC";
$result = DB::query($sql_models_used) or trigger_error();

while ($row = DBResult::fetchRow($result))
{
	$models[$row['om_code']] = array('model_measured' => $row['model_measured'],
									'model_estimated' => $row['model_estimated']);
}

// Table 1:  Community Education
$t->title = $report_title;
$t->display_row_count(false);
$t->set_table_title('Table 1: Community Legal Education');
$t->set_header(array('Models Used', '(a) Measured', '(b) Estimated', '(c) Total'));
$total_estimated = $total_measured = 0;

// Tally total assistance for all Community Legal Education codes (100-109)
// and add to the bottom line of the table.
for($i=100;$i<110;$i++) 
{
	if(isset($models[$i])) 
	{	
		$r = array();
		$label = pl_array_lookup($i,$plMenus['lsc_other_services']);	
		$r[] = $label;
		if($models[$i]['model_measured'])
		{
			$r[] = $models[$i]['model_measured'];
		}
		else
		{
			$r[] = 0;
		}	
		if($models[$i]['model_estimated'])
		{
			$r[] = $models[$i]['model_estimated'];
		}
		else
		{
			$r[] = 0;
		}
		$r[] = $models[$i]['model_measured'] + $models[$i]['model_estimated'];
		$t->add_row($r);
		$total_measured += $models[$i]['model_measured'];
		$total_estimated += $models[$i]['model_estimated'];
	}
}

$r = array('Totals',$total_measured,$total_estimated,$total_measured+$total_estimated);
$t->add_row($r);

if($show_sql) 
{
	$t->set_sql($sql_models_used);
}

// Table 2:  Pro Se Assistance
$t->add_table();
$t->display_row_count(false);
$t->set_table_title('Table 2: Pro Se Assistance, not included in case service statistics');
$t->set_header(array('Models Used', '(a) Measured', '(b) Estimated', '(c) Total'));
$total_estimated = $total_measured = 0;

for($i=110;$i<120;$i++) 
{
	if(isset($models[$i])) 
	{
		$r = array();
		$label = pl_array_lookup($i,$plMenus['lsc_other_services']);
		$r[] = $label;
		$r[] = $models[$i]['model_measured'];
		$r[] = $models[$i]['model_estimated'];
		$r[] = $models[$i]['model_measured'] + $models[$i]['model_estimated'];
		$t->add_row($r);
		$total_measured += $models[$i]['model_measured'];
		$total_estimated += $models[$i]['model_estimated'];
	}
}

$r = array('Totals',$total_measured,$total_estimated,$total_measured+$total_estimated);
$t->add_row($r);

if($show_sql) 
{
	$t->set_sql($sql_models_used);
}

// Table 3: Referred (om_codes 120 - 129)
$t->add_table();
$t->display_row_count(false);
$t->set_table_title('Table 3: Referred, not included in case service statistics');
$t->set_header(array('Models Used', '(a) Measured', '(b) Estimated', '(c) Total'));
$total_estimated = $total_measured = 0;

for($i=120;$i<130;$i++) 
{
	if(isset($models[$i])) 
	{
		$r = array();
		$label = pl_array_lookup($i,$plMenus['lsc_other_services']);
		$r[] = $label;
		$r[] = $models[$i]['model_measured'];
		$r[] = $models[$i]['model_estimated'];
		$r[] = $models[$i]['model_measured'] + $models[$i]['model_estimated'];
		$t->add_row($r);
		$total_measured += $models[$i]['model_measured'];
		$total_estimated += $models[$i]['model_estimated'];
	}
}
$r = array('Totals',$total_measured,$total_estimated,$total_measured+$total_estimated);
$t->add_row($r);

if($show_sql) 
{
	$t->set_sql($sql_models_used);
}

// 2012 point release change to account for NULL om_codes of type L
$null_value = "Left Blank";
$nulls_found = false;
$extra_sql_missing = " AND act_type = 'L' AND om_code IS NULL";
$extra_sql_missing .= $extra_sql;

$sql_models_missing = "SELECT act_id, act_date, ph_measured, ph_estimated
					FROM activities 
					WHERE 1	{$extra_sql_missing} 
					ORDER BY act_date ASC";
					
$result_missing = DB::query($sql_models_missing) or trigger_error();
// load associative array
while ($row = DBResult::fetchRow($result_missing))
{
	$blank_models[$row['act_id']] = $row;
	$nulls_found = true;				
}

// Only display table if nulls found 
if($nulls_found)
{
	$t->add_table();
	$t->display_row_count(false);
	$t->set_table_title("Service Provided Not Selected (Left Blank)");
	$t->set_header(array('Date ', '(a) Measured', '(b) Estimated'));
	
	foreach ($blank_models as $blanks)
	{
		$r = array();
		$label = pl_date_unmogrify($blanks['act_date']);
		$r['act_date'] = "<a href=\"{$base_url}/activity.php?act_id={$blanks['act_id']}\" target=\"_blank\">{$label}</a>";
		$r['ph_measured'] = $blanks['ph_measured'];
		$r['ph_estimated'] = $blanks['ph_estimated'];
		
		$t->add_row($r);
	}
	
	if($show_sql) 
	{
		$t->set_sql($sql_models_missing);
	}
}
// end of accounting for NULL value

$t->display();
exit();

?>
