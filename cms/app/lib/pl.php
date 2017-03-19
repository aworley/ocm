<?php

/**
* Pika Library of handy PHP functions.
*
* PL provides following functionality:
* user authentication via cookies (HTTP Basic),
* appliction settings management,
* benchmarking, 
* XHTML output validation, 
* time/date conversion
* functions for standardized error reporting, 
* HTML templating w/auto-menuing
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

// GLOBAL VARIABLES
/* This is used in some legacy files that use this library.
Initialize it here so any value from HTTP_GET[POST]_VARS is overwritten.
*/
$plMenus = array();

// Singleton - Global replacement
// Needed to prevent Bug #35634 in php < 5.2.11
// Nesting error 
require_once('pikaWarning.php');


/**
* @return string
* @param $element_name string
* @param $array_data array
* @desc Safely retrive an array element by key without generating a warning on missing keys
*/
function pl_array_lookup($element_name, $array_data)
{
	if (!is_null($element_name)
	&& is_array($array_data)
	&& array_key_exists($element_name, $array_data))
	{
		return $array_data[$element_name];
	}
	
	else
	{
		return $element_name;
	}
}


/*
* @return string
* @param $a array
* @desc Convert a 2-d array to a CSV-format string.
*/
function pl_array_to_csv($a)
{
	$csv = '';
	
	if (!is_array($a))
	{
		return false;
	}
	
	foreach ($a as $row)
	{
		if (!is_array($row))
		{
			return false;
		}
		
		$csv .= '"' . implode('","', $row) . '"';
	}
	
	return $csv;
}


/**
* @return string
* @param $array_name string
* @param $array_data array
* @desc Produce the PHP code to initialize an array.
*/
function pl_array_to_php($array_name, $array_data)
{
	$x = "";
	
	$x .= "\$$array_name = ";
	$x .= pl_array_to_php_sub($array_data);
	$x .= ";\n\n";
	
	return $x;
}

// helper function for pl_array_to_php()
/**
* @return string
* @param $array_data array
* @desc Subroutine used by pl_array_to_php()
*/
function pl_array_to_php_sub($array_data)
{
	$x = "";
	
	$x .= "array (\n";
	
	foreach ($array_data as $key => $val)
	{
		if (is_string($key))
		{
			$key = "'$key'";
		}
		
		if (is_string($val))
		{
			$val = stripslashes($val);
			$val = str_replace("'", "\'", $val);
			
			$val = "'$val'";
		}
		
		elseif (is_array($val))
		{
			$val = pl_array_to_php_sub($val);
		}
		
		else if (is_null($val))
		{
			$val = "'$val'";
		}
		
		$x .= "$key => $val,\n";
	}
	
	$x .= ")";
	
	return $x;
}



/**
 * function authenticate()
 * @desc Determines user login status and if not logged 
 * in displays login screen (form based login w/ sessions)
 * @return void;
 */
function authenticate()
{
	$user = $pass = null;
	if(isset($_REQUEST['login_user']))
	{
		$user = $_REQUEST['login_user'];
	}
	if(isset($_REQUEST['login_pass']))
	{
		$pass = $_REQUEST['login_pass'];
	}
	
	
	require_once('app/lib/pikaAuthDb.php');
	$auth = pikaAuth::getInstance();
	$authdb = new pikaAuthDb('users','username','password','md5');
	
	$display_login = false;
	if(!$auth->authenticate($user,$pass,$authdb)) 
	{
		$display_login = true;
	}
	else 
	{
		$auth_row = $auth->getAuthRow();
		if ($auth_row['password_expire'] != 0 && $auth_row['password_expire'] < time())
		{
			$auth->setMessage('105','Your password has expired, please contact your administrator to reset your password',__FILE__,__LINE__);
			$display_login = true;
		}
	}
	
	if($display_login && defined('PL_DISABLE_DISPLAY_LOGIN'))
	{
		exit();
	}
	
		
	if($display_login)
	{
		
		$html = array();
		require_once('app/lib/pikaTempLib.php');
		$form_data = array();
		if(isset($_SERVER['REQUEST_METHOD']) && strlen($_SERVER['REQUEST_METHOD']) != 'GET')
		{
			$form_data = $_POST;
		}
		$html['form_data'] = '';
		$reserved_names = array('login_user','login_pass','auth_id','signin');
		foreach ($form_data as $name => $value)
		{
			if(!in_array($name,$reserved_names))
			{
				$html['form_data'] .= pikaTempLib::plugin('input_hidden',$name,$value);
			}
		}
		$html['messages'] = '';
		foreach ($auth->getMessages() as $auth_message)
		{
			$html['messages'] .= $auth_message[1] . "<br/>\n"; 
		}
		$html['auth_id'] = $_SESSION['auth_id'];
		$default_template = new pikaTempLib('templates/login-form.html',$html);
		if(browser_is_mobile())
		{
			$default_template = new pikaTempLib('m/login-form.html',$html);
		}
		$buffer = $default_template->draw();
		
		echo $buffer;
		exit();
		
	}
}


/**
 * function authenticate_http()
 * @desc Verify that the user is authenticated via HTTP;
 * exit and prompt for login if she is not.
 * @return void;
 */
function authenticate_http()
{
	require_once('app/lib/pikaAuthHttp.php');
	require_once('app/lib/pikaAuthDb.php');
	$auth = pikaAuthHttp::getInstance();
	$authdb = new pikaAuthDb('users','username','password','md5');
	$auth->authenticate($authdb);
}


/**
* @return string
* @param $cmd string
* @desc Will gather and display benchmarking, HTML validation, file size info, when enabled.
*/
function pl_bench($cmd)
{
	// This is not the greatest design for a function ever.
	global $plTemplate, $plUserId;
	static $start_time;
	$C = '';
	$pl_settings = pl_settings_get_all();
	
	if ($pl_settings['enable_benchmark'] != true)
	{
		return '';
	}
	
	switch ($cmd)
	{
		case 'start':
		
		if (true == $pl_settings['enable_benchmark'])
		{
			list($usec, $sec) = explode(" ",microtime());
			$start_time = ((float)$usec + (float)$sec);
		}
		
		break;
		
		case 'results':
		
		if (true == $pl_settings['enable_benchmark'])
		{
			list($usec, $sec) = explode(" ",microtime());
			$stop_time = ((float)$usec + (float)$sec);
		}
		
		// yes, this gets run a second time here.  not a big deal.
		$buffer = pl_template($plTemplate, 'templates/default.html');
		
		if (true == $pl_settings['enable_benchmark'])
		{
			// 2013-04-12 AMW - Removed "thinborder" CSS class, looks better on pages without errors.
			$C .= '<br><table align=center width=500><tr><td><pre>';
			$C .= 'Execution Speed:  ' . round($stop_time - $start_time, 2) . ' seconds<br>';
			
			
			$C .= 'File Size:  ' . round(strlen($buffer) / 1024) . 'K<br>';
			
			$C .= '</pre></td></tr></table>';
		}

		// 2013-08-08 AMW - Removed all HTML validation code due to lack of HTML5 support in tidy.
		break;
	}
	
	return $C;
}


/**
 * Calculate the script execution time.
 * @return float
*/
function pl_benchmark()
{
	static $start_time = 0;
	
	if (0 == $start_time) 
	{
		list($usec, $sec) = explode(" ",microtime());
		$start_time = ((float)$usec + (float)$sec);
		$timer = 0;
	}
	
	else 
	{
		list($usec, $sec) = explode(" ",microtime());
		$timer = ((float)$usec + (float)$sec) - $start_time;
		// Reset start_time in case pl_benchmark() is called again.
		$start_time = ((float)$usec + (float)$sec);
	}
	
	return round($timer, 2);
}


/**
* @return int
* @param $dob date
* @param $current_date date
* @desc Calculate a person's age in years.
*/
function pl_calc_age($dob, $current_date = null)
{
	if (is_null($current_date))
	{
		$current_date = date("Y-m-d");
	}
	
	list($iYear, $iMonth, $iDay) = explode('-', $dob);
	list($nYear, $nMonth, $nDay) = explode('-', $current_date);
	
	$baseyear = $nYear - $iYear - 1;
	
	if ($iMonth < $nMonth || ($iMonth == $nMonth && $iDay <= $nDay))
	{
		$baseyear++;
	}
	
	return $baseyear;
}


/**
* @return string
* @param $str string
* @desc Removes naughty characters (";", ".." and "/") from a user-supplied file name.
*/
function pl_clean_file_name($str)
{
	$str = preg_replace("/\;/", "", $str);
	$str = preg_replace("/\//", "", $str);
	$str = preg_replace("/\.\./", "", $str);
	
	return $str;
}


/**
* @return string
* @param $str string
* @desc Removes naughty characters (";" and "..") from a user-supplied file path.
*/
function pl_clean_file_path($str)
{
	$str = preg_replace("/\;/", "", $str);	
	$str = preg_replace("/\.\./", "", $str);	
	return $str;
}


