<?php 

/**********************************/
/* Pika CMS (C) 2006 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once('pika-danio.php');

pika_init();

require_once('plFlexList.php');
require_once('pikaTransferOption.php');


// Variables

$main_html = array();  // Values for the main HTML template.
$main_html['page_title'] = "Transfer Options"; // Page Title
$base_url = pl_settings_get('base_url');
$action = pl_grab_post('action');
$transfer_option_id = pl_grab_post('transfer_option_id');

// Make sure User has system rights to change options

if (!pika_authorize("system", array()))
{
	$plTemplate["content"] = "Access denied";
	$plTemplate["nav"] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; {$main_html['page_title']}";

	$buffer = pl_template($plTemplate, 'templates/default.html');
	pika_exit($buffer);
}


// Main Code
switch ($action) {
	case 'add':
		$row['op_hdr'] = "New Agency";
		$row['action'] = 'update';
		$row['op_text'] = 'Add New';
		$options_html['edit_option'] = pl_template('subtemplates/transfer_options.html',$row,'edit_option');
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; 
						<a href=\"{$base_url}/transfer_options.php\">
						{$main_html['page_title']}</a> &gt;
						Add New Agency";
		break;
	case 'edit':
		$tx_option = new pikaTransferOption($transfer_option_id);
		$row = $tx_option->getValues();
	
		$row['op_hdr'] = "Edit Agency";
		$row['action'] = 'update';
		$row['op_text'] = 'Update';
	
		$options_html['edit_option'] = pl_template('subtemplates/transfer_options.html',$row,'edit_option');
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; 
						<a href=\"{$base_url}/transfer_options.php\">
						{$main_html['page_title']}</a> &gt;
						Edit Agency";
		break;
	case 'delete':
		$tx_option = new pikaTransferOption($transfer_option_id);
		$tx_option->delete();
		header("Location: {$base_url}/transfer_options.php");
		break;
	case 'update':
		if(!$transfer_option_id) {$transfer_option_id = null; }
		$tx_option = new pikaTransferOption($transfer_option_id);
		$tmp['label'] = pl_grab_post('label');
		$tmp['url'] = pl_grab_post('url');
		$tmp['transfer_mode'] = pl_grab_post('transfer_mode');
		$tmp['user'] = pl_grab_post('user');
		$tmp['password'] = pl_grab_post('password');
		$tx_option->setValues($tmp);
		$tx_option->save();
		header("Location: {$base_url}/transfer_options.php");
		break;
	default:
		pl_menu_get('transfer_mode');
		$options_list = new plFlexList();
		$options_list->template_file = 'subtemplates/transfer_options.html';

		$result = pikaTransferOption::getTransferOptionDB();
		while ($row = DBResult::fetchRow($result)) {
			if ($row['transfer_mode']) {
				$row['transfer_mode'] = $plMenus['transfer_mode'][$row['transfer_mode']];
			} else {$row['transfer_mode'] = '';}
			$options_list->addRow($row);
		}
		$options_html['flex_header'] = "<h2 class=\"hdt\">Options Listing</h2>\n" . $options_list->draw();
		$options_html['new_option'] = pl_template('subtemplates/transfer_options.html',array(),'new_option');
		$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; {$main_html['page_title']}";
}

$main_html['content'] = pl_template('subtemplates/transfer_options.html', $options_html);
$buffer = pl_template('templates/default.html', $main_html);
pika_exit($buffer);

?>
