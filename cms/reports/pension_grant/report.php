<?php

/**********************************/
/* Pika CMS (C) 2007 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

require_once('pikaCase.php');
require_once('pikaTempLib.php');

$report_title = "Pension - Grant Reporting";
$report_name = "pension_grant";

$base_url = pl_settings_get('base_url');
if(!pika_report_authorize($report_name)) {
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
$report_output = pl_grab_post('report_output',3);
$redact = pl_grab_post('redact');


$menu_report_output = array('1'=>'Stats Information Only',"2"=>'Detail Information Only','3'=>'Summary & Detail Information');

$menu_pension_issues = pl_menu_get('pension_issue');


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

$t->add_parameter('Report Output',pl_array_lookup($report_output,$menu_report_output));
$t->add_parameter('Redact Client Names',$redact ? 'Yes' : 'No');


$date_start = pl_grab_post('date_start');
$date_end = pl_grab_post('date_end');

$show_sql = pl_grab_post('show_sql');


$safe_ds = mysql_real_escape_string(pl_date_mogrify($date_start));
$safe_de = mysql_real_escape_string(pl_date_mogrify($date_end));


$where_sql = '';

if ($date_start && $date_end) {
	$t->add_parameter('Open Date Between',$date_start . " - " . $date_end);
	$where_sql = " AND cases.open_date >= '{$safe_ds}' AND cases.open_date <= '{$safe_de}'";
}
elseif (strtotime($date_start) > strtotime($date_end)) 
{
	echo "Invalid Date range";
	exit();
}
else
{
	echo "You must select the beginning and ending date for the reporting period";
	exit();
}




// run the report
$t->title = $report_title;

if(in_array($report_output,array(1,3)))
{
	$t->set_table_title('Summary: Number of Cases');
	$t->set_header(array("# Cases"));
	$t->display_row_count(false);
	
	$sql = "SELECT COUNT(DISTINCT case_id) as cases FROM cases WHERE 1{$where_sql}";
	$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());
	
	$row = mysql_fetch_assoc($result);
	$t->add_row($row);
	
	if($show_sql) {
		$t->set_sql($sql);
	}
	
	$t->add_table();
	$t->set_table_title("Summary: Types of Pension Legal Issues");
	$t->set_header(array("Pension Issue","#"));
	$t->display_row_count(false);
	
	$sql = "SELECT IFNULL(issue_type_lbl,'No Pension Issue Entered') AS issue_type_lbl, SUM(issue_type) AS issue_type_count
			FROM
			((SELECT
			menu_pension_issue.label as issue_type_lbl,
			COUNT(*) as issue_type
			FROM cases 
			LEFT JOIN menu_pension_issue ON menu_pension_issue.value = cases.issue1
			WHERE 1{$where_sql} AND issue1 IS NOT NULL
			GROUP BY issue1
			)
			UNION
			(SELECT
			menu_pension_issue.label as issue_type_lbl,
			COUNT(*) as issue_type
			FROM cases 
			LEFT JOIN menu_pension_issue ON menu_pension_issue.value = cases.issue2
			WHERE 1{$where_sql} AND issue2 IS NOT NULL
			GROUP BY issue2
			)
			UNION
			(SELECT
			menu_pension_issue.label as issue_type_lbl,
			COUNT(*) as issue_type
			FROM cases 
			LEFT JOIN menu_pension_issue ON menu_pension_issue.value = cases.issue3
			WHERE 1{$where_sql} AND issue3 IS NOT NULL
			GROUP BY issue3
			)
			UNION
			(SELECT
			NULL as issue_type_lbl,
			COUNT(*) as issue_type
			FROM cases 
			LEFT JOIN menu_pension_issue ON menu_pension_issue.value = cases.issue3
			WHERE 1{$where_sql} AND issue1 IS NULL AND issue2 IS NULL AND issue3 IS NULL
			)) AS issue_type_counts
			GROUP BY issue_type_lbl
			";
	$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());
	while($row = mysql_fetch_assoc($result))
	{
		$t->add_row(array($row['issue_type_lbl'],$row['issue_type_count']));
	}
	
	
	if($show_sql) {
		$t->set_sql($sql);
	}
	
	$stats_fields = array('issue_inequity' => 'Inequity',
	'pension_case_closure' => 'Case Closure',
	'pension_services' => 'Services',
	'pri_client.state' => 'State',
	'jurisdiction_reason' => 'Jurisdiction Reason',
	'good_story' => 'Success Story',
	'intake_type' => 'Intake Type',
	'benefit_form' => 'Distribution Type',
	'timing_distribution' => 'Timing of Distribution',
	'benefit_qualifier' => 'Benefit Features',
	'benefit_claimant' => 'Claimant'
	);
	
	$stats_fields_menus = array(
	'issue_inequity' => 'menu_inequity',
	'pension_case_closure' => 'menu_pension_case_closure',
	'pension_services' => 'menu_pension_services',
	'jurisdiction_reason' => 'menu_jurisdiction_reason',
	'good_story' => 'menu_yes_no',
	'intake_type' => 'menu_intake_type',
	'benefit_form' => 'menu_benefit_form',
	'timing_distribution' => 'menu_timing_distribution',
	'benefit_qualifier' => 'menu_benefit_qualifier',
	'benefit_claimant' => 'menu_benefit_claimant'
	);
	
	foreach ($stats_fields as $field_name => $label)
	{
		$t->add_table();
		$t->set_table_title('Summary: ' . $label);
		$t->set_header(array($label,"#"));
		$t->display_row_count(false);
		$sql_joins = " LEFT JOIN contacts AS pri_client ON cases.client_id = pri_client.contact_id";
		$sql_label = " IFNULL({$field_name},'Not Entered'),";
		if(isset($stats_fields_menus[$field_name]))
		{
			$sql_label = " IFNULL(menu_label.label,'Not Entered'),";
			$sql_joins .= " LEFT JOIN {$stats_fields_menus[$field_name]} AS menu_label ON menu_label.value = cases.{$field_name}";
		}
		$sql = "SELECT{$sql_label} COUNT(*) as stat FROM cases{$sql_joins} WHERE 1{$where_sql} GROUP BY {$field_name}";
		$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());
		
		while($row = mysql_fetch_assoc($result))
		{
			$t->add_row($row);
		}
		
		if($show_sql) {
			$t->set_sql($sql);
		}
	}
			
}




if(in_array($report_output,array(2,3)))
{
	if($report_output == 3)
	{ // Add additional table under display both otherwise detail is first table
		$t->add_table();
	}
	$t->set_table_title('Detail: Case Data');
	$t->set_header(array("Number","Client Name","Legal Issues","Inequity","Referred By","Case Closure","Services","State",
						"Jurisdiction Reason","Success Story","Intake Type","Interest","Not Recovered","Retroactive Payment",
						"Lump Sum","Annuity Cash Accumulated","Annuity Present Value","Total Cash Accumulated","Total Present Value",
						"Distribution Type","Timing of Distribution","Benefit Features","Claimant"));
	$t->display_row_count(true);
	
	
	
	$sql = "SELECT 
			cases.case_id,
			IFNULL(cases.number,'(no case #)') AS number,
			CONCAT(pri_client.last_name,', ',pri_client.first_name) AS client_name,
			cases.issue1,
			cases.issue2,
			cases.issue3,
			IFNULL(menu_inequity.label,'Not Entered') AS issue_inequity,
			IFNULL(menu_referred_by.label,'Not Entered') AS referred_by,
			IFNULL(menu_pension_case_closure.label,'Not Entered') AS pension_case_closure,
			IFNULL(menu_pension_services.label,'Not Entered') AS pension_services,
			cases.case_state,
			IFNULL(menu_jurisdiction_reason.label,'Not Entered') AS jurisdiction_reason,
			IF(cases.good_story = 1,'Yes','No') AS success_story,
			IFNULL(menu_intake_type.label,'Not Entered') AS intake_type,
			IFNULL(cases.annuity_interest,'0.00') AS annuity_interest,
			IFNULL(cases.annuity_not_recovered,'0.00') AS annuity_not_recovered,
			IFNULL(cases.annuity_retro_payment,'0.00') AS annuity_retro_payment,
			IFNULL(annuity_lump_sum,'0.00') AS annuity_lump_sum,
			IFNULL(annuity_cash_accumulated,'0.00') AS annuity_cash_accumulated,
			IFNULL(annuity_present_value,'0.00') AS annuity_present_value,
			IFNULL(annuity_total_cash_accumulated,'0.00') AS annuity_total_cash_accumulated,
			IFNULL(annuity_total_present_value,'0.00') AS annuity_total_present_value,
			IFNULL(menu_benefit_form.label,'Not Entered') AS type_distribution,
			IFNULL(menu_timing_distribution.label,'Not Entered') AS timing_distribution,
			IFNULL(menu_benefit_qualifier.label,'Not Entered') AS benefit_qualifier,
			IFNULL(menu_benefit_claimant.label,'Not Entered') AS benefit_claimant
			FROM cases 
			LEFT JOIN contacts AS pri_client ON pri_client.contact_id = cases.client_id 
			LEFT JOIN menu_inequity ON menu_inequity.value = cases.issue_inequity
			LEFT JOIN menu_referred_by ON menu_referred_by.value = cases.referred_by
			LEFT JOIN menu_pension_case_closure ON menu_pension_case_closure.value = cases.pension_case_closure
			LEFT JOIN menu_pension_services ON menu_pension_services.value = cases.pension_services
			LEFT JOIN menu_jurisdiction_reason ON menu_jurisdiction_reason.value = cases.jurisdiction_reason
			LEFT JOIN menu_intake_type ON menu_intake_type.value = cases.intake_type
			LEFT JOIN menu_benefit_form ON menu_benefit_form.value = cases.benefit_form
			LEFT JOIN menu_timing_distribution ON menu_timing_distribution.value = cases.timing_distribution
			LEFT JOIN menu_benefit_qualifier ON menu_benefit_qualifier.value = cases.benefit_qualifier
			LEFT JOIN menu_benefit_claimant ON menu_benefit_claimant.value = cases.benefit_claimant
			WHERE 1{$where_sql}";
	$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());
	
	$totals_array = array_fill(0,11,NULL);
	$totals_array['annuity_interest'] = 0;
	$totals_array['annuity_not_recovered'] = 0;
	$totals_array['annuity_retro_payment'] = 0;
	$totals_array['annuity_lump_sum'] = 0;
	$totals_array['annuity_cash_accumulated'] = 0;
	$totals_array['annuity_present_value'] = 0;
	$totals_array['annuity_total_cash_accumulated'] = 0;
	$totals_array['annuity_total_present_value'] = 0;

	while($row = mysql_fetch_assoc($result))
	{
		$case_id = $row['case_id'];
		unset($row['case_id']);
		if($report_format != 'csv')
		{
			$row['number'] = "<a href=\"{$base_url}/case.php?case_id={$case_id}\">" . $row['number'] . "</a>";
		}
		$issues = array();
		if(strlen($row['issue1']) > 0)
		{
			$issues[] = pl_array_lookup($row['issue1'],$menu_pension_issues);
		}
		if(strlen($row['issue2']) > 0)
		{
			$issues[] = pl_array_lookup($row['issue2'],$menu_pension_issues);
		}
		if(strlen($row['issue3']) > 0)
		{
			$issues[] = pl_array_lookup($row['issue3'],$menu_pension_issues);
		}
		if(count($issues) > 0)
		{
			$row['issue1'] = implode(" \n",$issues);
		}
		else 
		{
			$row['issue1'] = 'Not Entered';
		}
		unset($row['issue2']);
		unset($row['issue3']);
		if($redact)
		{
			$row['client_name'] = 'XXXX, xxxx';
		}
		$t->add_row($row);
		$totals_array['annuity_interest'] += $row['annuity_interest'];
		$totals_array['annuity_not_recovered'] += $row['annuity_not_recovered'];
		$totals_array['annuity_retro_payment'] += $row['annuity_retro_payment'];
		$totals_array['annuity_lump_sum'] += $row['annuity_lump_sum'];
		$totals_array['annuity_cash_accumulated'] += $row['annuity_cash_accumulated'];
		$totals_array['annuity_present_value'] += $row['annuity_present_value'];
		$totals_array['annuity_total_cash_accumulated'] += $row['annuity_total_cash_accumulated'];
		$totals_array['annuity_total_present_value'] += $row['annuity_total_present_value'];
			
	}
	foreach ($totals_array as $key => $value)
	{
		if(!is_null($value))
		{
			$totals_array[$key] = number_format($value,2);
		}
	}
	$totals_array[0] = "<b>Totals</b>";
	$totals_array[] = NULL;
	$totals_array[] = NULL;
	$totals_array[] = NULL;
	$totals_array[] = NULL;
	$t->add_row($totals_array);
	
	if($show_sql) {
		$t->set_sql($sql);
	}
}
/*

$sql = "SELECT
		IFNULL(pension_services,'No Data') AS pension_services,
		IFNULL(SUM(annuity_interest),0) AS annuity_interest,
		IFNULL(SUM(annuity_not_recovered),0) AS annuity_not_recovered,
		IFNULL(SUM(annuity_retro_payment),0) AS annuity_retro_payment,
		IFNULL(SUM(annuity_lump_sum),0) AS annuity_lump_sum,
		IFNULL(SUM(annuity_cash_accumulated),0) AS annuity_cash_accumulated,
		IFNULL(SUM(annuity_total_cash_accumulated),0) AS annuity_total_cash_accumulated
		FROM cases 
		WHERE 1{$where_sql}
		GROUP BY pension_services";


$t->add_table();
$t->set_table_title('Cash Accumulated');
$t->display_row_count(false);

// Build Columns
$cols = array(	'Service',
				'Interest',
				'Not Recovered', 
				'Retroactive Payment', 
				'Lump Sum', 
				'Annuity', 
				'Total');


// Build Header

$t->set_header($cols);


// Run the Report

$pension_services = pl_menu_get('pension_services');


$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while($row = mysql_fetch_assoc($result))
{
	$row['pension_services'] = pl_array_lookup($row['pension_services'],$pension_services);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}

$t->add_table();


$sql = "SELECT
		IFNULL(pension_services,'No Data') AS pension_services,
		IFNULL(SUM(annuity_interest),0) AS annuity_interest,
		IFNULL(SUM(annuity_not_recovered),0) AS annuity_not_recovered,
		IFNULL(SUM(annuity_retro_payment),0) AS annuity_retro_payment,
		IFNULL(SUM(annuity_lump_sum),0) AS annuity_lump_sum,
		IFNULL(SUM(annuity_present_value),0) AS annuity_present_value,
		IFNULL(SUM(annuity_total_present_value),0) AS annuity_total_present_value
		FROM cases 
		WHERE 1{$where_sql}
		GROUP BY pension_services";


$t->title = $report_title;
$t->set_table_title('Present Value');
$t->display_row_count(false);

// Build Columns
$cols = array(	'Service',
				'Interest',
				'Not Recovered', 
				'Retroactive Payment', 
				'Lump Sum', 
				'Annuity', 
				'Total');


// Build Header

$t->set_header($cols);


// Run the Report

$pension_services = pl_menu_get('pension_services');


$result = mysql_query($sql) or trigger_error('SQL:' . $sql . ' Error: ' . mysql_error());

while($row = mysql_fetch_assoc($result))
{
	$row['pension_services'] = pl_array_lookup($row['pension_services'],$pension_services);
	$t->add_row($row);
}

if($show_sql) {
	$t->set_sql($sql);
}
*/
$t->display();
exit();

?>