/**
* @return string
* @param $str string
* @param $mode string
* @desc Used by pl_grab_[post/get]() functions to cleanse incoming user-submitted form data.
*/
function pl_clean_form_input($form_str, $mode = 'nomode')
{
	static $magic_quotes_on = null;
	/*	This is split off into a standalone function since it's used in both
		pl_grab_var() and pl_grab_vars().  Probably shouldn't need to be invoked
		in any other cases.
		
		TODO - revamp to work better with new, simpler pl_table arrays,
		recursive for arrays
		TODO - return an error code.
	*/
	
	if (is_null($magic_quotes_on))
	{
		$magic_quotes_on = get_magic_quotes_gpc();
	}
	
	if (is_array($form_str))
	{
		$str = array();
		
		foreach ($form_str as $key => $val)
		{
			$str[$key] = pl_clean_form_input($val);
		}
		
		return $str;
	}

	else if (strlen($form_str) < 1)
	{
		return $form_str;
	}

	
	$str = $form_str;  // The "edited" version of the user-submitted string.

	// Get rid of any whitespace at beginning or end.
	$str = trim($str);
	
	if ($magic_quotes_on)
	{
		/*
		Examples:
		O\'Brian becomes O'Brian
		C:\\autoexec.bat is unaltered
		*/
		/* 	PHP tends to add slashes to strings.  Get rid of them,
		quote chars and such will be handled in pl_build_sql
		*/
		$str = stripslashes($str);
		/*	if there were slashes in the original string, before PHP escaped
		all slashes with a second slash, change these back to double
		slashes, since single slashes can cause problems in queries
		*/
		$str = str_replace('\\', '\\\\', $str);
	}
	
	// Perform any mode-specific transformations.
	switch ($mode)
	{
		case 'number':
		/*	This can be used to prevent non-numeric values from being assigned to
			numeric columns in MySQL.  They would be saved as '0', which makes it 
			impossible to discern actual zero values from improperly inputted values.  
			Save non-numerics as NULL instead.
		*/
		if (!is_null($str) && !is_numeric($str))
		{
			$str = null;
		}
		
		else if (is_null($str))
		{
			$str = null;
		}
		
		break;
		
				
		case 'date':
		$str = pl_date_mogrify($str);
		
		// Check for invalid dates.
		$a = explode('-', $str);
		if (sizeof($a) != 3)
		{
			$str = '';
		}
		
		else if (!checkdate($a[1], $a[2], $a[0]))
		{
			$str = '';
		}
		
		// Don't allow dates too far back or into the future.
		else if ($a[0] < 1800 || $a[0] > 2099)
		{
			$str = '';
		}
		
		break;
		
		
		case 'time':	
		$str = pl_time_mogrify($str);
		
		break;
		
		
		// Legacy.
		case 'boolean':
		// TRUE or FALSE, no NULLs allowed
		if (!(0 == $str || 1 == $str))
		{
			$str = 0;
		}
		
		break;
		
		
		// Legacy.
		case 'array':
		if (!is_array($str))
		{
			$str = array();
		}

		break;
		
		
		case 'text':
		case 'unformatted':
		case 'primary_key':
		case 'nomode':
		default:
		$str = str_replace('<', '&lt;', $str);
		$str = str_replace('>', '&gt;', $str);
		
		break;
	}
	
	return $str;
}


/**
* @return string
* @param $str string
* @desc Prevents Javascript insertion attacks in a string.
*/
function pl_clean_html($str)
{
	// 2013-06-27 AMW - Added version check.
	$version = phpversion();
	
	/*	Greater Than and Less Than characters are no longer allowed to be stored
		in the database.  The are filtered out when submitted via GET or POST.
		Restore them here, so htmlspecialchar (below) doesn't double encode
		them.
		*/
	$str = str_replace('&lt;', '<', $str);
	$str = str_replace('&gt;', '>', $str);
	
	if ($version[0] > 4 && $version[1] > 3)
	{
		// AMW - 2012-5-29 - Turned on quote encoding.
		// 2013-06-27 AMW - Changed to ENT_HTML5 for Bootstrap conversion.
		$clean_str = htmlspecialchars($str, ENT_QUOTES | ENT_HTML5);
	}
	
	else
	{
		// 2013-06-27 AMW - Removed ENT_HTML... for PHP versions prior to 5.4.
		$clean_str = htmlspecialchars($str, ENT_QUOTES);
	}
	
	return $clean_str;
}


/**
* @return array
* @param $a array
* @desc Prevents Javascript insertion attacks in an array of strings.
*/
function pl_clean_html_array($a)
{
	$b = array();
	// 2013-06-27 AMW - Added version check.
	$version = phpversion();

	if ($version[0] > 4 && $version[1] > 3)
	{
		foreach ($a as $key => $str)
		{
			// AMW - 2012-5-29 - Turned on quote encoding.
			// 2013-06-27 AMW - Changed to ENT_HTML5 for Bootstrap conversion.
			$b[$key] = htmlspecialchars($str, ENT_QUOTES | ENT_HTML5);
		}
	}
	
	else
	{
		foreach ($a as $key => $str)
		{
			// AMW - 2012-5-29 - Turned on quote encoding.
			// 2013-06-27 AMW - Removed ENT_HTML... for PHP versions prior to 5.4.
			$b[$key] = htmlspecialchars($str, ENT_QUOTES);
		}
	}
	

	
	return $b;
}


/**
* @return string
* @param 
* @desc Returns location of this site's custom code directory.
*/
function pl_custom_directory()
{
	if (isset($_SERVER['custom_directory']))
	{
		return $_SERVER['custom_directory'];
	}
	
	else
	{
		return getcwd() . "-custom";
	}
}


// $date is in ISO format (yyyy-mm-dd), as is the return value
/**
* @return date
* @param $interval string
* @param $number int
* @param $date date
* @desc Add the specified interval of time to a date.
*/
function pl_date_add($interval, $number, $date)
{
	/* notes:
	
	From PHP manual:  getdate
	If you need sth like "Tomorrow" "last week" etc. try this:
	
	$tomorrow = mktime(0,0,0,date("m"),date("d") +1,date("Y"))
	
	$last_week = mktime(0,0,0,date("m"),date("d") -7,date("Y"))
	
	etc. if works also at the end /beginning of a month.
	
	*/
	
	$date_time_array  = getdate(strtotime($date));
	
	$hours =  $date_time_array["hours"];
	$minutes =  $date_time_array["minutes"];
	$seconds =  $date_time_array["seconds"];
	$month =  $date_time_array["mon"];
	$day =  $date_time_array["mday"];
	$year =  $date_time_array["year"];
	
	switch ($interval)
	{
		case "yyyy":
		$year +=$number;
		break;
		case "q":
		$year +=($number*3);
		break;
		case "m":
		$month +=$number;
		break;
		case "y":
		case "d":
		case "w":
		$day+=$number;
		break;
		case "ww":
		$day+=($number*7);
		break;
		case "h":
		$hours+=$number;
		break;
		case "n":
		$minutes+=$number;
		break;
		case "s":
		$seconds+=$number;
		break;
	}
	
	return date('Y-m-d', mktime($hours ,$minutes, $seconds,$month ,$day, $year));
}


/**
* @return date
* @param $date string
* @desc Converts user-submitted dates to the ISO date format.
*/
function pl_date_mogrify($date_str)
{
	$date = $date_str;  // Used to construct the ISO date from the $date_str arg.
	$x = '';  // The final ISO date string, returned at end of function.
	$a = array();  // Stores the month, day and (sometimes) year.
	
	if (strlen($date_str) < 1)
	{
		return false;
	}
	
	// Eliminate commas.
	$date = str_replace(',', '', $date);
	
	// Identify possible date separators.
	if (strpos($date, '/') > 0)
	{
		$a = explode('/', $date);
	}
	
	else if (strpos($date, '-') > 0)
	{
		$a = explode('-', $date);
	}
	
	else if (strpos($date, ' ') > 0)
	{
		$a = explode(' ', $date);
	}
	
	else if (strpos($date, '\\') > 0)
	{
		$a = explode('\\', $date);
	}
	
	if (sizeof($a) > 1 && is_numeric($a[0]) && is_numeric($a[1]))
	{
		/*	A recognized date separator was found, and $a was populated
		with month, day and possibly year.
		
		If the date is not in YYYY?MM?DD format, assume MM?DD?YYYY.
		
		(A mode for DD?MM?YYYY dates here would be handy.)
		*/
		
		if (strlen($a[0]) == 4)
		{
			$x = "{$a[0]}-{$a[1]}-{$a[2]}";
		}
		
		else
		{
			// Determine the year first.
			if (sizeof($a) == 3)
			{
				if (strlen($a[2]) == 4)
				{
					$year = $a[2];
				}
				
				else if (strlen($a[2]) == 2)
				{
					$year = substr(date('Y'), 0, 2) . $a[2];
				}
				
				else
				{
					$year = date('Y');
				}
			}
			
			else
			{
				$year = date('Y');
			}
			
			$month = str_pad($a[0], 2, '0', STR_PAD_LEFT);
			$day = str_pad($a[1], 2, '0', STR_PAD_LEFT);
			
			$x = "$year-$month-$day";
		}
	}
	
	else
	{
		// Fallback mode
		
		/* attempt to determine the year, by grabbing the last 4 (non-white
		space) chars of the $date string
		If the year is earlier than 1902, strtotime will not work, so handle
		the date in a less flexible way
		*/
		//$year = substr(rtrim($date), -4, 4);
		
		/*	set upper and lower bounds for less flexible "old" data handling
		If there isn't a lower bound, text strings won't make it past this
		*/
		/*
		if ($year < 1970 && $year > 1500)
		{
		$a = explode('/', $date);
		
		$x = "{$a[2]}-{$a[0]}-{$a[1]}";
		}
		
		else
		{*/
		$x = date("Y-m-d", strtotime($date));
		//}
	}
	
	return $x;
}


