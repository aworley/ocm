<?php

require_once('template_plugins/radio.php');

$C .= '<h2 class="hdt">2017 Justice Gap Data Collection</h2>
<form action="' . pl_settings_get('base_url') . '/ops/update_case.php" method="post" name="ws"><p>';
$C .= radio('lsc_justice_gap', $case_row['lsc_justice_gap'], pl_menu_get('lsc_justice_gap'));
$C .= '</p><input type="hidden" name="case_id" value="' . $case_row['case_id'] . '">';
$C .= '<input type="hidden" name="screen" value="gap">';
$C .= '<input type="submit" name="update_case" value="Save" tabindex=1 class="save" accesskey="s">';
$C .= '</form>';

?>
