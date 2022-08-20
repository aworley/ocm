<?php
require_once('pika-danio.php');

pika_init();

require_once('pikaTempLib.php');
require_once('pikaFlags.php');
require_once('plFlexList.php');


$base_url = pl_settings_get('base_url');

if (!pika_authorize("system", array()))
{
	$plTemplate["content"] = "Access denied";
	$plTemplate["nav"] = "<a href=\"{$base_url}/\">Pika Home</a> &gt; <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; Red Flag Manager";
	$template = new pikaTempLib('templates/default.html', $plTemplate);
	$buffer = $template->draw();
	pika_exit($buffer);
}

$buffer = '';
$action = pl_grab_get('action');
$flag_id = pl_grab_get('flag_id');
$rule_id = pl_grab_get('rule_id');
$name = pl_grab_get('name');
$description = pl_grab_get('description');
$enabled = pl_grab_get('enabled');
$field_name = pl_grab_get('field_name');
$comparison = pl_grab_get('comparison');
$value = pl_grab_get('value');
$and_rule_id = pl_grab_get('and_rule_id');
$and_field_name = pl_grab_get('and_field_name');
$and_comparison = pl_grab_get('and_comparison');
$and_value = pl_grab_get('and_value');

$case_id = pl_grab_get('case_id');


switch($action) {
	case 'enable':
		$flag = new pikaFlags($flag_id);
		if($flag->enabled) { $flag->enabled = 0; }
		else {$flag->enabled = 1;}
		$flag->save();
		header("Location: {$base_url}/system-red_flags.php");
		break;
	case 'add_and_conditional':
		$flag = new pikaFlags($flag_id);
		$rules = $flag->rules;
		if(isset($rules[$rule_id])) {
			if(isset($rules[$rule_id]['and']) && is_array($rules[$rule_id]['and'])) {
				$num_ands = count($rules[$rule_id]['and']);
				$rules[$rule_id]['and'][$num_ands] = array('and_field_name' => '', 'and_comparison' => '', 'and_value' => '');
			}
			else {
				$rules[$rule_id]['and'][] = array('and_field_name' => '', 'and_comparison' => '', 'and_value' => '');
			}
		}
		$flag->rules = $rules;
		$flag->save();
		header("Location: {$base_url}/system-red_flags.php?action=edit_rule&flag_id={$flag_id}&rule_id={$rule_id}");
		break;
	case 'update':
		$flag = new pikaFlags($flag_id);
		$flag->name = $name;
		$flag->description = $description;
		$flag->enabled = $enabled;
		$flag->save();
		header("Location: {$base_url}/system-red_flags.php?flag_id={$flag->flag_id}&action=edit");
		break;
	case 'update_rule':
		$flag = new pikaFlags($flag_id);
		$rules = $flag->rules;
		$rules[$rule_id]['field_name'] = $field_name;
		$rules[$rule_id]['comparison'] = $comparison;
		$rules[$rule_id]['value'] = $value;
		
		if(is_array($and_field_name)) {
			foreach ($and_field_name as $key => $val) {
				if(isset($rules[$rule_id]['and'][$key])) {
					$rules[$rule_id]['and'][$key]['and_field_name'] = $val;
				}
			}
		}if(is_array($and_comparison)) {
			foreach ($and_comparison as $key => $val) {
				if(isset($rules[$rule_id]['and'][$key])) {
					$rules[$rule_id]['and'][$key]['and_comparison'] = $val;
				}
			}
		}if(is_array($and_value)) {
			foreach ($and_value as $key => $val) {
				if(isset($rules[$rule_id]['and'][$key])) {
					$rules[$rule_id]['and'][$key]['and_value'] = $val;
				}
			}
		}
		$flag->rules = $rules;
		$flag->save();
		header("Location: {$base_url}/system-red_flags.php?action=edit&flag_id={$flag_id}");
		break;
	case 'delete':
		$delete = pl_grab_get('Delete');
		if($delete == 'Delete') {
			$flag = new pikaFlags($flag_id);
			$flag->delete();
		}
		header("Location: {$base_url}/system-red_flags.php");
		break;
	case 'delete_rule':
		$delete = pl_grab_get('Delete');
		if($delete == 'Delete') {
			$flag = new pikaFlags($flag_id);
			$rules = array();
			foreach ($flag->rules as $key => $val) {
				if($key != $rule_id) {
					$rules[] = $val;
				}
			}
			$flag->rules = $rules;
			$flag->save();
		}
		header("Location: {$base_url}/system-red_flags.php?action=edit&flag_id={$flag_id}");
		break;
	case 'delete_and_rule':
		$delete = pl_grab_get('Delete');
		if($delete == 'Delete') {
			$flag = new pikaFlags($flag_id);
			$rules = $flag->rules;
			$and_rules = array();
			foreach ($rules[$rule_id]['and'] as $key => $val) {
				if($key != $and_rule_id) {
					$and_rules[] = $val;
				}
			}
			$rules[$rule_id]['and'] = $and_rules;
			$flag->rules = $rules;
			$flag->save();
		}
		header("Location: {$base_url}/system-red_flags.php?action=edit_rule&flag_id={$flag_id}&rule_id={$rule_id}");
		break;
	case 'confirm_delete':
		$a = array();
		$a['action'] = 'delete';
		$flag = new pikaFlags($flag_id);
		$flag_data = $flag->getValues();
		$a = array_merge($a,$flag_data);
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'delete_flag');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Delete Flag";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	case 'confirm_delete_rule':
		$a = array();
		$a['action'] = 'delete_rule';
		$a['rule_id'] = $rule_id;
		if(!$rule_id) {
			$a['rule_id'] = '0';
		}
		$flag = new pikaFlags($flag_id);
		$flag_data = $flag->getValues();
		$a = array_merge($a,$flag_data);
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'delete_rule');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Delete Flag";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	case 'confirm_delete_and_rule':
		$a = array();
		$a['action'] = 'delete_and_rule';
		$a['and_rule_id'] = $and_rule_id;
		if(!$and_rule_id) {
			$a['and_rule_id'] = '0';
		}
		$a['rule_id'] = $rule_id;
		if(!$rule_id) {
			$a['rule_id'] = '0';
		}
		$flag = new pikaFlags($flag_id);
		$flag_data = $flag->getValues();
		$a = array_merge($a,$flag_data);
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'delete_and_rule');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Delete Flag";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	case 'edit': 
		$a = array();
		$a['flag_id'] = $flag_id;
		$flag = new pikaFlags($flag_id);
		
		if(is_numeric($flag_id)) {
			$rule_list = new plFlexList();
			$rule_list->flex_header_name = 'rules_header';
			$rule_list->flex_row_name = 'rules_row';
			$rule_list->flex_footer_name = 'rules_footer';
			$rule_list->template_file = 'subtemplates/system-red_flags.html';
		
			foreach ($flag->rules as $rule_id => $rule) {
				$rule['rule_id'] = $rule_id;
				$rule['flag_id'] = $flag_id;
				if(isset($rule['and'])) { unset($rule['and']); }
				$rule_list->addRow($rule);
			}
			
			$a['rules_listing'] = $rule_list->draw();
			$default_rules = new pikaTempLib('subtemplates/system-red_flags.html',$a,'default_rules');
			$a['default_rules'] = $default_rules->draw();
		}
		$a['action'] = 'update';
		$flag_data = $flag->getValues();
		$a = array_merge($a, $flag_data);
		
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'edit_flag');
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Edit Flag";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		$flag->save();
		break;
	case 'edit_rule': 
		$a = array();
		$flag = new pikaFlags($flag_id);
		$a['flag_id'] = $flag->flag_id;
		$a['action'] = 'update_rule';
		$fields_menu = pikaFlags::generateFields();
		
		
		$rules = $flag->rules;
		
		if(isset($rules[$rule_id]) && is_array($rules[$rule_id])) {
			$rule = $rules[$rule_id];
			$a = array_merge($a,$rule);
			$a['rule_id'] = $rule_id;
			$a['and_rules'] = '';
			if (isset($rule['and']) && is_array($rule['and'])) { // AND conditional exists
				$and_rules = $rule['and'];
				
				foreach ($and_rules as $key => $val) {
					$val['and_field_name'] = pikaTempLib::plugin('menu_groups',"and_field_name[{$key}]",$val['and_field_name'],$fields_menu);
					$val['and_comparison'] = pikaTempLib::plugin('menu',"and_comparison[{$key}]",$val['and_comparison'],pl_menu_get('comparison'));
					$val['and_value'] = pikaTempLib::plugin('input_text',"and_value[{$key}]",$val['and_value']);
					$val['and_rule_id'] = $key;
					$val = array_merge($a,$val);
					$template = new pikaTempLib('subtemplates/system-red_flags.html',$val,'and_rules');
					$a['and_rules'] .= $template->draw();
				}
			}
		}
		else {	
			$rule_count = count($rules);
			$a['rule_id'] = $rule_count;
		}
		
		
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'edit_rule');
		
		$template->addMenu('field_name',$fields_menu);
		$a['content'] = $template->draw();
		
		
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Edit Rule";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	case 'test':
		$a = array();
		
		$flags = pikaFlags::generateFlags($case_id);
		$a['content'] = '<h2>Red Flags for Case_id=' . $case_id . '</h2>';
		foreach ($flags as $flag) {
			$a['content'] .= pikaTempLib::plugin('red_flag','flag',$flag['description']);
		}
		
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					<a href=\"{$base_url}/system-red_flags.php\">Red Flag Manager</a> &gt;
					Test Flags";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
	default:
		// Default view - list all flags
		
		
		$a = array();
		$menu_enable = array('0' => 'Enable', '1' => 'Disable');
		$flag_list = new plFlexList();
		$flag_list->template_file = 'subtemplates/system-red_flags.html';
		$a['action'] = 'test';
		$result = pikaFlags::getFlagsDB();
		
		while ($row = DBResult::fetchRow($result)) {
			$row['enable_text'] = 'Enable';
			if(isset($menu_enable[$row['enabled']])) {
				$row['enable_text'] = $menu_enable[$row['enabled']];
			}
			$flag_list->addHtmlRow($row);
		}
		
		
		
		$a['flag_listing'] = $flag_list->draw();
		
		$template = new pikaTempLib('subtemplates/system-red_flags.html',$a,'default_flags');
		
		$a['content'] = $template->draw();
		$a['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
					<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt; 
					Red Flag Manager";
		$a['page_title'] = "Red Flag Manager";
		
		$default_template = new pikaTempLib('templates/default.html',$a);
		$buffer = $default_template->draw();
		break;
}

pika_exit($buffer);

?>