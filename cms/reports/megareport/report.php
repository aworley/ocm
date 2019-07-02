<?php 

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'Mega Report';
$report_name = 'megareport';

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

$ffield0 = pl_grab_post('ffield0');
$ffield1 = pl_grab_post('ffield1');
$ffield2 = pl_grab_post('ffield2');
$ffield3 = pl_grab_post('ffield3');
$ffield4 = pl_grab_post('ffield4');
$ffield5 = pl_grab_post('ffield5');

$ffield = array($ffield0, $ffield1, $ffield2, $ffield3, $ffield4, $ffield5);

$fcomp0 = pl_grab_post('fcomp0');
$fcomp1 = pl_grab_post('fcomp1');
$fcomp2 = pl_grab_post('fcomp2');
$fcomp3 = pl_grab_post('fcomp3');
$fcomp4 = pl_grab_post('fcomp4');
$fcomp5 = pl_grab_post('fcomp5');

$fcomp = array($fcomp0,$fcomp1,$fcomp2,$fcomp3,$fcomp4,$fcomp5);

foreach ($fcomp as $key => $val)
{
	if ('&lt;' == $val)
	{
		$fcomp[$key] = '<';
	}
	
	else if ('&gt;' == $val)
	{
		$fcomp[$key] = '>';
	}
}

$fvalue0 = pl_grab_post('fvalue0');
$fvalue1 = pl_grab_post('fvalue1');
$fvalue2 = pl_grab_post('fvalue2');
$fvalue3 = pl_grab_post('fvalue3');
$fvalue4 = pl_grab_post('fvalue4');
$fvalue5 = pl_grab_post('fvalue5');

$fvalue = array($fvalue0,$fvalue1,$fvalue2,$fvalue3,$fvalue4,$fvalue5);

$sum = pl_grab_post('sum');
$count = pl_grab_post('count');
$order_by = pl_grab_post('order_by');
$order_by2 = pl_grab_post('order_by2');
$group_by = pl_grab_post('group_by');
$group_by2 = pl_grab_post('group_by2');
$users_list = pl_grab_post('users_list');
$recordlimit = pl_grab_post('recordlimit', 10000);

$fo = pl_grab_post('fo'); // Defines which columns to display, and in what order.
$showfields = '';  // This is basically the SELECT clause for the report query.
// to the SELECT clause, so the commas look correct.
$tables = array();  // Used to store table data type information.  Useful when determining
// which fields have dates or times that need to be mogrified.

$tables['cases'] = pl_table_fields_get('cases');
$tables['contacts'] = pl_table_fields_get('contacts');
$tables['activities'] = pl_table_fields_get('activities');
$report_format = pl_grab_post('report_format');
$show_sql = pl_grab_post('show_sql');



if ('csv' == $report_format)
{
	require_once ('app/lib/plCsvReportTable.php');
	require_once ('app/lib/plCsvReport.php');
	$r = new plCsvReport();
}

else
{
	require_once ('app/lib/plHtmlReportTable.php');
	require_once ('app/lib/plHtmlReport.php');
	$r = new plHtmlReport();
}

// BUILD SELECT CLAUSE
// When calcuating SUMs, only display the sum field, and the group_by field (if specified)
if ($sum && $group_by && $group_by2)
{
	$showfields = "$group_by, $group_by2, SUM($sum) as Sum";
}

else if ($sum && $group_by)
{
	$showfields = "$group_by, SUM($sum) as Sum";
}

else if ($sum)
{
	$showfields = "SUM($sum) as Sum";
}

else if ($count && $group_by && $group_by2)
{
	$showfields = "$group_by, $group_by2, COUNT($count) as Total";
}

else if ($count && $group_by)
{
	$showfields = "$group_by, COUNT($count) as Total";
}

else if ($count)
{
	$showfields = "COUNT($count) as Total";
}

else
{
	if (sizeof($fo) < 1)
	{
		echo "<h1>Error:  you need to check off the fields you want displayed on this report</h1>\n";
		exit();
	}
	
	else 
	{
		$z = implode(', ', $fo);
		$showfields = $z;
	}
}

// Clean $showfields before sending it to MySQL.
$showfields = DB::escapeString($showfields);

