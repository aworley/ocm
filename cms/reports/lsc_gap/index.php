<?php

chdir('../../');

require_once('pika-danio.php');
pika_init();
require_once('pikaTempLib.php');

$report_title = 'LSC Justice Gap Report';
$report_name = "lsc_gap";

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

$a = array();
$a['report_name'] = $report_name;
$a['open_date_begin'] = '3/6/2017';
$a['open_date_end'] = $a['open_on_date']= '4/14/2017';
$a['undup'] = '';

$main_html = array();
$main_html['page_title'] = $report_title;
$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a>
      &gt; <a href=\"{$base_url}/reports\">Reports</a> &gt; $report_title";
$template = new pikaTempLib("reports/{$report_name}/form.html", $a);
$main_html['content'] = $template->draw();


$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);


