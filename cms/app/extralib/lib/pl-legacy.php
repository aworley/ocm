<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.net        */
/**********************************/

if (!function_exists('pl_authenticate')) 
{
function pl_authenticate()
{
	echo 'also called';
	return pl_auth_check_http();
}
}

/**
 * @return unknown
 * @param str unknown
 * @desc Replace single quote characters in $str with two single quote characters.  Useful for cleansing data that will be sent to a database.
 */
function pl_double_quotes($str)
{
	return str_replace("'", "''", $str);
}
// Might try using mysql_real_escape_string in lieu of this.


function pl_input_filter($str, $mode)
{
	return pl_clean_form_input($str, $mode);
}

function pl_clean_path_chars($str)
{
	return pl_clean_file_name($str);
}

function pl_text($column, $value='', $ti='', $size='12', $max_length='25')
{
	global $plTabindex;
	
	if(!$ti)
	$ti = $plTabindex++;
	
	return "<input type=\"text\" name=\"$column\" value=\"$value\" size=$size tabindex=$ti>\n";
}


function pl_hidden($column, $value)
{
	return "<input type=hidden name=$column value=\"$value\">\n";
}


function pl_submit($op, $label, $ti='')
{
	global $plTabindex;
	
	if(!$ti)
	$ti = $plTabindex++;
	
	return "<input type=submit name=\"$op\" value=\"$label\" tabindex=\"$ti\">\n";
}


function pl_info($message)
{
	$C = '';
	
	$C .= '<p valign=middle><img width=10 height=10 src="images/info.gif" alt="info"> ';
	$C .= $message;
	$C .= "</p>\n";
	
	return $C;
}

function pl_warning($x)
{
	return pika_html_red_flag($x);
}

function _pl_input_filter($str, $mode)
{
	return pl_input_filter($str, $mode);
}

function pl_menu_init($menu_name)
{
	pl_menu_get($menu_name);
	
	return true;
}

if(!function_exists('pl_query'))
{
function pl_query($sql)
{
	static $db = null;
	$plSettings = pl_settings_get_all();
	
	// Initialize the database connection, if necessary
	if (!is_object($db))
	{
		require_once("DB.php");
		$db_cx = "{$plSettings['db_type']}://{$plSettings['db_user']}:{$plSettings['db_password']}@{$plSettings['db_host']}/{$plSettings['db_name']}";
		
		$db = DB::connect($db_cx, 0);
		
		if (DB::isError($db))
		{
			$m = $db->getMessage() . "<br>Database Name:  {$db->dsn}" .
			"Database Vendor:  {$db->phptype}";
			trigger_error($m);
		}
		
		$db->fetchmode = DB_FETCHMODE_ASSOC;
	}
	
	$result = $db->query($sql);
	
	if (DB::isError($result))
	{
		if (defined('PL_DIE_ON_QUERY_ERROR') && PL_DIE_ON_QUERY_ERROR == false)
		{
			return null;
		}
		
		else 
		{
			die(pika_error_notice($result->getMessage(), $result->getDebugInfo()));
		}
	}
	
	//echo $sql . "<br>\n";
	
	return $result;
}
}

function pl_new_id($sequence)
{
	return pl_db_new_id($sequence);
}

function pl_save_settings()
{
	return pl_settings_set();
}

function pl_array_menu($a, $field_name, $field_value, $add_blank='1', $ti='1')
{
	return pl_html_menu($a, $field_name, $field_value, $add_blank, $ti);
}

function pl_format_address($data)
{
	return pl_html_address($data);
}

function pl_format_text($data)
{
	return pl_html_text($data);
}

function pl_format_name($data)
{
	return pl_text_name($data);
}

function pl_format_phone($data)
{
	return pl_text_phone($data);
}


function pl_multiselect($a, $field_name, $field_values, $ti='1')
{
	return pl_html_multiselect($a, $field_name, $field_values, $ti);
}


// TODO - deprecate
/**
 * @return unknown
 * @param menu_table_name unknown
 * @param key = 'value' unknown
 * @param val = 'label' unknown
 * @desc Enter description here...
 */
function pl_table_array($menu_table_name, $key='value', $val='label')
{
	global $plMenus;

	return pl_table_to_array('menu_' . $menu_table_name, $key, $val);
}


