<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/* This screen is used to view and edit an existing contact record.
*/
require_once('pika-danio.php');
pika_init();

require_once('pikaContact.php');
require_once('pikaMisc.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');

$main_html = array();  // Values for the main HTML template.
$extra_url = pl_simple_url();
$base_url = pl_settings_get('base_url');
$contact_id = pl_grab_get('contact_id');
$case_id = pl_grab_var('case_id');
$number = pl_grab_var('number');
$screen = pl_grab_get('screen', 'act');
$relation_code = pl_grab_var('relation_code', null, 'REQUEST');  // Used when adding a new case contact


$contact = new pikaContact($contact_id);
$clean_contact_screen = $contact->getValues();

$clean_contact_screen = pl_clean_html_array($clean_contact_screen);
$clean_contact_screen['birth_date'] = pl_date_unmogrify($clean_contact_screen['birth_date']);
$clean_contact_screen['case_id'] = pl_grab_get('case_id');
$clean_contact_screen['screen'] = $screen;
$clean_contact_screen['intake_id'] = pl_grab_get('intake_id');
$clean_contact_screen['form_action'] = "{$base_url}/ops/update_contact.php?case_id={$clean_contact_screen['case_id']}";
$clean_contact_screen['submit_label'] = 'Save';



// Build the case list.
$offset = pl_grab_get('offset');

$cases_table = new plFlexList();
$cases_table->template_file = 'subtemplates/contact_full.html';
//$cases_table->setFilterParams($filter);
$cases_table->records_per_page = $_SESSION['paging'];
$cases_table->page_offset = $offset;

$result = $contact->getCasesDb();

if (DBResult::numRows($result) > 1)
{
	// remind the user that edit the contact record will cascade thru the system
	$clean_contact_screen['preamble'] = '<p><strong>Note</strong>:  The contact information on this screen is linked to multiple cases.
			If you change the information on this screen, it will affect <strong>each</strong> of these cases.  
			Only change this information if this person/organization has changed their address, telephone number, etc.';
}

$i = 0;
$staff = pikaMisc::fetchStaffArray();
while ($row = DBResult::fetchRow($result))
{
	if (strlen($row['number']) < 1)
	{
		$row['number'] = "No Case #";
	}

	$row['open_date'] = pl_date_unmogrify($row['open_date']);
	$row['close_date'] = pl_date_unmogrify($row['close_date']);
	$row['atty_name'] = pl_array_lookup($row['user_id'],$staff);

	$cases_table->addRow($row);

	$i++;
}

if (DBResult::numRows($result) > 0)
{
	$clean_contact_screen['case_list'] = $cases_table->draw();
}

else
{
	$clean_contact_screen['case_list'] = "<p><em>No cases found.</em></p>";
}

// End CASE LIST.


// Begin INTAKE LIST.
// Only LSNC uses intakes.


$i_sql = "select count(*) AS table_count from information_schema.tables where table_name = 'intakes'";
$i_sql .= " and table_schema='" . pl_settings_get('db_name') . "'";
$i_result = DB::query($i_sql);
$i_row = DBResult::fetchRow($i_result);

if ('1' == $i_row['table_count'])
{
	$intake_table = new plFlexList();
	$intake_table->flex_header_name = 'intake_header';
	$intake_table->flex_row_name = 'intake_row';
	$intake_table->flex_footer_name = 'intake_footer';
	$intake_table->template_file = 'subtemplates/contact_full.html';
	$intake_table->records_per_page = $_SESSION['paging'];
	$result = $contact->getIntakesDb();
	
	while ($row = DBResult::fetchRow($result))
	{
		$intake_table->addRow($row);
	}
	
	if (DBResult::numRows($result) > 0)
	{
		$clean_contact_screen['intake_list'] = $intake_table->draw();
	}
	
	else
	{
		$clean_contact_screen['intake_list'] = "<p><em>No intakes found.</em></p>";
	}
}
// End INTAKE LIST.


// Begin ALIASES LIST.
// Aliases section
$aliases_str = "";

$result = $contact->getAliasesDb();
if (DBResult::numRows($result) > 1)
{
	$aliases_str .= "<ul>\n";
	while ($row = DBResult::fetchRow($result))
	{
		if($row['primary_name'] != 1) {
			$aliases_str .= "<li>\n";
			$row['full_name'] = pl_text_name($row);
			$alias_template = new pikaTempLib('subtemplates/contact_full.html', $row, 'alias');
			$aliases_str .= $alias_template->draw();
		}
	}
	$aliases_str .= "</ul>\n";
}

else
{
	$aliases_str .= "<p>No aliases found.\n";
}

$clean_contact_screen['alias_list'] = $aliases_str;
// End ALIASES LIST.


$clean_contact_screen['full_name'] = pl_text_name($clean_contact_screen);
$contact_template = new pikaTempLib('subtemplates/contact_full.html', $clean_contact_screen, 'contact');
$main_html['page_title'] = $clean_contact_screen['full_name'];
$main_html['content'] = $contact_template->draw();

if (pl_mysql_column_exists('cases', 'benefit_form'))
{
	$main_html['content'] .= file_get_contents('js/form_save_contact.js');
}

// Display a different crumb trail if we just came from the case screen.
if (strlen($case_id) > 0) 
{
	// Create a case number label.
	if ($number)
	{
		$num = $number;
	}
	
	else
	{
		$num = 'This Case';
	}
	
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/case_list.php\">Cases</a> &gt; <a href=\"{$base_url}/case.php?case_id={$case_id}\">{$num}</a> &gt; {$main_html['page_title']}";
}

else 
{
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/addressbook.php\">Address Book</a> &gt; {$main_html['page_title']}";
}

$default_template = new pikaTempLib('templates/default.html', $main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
