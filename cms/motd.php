<?php 


/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once ('pika-danio.php'); 

pika_init();

require_once('pikaMotd.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');

$base_url = pl_settings_get('base_url');
$buffer = '';

if (!pika_authorize('motd', array()))
{
	
	$plTemplate["page_title"] = "Message Board";
	$plTemplate['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt;
						<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
						Message Board";
	$plTemplate["content"] = 'access denied';

	$default_template = new pikaTempLib('templates/default.html',$plTemplate);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$action = pl_grab_get('action');
$motd_id = pl_grab_get('motd_id');


switch ($action)
{
	case 'add_motd':
		$newMotd = new pikaMotd();
		require_once('pikaUser.php');
		$user = new pikaUser($auth_row['user_id']);
		$tmp['full_name'] = pl_text_name($user->getValues());
	
		$newMotd->setValues($tmp);
		$newMotd->save();
		$motd_id = $newMotd->motd_id;
	case 'edit':
		$motd = new pikaMotd($motd_id);
		$a = $motd->getValues();
		$a['base_url'] = $base_url;
		$template = new pikaTempLib('subtemplates/motd.html',$a,'edit_motd');
		$plTemplate['content'] = $template->draw();
		
	
	break;

	case 'update_motd':
		$Motd = new pikaMotd($motd_id);
		$tmp['content'] = pl_grab_get('content');
		$tmp['user_id'] = pl_grab_get('user_id');
		$tmp['title'] = pl_grab_get('title');
		$tmp['full_name'] = pl_grab_get('full_name');
		
		$Motd->setValues($tmp);
		$Motd->save();
		header("Location:{$base_url}/motd.php");
	
	break;
	
	case 'confirm_delete':
		$a['motd_id'] = $motd_id;
		$a['base_url'] = $base_url;
		$template = new pikaTempLib('subtemplates/motd.html',$a,'confirm_delete');
		$plTemplate['content'] = $template->draw();
	break;

	case 'delete':
		$Motd = new pikaMotd($motd_id);
		$Motd->delete();
		$Motd->save();
		header("Location:{$base_url}/motd.php");

	break;
	
	
	
	default:
		$result = pikaMotd::getMotdDB();
		
		$motd_list = new plFlexList();
		$motd_list->template_file = 'subtemplates/motd.html';
		$num_rows = DBResult::numRows($result);
		while ($row = DBResult::fetchRow($result)) {
				
				$row['content'] = pl_html_text($row['content']);
				$row['staff_name'] = pl_text_name($row);
				$row['num_rows'] = $num_rows;
				$row['created'] = date("m/d/Y", pl_mysql_timestamp_to_unix($row['created']));
				$motd_list->addHtmlRow($row);
		}
		
		$main_html['motd_list'] = $motd_list->draw();
		$main_html['num_rows'] = $num_rows;
		$template = new pikaTempLib('subtemplates/motd.html',$main_html,'view_motd');
		$plTemplate['content'] = $template->draw();
		
	
	break;
}


$plTemplate["page_title"] = "Message Board";
$plTemplate['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; Message Board";

$default_template = new pikaTempLib('templates/default.html',$plTemplate);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
