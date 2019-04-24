<?php
/**
* field_list (replaces deprecated megareport field_list.php)
*
* @author Matthew <matt@pikasoftware.com>;
* @version 1.0
* @package Danio
* @return string list of database fields w/ checkboxes
* @param string $field_name - Name of table (cases,contacts,activities)
* @param string $field_value - NOT USED
* @param array $menu_array - NOT USED
* @param array $args - NOT USED
* 
**/

function field_list($field_name = null, $field_value = null, $menu_array = null, $args = null)
{
	
	if (!is_array($menu_array))
	{
		$menu_array = array();
	}
	if(is_array($field_value)){
		$field_value = null;
	}
	
	if(!is_array($args)) {
		$args = array();
	}
	
	// Begin building menu
	$field_list_output = '';
	
	$allowed_tables = array('activities','cases','contacts');
	if(!in_array($field_name,$allowed_tables)) {
		$field_name = 'cases';
	}
	$safe_field_name = DB::escapeString($field_name);
	
	$menu_table_annotation = pikaTempLib::getMenu('annotate_'.$field_name);

	$sql = "DESCRIBE {$safe_field_name}";
	$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
	$field_list = array();
	while ($row = DBResult::fetchRow($result)) {
		$field = $row['Field'];
		if(isset($menu_table_annotation[$field])) {
			$label = $menu_table_annotation[$field];
		} else {
			$label = $field;
		}
		$field_list_output .= pikaTempLib::plugin('checkbox',"{$field_name}.{$field}",0,array(),array("onclick=update('$field_name.$field','$label');","label=$label"));	
	}
	
	return $field_list_output;
}

?>