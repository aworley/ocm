<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('pika-danio.php');
pika_init();

// LIBRARIES
require_once('pikaMisc.php');
require_once('pikaDocument.php');
require_once('plFlexList.php');

// VARIABLES
$base_url = pl_settings_get('base_url');
$main_html = array();  // Values for the main HTML template.
$content_t = array();
$search_value = pl_clean_html(pl_grab_get('s'));
$search_mode = pl_clean_html(pl_grab_get('m'));
$clean_search_value = pl_clean_html($search_value);
$content_t['search_value'] = $search_value;
$content_t['search_mode'] = $search_mode;

$last_name = null;
$first_name = null;
$names = explode(",", $search_value, 2);

$order = pl_grab_get('order', 'ASC');
$order_field = pl_grab_get('order_field');
$first_row = pl_grab_get('first_row');
$offset = pl_grab_get('offset');
$page_size = $_SESSION['paging'];
$row_count = null;

if (isset($names[0]))
{
	$last_name = trim($names[0]);
}

if (isset($names[1]))
{
	$first_name = trim($names[1]);
}

// AMW - 2012-5-29 - I removed the call to html_entity_decode() since it's so unsafe.
$content_t['first_name'] = $first_name;
$content_t['last_name'] = $last_name;

// MAIN CODE

pl_menu_set_temp('user_id', pikaMisc::fetchStaffArray());

// Idea:  implement global form letters & personal form letters folders.
// Implement non-case doc management.  Place these all with case docs
// under doc_storage.  Add doc delete, overwrite warning features.
// Add documents screen.


// Determine what search mode to use.
if ($search_mode != 'C' &&
$search_mode != 'P' &&
$search_mode != 'S' &&
$search_mode != 'N' &&
$search_mode != 'A' &&
$search_mode != 'D')
{
	$search_mode = 'X';
}

// If in X mode, try to guess what mode we should use.
if ('X' == $search_mode) 
{
	if (strlen($search_value) == 11 && '-' == $search_value[3] && '-' == $search_value[6]) 
	{
		$search_mode = 'S';
	}
}


$numeric_search_value = "";
for ($n = 0; $n < strlen($search_value); $n++)
{
	if (is_numeric($search_value[$n]))
	{
		$numeric_search_value .= $search_value[$n];
	}
}

if (strlen($search_value) < 1)
{
	$content_t['mode_name'] = "General";
	$content_t['doc_header'] = "<p><em>No records found.</em></p>";
}

