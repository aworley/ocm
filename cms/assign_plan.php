<?php 

/*****************************************/
/* Pika CMS (C) 2011 Pika Software, LLC. */
/* http://pikasoftware.com               */
/*****************************************/


require_once ('pika-danio.php');
pika_init();

require_once('pikaCase.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');
require_once('pikaPensionPlan.php');

pl_menu_get('yes_no');

$pension_plan_id = pl_grab_get('pension_plan_id');
$case_id = pl_grab_get('case_id');
$action = pl_grab_get('action');

$cancel = pl_grab_get('cancel');

$base_url = pl_settings_get('base_url');
$page_title = "Assign Pension Plan";


$menu_yes_no = array('1' => 'Yes', '0' => 'No', '' => 'No');

$buffer = '';

switch ($action)
{
	case 'assign_new':
		$plan = new pikaPensionPlan();
		$plan->save();
		$case = new pikaCase($case_id);
		$case->pension_plan_id = $plan->pension_plan_id;
		$case->save();
		header("Location: {$base_url}/pension_plans.php?action=edit&pension_plan_id={$plan->pension_plan_id}");
		exit();
		break;
	case 'assign':
		$case = new pikaCase($case_id);
		$case->pension_plan_id = $pension_plan_id;
		$case->save();
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=pension");
		exit();
		break;
	default:
		$a = array();
		$a['case_id'] = $case_id;
		
		$filter = array();
		$filter['plan_name'] = $a['plan_name'] = $plan_name = pl_grab_get('plan_name');
		$filter['sponsor_name'] = $a['sponsor_name'] = pl_grab_get('sponsor_name');
		
		$order_field = pl_grab_get('order_field','plan_name');
		$order = pl_grab_get('order','ASC');
		$offset = pl_grab_get('offset');

		$page_size = $_SESSION['paging'];
		
		
		$plan_list = new plFlexList();
		$plan_list->template_file = 'subtemplates/pension_plans.html';
		$plan_list->column_names = array('plan_name','sponsor_name');
		$plan_list->table_url = "{$base_url}/assign_plan.php";
		$plan_list->get_url = "plan_name={$a['plan_name']}&sponsor_name={$a['sponsor_name']}&case_id={$case_id}&";
		$plan_list->order_field = $order_field;
		$plan_list->order = $order;
		$plan_list->records_per_page = $page_size;
		$plan_list->page_offset = $offset;


		$result = pikaPensionPlan::getPensionPlansDB($filter,$row_count,$order_field,$order,$offset,$page_size);	
		while ($row = DBResult::fetchRow($result))
		{	
			$plan_name = "No Name";
			if(isset($row['plan_name']) && strlen($row['plan_name']) > 0)
			{
				$plan_name = $row['plan_name'];
			}
			$row['plan_name'] = "<a href={$base_url}/assign_plan.php?action=assign&pension_plan_id={$row['pension_plan_id']}&case_id={$case_id}>" . $plan_name . "</a>";
			$plan_list->addHtmlRow($row);
		}
		$plan_list->total_records = $row_count;
		$a['plan_list'] = "<a href=\"{$base_url}/assign_plan.php?action=assign_new&case_id={$case_id}\">Assign New Plan</a><br/>";
		$a['plan_list'] .= $plan_list->draw();
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 {$page_title}";
		$template = new pikaTempLib('subtemplates/pension_plans.html',$a,'view');
		$main_html['content'] = $template->draw();
		break;
}


$main_html['page_title'] = $page_title;
$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);
