<?php
// 11-20-2013 - caw - added new report to support newly added lsac case tab
chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'LSAC Outcome Measures Report';
$report_name = "lsac_outcome";

$base_url = pl_settings_get('base_url');

if(!pika_report_authorize($report_name)) 
{
	$main_html = array();
	$main_html['base_url'] = $base_url;
	$main_html['page_title'] = $report_title;
	$main_html['nav'] = "<a href=\"{$base_url}/\">Pika Home</a>
    				  &gt; <a href=\"{$base_url}/reports/\">Reports</a> 
    				  &gt; $report_title";
	$main_html['content'] = "You are not authorized to run this report";

	$buffer = pl_template('templates/default.html', $main_html);
	pika_exit($buffer);
}

$report_format = pl_grab_post('report_format');
$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');
// $open_on_date = pl_grab_post('open_on_date');
$status = pl_grab_post('status');

$show_sql = pl_grab_post('show_sql');
$problem = pl_grab_post('problem');
$case_county = pl_grab_post('case_county');

if ('csv' == $report_format)
{
	require_once ('app/lib/plCsvReportTable.php');
	require_once ('app/lib/plCsvReport.php');
	$t = new plCsvReport();
}

else
{
	require_once ('app/lib/plHtmlReportTable.php');
	require_once ('app/lib/plHtmlReport.php');
	$t = new plHtmlReport();
}


// run the report

$clb = pl_date_mogrify($close_date_begin);
$cle = pl_date_mogrify($close_date_end);
// $ood = pl_date_mogrify($open_on_date);

$results_sql = "SELECT SUM(if(lsac_result_necessities IS NULL,1,if(lsac_result_necessities=0,1,0))) as 'no1',
               SUM(if(lsac_result_necessities=1,1,0)) as `yes1`,
               SUM(if(lsac_result_necessities=2,1,0)) as `na1`,
               SUM(if(lsac_result_creditors IS NULL,1,if(lsac_result_creditors=0,1,0))) as `no2`,
               SUM(if(lsac_result_creditors=1,1,0)) as `yes2`,
               SUM(if(lsac_result_creditors=2,1,0)) as `na2`,
               SUM(if(lsac_result_job IS NULL,1,if(lsac_result_job=0,1,0))) as `no3`,
               SUM(if(lsac_result_job=1,1,0)) as `yes3`,
               SUM(if(lsac_result_job=2,1,0)) as `na3`,
               SUM(if(lsac_result_housing IS NULL,1,if(lsac_result_housing=0,1,0))) as `no4`,
               SUM(if(lsac_result_housing=1,1,0)) as `yes4`,
               SUM(if(lsac_result_housing=2,1,0)) as `na4`,
               SUM(if(lsac_result_conditions IS NULL,1,if(lsac_result_conditions=0,1,0))) as `no5`,
               SUM(if(lsac_result_conditions=1,1,0)) as `yes5`,
               SUM(if(lsac_result_conditions=2,1,0)) as `na5`,
               SUM(if(lsac_result_safety IS NULL,1,if(lsac_result_safety=0,1,0))) as `no6`,
               SUM(if(lsac_result_safety=1,1,0)) as `yes6`,
               SUM(if(lsac_result_safety=2,1,0)) as `na6`,
               SUM(if(lsac_result_quality IS NULL,1,if(lsac_result_quality=0,1,0))) as `no7`,
               SUM(if(lsac_result_quality=1,1,0)) as `yes7`,
               SUM(if(lsac_result_quality=2,1,0)) as `na7`,
               SUM(1) as `total`
        FROM cases
		WHERE 1";		
	
