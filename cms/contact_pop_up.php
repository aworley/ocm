<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaContact.php');
require_once('pikaMisc.php');
require_once('plFlexList.php');

$contact_id = pl_grab_get('contact_id');
$contact = new pikaContact($contact_id);
$con_data = $contact->getValues();
$con_data['full_name'] = pl_text_name($con_data);
$con_data['full_address'] = pl_html_address($con_data);

// The case list.
$offset = pl_grab_get('offset', '0', 'number');
$page_size = $_SESSION['paging'];

$cases_table = new plFlexList();
$cases_table->template_file = 'subtemplates/contact_pop_up.html';
$cases_table->records_per_page = $page_size;
$cases_table->page_offset = $offset;

$result = $contact->getCasesDb();
while ($row = DBResult::fetchRow($result))
{
	if (strlen($row['number']) < 1)
	{
		$row['number'] = "No Case #";
	}
	
	// Determine whether the user is authorized to view this case's information.
	$censored = true;

	if (pika_authorize('read_case', $row))
	{
		$censored = false;
	}
	
	if ($censored == true)
	{
		foreach ($row as $key => $val)
		{
			if ($key != 'number' && $key != 'row_class' && $key != 'case_id') 
			{
				$row[$key] = " ";
			}
		}
		
		$row['open_closed'] = "Not viewable.";
		$row['open_closed_color'] = "#666666";
	}
	
	else
	{
		if (strlen($row['close_date']) > 0) 
		{
			$row['open_closed'] = "Closed";
			$row['open_closed_color'] = "#ff0000";
		}

		else
		{
			$row['open_closed'] = "Open";
			$row['open_closed_color'] = "#008800";
		}
		
		$row['open_date'] = pl_date_unmogrify($row['open_date']);
		$row['close_date'] = pl_date_unmogrify($row['close_date']);	
	}
	
	$cases_table->addRow($row);
}

$con_data['flex_header'] = $cases_table->draw();
$main_html = array();
$main_html['content'] = pl_template('subtemplates/contact_pop_up.html', $con_data);
$buffer = pl_template('templates/empty.html', $main_html);
pika_exit($buffer);

?>