// Converts ISO 2000-12-31 to 12/31/2000
function pl_date_unmogrify($date)
{
	if ($date == '0000-00-00' || !$date)
	{
		return FALSE;
	}
	
	else
	{
		$a = explode('-', $date);
		
		// get rid of leading zeros, if they exist
		$month = (int) $a[1];
		$day = (int) $a[2];
		
		return "{$month}/{$day}/{$a[0]}";
	}
}


function pl_db_column_type($table, $column)
{
	$clean_table = mysql_real_escape_string($table);
	$clean_column = mysql_real_escape_string($column);
	$result = mysql_query("SHOW COLUMNS FROM {$clean_table} WHERE Field = '{$clean_column}'") or trigger_error(mysql_error());
	$row = mysql_fetch_assoc($result) or trigger_error(mysql_error());
	return $row['Type'];
}


function pl_error_fatal($errno = null, $errstr = null, $errfile = null, $errline = null)
{
	$str = "

<pre>
<h1>An error has occured</h1>\n
<center>{$errstr}</center>\n

<p>File: <b>{$errfile}</b>\n
<p>Line: <b>{$errline}</b>\n
";
	die($str);
}



function pl_error_handler($errno = null, $errstr = null, $errfile = null, $errline = null) {
	
	//if ("localhost" != $_SERVER['HTTP_HOST']) { return true; }
	
	switch ($errno) 
	{
		// Generated by trigger error - usually mysql - show the penguin.
		case E_USER_ERROR: // 256
		case E_USER_WARNING: // 512
		case E_USER_NOTICE: // 1024
			require_once('pikaTempLib.php');
			$buffer = pikaTempLib::plugin('pika_error',$errno, $errstr, $errfile, $errline);
			pika_exit($buffer);
			break;
	    case E_WARNING: // 2
	    case E_PARSE: // 4
		case E_NOTICE: // 8
		case E_STRICT: // 2048
	    case E_RECOVERABLE_ERROR: // 4096
	    case E_DEPRECATED: // 8192 PHP 5.3+
	    	//require_once('pikaWarning.php');
	    	$warning = pikaWarning::getInstance();
	    	$warning->setWarning($errno, $errstr, $errfile, $errline);
	 		break;
	   
	    case E_ERROR: // 1
	    case E_CORE_ERROR: // 16
		case E_CORE_WARNING: // 32
		case E_COMPILE_ERROR: // 64
		case E_COMPILE_WARNING: // 128
	    default:
	    	pl_error_fatal($errno, $errstr, $errfile, $errline);
	    	break;
	    case E_USER_DEPRECATED: // 16384 PHP 5.3+
			break;
	    //case E_ALL: // PHP6 = 32767 : PHP 5.3 = 30719 : PHP 5.2 = 6143 : PHP3/4/5 = 2047
	    //	break;
	}
	return true;
}



// HTML FORM SUBMISSION FUNCTIONS

/**
 * @return unknown
 * @param string $var_name
 * @param string $default_value
 * @param string $filter_mode
 * @desc Returns the value of a GET variable without tripping a PHP warning if the variable isn't set.
*/
function pl_grab_get($var_name, $default_value = null, $filter_mode='nomode')
{
	$value = $default_value;
	
	if (isset($_GET[$var_name]))
	{
		$value = $_GET[$var_name];
		$value = pl_clean_form_input($value, $filter_mode);
	}
	
	return $value;
}


/**
 * @return unknown
 * @param string $var_name
 * @param string $default_value
 * @param string $filter_mode
 * @desc Returns the value of a POST variable without tripping a PHP warning if the variable isn't set.
*/
function pl_grab_post($var_name, $default_value = null, $filter_mode='nomode')
{
	$value = $default_value;
	
	if (isset($_POST[$var_name]))
	{
		$value = $_POST[$var_name];
		$value = pl_clean_form_input($value, $filter_mode);
	}
	
	return $value;
}


// TODO - get rid of this
// return a form-submitted variable, after performing a basic data sanity test
function pl_grab_var($name, $method = null, $default_value = null, $format='text')
{
	/*	Handle legacy use of this function, before $method and $default_value 
		were swapped.
	*/
	if ($default_value == 'REQUEST' || $default_value == 'GET' || $default_value == 'POST')
	{
		$tmp = $method;
		$method = $default_value;
		$default_value = $tmp;
	}
	
	else if (!is_null($method) && is_null($default_value) 
		&& $method != 'REQUEST' && $method != 'GET' && $method != 'POST')
	{
		$tmp = $method;
		$method = $default_value;
		$default_value = $tmp;
	}
	
	if (is_null($method))
	{
		$method = 'REQUEST';
	}
	
	// ---
	
	$value = $default_value;
	
	switch ($method)
	{
		case 'GET':
		
		if (isset($_GET[$name]))
		{
			$value = pl_clean_form_input($_GET[$name], $format);
		}
		
		break;
		
		
		case 'POST':
		
		if (isset($_POST[$name]))
		{
			$value = pl_clean_form_input($_POST[$name], $format);
		}
		
		break;
		
		
		case 'REQUEST':
		
		if (isset($_REQUEST[$name]))
		{
			$value = pl_clean_form_input($_REQUEST[$name], $format);
		}
		
		break;
		
		
		default:
		
		trigger_error('Invalid form method specified');
		
		break;
	}
	
	return $value;
}


function pl_html_address($data)
{
	$C = "";
	
	if (isset($data["org"]) && $data["org"])
	{
		$C .= "{$data["org"]}<br>\n";
	}
	
	if (isset($data["address"]) && $data["address"])
	$C .= "{$data["address"]}<br>\n";
	
	if (isset($data["address2"]) && $data["address2"])
	$C .= "{$data["address2"]}<br>\n";
	
	if (isset($data["city"]) && (isset($data["state"]) || isset($data["zip"])))
	{
		$C .= "{$data["city"]}, {$data["state"]} {$data["zip"]}\n";
	}
	
	else
	{
		// no comma
		$C .= pl_array_lookup('city', $data) . ' '
			. pl_array_lookup('state', $data) . ' '
			. pl_array_lookup('zip', $data) . "\n";
	}
	
	return $C;
}

function pl_html_checkbox($name, $val)
{
	$C = '';
	
	if (1 == $val)
	{
		$checked = ' checked ';
	}
	
	else
	{
		$checked = '';
	}
	
	// Assigning two fields the same name may not be compatible with all browsers.  May not be compliant with W3C standards, either.
	$C .= "<input type=\"hidden\" name=\"$name\" value=\"0\"/>";
	$C .= "<input type=\"checkbox\" name=\"$name\" id=\"$name\" value=\"1\" class=\"plcheck\" tabindex=\"1\"{$checked}/>\n";
	return $C;
}


/**
 * Displays the Pika login screen.
 * This is in a standalone function  
 * @return unknown
 * @param unknown $username
 * @param unknown $status
 * @desc Enter description here...
*/
function pl_html_login_form($username, $status)
{
	$a = array('username' => $username, 'status' => $status, 'php_self' => $_SERVER['PHP_SELF']);
	echo pl_template('templates/login.html', $a);
	return true;
}


// generate an HTML menu, based on contents of an array
function pl_html_menu($a, $field_name, $field_value, $add_blank='1', $ti='1')
{
	$o = '';
	$field_value_selected = '';
	
	if (!is_array($a))
	{
		// $a = array("$field_value" => "$field_value");
		$a = array();
	}
	
	$o .= "<select name=\"$field_name\" id=\"$field_name\" class=\"plmenu\" tabindex=\"$ti\">\n";
	
	/*	Add a blank/NULL option if requested
	
	also, select the null option is the field's value is NULL
	(even if $add_blank is false)
	
	AMW - The label used to look like:
	
	<option ...> </option>
	
	I added the &nbsp; on 9/22/2004 to improve XHTML compliance.
	*/
	if (null === $field_value || '' == $field_value || !isset($field_value))
	{
		$o .= '<option selected value="">&nbsp;</option>
		    ';
		$field_value_selected = TRUE;
	}
	
	else if ($add_blank)
	{
		$o .= '<option value="">&nbsp;</option>
		    ';
	}
	
	// catch any cases where no menu data is available
	if (!$a)
	{
		$o .= '<option value="">No Menu Available</option>
		    ';
	}
	
	while (list($key, $label) = each($a))
	{
		/*
		Don't eval. fields with string values with this test; strings
		always return true.  Weed out strings here with is_numeric() and use
		the next test instead
		
		Be sure to show 0 values
		*/
		if (($field_value == $key) && is_numeric($field_value) && !$field_value_selected)
		{
			$o .= "<option selected value=\"$key\">$label</option>\n";
			$field_value_selected = TRUE;
		}
		
		// if not a string, must be a string.  use strcmp
		else if ((strcmp($field_value,$key) == 0) && !$field_value_selected)
		{
			$o .= "<option selected value=\"$key\">$label</option>\n";
			$field_value_selected = TRUE;
		}
		
		else
		{
			$o .= "<option value=\"$key\">$label</option>\n";
		}
	}
	
	if ($field_value_selected == FALSE)
	{
		$o .= '<option selected value="' . $field_value . '">' . $field_value . '</option>
		';
	}
	
	$o .= "</select>";
	
	return $o;
}