function pl_table_reset_all()
{
	// May want to make these static at some future point
	global $plFields, $plDvs;
	
	$tables = array('activities', 'cases', 'conflict', 'contacts');
	
	foreach ($tables as $table_name)
	{
		pl_table_init($table_name);
	}
	
	/*
	echo '<pre>';
	echo pl_array_to_php('plFields', $plFields);
	echo pl_array_to_php('plDvs', $plDvs);
	*/
	
	return true;
}

function pl_mogrify_date($date)
{
	return pl_date_mogrify($date);
}

function pl_mogrify_time($date)
{
	return pl_time_mogrify($date);
}


function pl_unmogrify_date($date)
{
	return pl_date_unmogrify($date);
}

function pl_unmogrify_time($date)
{
	return pl_time_unmogrify($date);
}

function pl_scrub_sql_data($str)
{
	$str = pl_double_quotes($str);
	$str = str_replace('\\', '\\\\', $str);
	
	return $str;
}


/*
Hmm... don't like having the js as a default behavior - what if user has js
disabled?  they don't get the warning.

function pl_warning($message)
{
return "<script language=\"javascript\" type=\"text/javascript\"> alert('$message'); </script>";
}

*/


/**
 * @return array
 * @param menu_table_name string
 * @param key = 'value' string
 * @param val = 'label' string
 * @param ord = 'menu_order' string
 * @desc Utility function used by pl_menu to load menu tables into an array.
 */
function pl_table_to_array($menu_table_name, $key='value', $val='label', $ord='menu_order')
{
	$data = array();
	
	$sql = "SELECT $key, $val FROM $menu_table_name ORDER BY $ord";

	$x = pl_db_cache_get($sql, $menu_table_name);

	foreach ($x as $y)
	{
		$data[$y['value']] = $y['label'];
	}
	
	return $data;
}



function pl_menu_add_item($menu, $value, $label, $menu_order)
{
	return pl_query("INSERT INTO menu_$menu SET value='$value', label='$label', menu_order='$menu_order'");
}


function pl_menu_empty($menu)
{
	pl_query_cached_rm("menu_$menu");
	return pl_query("DELETE FROM menu_$menu");
}

function pl_menu_register($menu_table_name, $key='value', $val='label', $ord='menu_order')
{
	static $plMenuDefs;
	
	if (!is_array($plMenuDefs))
	{
		$plMenuDefs = array();
	}
	
	$plMenuDefs[$menu_table_name] = array('key' => $key, 'val' => $val, 'ord' => $ord);
	
	return true;
}


function pl_build_sql($action, $table, $data)
{
	global $plFields;
	
	switch ($action)
	{
		case 'UPDATE':
		return pl_table_autosql_update($table, $data);
		break;
		
		
		case 'INSERT':
		return pl_table_autosql_insert($table, $data);
		break;
		
		
		default:
		
		die(pika_error_notice('Pika is sick', 'invalid build_sql action'));
		break;
	}
}

function pl_result_to_array($result)
{
	$a = array();
	
	while ($row = $result->fetchRow())
	{
		$a[] = $row;
	}
	
	return $a;
}

function pl_begin_form($action, $name='form1')
{
	return "<form action=$action method=POST name=$name>\n";
}


function pl_end_form()
{
	return "</form>\n";
}


function pl_date($column, $value, $ti='')
{
	global $plTabindex;
	$C = '';
	
	if (!$ti)
	{
		$ti = $plTabindex++;
	}
	
	$C .= "<input type=text name=$column value=\"$value\"	size=10 tabindex=$ti>\n";
	
	return $C;
}

function pl_table_menu($menu_name, $field_name, $val=null, $show_blank=1)
{
	$menu_name = str_replace('menu_', '', $menu_name);
	$menu = pl_menu_get($menu_name);
	return pl_html_menu($menu, $field_name, $val, $show_blank);
}

function clean_path_chars($str)
{
	return pl_clean_file_name($str);
}


function pl_auth_check_cookie()
{
	return pl_auth_verify_cookie();
}

function pl_auth_check_http()
{
	echo 'i was called';
	return pl_auth_verify_http();
}

