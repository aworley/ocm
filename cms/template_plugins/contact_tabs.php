<?php

function contact_tabs($field_name = null, $field_value = null, $menu_array = null, $args = null)
{
	
	if (!is_array($menu_array)){
		$menu_array = array();
	}if(!is_array($field_value)) {
		$field_value = array();
	}if(!is_array($args)) {
		$args = array();
	}
	
	$def_args = array(
		// JS Directives
		'onclick' => '', 
		// Debug Directives
		'url' => '',
		'view' => 'enabled'
	);
	
	// Allow arg override
	
	$temp_args = pikaTempLib::getPluginArgs($def_args,$args);
	
	$base_url = pl_settings_get('base_url');
	
	
	
	$contact_tabs = array();
	$menu_array = array(
		array('file' => 'contact.php', 'name' => 'General', 'screen' => '', 'tab_row' => '1', 'enabled' => '1'),
		array('file' => 'contact.php', 'name' => 'Pension Info', 'screen' => 'pension', 'tab_row' => '1', 'enabled' => '1'),
		array('file' => 'contact.php', 'name' => 'Files', 'screen' => 'files','tab_row' => '1', 'enabled' => '1')
	);
	
	
	foreach ($menu_array as $key => $tab)
	{
		
		//print_r($tab);
		if(!$temp_args['view'] || ($tab['enabled'] && $temp_args['view'] == 'enabled') ||  (!$tab['enabled'] && $temp_args['view'] == 'disabled')) {
			$current = '';
			if ($tab['screen'] == "{$field_name}"){
				$current = ' id=current';
			}
			
			$onclick = '';
			if(strlen($temp_args['onclick']) > 0) {
				$onclick .= $temp_args['onclick'];
			}
			
			if(!isset($contact_tabs[$tab['tab_row']])) { $contact_tabs[$tab['tab_row']] = ''; }
			$contact_tabs[$tab['tab_row']] .= "<li{$current}>";
			
			
			if($temp_args['url']) {
				$contact_tabs[$tab['tab_row']] .= "<a href=\"{$temp_args['url']}screen={$tab['screen']}\" onClick=\"{$onclick}\">{$tab['name']}</a>";
			} else {
				$contact_tabs[$tab['tab_row']] .= "<a href=\"{$base_url}/{$tab['file']}?screen={$tab['screen']}&contact_id={$field_value['contact_id']}&case_id={$field_value['case_id']}&number={$field_value['number']}\" onClick=\"{$onclick}\">{$tab['name']}</a>";				
			}
			$contact_tabs[$tab['tab_row']] .= "</li>\n";
		}
	}
	ksort($contact_tabs);
	$contact_tabs_html = "<ul>";
	$contact_tabs_html .= implode("</ul>\n<ul>",$contact_tabs);
	$contact_tabs_html .= "</ul>";
	
	
	return $contact_tabs_html;
	
}