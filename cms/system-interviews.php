<?php
// 04-19-2012 - caw - migrated during upgrade to v5.1.1
require_once('pika-danio.php');

pika_init();

require_once('pikaTempLib.php');
require_once('pikaInterview.php');
require_once('plFlexList.php');


$base_url = pl_settings_get('base_url');

if (!pika_authorize("system", array()))
{
	$plTemplate["content"] = "Access denied";
	$plTemplate["nav"] = "<a href=\"{$base_url}/\">Pika Home</a> 
							&gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> 
							&gt; Interview Manager";
	$template = new pikaTempLib('templates/default.html', $plTemplate);
	$buffer = $template->draw();
	pika_exit($buffer);
}

$buffer = '';
$action = pl_grab_get('action');
$interview_id = pl_grab_get('interview_id');
$name = pl_grab_get('name');
$interview_text = pl_grab_get('interview_text');
$enabled = pl_grab_get('enabled');


switch($action) {
	case 'enable':
		$interview = new pikaInterview($interview_id);
		if($interview->enabled) { $interview->enabled = 0; }
		else {$interview->enabled = 1;}
		$interview->save();
		header("Location: {$base_url}/system-interviews.php");
		break;
	case 'update':
		$interview = new pikaInterview($interview_id);
		$interview->name = $name;
		$interview->interview_text = $interview_text;
		$interview->enabled = $enabled;
		$interview->save();
		header("Location: {$base_url}/system-interviews.php");
		break;
	case 'delete':
		$delete = pl_grab_get('Delete');
		if($delete == 'Delete') {
			$interview = new pikaInterview($interview_id);
			$interview->delete();
		}
		header("Location: {$base_url}/system-interviews.php");
		break;
	case 'confirm_delete':
		$a = array();
		$a['action'] = 'delete';
		$interview = new pikaInterview($interview_id);
		$interview_data = $interview->getValues();
		$a = array_merge($a,$interview_data);
		$template = new pikaTempLib('subtemplates/system-interviews.html',$a,'delete_interview');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-interviews.php\">Interview Manager</a> &gt;
					Delete Flag";
		$a['page_title'] = "Interview Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	case 'edit': 
		$a = array();
		$a['interview_id'] = $interview_id;
		$interview = new pikaInterview($interview_id);
		
		
		$a['action'] = 'update';
		$interview_data = $interview->getValues();
		$a = array_merge($a, $interview_data);
		
		$template = new pikaTempLib('subtemplates/system-interviews.html',$a,'edit_interview');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-interviews.php\">Interview Manager</a> &gt;
					Edit Interview";
		$a['page_title'] = "Interview Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		$interview->save();
		break;
	default:
		// Default view - list all flags
		$a = array();
		$menu_enable = array('0' => 'Enable', '1' => 'Disable');
		$interview_list = new plFlexList();
		$interview_list->template_file = 'subtemplates/system-interviews.html';
		$a['action'] = 'test';
		$result = pikaInterview::getInterviewsDB();
		
		while ($row = DBResult::fetchRow($result)) {
			
			$row['enable_text'] = 'Enable';
			if(isset($menu_enable[$row['enabled']])) {
				$row['enable_text'] = $menu_enable[$row['enabled']];
			}
			$interview_list->addRow($row);
		}
		
		
		
		$a['interview_listing'] = $interview_list->draw();
		
		$template = new pikaTempLib('subtemplates/system-interviews.html',$a,'default_interviews');
		
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					Interview Manager";
		$a['page_title'] = "Interview Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
}

pika_exit($buffer);

?>