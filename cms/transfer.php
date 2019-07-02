<?php

/***************************/
/* Pika CMS (C) 2011       */
/* Pika Software, LLC.     */
/* http://pikasoftware.com */
/***************************/

require_once('pika-danio.php');

pika_init();

require_once('plFlexList.php');
require_once('pikaTempLib.php');
require_once('pikaCase.php');
require_once('pikaTransferOption.php');

// Variables

$main_html = $html = array();  // Template values.

$transfer_option_id = pl_grab_get('transfer_option_id');
$case_id = pl_grab_get('case_id');
$action = pl_grab_get('action');
$base_url = pl_settings_get('base_url');

$case = new pikaCase($case_id);
$case_number = 'No Case #';
if(strlen($case->number) > 0)
{
	$case_number = $case->number;
}

$html['case_id'] = $case_id;
$html['case_number'] = $case_number;


switch ($action) {
	case 'pika':
		$transfer_opt = new pikaTransferOption($transfer_option_id);
		$html = array_merge($html, $transfer_opt->getValues());
		$template = new pikaTempLib('subtemplates/transfer.html', $html, 'pika_menu');
		$main_html['content'] = $template->draw();
		break;
	default:
		$menu_transfer_mode = pl_menu_get('transfer_mode');
		$option_list = new plFlexList();
		$option_list->template_file = 'subtemplates/transfer.html';
		$result = pikaTransferOption::getTransferOptionDB();
		while ($row = DBResult::fetchRow($result)) {
			$row['case_id'] = $case_id;
			$row['case_number'] = $case_number;
			// TODO - Need to find a better way to do this - perhaps the menu_transfer_mode
			//        values should be the action names.
			if($row['transfer_mode'] == 1) { // Pika->Pika
				$row['action'] = 'pika';
			}
			$row['transfer_mode'] = pl_array_lookup($row['transfer_mode'],$menu_transfer_mode);
			$option_list->addRow($row);
		}
		$html['option_list'] = $option_list->draw();
		$template = new pikaTempLib('subtemplates/transfer.html',$html,'main_menu');
		$main_html['content'] = $template->draw();
		break;
}


$main_html['page_title'] = $page_title = "Case Transfer";
$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/case.php?case_id={$case_id}\">{$case_number}</a> &gt; 
					{$page_title}";

$default_template = new pikaTempLib('templates/default.html', $main_html);
$buffer = $default_template->draw();

pika_exit($buffer);

?>
