<?php

/**********************************/
/* Pika CMS (C) 2008 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

define('PL_DISABLE_SECURITY',true);

chdir('..');
require_once('pika-danio.php');
pika_init();



$problem = pl_grab_get('problem');
$problem = substr($problem, 0, 2);
$safe_problem = mysql_real_escape_string($problem);

$buffer = '';

$doc = new DOMDocument();
$problem_xml = $doc->createElement('problem_codes');
$problem_xml = $doc->appendChild($problem_xml);

if (strlen($problem) == 2)
{
	$sql = "SELECT value, label FROM menu_sp_problem WHERE value LIKE '{$safe_problem}%' ORDER BY menu_order";
	$result = mysql_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		$problem_node = $doc->createElement('problem');
		$problem_node = $problem_xml->appendChild($problem_node);
			$node = $doc->createElement('value', pl_clean_html($row['value']));
			$node = $problem_node->appendChild($node);
			$node = $doc->createElement('label', pl_clean_html($row['label']));
			$node = $problem_node->appendChild($node);
			
	}
	
}


$buffer = $doc->saveXML();
header('Content-type: text/xml');
exit($buffer);
?>
