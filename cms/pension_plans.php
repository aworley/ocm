<?php 

/*****************************************/
/* Pika CMS (C) 2011 Pika Software, LLC. */
/* http://pikasoftware.com               */
/*****************************************/


require_once ('pika-danio.php');
pika_init();

require_once('plFlexList.php');
require_once('pikaTempLib.php');
require_once('pikaUser.php');
require_once('pikaContact.php');
require_once('pikaPensionPlan.php');

pl_menu_get('yes_no');

$action = pl_grab_get('action');
$pension_plan_id = pl_grab_get('pension_plan_id');

$cancel = pl_grab_get('cancel');

$base_url = pl_settings_get('base_url');
$page_title = "Pension Plans";

$menu_yes_no = array('1' => 'Yes', '0' => 'No', '' => 'No');

$buffer = '';

switch ($action)
{
	case 'edit':
		$order_field = pl_grab_get('order_field');
		$order = pl_grab_get('order');
		$page_size = pl_grab_get('page_size');
		$offset = pl_grab_get('offset');

		if(!is_null($pension_plan_id) && is_numeric($pension_plan_id))
		{
			$plan = new pikaPensionPlan($pension_plan_id);
			$a = $plan->getValues();
		}
		else
		{
			$a = array();
			$a['plan_name'] = 'A New Pension Plan';
		}

		$cases_table = new plFlexList();
		$cases_table->setTemplatePrefix('case_list_');
		$cases_table->template_file = 'subtemplates/pension_plans.html';
		$cases_table->column_names = array('number', 'client_name', 'status', 'user_id', 'office', 'problem', 'funding', 'open_date', 'close_date');
		$cases_table->table_url = "{$base_url}/pension_plans.php";
		$cases_table->get_url = "pension_plan_id={$pension_plan_id}&action=edit&";
		$cases_table->order_field = $order_field;
		$cases_table->order = $order;
		$cases_table->records_per_page = $page_size;
		$cases_table->page_offset = $offset;

		if(is_numeric($pension_plan_id))
		{
			$i = 1;
			$result = pikaPensionPlan::getPensionPlanCases(array('pension_plan_id' => $pension_plan_id),$row_count,$order_field,$order,$offset,$page_size);
			while($row = DBResult::fetchRow($result))
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
				if(isset($row['user_id']) && is_numeric($row['user_id']))
				{
					$user = new pikaUser($row['user_id']);
					$row['user_id'] = pikaTempLib::plugin('text_name','',$user->getValues());
				}
				if(isset($row['client_id']) && is_numeric($row['client_id']))
				{
					$contact = new pikaContact($row['client_id']);
					$row['client_name'] = pikaTempLib::plugin('text_name','',$contact->getValues());
				}
				$row['open_date'] = pikaTempLib::plugin('text_date','',$row['open_date']);
				$row['close_date'] = pikaTempLib::plugin('text_date','',$row['close_date']);
				$cases_table->addFancyTextRow($row);
			}
			
		}

		$a['case_list'] = $cases_table->draw();

		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
			<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
			<a href=\"{$base_url}/pension_plans.php\">$page_title</a> &gt; 
			Editing {$a["plan_name"]}";
		$template = new pikaTempLib('subtemplates/pension_plans.html',$a,'edit');
		$main_html['content'] = $template->draw();
		break;
	case 'update':
		$plan = new pikaPensionPlan($pension_plan_id);
		$plan->setValues($_GET);
		$plan->save();
		header("Location: {$base_url}/pension_plans.php?action=edit&pension_plan_id={$pension_plan_id}");
		break;
	case 'confirm_delete':
		$tab = new pikaCaseTab($tab_id);
		$a = $tab->getValues();
		$a['action'] = 'delete';
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
			<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
			<a href=\"{$base_url}/system-case_tabs.php\">$page_title</a> &gt; 
			Delete&nbsp;{$a["name"]}";
		$template = new pikaTempLib('subtemplates/system-case_tabs.html',$a,'confirm_delete');
		$main_html['content'] = $template->draw();
		break;
	default:
		$a = array();
		$filter = array();

		$a['plan_name'] = $filter['plan_name'] = $plan_name = pl_grab_get('plan_name');
		$a['sponsor_name'] = $filter['sponsor_name'] = $sponsor_name = pl_grab_get('sponsor_name');

		$order_field = pl_grab_get('order_field','plan_name');
		$order = pl_grab_get('order','ASC');
		$offset = pl_grab_get('offset');

		$page_size = $_SESSION['paging'];


		$plan_list = new plFlexList();
		$plan_list->template_file = 'subtemplates/pension_plans.html';
		$plan_list->column_names = array('plan_name','sponsor_name');
		$plan_list->table_url = "{$base_url}/pension_plans.php";
		$plan_list->get_url = "plan_name={$plan_name}&sponsor_name={$sponsor_name}&";
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
			$row['plan_name'] = "<a href={$base_url}/pension_plans.php?action=edit&pension_plan_id={$row['pension_plan_id']}>" . $plan_name . "</a>";
			$plan_list->addHtmlRow($row);
		}
		
		$plan_list->total_records = $row_count;
		if ($row_count > 0) 
		{
			$a['total_plans'] = "{$row_count} plans found";
		}

		$a['new_plan_link'] = "<a href=\"{$base_url}/pension_plans.php?action=edit\">Add New Plan</a><br/>";
		$a['plan_list'] = $plan_list->draw();
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

?>
