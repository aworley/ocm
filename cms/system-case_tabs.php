<?php 

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once ('pika-danio.php');
pika_init();

require_once('plFlexList.php');
require_once('pikaCaseTab.php');
require_once('pikaTempLib.php');

pl_menu_get('yes_no');

$action = pl_grab_get('action');
$tab_id = pl_grab_get('tab_id');
$enabled = pl_grab_get('enabled');
$autosave = pl_grab_get('autosave');
$tab_order = pl_grab_get('tab_order');
$tab_row = pl_grab_get('tab_row');
$name = pl_grab_get('name');
$file = pl_grab_get('file');
$cancel = pl_grab_get('cancel');
$screen = pl_grab_get('screen');

$base_url = pl_settings_get('base_url');
$page_title = "System Case Tabs";

$menu_yes_no = array('1' => 'Yes', '0' => 'No', '' => 'No');

$buffer = '';


if (!pika_authorize("system", array()))
{
	$main_html['content'] = "Access denied";
	$main_html['page_title'] = $page_title;
	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
						 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
						 {$page_title}";
	
	$default_template = new pikaTempLib('templates/default.html', $main_html);
	$buffer = $default_template->draw();
	
	pika_exit($buffer);
}


switch ($action)
{
	case 'edit':
		$tab = new pikaCaseTab($tab_id);
		if($tab->is_new) {
			$tab_id = $tab->tab_id;
			$tab->save();
		} // If new entry
		$menu_tab_files = pikaCaseTab::getCaseTabFiles();
		$a = $tab->getValues();
		$a['base_url'] = $base_url;
		$a['action'] = 'update';
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
			<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
			<a href=\"{$base_url}/system-case_tabs.php\">$page_title</a> &gt; 
			Editing {$a["name"]}";
		$template = new pikaTempLib('subtemplates/system-case_tabs.html',$a,'edit_tab');
		$template->addMenu('tab_file',$menu_tab_files);
		$template->addMenu('tab_row',array('1'=>'1st Row','2' => '2nd Row'));
		$main_html['content'] = $template->draw();
		break;
	case 'update':
		$tab = new pikaCaseTab($tab_id);
		$tab->name = $name;
		$tab->file = $file;
		$tab->enabled = $enabled;
		$tab->tab_row = $tab_row;
		$tab->autosave = $autosave;
		$tab->save();
		//print_r($tab->getValues());
		//print_r($_GET);
		header("Location: {$base_url}/system-case_tabs.php");
		break;
	case 'enable':
		$tab = new pikaCaseTab($tab_id);
		if($tab->enabled == 1) {$tab->enabled = 0; }
		else {$tab->enabled = 1; }
		$tab->save();
		header("Location: {$base_url}/system-case_tabs.php");
		break;
	case 'move_up':
		$tab = new pikaCaseTab($tab_id);
		$tab->move_up();
		$tab->save();
		header("Location: {$base_url}/system-case_tabs.php");
		break;
	case 'move_down':
		$tab = new pikaCaseTab($tab_id);
		$tab->move_down();
		$tab->save();
		header("Location: {$base_url}/system-case_tabs.php");
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
	case 'delete':
		if(!$cancel) {
			$tab = new pikaCaseTab($tab_id);
			$tab->delete();
		}
		header("Location: {$base_url}/system-case_tabs.php");
		break;
	default:
		$a = array();
		$a['base_url'] = $base_url;
		$tab_list = new plFlexList();
		$tab_list->template_file = 'subtemplates/system-case_tabs.html';
		
		$result = pikaCaseTab::getCaseTabsDB();
		$case_tabs = array();
		while ($row = DBResult::fetchRow($result))
		{	
			$case_tabs[$row['tab_id']] = $row;
			$row['enable_text'] = 'Enable';
			if($row['enabled'] == 1) {
				$row['enable_text'] = 'Disable';
			}
			$row['enabled'] = pl_array_lookup($row['enabled'],$menu_yes_no);
			$row['autosave'] = pl_array_lookup($row['autosave'],$menu_yes_no);
			
			$tab_list->addRow($row);
		}
		if(!$screen) {$screen = 'info';}
		$a['tab_list'] = $tab_list->draw();
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 {$page_title}";
		$template = new pikaTempLib('subtemplates/system-case_tabs.html',$a,'view_tabs');
		$main_html['content'] = $template->draw();
		break;
}


$main_html['page_title'] = $page_title;
$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
