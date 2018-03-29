<?php

require_once('pikaMisc.php');
$pba_array = pikaMisc::fetchPbAttorneyArray();
$pba_fields = array('pba_id1','pba_id2', 'pba_id3');


$a = array();

foreach ($pba_fields as $field) {
	// Check if value exists
	$a[$field."_link"] = "Assign Pro Bono Attorney";
	if(isset($case_row[$field]) && $case_row[$field]) {
		$a[$field."_link"] = "Change";
		$a[$field."_time"] = "<a href=\"{$base_url}/activity.php?case_id={$case_id}&pba_id={$case_row[$field]}&act_type=T\">
							<img height=\"20px\" width=\"20px\" src=\"{$base_url}/images/time_add.png\" alt=\"Record Time\"/>
							</a>";
		// 2013-06-27 AMW & CAW
		$a[$field."_remove_link"] = "&nbsp;[<a href=\"{$base_url}/assign_pba.php?action=assign_pba&field={$field}&screen=pb&case_id={$case_id}\">Remove</a>]";
	} 
}

$a['lsc_close_code_menu'] = pikaTempLib::plugin('lsc_close_code','close_code',$case_row);

$a = array_merge($a,$case_row);
$a['current_date'] = date('n/d/Y');

$pb_template = new pikaTempLib('subtemplates/case-pb.html',$a);
$pb_template->addMenu('pb_attorneys',$pba_array);
$C .= $pb_template->draw();

	
	

