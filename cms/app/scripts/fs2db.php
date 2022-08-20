<?php

define('PL_DISABLE_SECURITY', 1);

chdir('../../');

require_once('pika-danio.php');
pika_init();

require_once('pikaDocument.php');

set_time_limit(0);
ini_set('memory_limit','256M');

$i = 0;
$j = 0;
$k = 0;
$l = 0;

$sql = "SELECT MAX(file_size) AS largest_doc FROM documents LIMIT 1";
$result = DB::query($sql) or trigger_error($sql);
if(DBResult::numRows($result) > 0) {
	$row = DBResult::fetchRow($result);
	$largest_file = $row['largest_doc'];
	echo "The largest file in doc_storage = ";
	echo number_format($largest_file,0,'.',',') . " bytes";
	echo " \n";
} else {pika_exit('no max documents filesize!');}

$sql = "SHOW VARIABLES LIKE 'max_allowed_packet'";
$result = DB::query($sql) or trigger_error($sql);
if(DBResult::numRows($result) > 0) {
	$row = DBResult::fetchRow($result);
	$mysql_max_file_size = $row['Value'];
	echo "The largest allowable mysql insert = ";
	echo number_format($mysql_max_file_size,0,'.',',') . " bytes";
	echo " \n";
} else {pika_exit('no max mysql allowed packet!');}

if(($mysql_max_file_size - $largest_file) < 0) {
	pika_exit("Import will not complete - Mysql variable max_allowed_packet must be set to a value >" . number_format($largest_file,0,'.',',') . " bytes");
}

echo " \n";
echo " \n";

$sql = "SELECT * FROM documents ORDER BY doc_id ASC";
$result = DB::query($sql) or die('3');
$doc_storage = pl_settings_get('docs_directory');

while ($row = DBResult::fetchRow($result))
{
	$path = $doc_storage . $row['filepath'] . "/" . $row['filename'];
	
	if (!file_exists($path) || is_dir($path))
	{
		echo "{$i} - DocID={$row['doc_id']} - {$path} - File doesn't exist - file system \n";
		
		$l++;
		
		if ($row['orphaned'] == 0)
		{
			echo "{$i} - DocID={$row['doc_id']} - {$path} - File doesn't exist - orphaned flag <b>incorrect</b> \n";
			$j++;
		}
	}
	
	else
	{
		if ($row['orphaned'] == 1)
		{
			echo "{$i} - DocID={$row['doc_id']} - {$path} - File exists - orphaned flag <b>incorrect</b> \n";
			$j++;
			
		}
		$doc = new pikaDocument();
		$doc_text = null;
		if($row['doc_text']) {
			$doc_text = $row['doc_text'];
		}
	
		$doc_id = $doc->importCaseDoc($row['filename'],$doc_storage . $row['filepath'],$row['summary'],$row['case_id'],$row['user_id'],$doc_text);
		if(!$doc_id) {
			echo "{$i} - DocID={$row['doc_id']} - {$path} - Error uploading document \n";		
		} else {
			echo "{$i} - DocID={$row['doc_id']} - {$path} - File Uploaded \n";
			$doc->save();
			$k++;
		}
	}
	
	
	$i++;
	flush();
	ob_flush();
}

// scan doc_storage folder for files that didn't have documents record(?)

// delete doc_storage

echo " \n";
echo "<table border=1><tr>";
echo "<td align=right>{$i}</td>";
echo "<td>Total document records checked</td>";
echo "</tr><tr>";
echo "<td align=right>{$j}</td>";
echo "<td>Document record inconsistencies found. (file exists - marked as orphaned)</td>";
echo "</tr><tr>";
echo "<td align=right>{$k}</td>";
echo "<td>Document records uploaded successfully</td>";
echo "</tr><tr>";
echo "<td align=right>{$l}</td>";
echo "<td>Orphaned document records.</td>";
echo "</tr><tr>";
echo "<td align=right>". ($k + $l) . "</td>";
echo "<td> Records successfully processed</td>";
echo "</tr></table>";

;

?>
