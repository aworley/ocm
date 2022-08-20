<?php

chdir('../../');

require_once ('pika-danio.php'); 
pika_init();

$report_title = 'LSC Age Race Report';
$report_name = "lsc_ar";

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
$open_on_date = pl_grab_post('open_on_date');
$funding = pl_grab_post('funding');
$office = pl_grab_post('office');
$status = pl_grab_post('status');
$county = pl_grab_post('county');
$gender = pl_grab_post('gender');
$undup = pl_grab_post('undup');
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

$eth_sql = "SELECT contacts.ethnicity AS 'Code', label AS 'Ethnicity',
	SUM(IF(client_age < 18, 1, 0)) AS 'Under 18',
	SUM(IF(client_age >= 18 AND client_age <= 35, 1, 0)) AS '18 to 35',
	SUM(IF(client_age >= 36 AND client_age <= 59, 1, 0)) AS '36 to 59',
	SUM(IF(client_age >= 60, 1, 0)) AS '60 and Older',
    SUM(IF(client_age IS NULL, 1, 0)) AS 'No Age Data',
	SUM(1) AS 'Total'
	FROM cases
	LEFT JOIN contacts ON (cases.client_id = contacts.contact_id)
    LEFT JOIN menu_ethnicity ON contacts.ethnicity=menu_ethnicity.value
	WHERE 1";



// handle the crazy date range selection
$range1 = $range2 = "";

$sql = '';

$safe_clb = DB::escapeString($clb);
$safe_cle = DB::escapeString($cle);
$safe_ood = DB::escapeString($ood);

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

if ($gender) 
{
	$t->add_parameter('Gender Code',$gender);
	$safe_gender = DB::escapeString($gender);
	$sql .= " AND gender='{$safe_gender}'";
}

if ($undup == 1 || ($undup == 0 && $undup != '')) 
{
		$t->add_parameter('Undup Service',pl_array_lookup($undup,$menu_undup));
		$safe_undup = DB::escapeString($undup);
        $sql .= " AND undup = '{$safe_undup}'";
}

$eth_sql .= $sql . " GROUP BY contacts.ethnicity";

$t->title = $report_title;
$t->set_table_title("Summary by Age and Ethnicity");
$t->display_row_count(false);
//$t->set_table_title('Table 1: Ethnicity by Age Category');
$t->set_header(array('Code','Category','Under 18','18 to 35','36 to 59','60 and Older','No Age Data','Total'));



$total = array();
$total['code'] = "";
$total['category'] = "";
	$total["A"]	= "0";
	$total["B"]	= "0";
	$total["C"]	= "0";
	$total["D"] = "0";
	$total["E"] = "0";
	$total["total"] = "0";
	
$result = DB::query($eth_sql) or trigger_error();
while ($row = DBResult::fetchRow($result))
{
	$t->add_row($row);

	$total["A"]	+= $row["Under 18"];
	$total["B"]	+= $row["18 to 35"];
	$total["C"]	+= $row["36 to 59"];
	$total["D"]	+= $row["60 and Older"];
	$total["E"] += $row["No Age Data"];
	$total["total"] += $row["Total"];
}

$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($eth_sql);
}


// Add the Veteran table
$t->add_table();
$t->set_table_title("Summary by Veteran Status");
$t->display_row_count(false);

$total = 0;
$vet_sql = "SELECT veteran_household, COUNT(*) AS a FROM cases WHERE 1"
	. $sql . " GROUP BY veteran_household";
$result = DB::query($vet_sql) or trigger_error();

while ($row = DBResult::fetchRow($result))
{
	if ($row['veteran_household'] == '1')
	{
		$row['veteran_household'] = 'Veteran';
	}
	
	else if ($row['veteran_household'] == '0')
	{
		$row['veteran_household'] = 'Non-Veteran';
	}
	
	else if ($row['veteran_household'] == "")
	{
		$row['veteran_household'] = 'No Data Entered';
	}
	
	$t->add_row($row);
	$total += $row['a'];
}

$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($vet_sql);
}


// Add the gender table
$t->add_table();
$t->set_table_title("Summary by Gender");
$t->display_row_count(false);

$total = 0;
$vet_sql = "SELECT gender, COUNT(*) AS a FROM cases LEFT JOIN contacts ON cases.client_id=contacts.contact_id WHERE 1"
	. $sql . " GROUP BY gender";
$result = DB::query($vet_sql) or trigger_error();

while ($row = DBResult::fetchRow($result))
{
	if ($row['gender'] == 'F')
	{
		$row['gender'] = 'Women';
	}
	
	else if ($row['gender'] == 'M')
	{
		$row['gender'] = 'Men';
	}
	
	else if ($row['gender'] == 'G')
	{
		$row['gender'] = 'Groups';
	}
	
	else if ($row['gender'] == "")
	{
		$row['gender'] = 'No Data Entered';
	}
	
	$t->add_row($row);
	$total += $row['a'];
}

$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($vet_sql);
}




// Add the language table
$t->add_table();
$t->set_table_title("Summary by Client Language");
$t->display_row_count(false);

$total = 0;
$lang_sql = "SELECT CONCAT(IFNULL(language, 'No Code'), ' - ', IFNULL(label, '')) AS 'Client Language', COUNT(*) AS a FROM cases 
	LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
	LEFT JOIN menu_language ON contacts.language = menu_language.value
	WHERE 1"
	. $sql . " GROUP BY language ORDER BY a DESC";
$result = DB::query($lang_sql) or trigger_error();

while ($row = DBResult::fetchRow($result))
{
	$t->add_row($row);
	$total += $row['a'];
}

$t->add_row($total);

if($show_sql) 
{
	$t->set_sql($lang_sql);
}



$t->display();	
exit();

?>
