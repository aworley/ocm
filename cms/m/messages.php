<?php 
// 05-26-2010 - caw - created for Pika Mobile

chdir('..');

require_once('pika-danio.php');
pika_init();

require_once('pikaMotd.php');

$main_html = array();  // Values for the main HTML template.
$home_page = array();
$messages_text = '';

$result = pikaMotd::getMotdDB();

if (DBResult::numRows($result) < 1)
{
	$messages_text .= "<div id=\"item_content\">Welcome to the Pika Case Management System!</div>\n";
}

else 
{	
	while ($row = DBResult::fetchRow($result))
	{
		$row['staff_name'] = pl_text_name($row);
		$row['summary_content'] = $row['content'];
		if(strlen($row['content']) > 140) {
			$row['summary_content'] = pl_html_text(substr($row['content'],0,140));
			$row['summary_content'] .= " ... (<i><a href=\"#\" onclick=\"toggleMotd({$row['motd_id']});" .
										 " return false;\">View Full Text</a></i>)";
		}
		$row['content'] = pl_html_text($row['content']);
// 06-11-2010 - caw - modified for Pika Mobile		
//		$messages_text .= pl_template('mobile/messages.html', $row, 'motd');
		$messages_text .= pl_template('m/messages.html', $row, 'motd');
	}
}

$home_page['motd'] = $messages_text;

$main_html['page_title'] = "Messages";
// 06-11-2010 - caw - modified for Pika Mobile
//$main_html['content'] = pl_template('mobile/messages.html', $home_page);
$main_html['content'] = pl_template('m/messages.html', $home_page);
$main_html['nav'] = "Pika Home";

// 06-11-2010 - caw - Pika Mobile
//$buffer = pl_template($main_html, 'mobile/default.html');
$buffer = pl_template($main_html, 'm/default.html');

pika_exit($buffer);

?>

