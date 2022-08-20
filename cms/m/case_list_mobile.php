<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/
// 06-11-2010 - caw - modified for Pika Mobile


chdir('..');
require_once('pika-danio.php');
pika_init();

// LIBRARIES
require_once('pikaMisc.php');
require_once('plFlexList.php');

// VARIABLES
$main_html = array();  // Values for the main HTML template.
$base_url = pl_settings_get('base_url');
$warnings = '';  // HTML text for the red flags.
$user_id = $auth_row['user_id'];

// Inititalize $filter array
$filter['user_id'] = pl_grab_get('user_id');
$filter['office'] = pl_grab_get('office');
$filter['status'] = pl_grab_get('status');
$filter['funding'] = pl_grab_get('funding');
$filter['client_name'] = pl_grab_get('client_name');
$filter['show_cases'] = pl_grab_get('show_cases', 0);
$filter['sp_problem'] = pl_grab_get('sp_problem');

$mode = pl_grab_get('mode', 'advanced');
if ('all' == $mode) 
{
	$filter['show_cases'] = 2;
	$filter['user_id'] = "";
}

else if ('open' == $mode) 
{
	$filter['show_cases'] = 0;
	$filter['user_id'] = $auth_row['user_id'];
}

else if ('closed' == $mode) 
{
	$filter['show_cases'] = 1;
	$filter['user_id'] = $auth_row['user_id'];
}


$q = explode(",", $filter['client_name'], 2);
$filter['last_name'] = trim($q[0]);

if (isset($q[1])) 
{
	$filter['first_name'] = trim($q[1]);
}

// dates will be in mm/dd/yyyy format, so they interact with the table column header links ok
$opened_before = pl_grab_get('opened_before');
$filter['opened_before'] = pl_grab_get('opened_before', null, 'date');

$closed_before = pl_grab_get('closed_before');
$filter['closed_before'] = pl_grab_get('closed_before', null, 'date');

$opened_on_after = pl_grab_get('opened_on_after');
$filter['opened_on_after'] = pl_grab_get('opened_on_after', null, 'date');

$closed_on_after = pl_grab_get('closed_on_after');

if ('NULL' == strtoupper($closed_on_after))
{
	$filter["closed_on_after"] = 'NULL';
}
else if ('NOT NULL' == strtoupper($closed_on_after))
{
	$filter["closed_on_after"] = 'NOT NULL';
}
else
{
	$filter["closed_on_after"] = pl_date_mogrify($closed_on_after);
}

// If no filter params are provided, then show the current user's open list.
if (!isset($_GET['user_id']) && "" != $filter['user_id'])
{
	$filter['user_id'] = $auth_row['user_id'];
}
// End $filter section

$order = pl_grab_get('order', 'DESC');
$order_field = pl_grab_get('order_field', 'open_date');
$first_row = pl_grab_get('first_row', '0', 'number');
$offset = pl_grab_get('offset', '0', 'number');
$page_size = $_SESSION['paging'];
$row_count = null;
$t_array =  pl_clean_html_array($filter);

pl_menu_set_temp('user_id', pikaMisc::fetchStaffArray());
pl_menu_set_temp('show_cases', array('Open', 'Closed', 'All'));

$cases_table = new plFlexList();
// 06-11-2010 - caw - modified for pika mobile
$cases_table->template_file = 'm/case_list_mobile.html';
//$cases_table->template_file = ase_list.html';
$cases_table->column_names = array('number', 'client_name', 'status', 'user_id', 'office', 'problem', 'funding', 'open_date', 'close_date');
// 06-23-2010 - caw - modified for pika mobile
$cases_table->table_url = "{$base_url}/m/case_list_mobile.php";
//$cases_table->table_url = "{$base_url}/case_list.php";
$cases_table->order_field = $order_field;
$cases_table->order = $order;
$cases_table->records_per_page = $page_size;
$cases_table->page_offset = $offset;

if ('advanced' == $mode) 
{
	$cases_table->setFilterParams($filter);
}

else 
{
	$cases_table->setFilterParams(array('mode' => $mode));
}

// MAIN CODE
// begin CASE LIST
$i = 1;
$result = pikaMisc::getCases($filter, $row_count, $order_field, $order, $offset, $page_size);
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
		$row['client_name'] = pl_text_last_name($row, 'contacts.');
	}
	
	if ($_SESSION['popup'] == true)
	{
		$row['link_target'] = " target=\"_blank\"";
	}
	
	$cases_table->addHtmlRow($row);
}

$cases_table->total_records = $row_count;

$t_array['flex_header'] = $cases_table->draw();

if ($row_count > 0) 
{
	$t_array['total_cases'] = "{$row_count} cases found";
}


if ("advanced" == $mode) 
{
// 06-23-2010 - caw - modified for pika mobile
	$t_array['cl_form'] = pl_template('m/case_list_mobile.html', $t_array, 'cl_form');
}

$main_html['page_title'] = 'Case List';
// 06-23-2010 - caw - modified for pika mobile
$main_html['content'] = pl_template('m/case_list_mobile.html', $t_array);
//06-23-2010 - caw - modified for pika mobile
$main_html['nav'] = "<a href=\"{$base_url}/m\">Pika Home</a> &gt; <a href=\"{$base_url}/case_list_mobile.php?mode=open\">Cases</a> &gt; Current Case List";


if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE) {
	$cal_url= "https://".$_SERVER['HTTP_HOST'].$base_url;
}else { $cal_url= "http://".$_SERVER['HTTP_HOST'].$base_url; }

$main_html['rss'] = "<link rel=\"alternate\" type=\"application/rss+xml\" title=\"RSS\" href=\"{$cal_url}/services/cases-rss.php?user_id={$user_id}\" />";

$buffer = pl_template('m/default.html', $main_html);
pika_exit($buffer);

?>
