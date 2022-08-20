<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.net        */
/**********************************/


require_once ('pika-danio.php');
pika_init();

require_once('plFlexList.php');
require_once('pikaGroup.php');
require_once('pikaTempLib.php');
require_once('pikaMisc.php');



pl_menu_get('yes_no');
pl_menu_get('office');



$action = pl_grab_get('action');
$group_id = pl_grab_get('group_id');

$base_url = pl_settings_get('base_url');
$owner_name = pl_settings_get('owner_name');
$buffer = '';


if (!pika_authorize("system", array()))
{
	$plTemplate["content"] = "Access denied";
	$plTemplate["page_title"] = "Groups Editor";
	$plTemplate["nav"] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; 
						<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
						{$plTemplate['page_title']}";
	
	
	$default_template = new pikaTempLib('templates/default.html',$plTemplate);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}


switch ($action)
{
	case 'add':
		$a['reports'] = array();
		$a['action'] = 'add_update';
		$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
			<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
			<a href=\"{$base_url}/system-groups.php\">Security Levels</a> &gt; 
			Add New Group";
		$template = new pikaTempLib('subtemplates/system-groups.html',$a,'edit_group');
		$template->addMenu('reports',pikaMisc::reportList());
		$plTemplate['content'] = $template->draw();
		break;
	case 'edit':
		$group = new pikaGroup($group_id);
		$a = $group->getValues();
		$a['reports'] = explode(',',$group->reports);
		$a['action'] = 'update';
		$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
			<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
			<a href=\"{$base_url}/system-groups.php\">Security Levels</a> &gt; 
			Editing {$a["group_id"]}";
		$template = new pikaTempLib('subtemplates/system-groups.html',$a,'edit_group');
		$template->addMenu('reports',pikaMisc::reportList());
		$plTemplate['content'] = $template->draw();
		break;
	case 'add_update':
		$group = new pikaGroup();
		$group->group_id = $group_id;
		$group->save();
		$group = null;
	case 'update':
		$group = new pikaGroup($group_id);
		$tmp['read_office'] = pl_grab_get('read_office');
		$tmp['read_all'] = pl_grab_get('read_all');
		$tmp['edit_office'] = pl_grab_get('edit_office');
		$tmp['edit_all'] = pl_grab_get('edit_all');
		$tmp['users'] = pl_grab_get('users');
		$tmp['pba'] = pl_grab_get('pba');
		$tmp['motd'] = pl_grab_get('motd');
		$tmp['reports'] = pl_grab_get('reports');
		if(is_array($tmp['reports'])) { $tmp['reports'] = implode(',',$tmp['reports']); }
		$group->setValues($tmp);
		$group->save();
		//print_r($tmp);
		header("Location: {$base_url}/system-groups.php");
		break;
	default:
		$a = array();
		$a['base_url'] = $base_url;
		$group_list = new plFlexList();
		$group_list->template_file = 'subtemplates/system-groups.html';
		
		$result = pikaGroup::getGroupsDB();
		while ($row = DBResult::fetchRow($result))
		{	
			$row['read_all'] = pl_array_lookup($row['read_all'],$plMenus['yes_no']);
			$row['edit_office'] = $row['edit_office'];
			$row['edit_all'] = pl_array_lookup($row['edit_all'],$plMenus['yes_no']);
			$row['users'] = pl_array_lookup($row['users'],$plMenus['yes_no']);
			$row['pba'] = pl_array_lookup($row['pba'],$plMenus['yes_no']);
			$row['motd'] = pl_array_lookup($row['motd'],$plMenus['yes_no']);

			$group_list->addRow($row);
		}

		$a['group_list'] = $group_list->draw();
		$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; Security Levels";
		
		
		$template = new pikaTempLib('subtemplates/system-groups.html',$a,'view_groups');
		$plTemplate['content'] = $template->draw();
		break;
}


$plTemplate['page_title'] = 'User Groups';
$default_template = new pikaTempLib('templates/default.html',$plTemplate);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