if (substr_count($showfields, 'activities.') > 0)
{
	$showfields .= ", activities.act_id AS act_id_deleteme";
	$sql = "SELECT {$showfields} FROM activities
			LEFT JOIN cases ON activities.case_id = cases.case_id 
			LEFT JOIN contacts ON cases.client_id = contacts.contact_id WHERE 1";
}

else 
{
	$sql = "SELECT {$showfields} FROM cases 
			LEFT JOIN contacts ON cases.client_id = contacts.contact_id WHERE 1";
}


// BUILD WHERE CLAUSE
$i = 0;
$special_fields = array('counsel_id', 'pba_id');

while (list($key, $val) = each($ffield))
{
	if ($val)
	{
		$val = DB::escapeString($val);
		list($table_name, $field_name) = explode('.', $val);
		$field_data_type = $tables[$table_name][$field_name];
		
		if (!in_array($val, $special_fields))
		{
			if ('is blank' == $fcomp[$i])
			{
				$sql .= " AND $val IS NULL";
			}
			
			else if ('is not blank' == $fcomp[$i])
			{
				$sql .= " AND $val IS NOT NULL";
			}
			
			else if ('=' == $fcomp[$i])
			{
				// use IN() comparison
				// first add quotes around each comma-separated search item
				$val_array = explode(",", $fvalue[$i]);
				$quoted_vals = '';
				$y = 0;
				foreach($val_array as $x)
				{
					$x = trim($x);
					$x = DB::escapeString($x);
					
					if ($field_data_type == 'date')
					{
						$field_value = pl_date_mogrify($x);
					}
					
					else if ($field_data_type == 'time')
					{
						$field_value = pl_time_mogrify($x);
					}

					else 
					{
						$field_value = $x;
					}
									
					if ($y > 0)
					{
						$quoted_vals .= ',';
					}
					
					$quoted_vals .= "'$field_value'";					
					$y++;
				}
								
				$sql .= " AND $val IN($quoted_vals)";
			}

			else if ('!=' == $fcomp[$i])
			{
				// use IN() comparison
				// first add quotes around each comma-separated search item
				$val_array = explode(",", $fvalue[$i]);
				$quoted_vals = '';
				$y = 0;
				foreach($val_array as $x)
				{
					$x = trim($x);
					$x = DB::escapeString($x);
					
					if ($field_data_type == 'date')
					{
						$field_value = pl_date_mogrify($x);
					}
					
					else if ($field_data_type == 'time')
					{
						$field_value = pl_time_mogrify($x);
					}

					else 
					{
						$field_value = $x;
					}
									
					if ($y > 0)
					{
						$quoted_vals .= ',';
					}
					
					$quoted_vals .= "'$field_value'";					
					$y++;
				}
								
				$sql .= " AND ($val NOT IN($quoted_vals) OR $val IS NULL)";
			}
			
			else if ('LIKE' == $fcomp[$i])
			{
				// use LIKE comparison
				// handle '*' as a wildcard - will only work on string fields
					if ($field_data_type == 'date')
					{
						$field_value = pl_date_mogrify($fvalue[$i]);
					}
					
					else if ($field_data_type == 'time')
					{
						$field_value = pl_time_mogrify($fvalue[$i]);
					}

					else 
					{
						$field_value = $fvalue[$i];
					}
				
				$field_value = str_replace('*', '%', $field_value);
				
				$sql .= " AND $val LIKE '$field_value'";
			}
			
			else if ('between' == $fcomp[$i])
			{
				$val_array = explode(",", $fvalue[$i]);
				$val_array[0] = trim($val_array[0]);
				$val_array[1] = trim($val_array[1]);
				
					if ($field_data_type == 'date')
					{
						$value_a = pl_date_mogrify($val_array[0]);
						$value_b = pl_date_mogrify($val_array[1]);
					}
					
					else if ($field_data_type == 'time')
					{
						$value_a = pl_time_mogrify($val_array[0]);
						$value_b = pl_time_mogrify($val_array[1]);
					}

					else 
					{
						//$field_value = $fvalue[$i];  - What is this???
						$value_a = $val_array[0];
						$value_b = $val_array[1];
					}
				
				$value_a = DB::escapeString($value_a);
				$value_b = DB::escapeString($value_b);
				
				$sql .= " AND ($val >= '$value_a' AND $val <= '$value_b')";
			}
			
			else if ($fvalue[$i])
			{
				$comp = $fcomp[$i];
				
					if ($field_data_type == 'date')
					{
						$field_value = pl_date_mogrify($fvalue[$i]);
					}
					
					else if ($field_data_type == 'time')
					{
						$field_value = pl_time_mogrify($fvalue[$i]);
					}

					else 
					{
						$field_value = $fvalue[$i];
					}
					
					$field_value = DB::escapeString($field_value);
					$comp = DB::escapeString($comp);
				
				$sql .= " AND $val$comp'$field_value'";
			}
		}
		/*
		else if (in_array($val, $special_fields))
		{
			if ('=' == $fcomp[$i])
			{
				// use IN() comparison
				// first add quotes around each comma-separated search item
				$val_array = explode(",", $fvalue[$i]);
				$quoted_vals = '';
				$y = 0;
				foreach($val_array as $x)
				{
					$field_value = _pl_input_filter($x, $plFields['cases'][$val]);
					
					if ($y > 0)
					{
						$quoted_vals .= ',';
					}
					
					$quoted_vals .= "'$field_value'";
					
					$y++;
				}
				
				
				$sql .= " AND (user_id IN($quoted_vals) OR cocounsel1 IN($quoted_vals) OR cocounsel2 IN($quoted_vals))";
			}
			
			else
			{
				// self-destruct if any operation other than '=' is attempted
				$sql .= "AND 0";
			}
		}
		*/
	}
	
	$i++;
}


