<?php


// get the staff array
$staff_array = pikaMisc::fetchStaffArray();

// generate template values.
$a['screener_name'] = pl_array_lookup($case_row['intake_user_id'], $staff_array);

$a['lsc_problem_menu'] = pikaTempLib::plugin('lsc_problem','problem',$case_row,null,array('onchange=problem_code_lookup(this.value)'));
$a['lsc_close_code_menu'] = pikaTempLib::plugin('lsc_close_code','close_code',$case_row);


$a = array_merge($case_row, $a);

$a['current_date'] = date('m/d/Y');
$a['created'] = pl_timestamp_unmogrify($a['created']);
$a['last_changed'] = pl_timestamp_unmogrify($a['last_changed']);

$case_lsc_compliance_template = new pikaTempLib('subtemplates/case-lsc-compliance.html',$a);
$C .= $case_lsc_compliance_template->draw();

?>