else
{
	switch ($search_mode)
	{
		case 'X':
		case 'C':

		// Begin CASE NUMBERS
		// Look for Case Numbers that match the search value.
		$i = 1;
		$filter = array('number' => $search_value, 'show_open' => true, 'show_closed' => true);
		$result = pikaMisc::getCases($filter, $row_count, 'number', 'ASC', 0, $page_size);
		$j = DBResult::numRows($result);

		$number_table = new plFlexList();
		$number_table->template_file = 'subtemplates/case_list.html';

		if ($j > 0)
		{

			while ($row = DBResult::fetchRow($result))
			{
				if ($row['number'] == $search_value && strlen($search_value) > 0)
				{
					header("Location: {$base_url}/case.php?case_id={$row['case_id']}");
					exit();
				}

				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}
				
				$row['client_name'] = pl_text_name($row,'client_');
				
				$number_table->addRow($row);
			}
		}

		if ($j > 0 || 'C' == $search_mode)
		{
			$content_t['case_header'] = $number_table->draw();
			$content_t['mode_name'] = "Case Number";
			$search_mode = 'C';
			break;
		}
		// End CASE NUMBERS

		case 'P':

		// Continue on with General Search.  Look for SSN or phone numbers.
		// Be careful not to find phone numbers if the user enters a long
		// text string that happens to have a lot of numbers in it.

		$contact_table = new plFlexList();
		$contact_table->template_file = 'subtemplates/contact_list.html';
		$j = 0;

		if (strlen($search_value) < 15)
		{
			// Phone number.
			$phone_search_value = $search_value;

			if (strlen($numeric_search_value) >= 10)
			{
				// There's probably an area code.  Strip it off.
				$phone_search_value = substr($numeric_search_value, 3, 3) . '-' . substr($numeric_search_value, 6, 4);
			}

			$a = array();
			$a['telephone'] = $phone_search_value;

			$result = pikaMisc::getContacts($a);
			$j = DBResult::numRows($result);

			while ($row = DBResult::fetchRow($result))
			{
				$row['client_name'] = pl_text_name($row);
				$row['client_phone'] = pl_text_phone($row);
				$row['client_phone_alt'] = pl_text_phone(array('area_code' => $row['area_code_alt'],'phone' => $row['phone_alt']));
				$contact_table->addRow($row);
			}
		}

		if ($j > 0 || 'P' == $search_mode)
		{
			$content_t['case_header'] = $contact_table->draw();
			$content_t['mode_name'] = "Phone";
			$search_mode = 'P';
			break;
		}


		case 'S':

		$contact_table = new plFlexList();
		$contact_table->template_file = 'subtemplates/contact_list.html';
		$j = 0;

		if (strlen($numeric_search_value) == 9 && strlen($search_value) < 14)
		{
			// SSN
			$a = array();
			$a['ssn'] = $search_value;

			$result = pikaMisc::getContacts($a);
			$j = DBResult::numRows($result);

			while ($row = DBResult::fetchRow($result))
			{
				$row['client_name'] = pl_text_name($row);
				$contact_table->addRow($row);
			}
		}

		if ($j > 0 || 'S' == $search_mode)
		{
			$content_t['name_header'] = $contact_table->draw();
			$content_t['mode_name'] = "SSN";
			$search_mode = 'S';
			break;
		}
		
		
		case 'N':
		// Begin Names.
		$i = 1;

		$result = pikaMisc::getContactsPhonetically($last_name, $first_name);
		$j = DBResult::numRows($result);

		$contact_table = new plFlexList();
		$contact_table->template_file = 'subtemplates/contact_list.html';

		if ($j > 0)
		{
			while ($row = DBResult::fetchRow($result))
			{
				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}
				$row['client_phone'] = pl_text_phone($row);
				$row['client_phone_alt'] = pl_text_phone(array('area_code' => $row['area_code_alt'],'phone' => $row['phone_alt']));
				$row['client_name'] = pl_text_name($row);
				$contact_table->addRow($row);
			}
		}

		if ($j > 0 || 'N' == $search_mode)
		{
			$content_t['name_header'] = $contact_table->draw();
			$content_t['mode_name'] = "Address Book";
			$search_mode = 'N';
			break;
		}
		// End CONTACTS


		/*	Don't go any further in X mode.  The activity and document searches are
			too expensive.
		*/
		if ('X' == $search_mode)
		{
			$content_t['mode_name'] = "Address Book";
			$content_t['doc_header'] = "<p><em>No records found.</em></p>";
			$search_mode = 'N';
			break;
		}
		

		case 'A':

		// ACTIVITIES
		$act_table = new plFlexList();
		$act_table->template_file = 'subtemplates/search_screen.html';
		$act_table->setTemplatePrefix('act_');
		$act_table->column_names = array('act_date');
		$act_table->table_url = "{$base_url}/search.php";
		$act_table->get_url = "&s={$search_value}&m=A&";
		if(!$order_field) { $order_field = 'act_date'; }
		$act_table->order_field = $order_field;
		$act_table->order = $order;
		$act_table->page_offset = $offset;
		$act_table->records_per_page = 100;
		if(strlen($search_value) < 4) { break; }
		$result = pikaMisc::getActivitiesByText($search_value, $row_count, $order_field, $order, $offset, 100);
		

		if (DBResult::numRows($result) > 0)
		{
			$i = 1;
			while ($row = DBResult::fetchRow($result))
			{
				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}
				$row['act_date'] = pl_date_unmogrify($row['act_date']);
				$row['act_time'] = pl_time_unmogrify($row['act_time']);
				$act_table->addRow($row);
			}
			
			$act_table->total_records = $row_count;

			// AMW - 2012-5-29 - I removed the call to html_entity_decode() since it's so unsafe.
			$content_t['search_value'] = $search_value;
		}
		
		if ($j > 0 || 'A' == $search_mode)
		{
			$content_t['act_header'] = $act_table->draw();
			$content_t['act_header'] .= "<center><p><b>{$row_count} Notes found</center></p></b>"; 
			$content_t['mode_name'] = "Activity";
			break;
		}

		case 'D':

		// DOCUMENTS
		$doc_table = new plFlexList();
		$doc_table->template_file = 'subtemplates/search_screen.html';
		$doc_table->setTemplatePrefix('doc_');
		$user_id = null;
		if(!is_null($user_id)) {
			$user_id = $auth_row['user_id'];
		}
		
		$result = pikaDocument::getDocumentsByText($search_value);
		$j = DBResult::numRows($result);

		if ($j > 0)
		{

			while ($row = DBResult::fetchRow($result))
			{
				$doc_table->addRow($row);
			}
		}

		if ($j > 0 || 'D' == $search_mode)
		{
			$content_t['doc_header'] = $doc_table->draw();
			$content_t['mode_name'] = "Document";
			break;
		}

		default:
		$content_t['doc_header'] = "<p><em>No records found.</em></p>";
		break;
	}
}
$url_search_value = urlencode($search_value);
$content_t['c_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=C\">Case Numbers</a>";
$content_t['p_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=P\">Phone</a>";
$content_t['s_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=S\">SSN</a>";
$content_t['n_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=N\">Names</a>";
$content_t['a_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=A\">Notes</a>";
$content_t['d_mode_link'] = "<a href=\"{$base_url}/search.php?s={$url_search_value}&m=D\">Docs</a>";

switch ($search_mode)
{
	case 'C':
	$content_t['c_mode_link'] = "<b>Case Numbers</b>";
	break;

	case 'P':
	$content_t['p_mode_link'] = "<b>Phone</b>";
	break;

	case 'S':
	$content_t['s_mode_link'] = "<b>SSN</b>";
	break;

	case 'N':
	$content_t['n_mode_link'] = "<b>Names</b>";
	break;

	case 'A':
	$content_t['a_mode_link'] = "<b>Notes</b>";
	break;

	case 'D':
	$content_t['d_mode_link'] = "<b>Docs</b>";
	break;
}


$main_html['content'] = pl_template('subtemplates/search_screen.html', $content_t);
$main_html['page_title'] = "Search Results for \"{$clean_search_value}\"";
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; Search Results";


$buffer = pl_template('templates/default.html', $main_html);
pika_exit($buffer);

?>