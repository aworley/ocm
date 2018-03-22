<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = "VOCA Victimization Report";
$report_name = "voca_victimization";

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
$close_date_begin = pl_grab_post('close_date_begin');
$close_date_end = pl_grab_post('close_date_end');
$open_on_date = pl_grab_post('open_on_date');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$county = pl_grab_post('county');
$undup = pl_grab_post('undup');

$menu_undup = pl_menu_get('undup');

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
$ood = pl_date_mogrify($open_on_date);

$labelnames = array("Adult Physical Assault (includes Aggravated and Simple Assault)",
"Adult Sexual Assault",
"Adults Sexually Abused/Assaulted as Children",
"Arson",
"Bullying (Verbal, Cyber, or Physical)",
"Burglary",
"Child Physical Abuse or Neglect",
"Child Pornography",
"Child Sexual Abuse/Assault",
"Domestic and/or Family Violence",
"DUI/DWI Incidents",
"Elder Abuse or Neglect",
"Hate Crime: Racial/Religious/Gender/Sexual Orientation/Other",
"Human Trafficking: Labor",
"Human Trafficking: Sex",
"Identity Theft/Fraud/Financial Crime",
"Kidnapping (noncustodial)",
"Kidnapping (custodial)",
"Mass Violence (Domestic/International)",
"Other Vehicular Victimization (e.g. Hit and Run)",
"Robbery",
"Stalking/Harassment",
"Survivors of Homicide Victims",
"Teen Dating Victimization",
"Terrorism (Domestic/International)",
"Other");
/*"A1. Information about the criminal justice process",
"A2. Information about victim rights, how to obtain notifications, etc.",
"A3. Referral to other victim service programs",
"A4. Referral to other services, supports, and resources (includes legal, medical, faith-based organizations, address-confidentiality programs, etc.)",
"B1. Victim advocacy/accompaniment to emergency medical care",
"B2. Victim advocacy/accompaniment to medical forensic exam",
"B3. Law enforcement interview advocacy/accompaniment",
"B4. Individual advocacy (e.g. assistance in applying for public benefits, return of personal property or effects)",
"B5. Performance of medical or nonmedical forensic exam or interview, or medical evidence collection",
"B6. Immigration assistance (e.g. special visas, continued presence application, and other immigration relief)",
"B7. Intervention with employer, creditor, landlord, or academic institution",
"B8. Child or dependent care assistance (includes coordination of services)",
"B9. Transportation assistance (includes coordination of services)",
"B10. Interpreter services",
"C1. Crisis intervention (in-person, includes safety planning, etc.)",
"C2. Hotline/crisis line counseling",
"C3. On-scene crisis response (e.g. community crisis response)",
"C4. Individual counseling",
"C5. Support groups (facilitated or peer)",
"C6. Other therapy (traditional, cultural, or alternative healing; art, writing, or play therapy, etc.)",
"C7. Emergency financial assistance (includes emergency loans and petty cash, payment for items such as food and/or clothing, changing windows and/or locks, taxis, prophylactic and nonprophylactic medications, durable medical equipment, etc.)",
"D1. Emergency shelter or safe house",
"D2. Transitional housing",
"D3. Relocation assistance (includes assistance with obtaining housing)",
"E1. Notification of criminal justice events (e.g. case status, arrest, court proceedings, case disposition, release, etc.)",
"E2. Victim impact statement assistance",
"E3. Assistance with restitution (includes assistance in requesting and when collection efforts are not successful)",
"E4. Civil legal assistance in obtaining protection or restraining order",
"E5. Civil legal assistance with family law issues (e.g. custody, visitation, or support)",
"E6. Other emergency justice‐related assistance",
"E7. Immigration assistance (e.g. special visas, continued presence application, and other immigration relief)",
"E8. Prosecution interview advocacy/accompaniment (includes accompaniment with prosecuting attorney and with victim/witness)",
"E9. Law enforcement interview advocacy/accompaniment",
"E10. Criminal advocacy/accompaniment",
"E11. Other legal advice and/or counsel",);*/
/*
$c=0;
foreach($_POST as $value){
	$labels[$labelnames[$c]] = $value;
	//echo "$c, "."$labelnames[$c]: "."$value<br/>";
	$c++;
	if($c>=61){
		break;
	}
}
foreach($labels as $j => $k){
	if($k==1){
		echo "$j :"."$k<br/>";
	}
}*/

$sql = "SELECT 
	SUM(voca2017_01),
	SUM(voca2017_02),
	SUM(voca2017_03),
	SUM(voca2017_04),
	SUM(voca2017_05),
	SUM(voca2017_06),
	SUM(voca2017_07),
	SUM(voca2017_08),
	SUM(voca2017_09),
	SUM(voca2017_10),
	SUM(voca2017_11),
	SUM(voca2017_12),
	SUM(voca2017_13),
	SUM(voca2017_14),
	SUM(voca2017_15),
	SUM(voca2017_16),
	SUM(voca2017_17),
	SUM(voca2017_18),
	SUM(voca2017_19),
	SUM(voca2017_20),
	SUM(voca2017_21),
	SUM(voca2017_22),
	SUM(voca2017_23),
	SUM(voca2017_24),
	SUM(voca2017_25),
	SUM(voca2017_26)
	FROM `cases`";
