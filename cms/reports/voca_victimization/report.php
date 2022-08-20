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
$show_sql = pl_grab_post('show_sql');


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
	FROM `cases` WHERE 1";
$columns = array("Category", "Total Cases");
$total = array();

					
// handle the crazy date range selection
$range1 = $range2 = "";
$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);
$safe_ood = DB::escapeString($ood);

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

$t->title = $report_title;
$t->display_row_count(false);

$t->set_header($columns);


$result = DB::query($sql) or trigger_error();

$row = DBResult::fetchRow($result);

$total=array_combine($labelnames, $row);


foreach ($total as $key=>$val) {
	$val+=0;
	$current = array(0=>$key, 1=>$val);
	$t->add_row($current);
}


if($show_sql) {
	$t->set_sql($sql);
}

$t->display();
exit();

?>
