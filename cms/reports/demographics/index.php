<?php

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


chdir('../../');

require_once('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');

$report_title = "Demographics Report";
$report_name = "demographics";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) {
	$main_html = array();
	$main_html['base_url'] = $base_url;
	$main_html['page_title'] = $report_title;
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
    				  &gt; <a href=\"{$base_url}/reports/\">Reports</a> 
    				  &gt; $report_title";
	$main_html['content'] = "You are not authorized to run this report";

	$default_template = new pikaTempLib('templates/default.html', $main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}


$current_month = date('n');
$current_year = date('Y');

$month_last_day = array ('1' => '31',
				'2' => '28',
				'3' => '31',
				'4' => '30',
				'5' => '31',
				'6' => '30',
				'7' => '31',
				'8' => '31',
				'9' => '30',
				'10' => '31',
				'11' => '30',
				'12' => '31');

if(($current_year % 4) == 0) {
	$month_last_day['2'] = '29';
}

$a = array();
$a['report_name'] = $report_name;

$a['close_date_begin'] = "{$current_month}/1/{$current_year}";		
$a['close_date_end'] = "{$current_month}/{$month_last_day[$current_month]}/{$current_year}";



$main_html = array();
$main_html['base_url'] = $base_url;
$main_html['page_title'] = $report_title;
$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
      &gt; <a href=\"{$base_url}/reports/\">Reports</a> &gt; $report_title";
$template = new pikaTempLib("reports/{$report_name}/form.html", $a);
$main_html['content'] = $template->draw();


$default_template = new pikaTempLib('templates/default.html', $main_html);
$buffer = $default_template->draw();
pika_exit($buffer);


?>