// generate an HTML menu, based on contents of an array
function pl_html_multiselect($a, $field_name, $field_values, $ti='1', $size = '18')
{
	$o = '';
	
	if (!is_array($a))
	{
		$a = array();
	}
	
	if (!is_array($field_values))
	{
		$field_values = array();
	}
	
	// Handle field_values with no corresponding value in $a
	foreach ($field_values as $value)
	{
		if (!array_key_exists($value, $a))
		{
			$a[$value] = $value;
		}
	}
	reset($field_values);
	
	// Save menu HTML code as $o
	$o .= "<select name=\"{$field_name}[]\" id=\"{$field_name}\" multiple size=\"{$size}\" tabindex=$ti>\n";
	
	// catch any cases where no menu data is available
	if (sizeof($a) < 1)
	{
		$o .= "<option value=\"\">No Menu Available</option>\n";
	}
	
	else foreach ($a as $key => $label)
	{
		/*
		Don't eval. fields with string values with this test; strings
		always return true.  Weed out strings here with is_numeric() and use
		the next test instead
		
		Be sure to show 0 values
		*/
		/*
		if (($field_value == $key) && is_numeric($field_value) && !$field_value_selected)
		{
		$o .= "<option selected value=\"$key\">$label</option>\n";
		}
		
		else if ((strcmp($field_value,$key) == 0) && !$field_value_selected)
		{
		$o .= "<option selected value=\"$key\">$label</option>\n";
		}*/
		
		if (in_array($key, $field_values))
		{
			$o .= "<option selected value=\"$key\">$label</option>\n";
		}
		
		else
		{
			$o .= "<option value=\"$key\">$label</option>\n";
		}
	}
	
	$o .= "</select>\n";
	
	return $o;
}


function pl_html_text($text)
{
	$a = pl_clean_html($text);
	$a = nl2br($a);
	
	// Linkify HTTP, HTTPS, FTP and email URIs
	// Based on code by Fredrik Kristiansen (russlndr at online.no)
	// and Albrecht Guenther (ag at phprojekt.de).
	$a = preg_replace('/(((f|ht){1}tps?:\/\/)[-a-zA-Z0-9@:%_\+.~#?&\/\/=]+)/i', '<a href="\\1" target="_blank">\\1</a>', $a);
	$a = preg_replace('/([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_\+.~#?&\/\/=]+)/i', '\\1<a href="http://\\2" target="_blank">\\2</a>', $a);
	$a = preg_replace('/([_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3})/i','<a href="mailto:\\1">\\1</a>', $a);
	
	// bold
	$a = str_replace('[b]', '<b>', $a);
	$a = str_replace('[/b]', '</b>', $a);
	
	// yellow hi-lite
	$a = str_replace('[y]', '<span class=yh>', $a);
	$a = str_replace('[/y]', '</span>', $a);
	
	// pink hi-lite
	$a = str_replace('[p]', '<span class=ph>', $a);
	$a = str_replace('[/p]', '</span>', $a);
	
	// italic
	$a = str_replace('[i]', '<i>', $a);
	$a = str_replace('[/i]', '</i>', $a);
	
	// line through
	$a = str_replace('[l]', '<span class=lt>', $a);
	$a = str_replace('[/l]', '</span>', $a);
	
	// underline
	$a = str_replace('[u]', '<span class=ul>', $a);
	$a = str_replace('[/u]', '</span>', $a);
	
	return $a;
}


function pl_html_text_array($a)
{
	$b = array();
	
	foreach ($a as $key => $str)
	{
		$b[$key] = pl_html_text($str);
	}
	
	return $b;
}

// pl_html_validate() is deprecated.
// 2013-04-12 AMW - Removed "thinborder" CSS class, looks better on pages without errors.
// 2013-07-17 AMW - Rearranged this code so <PRE> tags are not displayed if there's no output from tidy.
// 2013-08-08 AMW - Removed all code due to lack of HTML5 support in tidy.

function pl_keywords_build($first_name, $middle_name, $last_name, $extra_name)
{
	$a = array_merge(pl_text_to_search_array($first_name),
		pl_text_to_search_array($middle_name),
		pl_text_to_search_array($last_name),
		pl_text_to_search_array($extra_name));
	$a = array_unique($a);  // Duplicate metaphone values really throw the
	// results weighting off.
	$keywords = implode(' ', $a);
	
	$x = str_replace($first_name, '-', ' ');
	$y = explode($x, ' ');

	foreach ($y as $value)
	{
		if (strlen($value) > 1)  // Ignore punctuation and initials.
		{
			$sql = "SELECT root_name FROM name_variants WHERE first_name = '{$value}'";
			$result = mysql_query($sql) or trigger_error('hi');
			
			while ($row = mysql_fetch_assoc($result))
			{
				$keywords .= ' ' . $row['root_name'];
				$keywords .= ' ' . metaphone($row['root_name']);
			}
		}
	}
	
	$keywords = trim($keywords);
	return $keywords;
}

// MENUS

/**
* @return unknown
* @param menu_name unknown
* @param key = null unknown
* @param label = null unknown
* @param order = null unknown
* @desc Enter description here...
*/
function pl_menu_get($menu_name, $key = null)
{
	global $plMenus;
	static $plMenuDefs;
	
	if (!is_array($plMenus))
	{
		$plMenus = array();
	}

	if (!is_array($plMenuDefs))
	{
		$plMenuDefs = array();
	}
	
	if (!array_key_exists($menu_name, $plMenus))
	{
		$key = $val = $ord = '';
		$menu_table_name = 'menu_' . $menu_name;
		
		if (array_key_exists($menu_name, $plMenuDefs))
		{
			$key = $plMenuDefs[$menu_name]['key'];
			$val = $plMenuDefs[$menu_name]['val'];
			$ord = $plMenuDefs[$menu_name]['ord'];
		}
		
		else
		{
			$key = 'value';
			$val = 'label';
			$ord = 'menu_order';
		}
		
		$menu_exists = false;
		$sql = "SHOW TABLES;";
		$result = mysql_query($sql) or trigger_error($sql . "  " . mysql_error());
		while ($row = mysql_fetch_array($result)) {
			if($menu_table_name == $row[0]) {$menu_exists = true;}
		}
		if($menu_exists) {
			$sql = "SELECT $key, $val FROM $menu_table_name ORDER BY $ord";
			$result = mysql_query($sql) or trigger_error(mysql_error());
		
			$plMenus[$menu_name] = array();
			while ($y = mysql_fetch_assoc($result))
			{
				$plMenus[$menu_name][$y['value']] = $y['label'];
			}
			return $plMenus[$menu_name];
		}
		else {
			return false;
		}
	}
	
	/*	Don't bother returning a reference to the menu array,
	PHP 4's reference counting will make this very fast even
	for large arrays.
	*/
	return $plMenus[$menu_name];
}


function pl_menu_list()
{
	$db_name = pl_settings_get('db_name');
	$a = array();
	
	$result = mysql_query("SHOW TABLES");
	while ($row = mysql_fetch_assoc($result))
	{
		$table_name = $row["Tables_in_{$db_name}"];
		if ('menu_' == substr($table_name, 0, 5))
		{
			$a[] = substr($table_name, 5);
		}
	}
	
	return $a;
}


function pl_menu_set($menu_name, $menu_array)
{
	global $plMenus;
	
	$plMenus[$menu_name] = array();
	mysql_query("DELETE FROM menu_$menu_name");
	
	pl_menu_set_temp($menu_name, $menu_array);
	
	foreach ($menu_array as $x => $y)
	{
		mysql_query("INSERT INTO menu_$menu_name SET value='{$y['value']}', label='{$y['label']}', menu_order='$x'");
	}
	
	return true;
}


function pl_menu_set_temp($menu_name, $menu_array)
{
	global $plMenus;

	if (is_array($menu_array))
	{
		$plMenus[$menu_name] = $menu_array;
		return true;
	}
	
	return false;
}


// MYSQL DATABASE UTILITY FUNCTIONS

function pl_mysql_column_exists($table, $column)
{
	$clean_table = mysql_real_escape_string($table);
	$clean_column = mysql_real_escape_string($column);
	
	$result = mysql_query("SHOW COLUMNS FROM {$clean_table} LIKE '{$clean_column}'");
	
	if (mysql_num_rows($result) == 1)
	{
		return true;
	}
	
	return false;
}


