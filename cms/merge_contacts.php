<?php 

/*****************************************/
/* Pika CMS (C) 2008 Matthew Friedlander */
/* http://pikasoftware.com               */
/*****************************************/

require_once('pika-danio.php');
pika_init(); 
require_once('pikaContact.php');
require_once('plFlexList.php');
require_once('pikaTempLib.php');

$buffer = '';
$a = $main_html = array();

$base_url = pl_settings_get('base_url');

$contact_id = pl_grab_get('contact_id');
$action = pl_grab_get('action');
$offset = pl_grab_get('offset');
$merge_these = pl_grab_get('merge_these');
//$letter = pl_grab_get('letter');

switch ($action) {
	case 'merge':
		if(is_array($merge_these)) {
			$contact = new pikaContact($contact_id);
			foreach ($merge_these as $selected_contact_id) {
				if(is_numeric($selected_contact_id) && !$contact->is_new) { // Ensure a number is passed and the record isn't new
					
					if(!$contact->merge($selected_contact_id)) {
						die('An error occured during the merge');
					}
				}
			}
		}
		
		
		
	default:
		$contact = new pikaContact($contact_id);
		$result = $contact->metaphoneContactCheck();
		
		$contact_list = new plFlexList();
		$contact_list->template_file = 'subtemplates/merge_contacts.html';
		
		if(DBResult::numRows($result) > 0) {
			$i = 2;
			$row = $contact->getValues();
			$row['row_class'] = $i;	
			$row['selected_checkbox'] = '';
			$row['full_name'] = pikaTempLib::plugin('text_name','contact_name',$row,null,array('order=last'));
			$row['full_address'] = pikaTempLib::plugin('text_address','full_address',$row,null,array('output=html'));
			$row['full_phone'] = pikaTempLib::plugin('text_phone','phone',$row,null,array('notes'));
			$row['full_alt_phone'] = pikaTempLib::plugin('text_phone','alt_phone',$row,null,array('area_code=area_code_alt','phone=phone_alt','notes'));
			$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
			$contact_list->addHtmlRow($row);
			$i = 1;
			while ($row = DBResult::fetchRow($result)) {
				$row['row_class'] = $i;
				if ($i > 1){$i = 1;}
				else {$i++;}
				$row['selected_checkbox'] = pikaTempLib::plugin('checkbox','merge_these[]',$row['contact_id'],null,array('no_hidden',"default_value={$row['contact_id']}"));
				$row['full_name'] = pikaTempLib::plugin('text_name','contact_name',$row,null,array('order=last'));
				$row['full_address'] = pikaTempLib::plugin('text_address','full_address',$row,null,array('output=html'));
				/*if($row['address2']) {
					$row['full_address'] .= "&nbsp;&nbsp;" . $row['address2'];
				}*/
				$row['full_phone'] = pikaTempLib::plugin('text_phone','phone',$row,null,array('notes'));
				$row['full_alt_phone'] = pikaTempLib::plugin('text_phone','alt_phone',$row,null,array('area_code=area_code_alt','phone=phone_alt','notes'));
				$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
				$contact_list->addHtmlRow($row);
			}
			
		} 
		$a['contact_list'] = $contact_list->draw();
		
		
		$a['contact_id'] = $contact_id;
		$a['contact_name'] = pikaTempLib::plugin('text_name','contact_name',$contact->getValues());
		$template = new pikaTempLib('subtemplates/merge_contacts.html', $a);
		
		
		$main_html['content'] = $template->draw();
		$main_html['page_title'] = 'Merge Duplicate Contacts';
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/contact.php?contact_id={$contact_id}\">
					 {$a['contact_name']}
					 </a> &gt;
					 Merge Duplicate Contacts";
		
}




$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>

