<?php

/**********************************/
/* Pika CMS (C) 2008 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('..');
require_once('pika-danio.php');
pika_init();



$pension_issue = pl_grab_get('pension_issue');
$pension_issue = substr($pension_issue, 0, 2);

$safe_pension_issue = DB::escapeString($pension_issue);

$buffer = '';

$doc = new DOMDocument();
$problem_xml = $doc->createElement('pension_issues');
$problem_xml = $doc->appendChild($problem_xml);


$where_sql = '';
if (strlen($pension_issue) == 2)
{
	$where_sql .= " AND value LIKE '{$safe_pension_issue}%'";
}

$sql = "SELECT value, label FROM menu_pension_sub_issue WHERE 1 {$where_sql} ORDER BY menu_order";
// echo $sql;
$result = DB::query($sql);
while ($row = DBResult::fetchRow($result)) {
	$problem_node = $doc->createElement('pension_issue');
	$problem_node = $problem_xml->appendChild($problem_node);
	$node = $doc->createElement('value',$row['value']);
	$node = $problem_node->appendChild($node);
	$node = $doc->createElement('label',$row['label']);
	$node = $problem_node->appendChild($node);			
}


$buffer = $doc->saveXML();
header('Content-type: text/xml');
pika_exit($buffer);
?>
