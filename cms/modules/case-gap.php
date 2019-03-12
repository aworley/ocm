<?php

require_once('template_plugins/radio.php');

$C .= '<h2 class="hdt">2017 Justice Gap Data Collection</h2>
<form action="' . pl_settings_get('base_url') . '/ops/update_case.php" method="post" name="ws"><p>';
$C .= radio('justice_gap_2017', $case_row['justice_gap_2017'], pl_menu_get('justice_gap_2017'));
$C .= '</p><input type="hidden" name="case_id" value="' . $case_row['case_id'] . '">';
$C .= '<input type="hidden" name="screen" value="gap">';
$C .= '<input type="submit" name="update_case" value="Save" tabindex=1 class="save" accesskey="s">';
$C .= '</form>';

?>
