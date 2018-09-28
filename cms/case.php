<?php
// 08-19-2011 - AMW - inserted change to support email link
/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/* These screens display case information. */
require_once('pika-danio.php');
pika_init();

require_once('pikaCase.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');
require_once('pikaCaseTab.php');


// TODO - deprecate this when the case_screen module is revamped for PHP 5.
function pl_warning($str)
{
	return pikaMisc::htmlRedFlag($str);
}


// VARIABLES
$main_html = array();  // Values for the main HTML template.
$base_url = pl_settings_get('base_url');
$warnings = '';  // HTML text for the red flags.
$screen = pl_grab_get('screen', 'act');
$clean_screen = pl_clean_file_name($screen);
$case_id = pl_grab_get('case_id', null, 'number');

// BEGIN MAIN CODE...

// first off, make sure there's a case_id
if (!is_numeric($case_id))
{
	header("Location: {$base_url}/cal_week.php");
	exit();
}

/* Get case record data (it'll be needed on every page is some form), store in $case_row. */
$case1 = new pikaCase($case_id);
$case_row = $case1->getValues();

if (is_numeric($case1->getValue('client_id')))
{
	/* AMW 2017-02-10 - A missing contact record is rare but happens often enough
	that we	should check for it	and handle it gracefully if it occurs. */
	
	$clean_client_id = mysql_real_escape_string($case1->getValue('client_id'));
	$resultc = mysql_query("SELECT contact_id FROM contacts WHERE contact_id = 
	{$clean_client_id}");
	
	if (mysql_num_rows($resultc) == 1)
	{
	require_once('pikaContact.php');
	
	$primary_client = new pikaContact($case1->getValue('client_id'));
	$case1->makeClientDataSnapshot($primary_client);
	$case_row = array_merge($case_row, $primary_client->getValues());
	
	if(!isset($case_row['client_age']) || !$case_row['client_age'])
	{
		$client_age = $primary_client->calcAge($primary_client->birth_date,$case_row['open_date']);
		if($client_age && is_numeric($client_age))
		{
			$case1->client_age = $case_row['client_age'] = $client_age;
			$case1->save();
		}
	}
	
	$case_row['client_name'] = pl_text_name($case_row);
	$case_row['client_phone'] = pl_text_phone($case_row);
	$case_row['birth_date'] = pl_date_unmogrify($case_row['birth_date']);
}	
}	

// Prevent JS insertion attacks.
$dirty_case_row = $case_row;
$case_row = pl_clean_html_array($case_row);

// Do this after HTML tags are stripped out so the line break is preserved.
if (is_numeric($case1->getValue('client_id')))
{
	$case_row['client_address'] = nl2br(pl_text_address($case_row));
}

// PAGE HEADING
if ($case_row['number'])
{
	$num = $case_row['number'];
}

else
{
	$num = '(no case #)';
}


// Determine if the user is allowed to edit this case.
$allow_edits = pika_authorize('edit_case', $case_row);
$readonly = '';
if (!$allow_edits)
{
	$readonly = '*READ ONLY*';
}

$main_html['page_title'] = "Case # {$num}";
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/case_list.php/\">Cases</a> &gt; {$num} {$readonly}";


// ENFORCE PERMISSIONS
if (!pika_authorize('read_case', $dirty_case_row))
{
	// set up template, then display page
	$main_html['content'] = "This case is not viewable.";
	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

if ('confirm_delete' == $screen) 
{
	$template = new pikaTempLib('subtemplates/case_delete.html', $case_row);
	$main_html['content'] = $template->draw();
	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

// AMW - Begin of LSC 2008 CSR section.
//ini_set('display_errors', 'On');
$current_year = date('Y');
$current_datetime = date('U');
$cutoff_datetime = '1207022340';

$year_opened = substr($case_row['open_date'], 0, 4);
$year_closed = substr($case_row['close_date'], 0, 4);

/*
The following if clause chooses whether to use the 2007 or the 2008 closing and 
problem codes based on the case's open and closed dates.  TODO:  After March 2009
 it should revert back to the regular old closing and problem code menus.  The
2008 menu tables can be discarded and the 2007 menu tables can be kept around
for historical reporting purposes if desired.

Cases that are closed in 2007 or earlier will use the 2007 codes.  Cases that are
closed in 2008 or later will use the 2008 codes.
Cases that haven't been closed are more complicated.  If they were opened in 2008 or
later, they will use 2008 codes.  If not, they will use 2007 codes until March 31st,
after which they will change to the 2008 codes.
*/

if ($year_opened >= 2008)
{
	// Use 2008 codes.
	$m1 = pl_menu_get('problem_2008');
	$case_row['lsc_problem_text'] = pl_array_lookup($case_row['problem'],$m1);
} else if ($year_closed < 2008 ||
	(strlen($case_row['close_date']) == 0 && $year_opened < 2008 && $current_datetime < $cutoff_datetime))
{
	// Use 2007 codes.
	$m1 = pl_menu_get('problem_2007');
	$case_row['lsc_problem_text'] = pl_array_lookup($case_row['problem'],$m1);
} else {
	// Use 2008 codes.
	$m1 = pl_menu_get('problem_2008');
    $case_row['lsc_problem_text'] = pl_array_lookup($case_row['problem'],$m1);
}

// AMW - End of LSC 2008 CSR.


// CASE CONTACTS LISTING	
$clients = array();
$opposings = array();
$others = array();

$clients_html = '';
$opposings_html = '';
$others_html = '';

$primary_html = '';
$contacts_html = '';

// get contacts info to complement the $caserow array
$result = $case1->getContactsDb();
while ($row = mysql_fetch_assoc($result))
{
	$contact_ids[] = $row['contact_id'];
	$clean_contact_name = addslashes($row['last_name']) . ', ' . addslashes($row['first_name']);
	$row['full_name'] = pl_text_name($row);
	$row['full_phone'] = pl_text_phone($row);
	$row['full_address_js'] = json_encode($row['full_name'] . "\n" . pl_text_address($row));
	
	// NEW WAY
	if ($row['contact_id'] == $case_row['client_id'] && '1' == $row['relation_code'])
	{
		$primary_html .= pl_template('subtemplates/case_screen.html', array_merge($row, $case_row), 'client');
	}
	
	else
	{
		$row['number'] = $case_row['number'];  // Needed for contact.php link.
		$contacts_html .= pl_template('subtemplates/case_screen.html', $row, 'contacts');
	}
	
	// OLD WAY
	// If it's a client
	if ($row["relation_code"] == 1)
	{
		$clients[$row['contact_id']] = $row;
		
		if ($row['contact_id'] == $case_row['client_id'])
		{
			// Don't display the primary client; they've already been displayed.
			$clients_html .= "<img src=\"images/point.gif\" alt=\"Arrow\"/> <a onClick=\"return confirm('Are you sure you want to remove " . pl_text_name($row) . " from this case?');\" href=\"dataops.php?action=delete_conflict&conflict_id={$row['conflict_id']}&case_id={$row['case_id']}\">remove</a>\n";
		}
		
		else
		{
			$clients_html .= pl_template('subtemplates/case_screen.html', $row, 'contacts');
		}
	}
	
	// If it's an opposing party...
	else if (2 == $row["relation_code"])
	{
		$opposings[$row['contact_id']] = $row;
		$opposings_html .= pl_template('subtemplates/case_screen.html', $row, 'contacts');
	}
	
	// If it's something else (Opposing Counsel, Witness, etc.)
	else
	{
		$others[] = $row;
		$others_html .= pl_template('subtemplates/case_screen.html', $row, 'contacts');
	}
}
	
// NEW WAY
$case_row['client'] = $primary_html;
$case_row['contacts'] = $contacts_html;

if ($case1->unread_sms > 0)
{
	$case_row['client'] = "<p><a href=\"{$base_url}\case.php?"
		. "case_id={$case1->case_id}&screen=sms\"><span class=\"badge badge-info\">"
		. "{$case1->unread_sms}</span> new SMS messages</a></p>" . $case_row['client'];
}

// OLD WAY
/*
$case_row['contacts'] = '';

if (sizeof($clients) > 1)
{
	$case_row['contacts'] .= "<h2 class=\"chdt\">Additional&nbsp;Clients</h2><p>{$clients_html}</p>\n";
}

if (sizeof($opposings) > 0)
{
	$case_row['contacts'] .= "<h2 class=\"chdt\">Opposing&nbsp;Parties</h2><p>{$opposings_html}</p>\n";
}

if (sizeof($others) > 0)
{
	$case_row['contacts'] .= "<h2 class=\"chdt\">Additional&nbsp;Parties</h2><p>{$others_html}</p>\n";
}
*/

// This is for Toledo.
if (isset($case_row['in_holding_pen']) && true == $case_row['in_holding_pen'])
{
	// set up template, then display page
	$plTemplate["page_title"] = "Case: {$num}";
	$plTemplate['nav'] = "<a href=\"{$base_url}/site_map.php\">Pika Home</a>
 	  &gt; <a href='case_list.php'>Case List</a> &gt;  $num ";

	$holding_tags = $case_row;
	
	$holding_tags['client'] = '';
	foreach ($clients as $m)
	{
		$q = pl_template('subtemplates/holding_pen_client.html', $m);
		
		$holding_tags['client'] .= $q;
	}

	$holding_tags['opposing'] = '';
	foreach ($opposings as $m)
	{
		$q = pl_template('subtemplates/holding_pen_client.html', $m);
		
		$holding_tags['opposing'] .= $q;
	}
	
	$plTemplate['content'] = pl_template('subtemplates/holding_pen.html', $holding_tags);
	
	echo pl_template($plTemplate, 'templates/default.html');
	echo pl_bench('results');
	exit();
}


// more TEMPLATE VARIABLES
if(isset($_SESSION['def_relation_code']) && $_SESSION['def_relation_code']) {
	$case_row['relation_code'] = $_SESSION['def_relation_code'];	
}
else {
	$case_row['relation_code'] = 1;	
}

if (array_key_exists('client_id', $case_row) 
	&& !is_null($case_row['client_id'])
	&& array_key_exists($case_row['client_id'], $clients))
{
	$case_row['client_name'] = pl_text_name($clients[$case_row['client_id']]);
	$case_row['client_address'] = pl_html_address($clients[$case_row['client_id']]);
	$case_row['client_phone'] = pl_text_phone($clients[$case_row['client_id']]);
	$case_row['birth_date'] = pl_date_unmogrify($clients[$case_row['client_id']]['birth_date']);
	$case_row['phone_notes'] = pl_html_text($case_row['phone_notes']);
	$case_row['notes'] = pl_html_text($case_row['notes']);
	$case_row = array_merge($clients[$case_row['client_id']], $case_row);
}

/* Some programs may want to put little blurbs on the case screen. */
$case_row['open_date_label'] = pl_date_unmogrify($case_row['open_date']);
//$case_row['atty_label'] = pl_array_lookup($case_row['user_id'], $user_id_menu);


// CASE TAB MODULE
$C = '';  // The HTML displayed by the case tab module.

// This GARBAGE is needed for legacy case tab modules.
function pl_array_menu() {}
function pl_table_array() {}
function pika_case_heading() {}
function pika_heading() {}

$pk = new pikaMisc();
$user_id = $auth_row['user_id'];
// 2013-08-13 AMW - Removed =& for compatibility with PHP 5.3.
$clean_case_screen = $case_row;
$client = array();
$primary_client = array();
$custom_dir = pl_custom_directory() . "/";

pl_menu_set_temp('user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('case_handlers', pikaMisc::getCaseHandlerArray($case1->getValue('user_id'), $case1->getValue('cocounsel1'), $case1->getValue('cocounsel2')));
// End GARBAGE.
/*	Use $screen to look for a custom or stock tab module to include, otherwise 
	give an error message.
	Remove any naughty control characters before attempting to include the file.
*/
if (file_exists("{$custom_dir}/case_tabs/{$clean_screen}/{$clean_screen}.php")){	
	include("{$custom_dir}/case_tabs/{$clean_screen}/{$clean_screen}.php");
}elseif (file_exists("{$custom_dir}/modules/case-{$clean_screen}.php")){	
	include("{$custom_dir}/modules/case-{$clean_screen}.php");
}else if (file_exists("modules/case-{$clean_screen}.php")){
	include("modules/case-{$clean_screen}.php");
}

else
{
	$C .= "Error:  Invalid screen mode ({$clean_screen}) cannot be loaded";
}


// CASE TABS

if (file_exists(pl_custom_directory() . "/extensions/case_tabs/case_tabs.php"))
{
	require_once(pl_custom_directory() . "/extensions/case_tabs/case_tabs.php");
	$menu_case_tabs = case_tabs_extension($case1);
}

else
{
	$result = pikaCaseTab::getCaseTabsDB();
	$menu_case_tabs = array();
	while($row = mysql_fetch_assoc($result)) 
	{
		$menu_case_tabs[$row['file']] = $row;
	}
}

$case_row['case_tabs'] = pikaTempLib::plugin('case_tabs',$screen,$case_row,$menu_case_tabs,array('js_mode'));

// end TABS



// CASE SCREEN MODULE
if (file_exists("{$custom_dir}/modules/case_screen.php"))
{	
	include("{$custom_dir}/modules/case_screen.php");
}
else {
	include('modules/case_screen.php');
}


// RED FLAGS
if ($warnings)
{
	$case_row['flags'] = "<p>$warnings</p>\n";
}

$case_row['case_screen'] = $C;

// 08-18-2011 - AMW - Populate the "server_url" template tag.  This is needed for email link.
$case_row['server_url'] = $_SERVER['SERVER_NAME'];

$main_html['content'] = pl_template('subtemplates/case_screen.html', $case_row);
$main_html['rss'] = file_get_contents('js/form_save.js');

$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