$funding_sql = "SELECT SUM(if(lsac_protect_benefits,1,0)) as `yes1`,
				SUM(if(lsac_protect_benefits,if(lsac_protect_fed_benefit,IFNULL(lsac_protect_annual_benefits, 0) + IFNULL(lsac_protect_payment, 0),0),0)) as `protect_fed`,
				SUM(if(lsac_protect_benefits,if(lsac_protect_fed_benefit <> 1 
				         AND lsac_protect_state_benefit,IFNULL(lsac_protect_annual_benefits, 0) + IFNULL(lsac_protect_payment, 0),0),0)) as `protect_state`,
				 SUM(if(lsac_protect_benefits,if(lsac_protect_fed_benefit <> 1
                           AND lsac_protect_state_benefit <> 1
                           AND lsac_protect_child_support,IFNULL(lsac_protect_annual_benefits, 0) + IFNULL(lsac_protect_payment, 0),0),0)) as `protect_child`,
                  SUM(if(lsac_protect_benefits,if(lsac_protect_fed_benefit <> 1
                           AND lsac_protect_state_benefit <> 1
                           AND lsac_protect_child_support <> 1
                           AND lsac_protect_other,IFNULL(lsac_protect_annual_benefits, 0) + IFNULL(lsac_protect_payment, 0),0),0)) as `protect_other`,
     			SUM(if(lsac_obtain_benefits,1,0)) as `yes2`,
     			SUM(if(lsac_obtain_benefits,if(lsac_obtain_fed_benefit,IFNULL(lsac_obtain_annual_benefits, 0) + IFNULL(lsac_obtain_payment, 0),0),0)) as `obtain_fed`,
     			SUM(if(lsac_obtain_benefits,if(lsac_obtain_fed_benefit <> 1 
				         AND lsac_obtain_state_benefit,IFNULL(lsac_obtain_annual_benefits, 0) + IFNULL(lsac_obtain_payment, 0),0),0)) as `obtain_state`,
				SUM(if(lsac_obtain_benefits,if(lsac_obtain_fed_benefit <> 1
                           AND lsac_obtain_state_benefit <> 1
                           AND lsac_obtain_child_support,IFNULL(lsac_obtain_annual_benefits, 0) + IFNULL(lsac_obtain_payment, 0),0),0)) as `obtain_child`,
                 SUM(if(lsac_obtain_benefits,if(lsac_obtain_fed_benefit <> 1
                           AND lsac_obtain_state_benefit <> 1
                           AND lsac_obtain_child_support <> 1
                           AND lsac_obtain_other,IFNULL(lsac_obtain_annual_benefits, 0) + IFNULL(lsac_obtain_payment, 0),0),0)) as `obtain_other`                    
		FROM cases
		WHERE 1";

/*				
				SUM(if(lsac_protect_benefits=1,if(lsac_protect_state_benefit=1,lsac_protect_annual_benefits,0),0)) as `protect_state`,
				SUM(if(lsac_protect_benefits=1,if(lsac_protect_child_support=1,lsac_protect_annual_benefits,0),0)) as `protect_child`,
				SUM(if(lsac_protect_benefits=1,if(lsac_protect_other=1,lsac_protect_annual_benefits,0),0)) as `protect_other`
*/


// handle the crazy date range selection
$range1 = $range2 = "";

$sql = '';

$safe_clb = mysql_real_escape_string($clb);
$safe_cle = mysql_real_escape_string($cle);
$safe_ood = mysql_real_escape_string($ood);

if ($clb && $cle) 
{
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$range1 = "close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
}

elseif ($clb) 
{
	$t->add_parameter('Closed After',$close_date_begin);
	$range1 = "close_date >= '{$safe_clb}'";
}

elseif ($cle) 
{
	$t->add_parameter('Closed Before',$close_date_end);
	$range1 = "close_date <= '{$safe_cle}'";
}

if ($ood) 
{
	$t->add_parameter('Open On',$open_on_date);
	$range2 = "(open_date <= '{$safe_ood}' AND (close_date IS NULL OR close_date > '{$safe_ood}'))";
}

if ($ood) 
{
	if ($clb || $cle) 
	{
		$sql .= " AND (($range1) OR $range2)";
	} 
	
	else 
	{
		$sql .= " AND $range2";
	}
} 

else 
{
	if ($clb || $cle) 
	{
		$sql .= " AND $range1";
	}
}


