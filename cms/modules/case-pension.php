<?php

require_once('pikaPensionPlan.php');

$case_row['pension_plan_name'] = 'No Plan Selected';
if(is_numeric($case_row['pension_plan_id']))
{
	$pension_plan = new pikaPensionPlan($case_row['pension_plan_id']);
	$plan_name = "No Plan Name Entered";
	if(strlen($pension_plan->plan_name) > 0)
	{
		$plan_name = $pension_plan->plan_name;
	}
	$case_row['pension_plan_name'] = "<a href=\"{$base_url}/pension_plans.php?action=edit&pension_plan_id={$case_row['pension_plan_id']}\">{$plan_name}</a>";
	$case_row['pension_plan_remove_link'] = "&nbsp;(<a href=\"{$base_url}/assign_plan.php?action=assign&case_id={$case_id}&pension_plan_id=\">Remove</a>)";

}

$menu_mortality_table = array(
	'1' => "Combined Static",
	'2' => "Static",
	'3' => "Fully Generational",
	'4' => "417(e)",
	);

$menu_employee_sex = array(
	'F' => "Female",
	'M' => "Male",
	);
	
$menu_payment_form = array(
	'LS' => "Lump Sum"
	);

$case_row['valuation_year'] = date('Y');
$case_row['mortality_table'] = 4;
$case_row['payment_form'] = 'LS';
if($case_row['gender'] == 'M')
{
	$case_row['employee_sex'] = 'M';
}


if(is_numeric($case_row['client_age']))
{
	$case_row['employee_age'] = $case_row['client_age'];
}


$pension_template = new pikaTempLib('subtemplates/case-pension.html',$case_row);
$pension_template->addMenu('mortality_table',$menu_mortality_table);
$pension_template->addMenu('employee_sex',$menu_employee_sex);
$pension_template->addMenu('payment_form',$menu_payment_form);
$C .= $pension_template->draw();

?>
