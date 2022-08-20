<?php


function case_contacts_menu($field_name = null, $field_value = null, $menu_array = null, $args = null, $data_array = null)
{
	
	if (!is_array($menu_array))
	{
		$menu_array = array();
	}
	
	if (!is_array($data_array))
	{
		$data_array = array();
	}
	
	if(is_array($field_value)){
		$field_value = null;
	}
	
	if(!is_array($args)) {
		$args = array();
	}
	
	$def_args = array(
		// STD Directives
		'relation_code' => '',
		'show_atty' => false,
		'show_pba' => false
	);
	
	
	// Allow arg override
	
	$temp_args = pikaTempLib::getPluginArgs($def_args,$args);
	
	// Begin building menu
	
	$case_contacts_menu_output = '';
	
	$relation_codes = array();
	if(strlen($temp_args['relation_code']) > 0)
	{
		$tmp_codes = explode(',',$temp_args['relation_code']);
		$menu_relation_codes = pikaMenu::getMenu('relation_codes');
		foreach ($tmp_codes as $value)
		{
			if(in_array($value,array_keys($menu_relation_codes)))
			{
				$relation_codes[] = $value;
			}
		}
	}
	
	
	
	
	if(isset($data_array['case_id']) && is_numeric($data_array['case_id']))
	{
		require_once('pikaCase.php');
		$case = new pikaCase($data_array['case_id']);
		$result = $case->getContactsDb();
		while($row = DBResult::fetchRow($result))
		{
			if(count($relation_codes) < 1 || in_array($row['relation_code'],$relation_codes))
			{
				$menu_array[$row['contact_id']] = pikaTempLib::plugin('text_name','',$row);	
			}
			
		}
		
		// Load up Attorneys's
		if($temp_args['show_atty'])
		{
			
			$result = $case->getCaseAttorneysDB();
			while($row = DBResult::fetchRow($result))
			{
				$menu_array['atty'.$row['user_id']] = pikaTempLib::plugin('text_name','',$row);
			}
		}
		// Load up PBA's
		if($temp_args['show_pba'])
		{
			$result = $case->getCasePbAttorneysDB();
			while($row = DBResult::fetchRow($result))
			{
				$menu_array['pba'.$row['pba_id']] = pikaTempLib::plugin('text_name','',$row);
			}
		}
	}
	
	$case_contacts_menu_output .= pikaTempLib::plugin('menu',$field_name,$field_value,$menu_array,$args);
	
	return $case_contacts_menu_output;
}

?>