// Other filters
$x = pl_process_comma_vals($close_code);
if ($x != false) 
{
	$t->add_parameter('Closing Code(s)',$close_code);
	$sql .= " AND close_code IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false) 
{
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND status IN $x";
}

$x = pl_process_comma_vals($problem);
if($x != false){
	$t->add_parameter('Problem Code(s)',$problem);
	$sql .= " AND problem IN $x";	
}

$x = pl_process_comma_vals($case_county);
if($x != false){
	$t->add_parameter('Case County(ies)',$case_county);
	$sql .= " AND case_county IN $x";	
}

// combine results sql & other filters
$results_sql .= $sql;
$funding_sql .= $sql;

$t->title = $report_title;
$t->set_table_title("Qualitative Statements - Because of my legal representation, my client ");
$t->display_row_count(false);
$t->set_header(array('Outcome Measure','Yes','No','N/A'));

// load the outcome measures array
$outcome = array();
$outcome['om1'] = "Has increased ability to pay for daily necessities";
$outcome['om2'] = "Is less likely to be harassed by creditors";
$outcome['om3'] = "Is in  better position to keep or find a job";
$outcome['om4'] = "Is in a better position to keep or find housing";
$outcome['om5'] = "Has improved housing conditions";
$outcome['om6'] = "Has increased safety";
$outcome['om7'] = "Has improved quality of life";

// load the benefits protected/obtained 
$benefits_funding = array();
$benefits_funding['b1'] = "Did your legal services <b>protect</b> money/benefits for the client?";
$benefits_funding['b2'] = "Did your legal services <b>recover</b> money/benefits for the client?";

$count_yes = 0;
$count_no = 0;
$count_na = 0;

$result = mysql_query($results_sql) or trigger_error();
// result should be a single row of totals
while ($row = mysql_fetch_assoc($result))
{
	$r = array();
	$count_yes = $row['yes1'];
	$count_no = $row['no1'];
	$count_na = $row['na1'];
	
	$r['outcome'] = $outcome['om1'];
	$r['yes'] = $row['yes1'];
	$r['no'] = $row['no1'];
	$r['na'] = $row['na1'];
		
	$t->add_row($r);

	$count_yes += $row['yes2'];
	$count_no += $row['no2'];
	$count_na += $row['na2'];

	$r['outcome'] = $outcome['om2'];
	$r['yes'] = $row['yes2'];
	$r['no'] = $row['no2'];
	$r['na'] = $row['na2'];	
	
	$t->add_row($r);

	$count_yes += $row['yes3'];
	$count_no += $row['no3'];
	$count_na += $row['na3'];
	
	$r['outcome'] = $outcome['om3'];
	$r['yes'] = $row['yes3'];
	$r['no'] = $row['no3'];
	$r['na'] = $row['na3'];	
	
	$t->add_row($r);

	$count_yes += $row['yes4'];
	$count_no += $row['no4'];
	$count_na += $row['na4'];
	
	$r['outcome'] = $outcome['om4'];
	$r['yes'] = $row['yes4'];
	$r['no'] = $row['no4'];
	$r['na'] = $row['na4'];	
	
	$t->add_row($r);

	$count_yes += $row['yes5'];
	$count_no += $row['no5'];
	$count_na += $row['na5'];

	$r['outcome'] = $outcome['om5'];
	$r['yes'] = $row['yes5'];
	$r['no'] = $row['no5'];
	$r['na'] = $row['na5'];	
	
	$t->add_row($r);

	$count_yes += $row['yes6'];
	$count_no += $row['no6'];
	$count_na += $row['na6'];
	
	$r['outcome'] = $outcome['om6'];
	$r['yes'] = $row['yes6'];
	$r['no'] = $row['no6'];
	$r['na'] = $row['na6'];	
	
	$t->add_row($r);

	$count_yes += $row['yes7'];
	$count_no += $row['no7'];
	$count_na += $row['na7'];

	$r['outcome'] = $outcome['om7'];
	$r['yes'] = $row['yes7'];
	$r['no'] = $row['no7'];
	$r['na'] = $row['na7'];	
	
	$t->add_row($r);
	
// add totals 
	$cases_evaluated = $row['total'];
	$r['outcome'] = '<div align="right">Total cases evaluated: <b>'.$row['total'].'</b>';
	$r['yes'] = $count_yes;
	$r['no'] = $count_no;
	$r['na'] = $count_na;
	
	$t->add_row($r);	
}

// $t->add_row($total);

if($show_sql) 
{
	$t->set_sql($results_sql);
}

// Add the Funding table
$t->add_table();
$t->set_table_title("Annual Benefits Plus Lump Sums");
$t->display_row_count(false);
$t->set_header(array('Funding','Yes','$ Federal','$ State','$ Child Support','$ Other'));

$result = mysql_query($funding_sql) or trigger_error();
// result should be a single row of totals
while ($row = mysql_fetch_assoc($result))
{
	$r=array();
	$r['benefits_funding'] = $benefits_funding['b1'];
	$r['yes'] = $row['yes1'];
	$r['fed'] = $row['protect_fed'];
	$r['state'] = $row['protect_state'];
	$r['child'] = $row['protect_child'];
	$r['other'] = $row['protect_other'];
	
	$t->add_row($r);
	
	$r['benefits_funding'] = $benefits_funding['b2'];
	$r['yes'] = $row['yes2'];
	$r['fed'] = $row['obtain_fed'];
	$r['state'] = $row['obtain_state'];
	$r['child'] = $row['obtain_child'];
	$r['other'] = $row['obtain_other'];
	
	$t->add_row($r);
}


if($show_sql) 
{
	$t->set_sql($funding_sql);
}



$t->display();	
exit();

?>