if (strlen($users_list) > 0)
{
	$users_list = pl_process_comma_vals($users_list);
	//$users_list = substr($users_list, 0, (strlen($users_list) - 1));
	//$users_list = mysql_real_escape_string($users_list);
	
	if (strpos($showfields, 'activities.') === false)
	{
		$sql .= " AND (cases.user_id IN {$users_list} OR cases.cocounsel1 IN {$users_list} OR cases.cocounsel2 IN {$users_list})";
	}
	
	else
	{
		$sql .= " AND activities.user_id IN {$users_list}";
	}
}


// Build ORDER BY clause
if ($order_by)
{
	$order_by = DB::escapeString($order_by);
	$sql .= " ORDER BY $order_by";
	
	if ($order_by2)
	{
		$order_by2 = DB::escapeString($order_by2);
		$sql .= ", $order_by2";
	}
}

// Build GROUP BY clause
if ($group_by)
{
	$group_by = DB::escapeString($group_by);
	$sql .= " GROUP BY $group_by";
	
	if ($group_by2)
	{
		$group_by2 = DB::escapeString($group_by2);
		$sql .= ", $group_by2";
	}
}

// Build LIMIT clause
if (!$recordlimit) {
        $recordlimit = 1000;
}
        $sql .= " LIMIT $recordlimit";

$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());

$r->title = $report_title;
$r->display_row_count(true);

while ($row = DBResult::fetchRow($result))
{
	if (isset($row['open_date']))
	{
		$row['open_date'] = pl_date_unmogrify($row['open_date']);
	}
	
	if (isset($row['close_date']))
	{
		$row['close_date'] = pl_date_unmogrify($row['close_date']);
	}
	
	if (isset($row['act_date']))
	{
		$row['act_date'] = pl_date_unmogrify($row['act_date']);
	}
	
	if (isset($row['act_time']))
	{
		$row['act_time'] = pl_time_unmogrify($row['act_time']);
	}

	if ($report_format != 'csv' && isset($row['number']))
	{
		$url_number = urlencode($row['number']);
		$row['number'] = "<a href=\"{$base_url}/search.php?s={$url_number}\">{$row['number']}</a>";
	}

	if ($report_format != 'csv' && isset($row['act_date']))
	{
		$row['act_date'] = "<a href=\"{$base_url}/activity.php?act_id={$row['act_id_deleteme']}\">{$row['act_date']}</a>";
	}
	
	if ($report_format != 'csv' && isset($row['act_time']))
	{
		$row['act_time'] = "<a href=\"{$base_url}/activity.php?act_id={$row['act_id_deleteme']}\">{$row['act_time']}</a>";
	}
	
	unset($row['act_id_deleteme']);
	$r->set_header(array_keys($row));
	$r->add_row($row);
}

if ($show_sql)
{
	$r->set_sql($sql);
}

$r->display();
exit();

?>