function pl_mysql_init()
{
	/*	Don't trigger any errors if the connection fails, just return false
		and let the app. code handle the error.
	*/
	$db_host = pl_settings_get('db_host');
	$db_name = pl_settings_get('db_name');
	$db_user = pl_settings_get('db_user');
	$db_password = pl_settings_get('db_password');
	static $connection_is_live = false;
	
	if (false == $connection_is_live)
	{
		$status = mysql_connect($db_host, $db_user, $db_password);
		if ($status !== false)
		{
			$connection_is_live = mysql_select_db($db_name) or trigger_error(mysql_error());
		}
	}
	
	return $connection_is_live;
}

if (!function_exists('pl_mysql_next_id')) 
{
function pl_mysql_next_id($sequence)
{
	// VARIABLES
	$safe_sequence = mysql_real_escape_string($sequence);
	$next_id = null;
	
	pl_mysql_init() or trigger_error('');
	
	mysql_query("LOCK TABLES counters WRITE") or trigger_error('counters table lock failed');
	$result = mysql_query("SELECT count FROM counters WHERE id = '{$safe_sequence}' LIMIT 1")
		or trigger_error('');
	
	if (mysql_num_rows($result) < 1)
	{
		mysql_query("INSERT INTO counters SET id = '{$safe_sequence}', count = '1'")
		or trigger_error('');
		$next_id = 1;
	}
	
	else
	{
		$row = mysql_fetch_assoc($result);
		$next_id = $row['count'] + 1;
		
		mysql_query("UPDATE counters SET count = count + '1' WHERE id = '{$safe_sequence}' LIMIT 1")
			or trigger_error('error_during_increment');
	}
	
	mysql_query("UNLOCK TABLES") or trigger_error('error');
	return $next_id;
}
}


function pl_mysql_timestamp_to_unix($timestamp, $version = null)
{
	$unix = 0;
	
	if (is_null($version)) 
	{
		$version = mysql_get_server_info() or trigger_error("");
	}
	
	$major_version = $version[0];
	$minor_version = $version[2];

	// On my MacBook, the version shows 4.1 but the timestamp is the
	// old 14 character format.  So I added the strlen check here
	// as a workaround.
	if (strlen($timestamp) == 14 || $major_version < 4 || ($major_version == 4 && $minor_version < 1))
	{
		// Mysql 4.0 or lower.
		$year = substr($timestamp, 0, 4);
		$month = substr($timestamp, 4, 2);
		$day = substr($timestamp, 6, 2);
		$hour = substr($timestamp, 8, 2);
		$minute = substr($timestamp, 10, 2);
		$second = substr($timestamp, 12, 2);
		$unix = mktime($hour, $minute, $second, $month, $day, $year);
	}

	else if ($major_version > 4 || ($major_version == 4 && $minor_version >= 1))
	{
		// Mysql 4.1 or higher.
		if (strtotime($timestamp) === -1)
		{
			trigger_error("MySQL version {$version} {$major_version} {$minor_version} " . $timestamp);
		}
		
		else 
		{
			$unix = strtotime($timestamp);
		}
	}

	else
	{
		trigger_error("");
	}

	return $unix;
}


/*
 *
 */
function pl_prepare_dir($fs_dir_path)
{
	if (strlen($fs_dir_path) < 1)
	{
		trigger_error('');
	}
	
	if (!is_dir($fs_dir_path))
	{
		$b = explode('/', $fs_dir_path);
		$dir_name = array_pop($b);
		$parent_dir = implode($b, '/');	
		pl_prepare_dir($parent_dir);
		
		if (!mkdir($fs_dir_path, 0700))
		{
			trigger_error('');
		}
	}
	
	if (!is_writable($fs_dir_path)) 
	{
		trigger_error($fs_dir_path);
	}
	
	return true;
}

// User SESSION Functions

function pl_session_close()
{
	return true;
}


function pl_session_destroy($sessionID)
{	
	return true;
}


function pl_session_gc($lifetime)
{
	return true;
}


function pl_session_open($session_path, $session_name)
{
	return true;
}


function pl_session_read($SID)
{
	$session_data = "SID|s:" . strlen($SID) . ":\"" . $SID . "\";";
	return $session_data;
}

function pl_session_write($SID, $value)
{
	return true;
}


function pl_session_set_default($name, $value)
{
	if (!isset($_SESSION[$name])) 
	{
		$_SESSION[$name] = $value;
	}
}

// End SESSION Functions


// SYSTEM SETTINGS FUNCTIONS

function pl_settings_get($setting_name)
{
	$pl_settings = pl_settings_init();
	if (array_key_exists($setting_name, $pl_settings))
	{
		$x = $pl_settings[$setting_name];
	}
	
	else 
	{
		$x = null;
	}
	
	return $x;
}


function pl_settings_get_all()
{
	$pl_settings = pl_settings_init();
	return $pl_settings;
}


function pl_settings_init($x = null)
{
	static $plSettings;

	if (!is_array($plSettings))
	{
		$settings_path = pl_custom_directory() . "/config/settings.php";
		include($settings_path);
		
		if (!is_array($plSettings))
		{
			trigger_error("System settings were not found");
		}
		
		pl_mysql_init();

		$sql = "SELECT label, value FROM settings";
		$result = mysql_query($sql);

		while ($row = mysql_fetch_assoc($result))
		{
			$plSettings[$row['label']] = $row['value'];
		}
	}
	
	if (is_array($x)) 
	{
		$plSettings = array_merge($plSettings, $x);
	}
	
	return $plSettings;
}

function pl_settings_save()
{
	$pl_settings = pl_settings_init();
	
	// 2013-08-23 AMW - These lines are needed so the db_* and base_* settings from 
	// settings.php don't get copied into the database.
	unset($pl_settings['db_type']);
	unset($pl_settings['db_host']);
	unset($pl_settings['db_name']);
	unset($pl_settings['db_user']);
	unset($pl_settings['db_password']);
	unset($pl_settings['base_url']);
	unset($pl_settings['base_directory']);
	
	mysql_query("LOCK TABLE settings LOW_PRIORITY WRITE");
	mysql_query("DELETE FROM settings");
	
	foreach($pl_settings as $x => $y)
	{
		$safe_x = mysql_real_escape_string($x);
		$safe_y = mysql_real_escape_string($y);
		mysql_query("INSERT INTO settings SET label='$safe_x', value='$safe_y'") or trigger_error(mysql_error());
	}

	mysql_query("UNLOCK TABLES");
	return true;
}


function pl_settings_set($setting_name, $value)
{
	pl_settings_init(array($setting_name => $value));
	return true;
}


function pl_simple_url($request_uri = null, $script_filename = null)
{
	if (is_null($request_uri))
	{
		$request_uri = $_SERVER['REQUEST_URI'];
	}
	
	if (is_null($script_filename))
	{
		$script_filename = $_SERVER['SCRIPT_FILENAME'];
	}
	
	if (strpos($request_uri, '?') === false)
	{
		$request_uri_no_get = $request_uri;
	}
	
	else 
	{
		$request_uri_no_get = substr($request_uri, 0, strpos($request_uri, '?'));
	}
	
	// Deconstruct the URL being used.
	$file_name = strrchr($script_filename, '/');

	$z = explode($file_name, $request_uri_no_get);

	if (!isset($z[1]))
	{
		$z[1] = '';
	}
	
	$y = explode('/', $z[1]);
	if (!isset($y[1]))
	{
		$y[1] = null;
	}
	
	// There will always be a blank element at the beginning of the array.
	array_shift($y);
	
	//Often there is one at the end as well.
	if (strlen($y[sizeof($y) - 1]) < 1)
	{
		array_pop($y);
	}

	return $y;
}


function pl_table_fields_get($table_name)
{
	$field_list = array();
	$result = mysql_query("DESCRIBE $table_name") 
		or trigger_error("no table $table_name");
	
	while ($row = mysql_fetch_assoc($result))
	{
		if ($row['Key'] == 'PRI')
		{
			$field_list[$row['Field']] = 'primary_key';
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
				
				$field_list[$row['Field']] = 'number';
				
				break;
				
				
				case 'char':
				case 'varchar':
				case 'text':
				case 'tinytext':
				case 'timestamp':
				
				$field_list[$row['Field']] = 'text';
				
				break;
				
				
				case 'date':
				
				$field_list[$row['Field']] = 'date';
				
				break;
				
				
				case 'time':
				
				$field_list[$row['Field']] = 'time';
				
				break;
			}
		}
	}
	
	return($field_list);
}