if (!function_exists('pl_fetch_base_uri'))
{
	// returns the base uri of the this system
	function pl_fetch_base_uri()
	{
		global $plSettings;
		
		// build re-direct URI
		if ('on' == $_SERVER['HTTPS'])
		{
			$protocol = 'https';
		}
		else
		{
			$protocol = 'http';
		}
		
		$hostname = $_SERVER['HTTP_HOST']; // $_SERVER['SERVER_NAME'];
		
		/*	be sure to handle situations where you're playing URI tricks,
		for example: "/pikacms/docgen.php/file.rtf"
		*/
		$base_path = $plSettings['base_uri'];
		
		return "$protocol://$hostname$base_path";
	}
}

if(!function_exists('pl_db_cache_get'))
{
	function pl_db_cache_get($sql, $key_deprecated= null)
	{
		$a = array();
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		
		while ($row = mysql_fetch_assoc($result))
		{
			$a[] = $row;
		}
		
		return($a);
	}
}

if(!function_exists('pl_grab_vars'))
{
function pl_grab_vars($table, $append='')
{
	global $plFields;
	$r = array();
	
	pl_table_init($table);
	
	// check for valid table name
	if (!$plFields["$table"])
	{
		die(pika_error_notice('Pika is sick', "pl_grab_vars() could not find database table \"$table\""));
	}
	
	$fl = $plFields["$table"];
	
	reset($fl);
	while (list($key, $val) = each($fl))
	{
		if (isset($_POST["{$key}{$append}"]))
		{
			$r["$key"] = pl_clean_form_input($_POST["{$key}{$append}"], $val);
		}
		
		else if (isset($_GET["{$key}{$append}"]))
		{
			$r["$key"] = pl_clean_form_input($_GET["{$key}{$append}"], $val);
		}
	}
	
	return $r;
}
}


function pl_db_new_id($sequence)
{	
	mysql_query("LOCK TABLES counters WRITE");
	mysql_query("UPDATE counters SET count=count+1 WHERE id = '$sequence' LIMIT 1");
	$result = mysql_query("SELECT count FROM counters WHERE id = '$sequence'")
		or trigger_error("The  \"$sequence\" counter is not working correctly.");
	mysql_query("UNLOCK TABLES");
	$row = mysql_fetch_assoc($result);
	return $row['count'];
}


function pl_table_autosql_update($table, $data)
{
	global $plFields;
	
	$primary_key = '';
	
	pl_table_init($table);
	reset($plFields[$table]);
	reset($data);
	
	// check for valid $data array
	if (!is_array($data))
	{
		die(pika_error_notice('Pika is sick', 'Invalid data array supplied to pl_build_sql'));
	}
	
	
	$sql = "UPDATE $table SET";
	
	$i = 0;
	while (list($key, $val) = each($plFields["$table"]))
	{
		if ('primary_key' == $val)
		{
			// if the primary key is not specified in $data, exit with an error
			if (!$data[$key])
			{
				die(pika_error_notice('Pika is sick', "Value for primary key '$key' is missing"));
			}
			
			// don't have to handle multiple keys right now (otherwise this would be an array...
			$primary_key = $key;
		}
		
		// make sure the data's column name is valid
		else if (array_key_exists($key, $data))
		{
			// need commas to separate each key/value pair
			if ($i != 0)
			{
				$sql .= ', ';
			}
			
			/*	Any field that is NULL, or any zero-length string,
				should be assigned the value NULL.  
				
				We don't want zero-length strings in the database, because 
				they screw up "WHERE x IS NULL" SQL statements.  Additionally,
				a numeric field cannot be assigned a zero length string in MySQL;
				it will instead assume the value 0.
			
				This may be obvious, but note that, in cases where a blank text 
				string is desired, but NOT NULL is specified for that column, you'll
				need to add a whitespace character to the field before passing the 
				data on to pl_build_sql().
			*/
			if ((is_null($data["$key"]) && '0' != $data["$key"]) 
				|| ($data["$key"] == '' && $data[$key] != '0')
				|| (strlen($data["$key"]) == 0))
			{
				// for some reason this screws up number fields with menus...
				/* if ('text' == $val)
				$sql .= ' ' . $key . '=\'\'';
				
				else  */
				$sql .= ' ' . $key . ' = NULL';
			}
			
			//else if ('NULL' == $data[$key] && 'number' == $val)
			else if (is_null($data[$key]) && 'number' == $val)
			{
				/*	pl_input_filters() will set fields = (string) 'NULL' if
				the actual value is a null numeric field
				*/
				$sql .= " {$key} = NULL";
			}
			
			else
			{
				$w = mysql_real_escape_string($data["$key"]);
				$sql .= " $key = '$w'";
			}
			// hopefully this works with other DBs besides MySQL & Postgres...
			
			$i++;
		}
	}
	
	$sql .= " WHERE $primary_key='" . $data["$primary_key"] . "'";
	
	$sql .= ' LIMIT 1';

	return $sql;
}

