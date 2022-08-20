<?php 

/**********************************/
/* Pika CMS (C) 2005 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once ('pika-danio.php'); 
pika_init();


// VARIABLES
$buffer = "";
$dummy = array();  // Used when calling pika_authorize().
$base_url = pl_settings_get('base_url');
$screen = pl_grab_post('screen_name');  // Which mode to display.
$screen_msg = pl_grab_get('screen_msg');  // Message to be displayed on the screen.
$safe_screen_msg = pl_clean_html($screen_msg);
$zipcode = pl_grab_post('zipcode');
$safe_zipcode = DB::escapeString($zipcode);


// AUTHORIZATION
if (!pika_authorize("system", $dummy))
{
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; Zip Codes";
	$main_html['page_title'] = 'Zip Codes';
	$main_html['content'] =  "Access denied";
	$buffer = pl_template($main_html, 'templates/default.html');
	pika_exit($buffer);
}


// MAIN CODE

switch ($screen)
{
	case 'edit':
		
		$sql = "SELECT * FROM zip_codes WHERE zip='{$safe_zipcode}' LIMIT 1";
		$result = DB::query($sql) or trigger_error("");
		
		if (DBResult::numRows($result) == 1)
		{
			$row = DBResult::fetchRow($result) or trigger_error('');
		}
		
		else 
		{
			$row = array();
			$row['zip']= pl_clean_html($zipcode);
		}

		$row['screen_name'] = 'edit';
		$row['button_text'] = 'Update';		
		$buffer .= pl_template('subtemplates/zipcode.html', $row, 'zipcode');
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; <a href=\"zipcode.php\">Zip Codes</a> &gt; Editing \"{$row['zip']}\"";

		break;
	
	
	case 'add':

		$a = array();
		$a['heading'] = '<h2>Add a New Zip Code</h2>';
		$a['button_text'] = 'Add';
		$a['screen_name'] = 'add';		
		$buffer .= pl_template('subtemplates/zipcode.html', $a, 'zipcode');
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; <a href=\"zipcode.php\">Zipcodes</a> &gt; Adding New Zipcode";
		
		break;
		
	
	default:

		
		$buffer = $safe_screen_msg;
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; Zip Codes";

		$buffer .= pl_template('subtemplates/zipcode.html', array(), 'zipcode_default');
		break;
}


$main_html['page_title'] = 'Zip Codes';
$main_html['content'] =  $buffer;

$buffer = pl_template($main_html, 'templates/default.html');
pika_exit($buffer);

?>