/*
Current Syntax
Variable Tag:  %%[tag_name]%%
Radio Tag:  %%[tag_name radio menu=menu_name]%%
Select Tag:
Checkbox Tag:
Select Options Tag:  %%[tag_name option menu=menu_name]%% (incomplete)

Proposed Additional Syntax
Block Tags
Section Tag:  <!--[section datasource_name]-->...<!--[/section]-->
or Section Tag:  <!--[begin datasource_name]-->...<!--[end]-->
or Section Tag:  <!--[begin:datasource_name]-->...<!--[end:ds_name]-->
Record Tag:  %%[record object_name]%% %%[/record]%% (autodetect record ID from GET?)

Retrieval/Acquisition/Misc Tags
Include Tag:  %%[include filename]%%
Import Tag:  %%[import function_name]%%

GET, POST, SETTINGS?


can be used for processing HTML form files
note: it no longer exit()s once it finished executing

pl_template uses tags such as:  %%[name]%%   where 'name' is the name of the
template value to insert in place of the tag.  Percent signs and brackets
are used instead of a HTML comment tag.  Template values may need to be
placed inside HTML tags (such as input and link tags), and comment tags
can not validly be embedded inside other tags.

Proposed Template tag syntax

<<[begin:/end:]tag_name[; options=values;]>>

[[(macro_name:)tag_name( option=value)( option=value)(...)]]


Macros:
begin
end

Options:
source
tabindex
show_blank
[html_]menu
[html_]check
[html_]radio
text
option (incomplete)

Options:

* menu=menu_name - 
* show_blank=[yes/no] - show a blank menu entry.  Yes by default.  Selection widgets only.
* tabindex=int - HTML widgets only.
* encode=[html/none/url/js/rtf]

Forbidden characters and words:
:
;
=
,
begin
end
"
'
*/

function fff($in, &$smarty)
{
	$out = $in;
//	$out = str_replace('%%[begin:', '%%[', $out);
//	$out = str_replace('%%[end:', '%%[', $out);
	return str_replace('%%[', '%%[$', $out);
}

function li_repeat($t)
{
	$start = strpos($t, "<li repeat>");
	
	if ($start != false) 
	{
		$end = strpos($t, "</option>") + 9;
		$repeat_str .= "{foreach from=\$custid item=curr_id}
  id: {\$curr_id}<br />
{/foreach}";
		return substr($t, 0, $start) . $repeat_str . dyn_tables(substr($t, $end));
	}
	
	else 
	{
		return $t;
	}
}


function pl_template3($template_file, $template_data = array(), $subtpl_label = null)
{
	require_once('Smarty.class.php');
	$smarty = new Smarty();
	
	$smarty->register_prefilter('fff');
	$smarty->template_dir = '.';
	$smarty->compile_dir = 'app/smarty/templates_c';
	$smarty->cache_dir = 'app/smarty/cache';
	$smarty->config_dir = 'app/smarty/configs';
	$smarty->left_delimiter = '%%[';
	$smarty->right_delimiter = ']%%';
	
	foreach ($template_data as $key => $val)
	{
		$smarty->assign($key, $val);
	}
	
	$smarty->assign('base_url', '/~aaron/danio');
	$smarty->display($template_file);
}

function pl_template_section_handler()
{
}

// function pl_template($template_data, $template_file='templates/default.html', $retmode='no')
/**
* @return string
* @param template_file string
* @param template_data array
* @desc A basic templating function, replaces template tags with values from $template_data array
*/
function pl_template($template_file, $template_data = array(), $subtpl_label = null)
{
	$out = '';
	$subtpl_is_found = false;
	$section_is_found = false;
	$str = '';
	$tpl_prefix = '[[';
	$tpl_suffix = ']]';
	$current_subtpl = '';
	
	if (defined('PL_TEMPLATE_PREFIX'))
	{
		$tpl_prefix = PL_TEMPLATE_PREFIX;
	}
	
	if (defined('PL_TEMPLATE_SUFFIX'))
	{
		$tpl_suffix = PL_TEMPLATE_SUFFIX;
	}
	
	// Accept legacy argument order
	if (is_array($template_file))
	{
		$tmp = $template_data;
		$template_data = $template_file;
		$template_file = $tmp;
	}
	
	// Fix legacy use of argument #3
	if (strlen($subtpl_label) < 4)
	{
		$subtpl_label = null;
	}
	
	// Handle custom templates.
	// First, use the custom template path search algorithm if one is installed.
	if (file_exists(pl_custom_directory() . "/extensions/template_path/template_path.php"))
	{
		require_once(pl_custom_directory() . "/extensions/template_path/template_path.php");
		$template_file = template_path($template_file);
	}

	else if (file_exists(pl_custom_directory() . "/{$template_file}"))
	{
		$template_file = pl_custom_directory() . "/{$template_file}";
	}
	
	// Throw an error if the specified file cannot be found.
	if (!file_exists($template_file))
	{
		trigger_error("Invalid template file $template_file");
	}

	$file = fopen($template_file, 'r');

	if (!$file)
	{
		trigger_error('Failed to open template file');
	}

	while (!feof ($file))
	{
		$str = fgets ($file, 1024);
		
		// This will let us accept tags in HTML comment form.
		// 2013-06-27 AMW - This is interfering with Bootstrap.  I'm removing it to see if
		// anything breaks.  Grep says no.
		/*
		$str = str_replace("<!--[", $tpl_prefix, $str);
		$str = str_replace("]-->", $tpl_suffix, $str);
		*/

		// Not in sub mode
		if (is_null($subtpl_label))
		{
			// Weed out any text belonging to a sub template.
			if ($subtpl_is_found == true)
			{
				$p = strpos($str, "{$tpl_prefix}end:{$current_subtpl}{$tpl_suffix}");
				if (!($p === false))
				{
					$out .= $tpl_prefix . $current_subtpl . $tpl_suffix;
					$out .= substr($str, $p + strlen("{$tpl_prefix}end:{$current_subtpl}{$tpl_suffix}"));

					$subtpl_is_found = false;
				}
			}

			// Now handle sections.
			else if ($section_is_found == true)
			{
				$p = strpos($str, "{$tpl_prefix}end{$tpl_suffix}");
				if (!($p === false))
				{
					$section_text .= substr($str, 0, $p);

					if (!function_exists($section_data_src))
					{
						trigger_error("No function {$section_data_src}.");
					}
					
					$section_data = $section_data_src();

					if (is_array($section_data))
					{
						foreach ($section_data as $val)
						{
							$out .= pl_template_sub($section_text, $val);
						}
					}

					else if ($section_data != false)
					{
						$out .= $section_text;
					}

					// Do not display block if the data is zero or false and not an array.

					$out .= substr($str, $p + strlen("{$tpl_prefix}end{$tpl_suffix}"));
					$section_is_found = false;
				}

				else
				{
					$section_text .= $str;
				}
			}

			else
			{
				$p = strpos($str, "{$tpl_prefix}begin:");
				$p2 = strpos($str, "{$tpl_prefix}begin ");

				if (!($p === false))
				{
					$subtpl_is_found = true;

					$d = substr($str, $p + strlen("{$tpl_prefix}begin:"));
					$e = strpos($d, $tpl_suffix);
					$current_subtpl = substr($d, 0, $e);

					$f = substr($str, 0, $p);

					$out .= $f;
				}

				else if (!($p2 === false))
				{
					$section_is_found = true;

					$d = substr($str, $p2 + strlen("{$tpl_prefix}begin "));
					$e = strpos($d, $tpl_suffix);
					$section_data_src = substr($d, 0, $e);
					$section_text = substr($d, $e + strlen($tpl_suffix));
					$out .= substr($str, 0, $p2);
				}

				else
				{
					$out .= $str;
				}
			}
		}

		// In sub mode
		else
		{
			// Weed out text not related to the selected sub template.
			if ($subtpl_is_found == true)
			{
				$p = strpos($str, "{$tpl_prefix}end:$subtpl_label{$tpl_suffix}");
				if ($p === false)
				{
					$out .= $str;
				}

				else
				{
					$subtpl_is_found = false;
					$out .= substr($str, 0, $p);
					break;
				}
			}

			else
			{
				$p = strpos($str, "{$tpl_prefix}begin:$subtpl_label{$tpl_suffix}");
				if (!($p === false))
				{
					$subtpl_is_found = true;
					$out .= substr($str, $p + strlen("{$tpl_prefix}begin:$subtpl_label{$tpl_suffix}"));
				}
			}
		}
	}

	fclose($file);

	$out = pl_template_sub($out, $template_data);
	
	if (defined('PL_TEMPLATE_HTML_COMMENTS'))
	{
		$out = "\n<!-- Start:  '{$template_file}' -->\n{$out}\n<!-- End:  '{$template_file}' -->\n";
	}
	
	return $out;
}


