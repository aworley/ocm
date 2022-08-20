<?php
/**
* pba_list - similar to megareport user_list
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

function pba_list($field_name = null, $field_value = null, $menu_array = null, $args = null)
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
	);
	
	$temp_args = pikaTempLib::getPluginArgs($def_args,$args);
	
	require_once('pikaPbAttorney.php');
	
	// Begin building menu
	$pba_list_output = '';
	$pba_array = array();
	$row_count = 0;
	$result = pikaPbAttorney::getPbAttorneys(array(),$row_count,'atty_name','ASC',0,0);
	while ($row = DBResult::fetchRow($result))
	{
		$pba_array[$row['pba_id']] = pikaTempLib::plugin('text_name','',$row,'',array('order=last','no_extra','no_middle'));
	}
	
	$pba_list_output = pikaTempLib::plugin('checkbox_list',$field_name,$field_value,$pba_array,$args);
	
	return $pba_list_output;
}

?>