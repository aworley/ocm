<?php
/**
* user_list (replaces deprecated megareport user_list.php)
*
* @author Matthew <matt@pikasoftware.com>;
* @version 1.0
* @package Danio
* @return string list of user names as checkboxes
* @param string $field_name - NOT USED
* @param string $field_value - NOT USED
* @param array $menu_array - NOT USED
* @param array $args - NOT USED
* 
**/

function user_list($field_name = null, $field_value = null, $menu_array = null, $args = null)
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
	
	$def_args = array(
		// STD Directives
		'enabled' => false
	);
	
	$temp_args = pikaTempLib::getPluginArgs($def_args,$args);
	
	require_once('pikaUser.php');
	
	// Begin building menu
	$user_list_output = '';
	$filter = array();
	if($temp_args['enabled']) {
		$filter['enabled'] = 1;
	}
	$row_count = 0;
	$result = pikaUser::getUsers($filter,$row_count,'name','ASC',0,0);
	while ($row = DBResult::fetchRow($result))
	{
		$staff_array[$row['user_id']] = pikaTempLib::plugin('text_name','',$row,'',array('order=last','no_extra','no_middle'));
	}
	
	$user_list_output = pikaTempLib::plugin('checkbox_list',$field_name,$field_value,$staff_array,$args);
	
	return $user_list_output;
}

?>