// Recursively process template string
/*	Process the text in $out.
Find the first tag,
determine it's value,
replace all instances of that tag,
repeat until no more tags are present
*/
function pl_template_sub($str, $template_data)
{
	static $app_settings = null;
	// this value is flipped later if a menu is specified
	$use_auto_lookup = false;
	$tpl_prefix = '[[';
	$tpl_suffix = ']]';
	$prefix_size = 2;
	$suffix_size = 2;
	$tag_tabindex = 1;
	$encoding_mode = 'html';
	$y = array();  // Used to capture the exploded tag attributes.
	
	if (!is_array($app_settings)) 
	{
		$app_settings = pl_settings_get_all();
	}
	
	if (defined('PL_TEMPLATE_PREFIX'))
	{
		$tpl_prefix = PL_TEMPLATE_PREFIX;
		$prefix_size = strlen($tpl_prefix);
	}
	
	if (defined('PL_TEMPLATE_SUFFIX'))
	{
		$tpl_suffix = PL_TEMPLATE_SUFFIX;
		$suffix_size = strlen($tpl_suffix);
	}
	
	$pos = strpos($str, $tpl_prefix);
	
	// this might not work if a template field starts at the very beginning
	// of the template file (ie. $pos == 0)
	//		if ($pos === false)
	if (FALSE === $pos)
	{
		return $str;
	}
	
	// first, get the name of the first template field
	/*	Using $pos as the offset will hopefully prevent stray PHP array code ($a[$b[0]]) 
	from causing problems.
	*/
	$endpos = strpos($str, $tpl_suffix, $pos);
	
	// check $next_name for error conditions
	if ($pos > $endpos)
	{
		trigger_error("templating error #1");
	}
	
	if (($endpos - $pos) > 50)
	{
		// AMW 2013-06-27 - Added more details to help speed up debugging.
		trigger_error("template field is missing end tag, or name is too large:  " . substr($str, $pos, 10));
	}
	
	$next_name = substr($str, $pos + $prefix_size, $endpos - $pos - $suffix_size);
	
	/*  Determine whether this tag requires a pl_menu lookup.  There are a couple older
	syntaxes still in use, be sure to handle those as well as the new syntax.
	*/
	// Check for new syntax
	if (strstr($next_name, ' '))
	{
		$use_auto_lookup = true;
		$tag_name = '';
		$tag_lookup = '';
		$tag_mode = 'text';
		$tag_tabindex = 1;
		$encoding_mode = 'html';
		
		/* Determine the tag_mode and tag_name from the first tag element.  The tag_lookup
		should default to the tag_name.  They are often identical, so this shortcut can save
		a few keystrokes.
		*/
		$a = explode(' ', $next_name);
		$tag_name = $a[0];
		// tag_lookup should, if not specified, default to the tag_name value.
		$tag_lookup = $tag_name;
		
		// Process options.  TODO - make this less picky about syntax.
		$options_str = substr($next_name, strlen($a[0]) + 1);
		$options = explode(' ', $options_str);
		foreach ($options as $q)
		{
			$a = null;
			$b = null;
			//list($a, $b)
			$y = explode('=', $q);
			if (isset($y[0])) $a = $y[0];
			if (isset($y[1])) $b = $y[1];
			
			switch ($a)
			{
				case 'tabindex':
				$tag_tabindex = $b;
				break;
				
				case 'lookup':
				case 'source':
				$tag_lookup = $b;
				break;
				
				case 'show_blank':
				if (false === $b || 'no' == $b)
				{
					$tag_mode = 'menu_no_blank';
				}
				break;
				
				case 'menu':
				case 'checkbox':
				case 'radio':
				case 'vradio':
				case 'text':
				case 'option':
				$tag_mode = $a;
				
				if (strlen($b) > 0)
				{
					$tag_lookup = $b;
				}
				
				break;
				
				
				case 'encode':
				
				if (in_array($b, array('html', 'none', 'js'))) 
				{
					$encoding_mode = $b;
				}
				
				else 
				{
					trigger_error("Encoding mode {$b} does not exist.");
				}
				
				break;
				
				
				default:
				break;
			}
		}
	}
	
	// Check for old syntax.
	else if (strstr($next_name, ","))
	{
		$use_auto_lookup = true;
		
		$tag_name = '';
		$tag_lookup = '';
		$tag_mode = 'menu';
		
		$a = explode(",", $next_name);
		
		$tag_name = $a[0];
		
		if (array_key_exists(1, $a))
		{
			$tag_lookup = $a[1];
		}
		
		if (array_key_exists(2, $a))
		{
			$tag_mode = $a[2];
		}
	}
	
	// menus that don't need a blank entry added - for backwards compatibility
	else if (strstr($next_name, ";"))
	{
		$use_auto_lookup = true;
		
		list($tag_name, $tag_lookup) = explode(";", $next_name);
		$tag_mode = 'menu_no_blank';
	}
	
	// If we get this far, it's not a lookup tag.
	else 
	{
		$tag_name = $next_name;
	}
	
	
	// Determine this tag's value, and then encode that value properly.
	if (array_key_exists($tag_name, $template_data))
	{
		$tag_value = $template_data[$tag_name];
		
		switch ($encoding_mode)
		{
			case 'none':
			break;
			
			
			case 'url':
			$tag_value = urlencode($tag_value);
			break;
			
			case 'html':
			case 'js':
			default:
			$tag_value = htmlentities($tag_value);
			break;
		}
	}
	
	else
	{
		$tag_value = null;
	}	
	
	if (true == $use_auto_lookup)
	{
		/*	This is a lookup tag, and so the value in $template_data[$next_name]
		needs to be replaced by the lookup value or by HTML code
		*/
		
		// Get the menu array
		$menu_array = pl_menu_get($tag_lookup);
		
		
		$x = '';
		// Determine what to draw based on $tag_mode
		switch ($tag_mode)
		{
			case 'text':
			
			$x = pl_array_lookup($tag_value, $menu_array);
			
			break;
			
			
			case 'radio':
			
			foreach ($menu_array as $key => $val)
			{
				if ($key == $tag_value)
				{
					$checked = ' checked ';
				}
				
				else
				{
					$checked = '';
				}
				
				$x .= "<input type=\"radio\" name=\"$tag_name\" value=\"$key\" class=\"plradio\" tabindex=\"{$tag_tabindex}\"{$checked}/>{$val} &nbsp; ";
			}
			
			break;
			
			case 'vradio':
			
			foreach ($menu_array as $key => $val)
			{
				if ($key == $tag_value)
				{
					$checked = ' checked ';
				}
				
				else
				{
					$checked = '';
				}
				
				$x .= "<input type=\"radio\" name=\"$tag_name\" value=\"$key\" class=\"plradio\" tabindex=\"{$tag_tabindex}\"{$checked}/>{$val}<br/>\n";
			}
			
			break;
			
			
			case 'checkbox':
			
			if ('yes_no' != $tag_lookup)
			{
				break;
			}
			
			$x .= pl_html_checkbox($tag_name, $tag_value);
			/*
			if (1 == $tag_value)
			{
				$checked = ' checked';
			}
			
			else
			{
				$checked = '';
			}
			
			// Assigning two fields the same name may not be compatible with all browsers.  May not be compliant with W3C standards, either.
			$x .= "<input type=\"hidden\" name=\"$tag_name\" value=\"0\">";
			$x .= "<input type=\"checkbox\" name=\"$tag_name\" value=\"1\" tabindex=\"1\"{$checked}>\n";
			*/
			
			break;
			
			
			case 'menu_no_blank':
			
			$x = pl_html_menu($menu_array, $tag_name, $tag_value, 0);
			
			break;
			
			
			case 'menu':
			
			$x = pl_html_menu($menu_array, $tag_name, $tag_value, 1);
			
			break;
			
			case 'option':
			$x = "";
			
			foreach ($menu_array as $key => $val)
			{
				$clean_key = $key;
				$clean_val = $val;
				$x .- "<option value=\"{$clean_key}\">{$clean_val}</option>\n";
			}
			
			break;
			
			
			default:
			
			$x = 'INVALID MENU MODE';
			
			break;
		}
		
		$template_data[$next_name] = $x;
	}
	
	// Check that the key exists in the template data, to avoid triggering a PHP warning
	if (array_key_exists($next_name, $template_data))
	{
		// we have the name, now replace the first and any additional fields
		$newstr = str_replace($tpl_prefix . $next_name . $tpl_suffix, $template_data[$next_name], substr($str, $pos));
	}
	
	// Next, check the application settings.
	else if (array_key_exists($next_name, $app_settings))
	{
		// we have the name, now replace the first and any additional fields
		$newstr = str_replace($tpl_prefix . $next_name . $tpl_suffix, $app_settings[$next_name], substr($str, $pos));
	}
	
	else if ('ssn_compat_mode' == $next_name)
	{
		require_once('template_plugins/input_ssn.php');
		$newstr = str_replace($tpl_prefix . $next_name . $tpl_suffix, input_ssn('ssn', $template_data['ssn']), substr($str, $pos));
	}

	// Leave blank if no match is found in either the template data or the app. settings.
	else
	{
		$newstr = str_replace($tpl_prefix . $next_name . $tpl_suffix, '', substr($str, $pos));
	}
	
	// now proceed to next template field in $str
	return substr($str, 0, $pos) . pl_template_sub($newstr, $template_data);
}


// TEXT FORMATTING FUNCTIONS

