<?php 

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('pika-danio.php');
pika_init();
require_once('plFlexList.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');
require_once('pikaPbAttorney.php');





$C = '';
$filter = array();

$pba_id = pl_grab_get('pba_id');

$county = pl_grab_get('county');
$languages = pl_grab_get('languages');
$practice_areas = pl_grab_get('practice_areas');
$last_name = pl_grab_get('last_name');


$order = pl_grab_get('order','ASC');
$order_field = pl_grab_get('order_field','atty_name');
$offset = pl_grab_get('offset');
$page_size = $_SESSION['paging'];
$screen = pl_grab_get('screen');
$action = pl_grab_get('action');
$case_id = pl_grab_get('case_id');
$field = pl_grab_get('field');

$base_url = pl_settings_get('base_url');
$a = $main_html = array();

switch ($action)
{
	case 'assign_pba':
		if($pba_id && is_numeric($pba_id))
		{
			$pb_attorney = new pikaPbAttorney($pba_id);
			$pb_attorney->last_case = date('Y-m-d');
			$pb_attorney->save();
		}
		header("Location:{$base_url}/ops/update_case.php?case_id={$case_id}&{$field}={$pba_id}&screen=pb");
		break;
	default:
		$filter = array();
		if ($county)
		{
			$filter['county'] = $county;
		}

		if ($languages)
		{
			$filter['languages'] = $languages;
		}

		if ($practice_areas)
		{
			$filter['practice_areas'] = $practice_areas;
		}

		if ($last_name)
		{
			$filter['last_name'] = $last_name;
		}

		if (!$offset)
		{
			$offset = 0;		
		}


		$pba_count = 0;

		$pba_list = new plFlexList();
		$pba_list->template_file = 'subtemplates/pb_attorneys.html';
		$pba_list->column_names = array('atty_name','last_case','firm','address','phone_notes','email','county','languages','practice_areas','notes');
		$pba_list->table_url = "{$base_url}/assign_pba.php";
		$pba_list->get_url = "practice_areas={$practice_areas}&county={$county}&last_name={$last_name}&languages={$languages}&case_id={$case_id}&field={$field}&";
		$pba_list->order_field = $order_field;
		$pba_list->order = $order;
		$pba_list->records_per_page = $page_size;
		$pba_list->page_offset = $offset;

		$result = pikaPbAttorney::getPbAttorneys($filter, $pba_count, $order_field, $order, $offset, $page_size);

		while ($row = DBResult::fetchRow($result))
		{
			$row['atty_address'] = pl_text_address($row);
			$row['last_case'] = pl_date_unmogrify($row['last_case']);
			$row['atty_name'] = "<a href='{$base_url}/assign_pba.php?action=assign_pba&case_id=$case_id&pba_id={$row['pba_id']}&field={$field}&screen=pb'>{$row["last_name"]}, {$row["first_name"]} {$row["middle_name"]} {$row["extra_name"]}</a>";
			$pba_list->addHtmlRow($row);
		}

		$pba_list->total_records = $pba_count;
		if ($pba_count > 0) {
			$a['total_pba'] = "{$pba_count} Pro Bono Attorney(s) found";
		}


		$a['atty_list'] = $pba_list->draw();
		$a['field'] = $field;
		$a['case_id'] = $case_id;
		$a['screen'] = $screen;
		$a['county'] = $county;
		$a['languages'] = $languages;
		$a['practice_areas'] = $practice_areas;
		$a['last_name'] = $last_name;
		$a['order'] = $order;
		$a['order_field'] = $order_field;
		$template = new pikaTempLib('subtemplates/assign_pba.html',$a);
		$main_html['content'] = $template->draw();
		
		break;
}






$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
						Pro Bono Attorneys";

$main_html['page_title'] = "Pro Bono Attorneys";

$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);