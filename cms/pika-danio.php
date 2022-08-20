<?php

/****************************************/
/* Pika CMS	(C) 2011 Pika Software, LLC	*/
/* http://pikasoftware.com				*/
/****************************************/


// GLOBAL VARIABLES
$auth_row = array();

// CONSTANTS
if(!defined('PIKA_VERSION'))   {  define('PIKA_VERSION', '7');       }
if(!defined('PIKA_REVISION'))  {  define('PIKA_REVISION', '1');     }
if(!defined('PIKA_PATCH_LEVEL'))  {  define('PIKA_PATCH_LEVEL', '0');     }
if(!defined('PIKA_CODE_NAME')) {  define('PIKA_CODE_NAME', 'danio'); }


/**
 * Determine whether the user identified by $row has permission to perform action $op.
 * @return boolean
 * @param string $op
 * @param array $row
*/
function pika_authorize($op, $row)
{
	global $auth_row;
	
	if ('system' == $auth_row['group_id'])
	{
		return true;
	}
	
	$allow_this = false;
	
	switch ($op)
	{
		case 'read_case':
		
		if ($auth_row['read_all'])
		{
			$allow_this = true;
		}
		
		else if ($row['user_id'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if ($row['cocounsel1'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if ($row['cocounsel2'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if (is_null($row['user_id']))
		{
			$allow_this = true;
		}
		
		else if (is_null($row['office']))
		{
			$allow_this = true;
		}
		
		else if (in_array($row['office'], $auth_row['read_office']))
		{
			$allow_this = true;
		}
		// this is handy for intake staff who don't have a default office set
		
		break;
		
		
		case 'edit_case':
		
		if ($auth_row['edit_all'])
		{
			$allow_this = true;
		}
		
		else if ($row['user_id'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if ($row['cocounsel1'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if ($row['cocounsel2'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		else if (is_null($row['user_id']))
		{
			$allow_this = true;
		}
		
		else if (is_null($row['office']))
		{
			$allow_this = true;
		}
		
		else if (in_array($row['office'], $auth_row['edit_office']))
		{
			$allow_this = true;
		}
		
		
		// this is handy for intake staff who don't have a default office set
		break;
		
		
		case 'read_act':
		
		if ($auth_row['read_all'])
		{
			$allow_this = true;
		}
		
		else if ($row['user_id'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		/* AMW - Allow anyone to read that no user owns (should only be PB). */
		else if (strlen($row['user_id']) == 0)
		{
			$allow_this = true;
		}

		break;
		
		case 'edit_doc':
		
		if ($auth_row['edit_all'])
		{
			$allow_this = true;
		}
		
		else if ($row['user_id'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
			
		break;
		
		case 'edit_act':
		
		if ($auth_row['edit_all'] && pl_settings_get('db_name') != 'legalaidnebraska')
		{
			$allow_this = true;
		}
		
		else if ($row['user_id'] == $auth_row['user_id'])
		{
			$allow_this = true;
		}
		
		/* AMW - Allow anyone to edit that no user owns (should only be PB). */
		else if (strlen($row['user_id']) == 0)
		{
			$allow_this = true;
		}
		
		break;
		
		case 'users':
		
		if ($auth_row['users'])
		{
			$allow_this = true;
		}
		
		break;
		
		case 'motd':
		
		if ($auth_row['motd'])
		{
			$allow_this = true;
		}
		
		break;

		case 'system':
		case 'delete_case':
		case 'delete_act':
		
		if ('system' == $auth_row['group_id'])
		{
			$allow_this = true;
		}
		
		break;
	}
	
	return $allow_this;
}


/**
 * Implements security on Pika reports
 * @param string $report_name - name of report requested
 * @return boolean (true/false) - true if authorized - false if otherwise
*/
function pika_report_authorize($report_name)
{ 
	
	global $auth_row;
	$allow_this = false;
	
	if ('system' == $auth_row['group_id'])
	{
		return true;
	}
	
	
	$reports = array();
	if(strlen($auth_row['reports']) > 1) {
		if(strpos($auth_row['reports'],',') !== false) {
			$reports = explode(',',$auth_row['reports']);
		} else {
			$reports[] = $auth_row['reports'];
		}
	}
	foreach ($reports as $report) {
		if($report_name == $report) { $allow_this = true; }
	}
	
	return $allow_this;
}

/**
 * Performs shutdown tasks for "danio" scripts.
 * This function should be called at the end of every "danio"-based 
 * script.
 *
 * @return boolean
*/
function pika_exit($buffer)
{
	// FONT SIZE
	/* H4 for backward compat. */
	/*
	$pikaFontSizes = array();
	$pikaFontSizes['Small'] = "
	BODY { font-size: 13px; }\n
	TR { font-size: 12px; }\n
	INPUT, SELECT, TEXTAREA, TH, .row1, .row2 { font-size: 11px; }\n
	TT { font-size: 14px; }\n
	.mycal, .othercal { font-size: 10px; }\n
	H1 { font-size: 18px; }\n
	H1.crumbtrail { font-size: 13px; }\n
	H2, H4 { font-size: 15px;	}\n
	.small { font-size: 10px; }\n
	.nav, .nav a { font-size: 11px; }
	";
	$pikaFontSizes['Medium'] = '';
	*/
	/* BODY 15px
	// H1 20px;
	// h1.crumbtrail 14px;
	// H2 17px
	// TR 13px
	// TH 12px
	// TT ?
	// .small ?
	// ,mycal, .othercal ?
	*/
	/*
	$pikaFontSizes['Large'] = "
	BODY { font-size: 16px; }\n
	TR { font-size: 14px; }\n
	INPUT, SELECT, TEXTAREA, TH, .row1, .row2 { font-size: 13px; }\n
	TT { font-size: 16px; }\n
	.mycal, .othercal { font-size: 12px; }\n
	H1 { font-size: 21px; }\n
	H1.crumbtrail { font-size: 15px; }\n
	H2, H4 { font-size: 18px;	}\n
	.small { font-size: 12px; }\n
	.nav, .nav a { font-size: 13px; }
	";
	$pikaFontSizes['Super Size'] = "
	BODY { font-size: 18px; }\n
	TR { font-size: 16px; }\n
	INPUT, SELECT, TEXTAREA, TH, .row1, .row2 { font-size: 15px; }\n
	TT { font-size: 17px; }\n
	.mycal, .othercal { font-size: 13px; }\n
	H1 { font-size: 22px; }\n
	H1.crumbtrail { font-size: 46px; }\n
	H2, H4 { font-size: 19px;	}\n
	.small { font-size: 13px; }\n
	.nav, .nav a { font-size: 14px; }
	";
	*/

	// Color Schemes - the 4px line at the bottom of the header.
	/*
	2013-08-14 AMW - I turned off Color Schemes, they seem antiquated.  We will see what feedback I get.
	$theme = 'Blue';
	
	if(isset($_SESSION['theme']) && strlen($_SESSION['theme']))
	{
		$theme = $_SESSION['theme'];
	}
	*/
	
	//  2013-08-13 AMW - I removed font size settings; the browsers handle this so much better nowadays.
	/*
	$font_size = 'Medium';
	if(isset($_SESSION['font_size']) && strlen($_SESSION['font_size']))
	{
		$font_size = $_SESSION['font_size'];
	}

	$pikaTheme = str_replace("url(", "url({$base_url}/", $pikaTheme);
	*/
	// Include theme, font size CSS code in the HTML header
	//$plTemplate['header'] = "-->\n<style type='text/css'><!--\n{$pikaTheme}\n{$pikaFontSizes[$font_size]}\n--></style>\n<!--";
	//$theme_css_str = "<style type='text/css'><!--\n{$pikaTheme}\n{$pikaFontSizes[$font_size]}\n--></style>";
	
	/*
	2013-08-14 AMW - I turned off Color Schemes, they seem antiquated.  We will see what feedback I get.
	$color_schemes = pl_menu_get('color_scheme');
	$theme_css_str = array_search($theme, $color_schemes);
	
	if("" == $theme_css_str) // Something didn't work, use the fallback value.
	{
		$theme_css_str = "#0000DD";
	}
	*/
	
	require_once('app/lib/pikaAuth.php');
	$auth_row = pikaAuth::getInstance()->getAuthRow();
	
	$username = '';
	if(isset($auth_row['username']) && strlen($auth_row['username']) > 0)
	{
		$username = $auth_row['username'];
	}
	
	// 2013-08-14 AMW - I turned off Color Schemes, they seem antiquated.  We will see what feedback I get.
	//$buffer = str_replace("/* color_scheme_value */", $theme_css_str, $buffer);
	$buffer = str_replace("<!-- username -->", pl_clean_html($username), $buffer);
	$buffer = str_replace("<!-- org_name -->", pl_settings_get('owner_name'), $buffer);
	
	//mysql_close();  Don't do this; it will mess up plBase autosaving.
	
	// BENCHMARKING
	if (pl_settings_get('enable_benchmark')) 
	{
		$buffer .= "<p>\n";
		$buffer .= "File Size:  " . round(strlen($buffer) / 1024) . "KB<br/>\n";
		$buffer .= "Server Time:  " . pl_benchmark() . " seconds<br/>\n";
		// Transmit current buffer.
		echo $buffer;
		// Reset buffer, so the page isn't sent twice.
		$buffer = "";
		// Run pl_benchmark() again to see how long the buffer transmit took.
		$buffer .= "Transmit Time:  " . pl_benchmark() . " seconds *<br/>\n";
		$buffer .= "</p>\n";
	}

	// HTML VALIDATION
	// 2013-08-08 AMW - Validation removed due to lack of HTML5 support in tidy application.
	
	echo $buffer;
	exit();
}


/**
 * Returns 9 if the database stores all 9 digits and two hyphens in SSN columns.
 * Returns 4 if only truncated SSNs are stored.
 * Returns 0 if SSNs are not stored.
 *
 * @return boolean
*/
function pika_ssn_mode()
{
	$result = DB::query("DESCRIBE contacts") or trigger_error(DB::error());
	
	while ($row = DBResult::fetchRow($result))
	{
		if ($row['Field'] == 'ssn')
		{
			if ($row['Type'] == 'varchar(11)')
			{
				return 9;
			}
			
			else if ($row['Type'] == 'char(4)')
			{
				return 4;
			}
			
			else if ($row['Type'] == 'char(0)')
			{
				return 0;
			}
			
			else 
			{
				return null;
			}
		}
	}
}


/**
 * Initializes the Pika CMS "danio" framework.
 * This function should be called at the beginning of every "danio"-based 
 * script.  If the user is not authenticated, it will display the login
 * screen and exit, so the remainder of the script cannot be accessed.
 *
 * @return boolean
*/
function pika_init()
{
	global $auth_row;
	/* 2013-08-14 AMW - Copying old code into the Extensions folder often
	ends up with pika_init() getting called twice.  To make things simple
	to migrate code to Extensions, keep track of how many times pika_init
	gets called, and only let it run once. */
	static $z = 0;
	$z++;
	
	if ($z > 1)
	{
		return true;
	}
	
	/* Play some games with the PHP include_path so the 'gila'
	framework libraries are not available.
	*/
	$include_str = './app/lib' . PATH_SEPARATOR . './app/extralib' 
		. PATH_SEPARATOR . ini_get('include_path');
	ini_set('include_path', $include_str);
	
	// Now that the include_path is set, load the danio pl.php library.
	//TODO fix this
	require_once('pl.php');
	
	// Before we go any further, start the benchmark timer.
	pl_benchmark();
	// Notify PHP to use the custom Pika error handler.
	set_error_handler("pl_error_handler");
	
	/* Override the default PHP session handler.*/
	session_set_save_handler("pl_session_open", "pl_session_close", "pl_session_read", "pl_session_write","pl_session_destroy", "pl_session_gc");

	
	// destroy all MAGIC QUOTES
	if (PHP_VERSION_ID < 80000)
	{
	  if (get_magic_quotes_runtime() == true)
	  {
		set_magic_quotes_runtime(false);
	  }
	}
	
	/* The default pl_template tag prefix and suffix are '[[' and ']]',
	change this.
	*/
	define('PL_TEMPLATE_PREFIX', '%%[');
	define('PL_TEMPLATE_SUFFIX', ']%%');
	
	/* Set location of settings file */
	define('PL_SETTINGS_FILE', pl_custom_directory() . '/config/settings.php');
	define('PL_DEFAULT_PREFS_FILE', pl_custom_directory() . '/config/default_prefs.php');
	
	// Initialize the connection to the MySQL server.
	if(!defined('PL_DISABLE_MYSQL'))
	{
		pl_mysql_init() or trigger_error('Could not connect to MySQL server.  Please check PikaCMS database connection settings and/or verify that an instance of MySQL is running on the specified host.  ERROR # ' . mysql_errno());
	}
	
	
	ini_set('session.use_cookies', 1);
	ini_set('session.use_only_cookies',1);
	ini_set('session.use_trans_sid', 0);
	ini_set('session.hash_function', 1);
	ini_set('session.hash_bits_per_character', 5);

	require_once('pikaSettings.php');
	$plSettings = pikaSettings::getInstance();
	
	// AMW - This will redirect the user to https:// if they connect over
	// http:// to a server that requires a secure connection.
	if (true == $plSettings['force_https'] && 0 == strlen($_SERVER['HTTPS']))
	{
		header("Location: https://" . $_SERVER['SERVER_NAME'] . 
			$_SERVER['REQUEST_URI']);
	}
	
	// GZIP compression
	if ($plSettings['enable_compression'] && !defined('PIKA_NO_COMPRESSION'))
	{
		ob_start("ob_gzhandler");
	}
	
	
	session_set_cookie_params(0,$plSettings['base_url']);
	
	 // Set this to avoid other php websites (such as SugarCRM) from invading the current session w/ serialized objects
	$session_name = 'PikaCMS' . PIKA_VERSION . PIKA_REVISION . PIKA_PATCH_LEVEL;
	if(isset($plSettings['cookie_prefix']) && strlen($plSettings['cookie_prefix']))
	{ // Session Name only accepts letters and numbers so remove all non letters and/or numbers
		$session_name = preg_replace('/[^a-z0-9]/i','',$plSettings['cookie_prefix']);
	}
	
	session_name($session_name);
	session_start();
	
	// Set server time zone, per PHP best practices.
	// AMW - 2013-02-20 - I moved this up, above authentication, because authentication
	// was using date() and causing warnings.
	$time_zone = pl_settings_get('time_zone');
	
	if (function_exists('date_default_timezone_set')) 
	{
		if (!$time_zone)
		{
			$time_zone='America/New_York';
		}
		
		date_default_timezone_set($time_zone);
	}
	
	require_once('pikaAuth.php');
	
	// TODO - need to fix pikaAuth to be parent super-object over pikaAuthHttp
	//        until then will need to refer to auth object in context 
	//        pikaAuthHttp in HTTP sections pikaAuth in all other sections
	if(defined('PL_DISABLE_SECURITY'))
	{
		$auth_row = pikaAuth::getInstance()->getAuthRow();
	}
	elseif(defined('PL_HTTP_SECURITY')) 
	{
		authenticate_http();
		$auth_row = pikaAuthHttp::getInstance()->getAuthRow();
	}
	else
	{
		authenticate();
		$auth_row = pikaAuth::getInstance()->getAuthRow();
	}
	
	require_once('pikaDefPrefs.php');
	pikaDefPrefs::getInstance()->initPrefs($auth_row['user_id']);
	
	return true;
}


?>