function pl_text_address($data)
{
	$C = "";
	
	if (isset($data["org"]) && $data["org"])
	{
		$C .= "{$data["org"]}\n";
	}
	
	if (isset($data["address"]) && $data["address"])
	$C .= "{$data["address"]}\n";
	
	if (isset($data["address2"]) && $data["address2"])
	$C .= "{$data["address2"]}\n";
	
	if (isset($data["city"]) && (isset($data["state"]) || isset($data["zip"])))
	{
		$C .= "{$data["city"]}, {$data["state"]} {$data["zip"]}";
	}
	
	else
	{
		// no comma
		$C .= pl_array_lookup('city', $data) . ' '
			. pl_array_lookup('state', $data) . ' '
			. pl_array_lookup('zip', $data);
	}
	
	return $C;
}


function pl_text_last_name($data, $prefix = '')
{
	$is_comma_added = false;
	
	// All names (should) have a last_name
	$x = $data["{$prefix}last_name"];
	
	// Everything else is optional
	if (array_key_exists("{$prefix}extra_name", $data) && strlen($data["{$prefix}extra_name"]) > 0)
	{		
		$x .= ' ' . $data["{$prefix}extra_name"];
	}
	
	if (array_key_exists("{$prefix}first_name", $data) && strlen($data["{$prefix}first_name"]) > 0)
	{
		if (!$is_comma_added)
		{
			$x .= ',';
			$is_comma_added = true;
		}
		
		$x .= " {$data["{$prefix}first_name"]}";
	}

	if (array_key_exists("{$prefix}middle_name", $data) && strlen($data["{$prefix}middle_name"]) > 0)
	{
		if (!$is_comma_added)
		{
			$x .= ',';
			$is_comma_added = true;
		}
		
		$x .= " {$data["{$prefix}middle_name"]}";
	}
		
	return $x;
}


function pl_text_name($data, $prefix = '')
{
	// All names (should) have a last_name
	if (!isset($data["{$prefix}last_name"])) 
	{
		return "";
	}
	
	$x = $data["{$prefix}last_name"];
	
	// Everything else is optional
	if (array_key_exists("{$prefix}extra_name", $data))
	{
		$x .= ' ' . $data["{$prefix}extra_name"];
	}
	
	if (array_key_exists("{$prefix}middle_name", $data))
	{
		$x = "{$data["{$prefix}middle_name"]} $x";
	}
	
	if (array_key_exists("{$prefix}first_name", $data))
	{
		$x = "{$data["{$prefix}first_name"]} $x";
	}
	
	return $x;
}


function pl_text_phone($data)
{
	$ac = "";
	
	if (isset($data["area_code"]) && $data["area_code"])
	{
		$ac = "({$data["area_code"]})";
	}
	
	$pn = "";
	
	if (isset($data["phone"]) && $data["phone"])
	{
		$pn = $data["phone"];
	}
	
	
	return "$ac $pn";
}

function pl_text_to_search_array($s)
{
	/*	Save this code because it took a while to put together.  This version
			includes characters that end up as vowels.  Metaphone will remove these
			so their entries are not currently needed.

	$x = strtr($s, array('à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o',
		'ù' => 'u', 'À' => 'a', 'È' => 'e', 'Ì' => 'i', 'Ò' => 'o', 'Ù' => 'u',
		'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ý' => 'y',
		'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ý' => 'y',
		'â' => 'a', 'ê' => 'e', 'î' => 'i', 'ô' => 'o', 'û' => 'u',
		'Â' => 'a', 'Ê' => 'e', 'Î' => 'i', 'Ô' => 'o', 'Û' => 'u',
		'ã' => 'a', 'ñ' => 'n', 'õ' => 'o',
		'Ã' => 'a', 'Ñ' => 'n', 'Õ' => 'o',
		'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u', 'ÿ' => 'y',
		'Ä' => 'a', 'Ë' => 'e', 'Ï' => 'i', 'Ö' => 'o', 'Ü' => 'u', 'Ÿ' => 'y',
		'ç' => 'c', 'Ç' => 'c', 'ø' => 'o', 'Ø' => 'o',
		'æ' => 'a', 'Æ' => 'a', 'œ' => 'o', 'Œ' => 'o',
		'.' => '', '-' => ' ', '/' => ' '));
	*/
	
	$x = strtr($s, array('ý' => 'y', 'Ý' => 'y', 'ñ' => 'n', 'Ñ' => 'n',
		'ÿ' => 'y', 'Ÿ' => 'y', 'ç' => 'c', 'Ç' => 'c',
		'.' => '', '-' => ' ', '/' => ' '));
	/*	Metaphone removes hyphens and other characters, and hyphenated names
			end up glued together.  Each name part should be separated so the record
			is easier to find if only part of the hypenated name appears in the 
			search string.  So replace hyphens (and slashes) with spaces, and then
			metaphone() each separate word in the resulting string.
			*/
	// Sometimes slashes are used to hyphenate.
	
	$y = explode(' ', $x);
	$z = array();
	$squished_metaphone = '';
	
	foreach ($y as $value) 
	{
		if (is_numeric($value))
		{
			$z[] = str_pad($value, 3, '_');
			$squished_metaphone .= $value;
		}
		/*  MySQL FULLTEXT ignores strings under 3 characters in length.
				Pad metaphone strings so FULLTEXT will index them.  An underscore is
				used to pad the metaphone values because metaphone will never output
				that character, so a name collision with a non-padded metaphone value
				will never occur.
				Testing seems to show that including one-letter initials makes the
				results less accurate, so omit them.
				*/
		// Omit any strings, such as "123", that result in a zero-length mp string.
		else if (strlen($value) > 1 && strlen(metaphone($value)) > 0)
		{
			$z[] = str_pad(metaphone($value), 3, '_');
			$squished_metaphone .= $value;
		}
	}
	
	$squished_name = str_replace(' ', '', $x);
	// We will lose number characters here.
	$squished_metaphone = str_pad(metaphone($squished_metaphone), 3, '_');
	
	if (' ' . $squished_metaphone != $z && strlen($squished_name) > 2)
	{
		$z[] = $squished_metaphone;
	}
	
	return $z;
}

// TIME Functions

function pl_time_string($timestamp = null)
{
	$offset = 0;
	if(!function_exists('date_default_timezone_set')) {
		$time_zone_offset = pl_settings_get('time_zone_offset');
	
		if (!is_numeric($time_zone_offset))
		{
			$time_zone_offset = 0;
		}
	
		$offset = 3600 * $time_zone_offset;	
	}
	
	if(!is_null($timestamp) && is_numeric($timestamp)) {
		return date("g:i A", $timestamp + $offset);
	} else {
		return date("g:i A", time() + $offset);	
	}
	
	
	
}

function pl_time_current_string()
{
	$offset = 0;
	if(!function_exists('date_default_timezone_set')) {
		$time_zone_offset = pl_settings_get('time_zone_offset');
	
		if (!is_numeric($time_zone_offset))
		{
			$time_zone_offset = 0;
		}
	
		$offset = 3600 * $time_zone_offset;	
	}
	
	return date("g:i A", time() + $offset);	
	
}


function pl_time_mogrify($time)
{
	// takes string,such as "4:45 PM", and converts it to decimal, like 16.75
	/*	$date = getdate(strtotime($time));
	
	$hours = $date["hours"];
	$hours += $date["minutes"] / 60.0;
	$hours += $date["seconds"] / 3600.0; */
	
	if ($time == '')
	{
		return '';
	}
	
	$date = getdate(strtotime($time));
	$hours = "{$date['hours']}:{$date['minutes']}:{$date['seconds']}";
	
	return $hours;
}


function pl_time_unmogrify($time)
{
	if ('' == $time)
	{
		return '';
	}
	
	// won't work right for values >= 24
	list($hours, $minutes, $seconds) = explode(':', $time);
	return date('g:i A', mktime($hours, $minutes, 0));
}


function pl_timestamp_unmogrify($x)
{
	if ('0000-00-00 00:00:00' == $x || strlen($x) == 0)
	{
		return null;
	}
	
	return date("m/d/Y", pl_mysql_timestamp_to_unix($x));
}


function pl_tmp_path()
{
	if (isset($_ENV['TEMP']))
	{
		$tmp_path = $_ENV['TEMP'];
	}
	
	else
	{
		$tmp_path = '/tmp';
	}
	
	return $tmp_path;
}

function pl_process_comma_vals($str)
{
	$tmp_array = array();
	$out = false;
	$a = explode(",", $str);
	foreach ($a as $val) { // Remove blank values and escape non-blank values
		if($val != '') {$tmp_array[] = mysql_real_escape_string($val);}
	}
	if(count($tmp_array) > 0) { // Ensure non-empty set
		$out = "('" . implode("','",$tmp_array) . "')";
	}
	return $out;
}

function browser_is_mobile()
{
	// iOS devices
	$iphone = strpos($_SERVER['HTTP_USER_AGENT'], "iPhone");
	$ipod = strpos($_SERVER['HTTP_USER_AGENT'], "iPod");
	// Android devices
	$android = strpos($_SERVER['HTTP_USER_AGENT'], "Android");
	if ($iphone || $android || $ipod)
	{
		return true;
	}
	else
	{
		return false;
	}
}

// SECURITY TIPS
// do SQL insertion check at object level - thru autosql or manually
// do HTML/Js insertion check above object level - now thru grab_var or manually,
//		later through some function in pl_template?
// check paths everytime a variable path is used

?>
