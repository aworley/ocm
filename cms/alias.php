<?php 

/**********************************/
/* Pika CMS (C) 2010 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('pika-danio.php');

pika_init();

require_once('pikaContact.php');
require_once('pikaAlias.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');

$base_url = pl_settings_get('base_url');

$contact_id = pl_grab_get('contact_id');
$alias_id = pl_grab_get('alias_id');
$action = pl_grab_get('action');

$first_name = pl_grab_get('first_name');
$middle_name = pl_grab_get('middle_name');
$last_name = pl_grab_get('last_name');
$extra_name = pl_grab_get('extra_name');
$ssn = pl_grab_get('ssn');
$alias_description = pl_grab_get('alias_description');


$contact = new pikaContact($contact_id);
$contact_full_name = pikaTempLib::plugin('text_name','',$contact->getValues(),array(),array('order=last'));


switch ($action) {
	case 'edit':
		$alias_row = array();
		$alias_row['contact_id'] = $contact_id;
		if(is_numeric($alias_id)) {
			$alias = new pikaAlias($alias_id);
			$alias_row = $alias->getValues();
		}
		
		else
		{
			$alias_row['alias_description'] = 1; // "Alias"
		}
		
		$template = new pikaTempLib('subtemplates/alias.html',$alias_row,'edit');
		$main_html['content'] = $template->draw();
		break;
	case 'confirm_delete':
		$alias_row = array();
		$alias_row['contact_id'] = $contact_id;
		if(is_numeric($alias_id)) {
			$alias = new pikaAlias($alias_id);
			$alias_row = $alias->getValues();
			
			$alias_row['alias_name'] = pikaTempLib::plugin('text_name','',$alias_row);
			if(strlen($alias_row['ssn']) < 1) {
				$alias_row['ssn'] = "&lt;None Entered&gt;";
			}
			
		}
		
		$template = new pikaTempLib('subtemplates/alias.html',$alias_row,'confirm_delete');
		$main_html['content'] = $template->draw();
		break;
	case 'update':
		
		$alias = new pikaAlias($alias_id);
		$alias->contact_id = $contact_id;
		$alias->first_name = $first_name;
		$alias->middle_name = $middle_name;
		$alias->last_name = $last_name;
		$alias->extra_name = $extra_name;
		$alias->ssn = $ssn;
		// For programs that do not use the alias description feature, this 
		// simply returns false.
		$alias->alias_description = $alias_description;
		$alias->save();
		
		header("Location: {$base_url}/alias.php?contact_id={$contact_id}");
		break;
	case 'delete':
		$cancel = pl_grab_get('cancel');
		if(is_numeric($alias_id) && strlen($cancel) < 1) {
			$alias = new pikaAlias($alias_id);
			$alias->delete();
		}
		header("Location: {$base_url}/alias.php?contact_id={$contact_id}");
		break;
	default:
		$aliases = array();
		$result = $contact->getAliasesDb();
		$alias_list = new plFlexList();
		$alias_list->template_file = 'subtemplates/alias.html';
		while ($row = DBResult::fetchRow($result)) {
			if($row['primary_name'] != 1) {
				$row['alias_name'] = pikaTempLib::plugin('text_name','',$row);
				$alias_list->addRow($row);
			}
		}
		$a = array();
		$a['contact_id'] = $contact_id;
		$a['contact_full_name'] = $contact_full_name;
		$a['alias_list'] = $alias_list->draw();
		
		$template = new pikaTempLib('subtemplates/alias.html',$a,'view');
		$main_html["content"] = $template->draw();
		break;
}




$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/contact.php?contact_id={$contact_id}\">$contact_full_name</a> &gt; 
					 Contact Alias";
$main_html['page_title'] = "Contact Alias";


$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();

pika_exit($buffer);

?>