function pl_table_autosql_insert($table, $data)
{
	global $plFields;
	
	$primary_key = '';
	
	pl_table_init($table);
	reset($plFields[$table]);
	reset($data);
	
	// check for valid $data array
	if (!is_array($data))
	{
		die(pika_error_notice('Pika is sick', 'Invalid data array supplied to pl_build_sql'));
	}
	
	
	$sql = "INSERT $table SET";
	
	$i = 0;
	while (list($key, $val) = each($plFields["$table"]))
	{
		if (isset($data["$key"]))
		{
			// need commas to separate each key/value pair
			if ($i != 0)
			{
				$sql .= ', ';
			}
			
			/*	Any field that is NULL, or any zero-length string,
				should be assigned the value NULL.  
				
				We don't want zero-length strings in the database, because 
				they screw up "WHERE x IS NULL" SQL statements.  Additionally,
				a numeric field cannot be assigned a zero length string in MySQL;
				it will instead assume the value 0.
			
				This may be obvious, but note that, in cases where a blank text 
				string is desired, but NOT NULL is specified for that column, you'll
				need to add a whitespace character to the field before passing the 
				data on to pl_build_sql().
			*/
			if ((is_null($data["$key"]) && '0' != $data["$key"]) 
				|| ($data["$key"] == '' && $data[$key] != '0')
				|| (strlen($data["$key"]) == 0))
			{
				$sql .= ' ' . $key . ' = NULL';
			}

			
			else if ('NULL' == $data[$key] && 'number' == $val)
			{
				/*	pl_input_filters() will set fields = (string) 'NULL' if
				the actual value is a null numeric field
				*/
				$sql .= " {$key}=NULL";
			}
			
			else
			{
				$sql .= " $key='" . mysql_real_escape_string($data["$key"]) . "'";
			}
			
			$i++;
		}
	}
	
	return $sql;
}

$plFields = array();
$plDvs = array();

function pl_table_init($table_name)
{
	global $plFields;
	global $plDvs;
	
	if (!array_key_exists($table_name, $plFields) || !array_key_exists($table_name, $plDvs))
	{
		$plFields[$table_name] = array();
		$plDvs[$table_name] = array();
		
		// $result will be an array
		$result = pl_db_cache_get("DESCRIBE $table_name");
		
		foreach ($result as $row)
		{
			if ($row['Key'] == 'PRI')
			{
				$plFields[$table_name][$row['Field']] = 'primary_key';
			}
			
			else
			{
				$a = explode('(', $row['Type']);
				
				switch ($a[0])
				{
					case 'int':
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'decimal':
					
					$plFields[$table_name][$row['Field']] = 'number';
					
					break;
					
					
					case 'char':
					case 'varchar':
					case 'text':
					case 'tinytext':
					case 'timestamp':
					
					$plFields[$table_name][$row['Field']] = 'text';
					
					break;
					
					
					case 'date':
					
					$plFields[$table_name][$row['Field']] = 'date';
					
					break;
					
					
					case 'time':
					
					$plFields[$table_name][$row['Field']] = 'time';
					
					break;
				}
			}
			
			if (!is_null($row['Default']) && '' != $row['Default'] && 'PRI' != $row['Key'])
			{
				$plDvs[$table_name][$row['Field']] = $row['Default'];
			}
		}
	}
}


function pl_table_defaults_get($table_name)
{
	global $plDvs;
	pl_table_init($table_name);
	return $plDvs[$table_name];
}
?>
