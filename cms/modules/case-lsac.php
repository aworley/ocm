<?php
// 11-20-2013 - caw - LSAC project, new case tab
$a = array();
$a['screen'] = 'lsac';

$a = array_merge($case_row,$a);


$case_info_template = new pikaTempLib('subtemplates/case-lsac.html',$a);
$C .= $case_info_template->draw();

?>
