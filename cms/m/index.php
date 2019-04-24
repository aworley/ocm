<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('..');

require_once('pika-danio.php');
pika_init();

require_once('pikaMotd.php');
require_once('pikaRssFeed.php');


$main_html = array();  // Values for the main HTML template.
$home_page = array();
$messages_text = '';

$result = pikaMotd::getMotdDB();
if (DBResult::numRows($result) < 1)
{
	$messages_text .= "<blockquote><tt>Welcome to the Pika Case Management System!</tt></blockquote>\n";
}

else 
{
	while ($row = DBResult::fetchRow($result))
	{
		$row['staff_name'] = pl_text_name($row);
		$row['summary_content'] = $row['content'];
		if(strlen($row['content']) > 140) 
		{
			$row['summary_content'] = pl_html_text(substr($row['content'],0,140));
			$row['summary_content'] .= " ... (<i><a href=\"#\" onclick=\"toggleMotd({$row['motd_id']});" .
										 " return false;\">View Full Text</a></i>)";
		}
		$row['content'] = pl_html_text($row['content']);
// 06-11-2010 - caw - put code here to detect mobile device				
		$messages_text .= pl_template('m/home.html', $row, 'motd');
	}
}


$feeds_array = pikaRssFeed::getFeeds();
$feeds_text = '';
if(count($feeds_array) >= 1) {
	foreach ($feeds_array as $feed) {
		$feeds_text .= "<h2>{$feed['title']}</h2>\n";
		
		foreach ($feed['entries'] as $entry) {
			$entry['feed_id'] = rand();
			$entry['content'] = strip_tags($entry['content'],'<a><ul><ol><li><p>');
			$entry['summary_content'] = $entry['content'];
			if(strlen($entry['content']) > 140) {
				$entry['summary_content'] = substr($entry['content'],0,140);
				$entry['summary_content'] .= " ... (<i><a href=\"#\" onclick=\"toggleFeed({$entry['feed_id']});" .
											 " return false;\">View Full Text</a></i>)";
			}
			$feeds_text .= pl_template('m/home.html',$entry,'rss_feeds');
		}
	}
}


$home_page['motd'] = $messages_text;
$home_page['rss_feeds'] = $feeds_text;
$home_page['user_id'] = $auth_row['user_id'];


$main_html['page_title'] = "Home Page";
// 06-11-2010 - caw - put code here to detect mobile device
$main_html['content'] = pl_template('m/home.html', $home_page);
// end of 06-11 - caw changes

$main_html['nav'] = "Pika Home";

// 06-11-2010 - caw - put code here to detect mobile device
$buffer = pl_template($main_html, 'm/default.html');
// end of 06-11 changes
pika_exit($buffer);

?>