$columns = array("Category", "Total Cases");
$total = array();

/*$sql2008 = "SELECT label as 'problem_label', problem,
	SUM(IF(close_code = 'A', 1, 0)) AS 'A',
	SUM(IF(close_code = 'B', 1, 0)) AS 'B',
	SUM(IF(close_code = 'F', 1, 0)) AS 'F',
	SUM(IF(close_code = 'G', 1, 0)) AS 'G',
	SUM(IF(close_code = 'H', 1, 0)) AS 'H',
	SUM(IF(close_code = 'IA', 1, 0)) AS 'IA',
	SUM(IF(close_code = 'IB', 1, 0)) AS 'IB',
	SUM(IF(close_code = 'IC', 1, 0)) AS 'IC',
	SUM(IF(close_code = 'K', 1, 0)) AS 'K',
	SUM(IF(close_code = 'L', 1, 0)) AS 'L',
	SUM(IF(close_code IS NULL OR close_code NOT IN ('A','B','F','G','H','IA','IB','IC','K','L'), 1, 0)) AS 'Z',
	SUM(1) AS total
	FROM cases
	LEFT JOIN menu_problem_2008 ON cases.problem=menu_problem_2008.value
	WHERE 1";
$columns2008 = array('Problem Code','Code #','A','B','F','G','H','IA','IB','IC','K','L','No Code','Total');
$total2008 = array('A'=>'0','B'=>'0','F'=>'0','G'=>'0','H'=>'0','IA'=>'0','IB'=>'0',
					'IC'=>'0','K'=>'0','L'=>'0','Z'=>'0','total'=>'0');

if(strtotime($cle) >= strtotime('1/1/2008')) {
	$sql = $sql2008;
	$columns = $columns2008;
	$total = $total2008;
}*/
					
// handle the crazy date range selection
/*$range1 = $range2 = "";
$safe_clb = mysql_real_escape_string($clb);
$safe_cle = mysql_real_escape_string($cle);
$safe_ood = mysql_real_escape_string($ood);

if ($clb && $cle) {
	$t->add_parameter('Closed Between',$close_date_begin . " - " . $close_date_end);
	$range1 = "close_date >= '{$safe_clb}' AND close_date <= '{$safe_cle}'";
} elseif ($clb) {
	$t->add_parameter('Closed After',$close_date_begin);
	$range1 = "close_date >= '{$safe_clb}'";
} elseif ($cle) {
	$t->add_parameter('Closed Before',$close_date_end);
	$range1 = "close_date <= '{$safe_cle}'";
}

if ($ood) {
	$t->add_parameter('Open On',$open_on_date);
	$range2 = "(open_date <= '{$safe_ood}' AND (close_date IS NULL OR close_date > '{$safe_ood}'))";
}

if ($ood) {
	if ($clb || $cle) {
		$sql .= " AND (($range1) OR $range2)";
	} else { $sql .= " AND $range2"; }
} else {
	if ($clb || $cle) {
		$sql .= " AND $range1";
	}
}

$x = pl_process_comma_vals($funding);
if ($x != false)
{
	$t->add_parameter('Funding Code(s)',$funding);
	$sql .= " AND funding IN $x";
}

$x = pl_process_comma_vals($office);
if ($x != false)
{
	$t->add_parameter('Office Code(s)',$office);
	$sql .= " AND office IN $x";
}

$x = pl_process_comma_vals($status);
if ($x != false)
{
	$t->add_parameter('Case Status Code(s)',$status);
	$sql .= " AND status IN $x";
}

$x = pl_process_comma_vals($county);
if ($x != false)
{
	$t->add_parameter('Counties',$county);
	$sql .= " AND case_county IN $x";
}

$safe_undup = mysql_real_escape_string($undup);
if ($undup == 1 || ($undup == 0 && $undup != ''))
{
	$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
	$sql .= " AND undup = '{$safe_undup}'";
}

$sql .= " GROUP BY problem ORDER BY problem ASC";
*/

$t->title = $report_title;
$t->display_row_count(false);
//$t->set_table_title('Table 1: Ethnicity by Age Category');
$t->set_header($columns);


$result = mysql_query($sql) or trigger_error();

$row = mysql_fetch_row($result);

$total=array_combine($labelnames, $row);


foreach ($total as $key=>$val) {
	$val+=0;
	$current = array(0=>$key, 1=>$val);
	$t->add_row($current);
}

/*while ($row = mysql_fetch_row($result))
{
	$r=array_merge($labelnames[$c], $row[$c]);
	$t->add_row($r);
	//unset($row['problem_label']);
	//unset($row['problem']);
	$c++;
}*/

//$r = array_merge(array('','Totals'), array_values($total));

//$t->add_row($r);

if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
