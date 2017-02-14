<?php
function javascript($file_name = null, $field_value = null, $menu_array = null, $args = null) {

	$javascript_output = '';
	
	// Ensure that the htmlTempLib data array was passed
	// Only needed if parse argument is set
	if (!is_array($field_value)) {
		$field_value = array();
	}
	
	if(!is_array($args)) {
		$args = array();
	}
	
	$def_args = array(
		// STD Directives
		'parse' => false,
		'script_tags' => true
	);
	
	// Allow arg override
	
	$temp_args = pikaTempLib::getPluginArgs($def_args,$args);
	
	// Begin building javascript
	
	// Locates requested javascript based on name of file
	if (is_null($file_name) || !$file_name) { // if no file_name specified return blank
		return $javascript_output;
	} 
	// Allow js file overload
	// Check to see if custom js file has been created
	$js_file_string = '';
	if(file_exists(pl_custom_directory() . "/js/{$file_name}")) {
		$js_file_string = file_get_contents(pl_custom_directory() . "/js/{$file_name}");
	} elseif (file_exists(getcwd() . "/js/{$file_name}")) {
		$js_file_string = file_get_contents(getcwd() . "/js/{$file_name}");
	}
	
	else 
	{
		$js_file_string = htmlspecialchars($file_name) . " not found";
	}
	
	// If the js file needs to be templated (usually for base_url) then run another template object
	if ($temp_args['parse'] === true) {
		$javascript_template = new pikaTempLib($js_file_string,$field_value);
		$javascript_output = $javascript_template->draw();
	} else {
		$javascript_output = $js_file_string;
	}
	
	
	// Add opening and closing declarations
	$js_open = $js_close = '';
	if($temp_args['script_tags']) {
		$js_open = "<script language=\"JavaScript\" type=\"text/javascript\"><!-- \n";
		$js_close = "   \n//--></script>";
	}
	$javascript_output = $js_open . $javascript_output . $js_close;
	
	return $javascript_output;



}
?>