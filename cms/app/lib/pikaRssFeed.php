<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* written by: Matthew Friedlander*/
/* http://www.pikasoftware.com    */
/**********************************/

require_once('plBase.php');


/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaRssFeed extends plBase 
{
	
	public function __construct($feed_id = null)
	{
		$this->db_table = 'rss_feeds';
		parent::__construct($feed_id);
		if(strlen($this->feed_cache) > 0) {
			$this->feed_cache = stripslashes($this->feed_cache);
		}
		if(is_null($feed_id)) {
			// New record
			$this->created = date('YmdHis');
		} 
	}
	
	public function save($show_sql = false) {
		$this->feed_cache = addslashes($this->feed_cache);
		parent::save($show_sql);
	}
	
	public static function getRssDB() {
		$result_array = array();
		$result = DB::query("SELECT rss_feeds.* FROM rss_feeds WHERE 1;");
		while ($row = DBResult::fetchRow($result)) {
			$row['feed_cache'] = stripslashes($row['feed_cache']);
			$result_array[] = $row;
		}
		return $result_array;
	}
	
	public static function updateFeeds() {
		$result = self::getRssDB();
		$current_timestamp = pl_mysql_timestamp_to_unix(date('YmdHis'));
		
		foreach ($result as $row) {
			if($row['enabled']) {
				$last_modified = pl_mysql_timestamp_to_unix($row['last_modified']);
				//echo $current_timestamp - $last_modified;
				if(strlen($row['feed_cache']) < 1 || ($current_timestamp - $last_modified) > 3600) {
					// Set timeout to 3 seconds (otherwise when it errors it sits for the full 30 sec script timeout)
					$rss_stream_context = stream_context_create(array('http' => array('timeout' => 3)));
					$feed_cache = file_get_contents($row['feed_url'],0,$rss_stream_context);
					$doc = new DOMDocument();
					$doc->preserveWhiteSpace = true;
					$doc->formatDocument = true;
					$doc->loadXML($feed_cache);
					$feed = new pikaRssFeed($row['feed_id']);
					$feed->last_modified = date('YmdHis');
					$feed->feed_cache = $doc->saveXML();
					$feed->save();
				}
			}
		}
	}
	
	public static function getFeeds() {
		self::updateFeeds();
		$result = self::getRssDB();
		$feeds_array = array();
		foreach ($result as $row) {
			if($row['enabled']) {
				$feed_type = $row['feed_type'];
				$feed_cache = $row['feed_cache'];
				if($feed_type != 1 && $feed_type != 2) {
					
					$feed_type = self::feedType($feed_cache);
					
					
					if($feed_type) {
						$feed = new pikaRssFeed($row['feed_id']);
						$feed->feed_type = $feed_type;
						$feed->save();
						
					}
				}
				$feed_array = array();
				if($feed_type == 1) {
					$feed_array = self::parseRSS($feed_cache,$row['list_limit']);
				}
				if($feed_type == 2) {
					
					$feed_array = self::parseATOM($feed_cache,$row['list_limit']);
				}
				if($feed_type == 3) {
					
					$feed_array = self::parseRDF($feed_cache,$row['list_limit']);
				}
				if(isset($feed_array['title']) && $feed_array['title']) {
					$feeds_array[] = $feed_array;
				}
			}
		}
		return $feeds_array;
	}
	
	public static function feedType($feed_cache = null) {
		// Returns 1 (RSS), 2 (ATOM), 3 (RDF), or false
		
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = true;
		$doc->formatDocument = true;
		
		if(!is_null($feed_cache) && $doc->loadXML($feed_cache)) {
			
			$xpath = new DOMXPath($doc);
			$channels = $xpath->query('/rss/channel');
			
			if ($channels->length >= 1) {
				return 1;
			}
			$xpath = new DOMXPath($doc);
			$xpath->registerNameSpace('atom', 'http://www.w3.org/2005/Atom');

			$feeds = $xpath->query('/atom:feed');
			if ($feeds->length >= 1) {
				return 2;
			}
			
			$xpath = new DOMXPath($doc);
			$xpath->registerNameSpace('rss', 'http://my.netscape.com/rdf/simple/0.9/');
			$channels = $xpath->query('/rdf:RDF/rss:channel');
			if ($channels->length >= 1) {
				return 3;
			}
			
		}
		return false;
	}
	public static function parseRSS($feed_cache = null, $limit = 0) {
		$feed_array = array();
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = true;
		$doc->formatDocument = true;
		if(!is_null($feed_cache) && $doc->loadXML($feed_cache)) {
			$xpath = new DOMXPath($doc);
			$title = $xpath->query('/rss/channel/title')->item(0)->nodeValue;
			$items = $xpath->query('/rss/channel/item');
			$feed_array['title'] = $title;
			foreach ($items as $item) {
				$title = $xpath->query('title',$item)->item(0)->nodeValue;
				$content = $xpath->query('description',$item)->item(0)->nodeValue;
				$link = $xpath->query("link",$item)->item(0)->nodeValue;
				$feed_array['entries'][] = array('title' => $title, 'content' => $content, 'link' => $link);
			}
		}
		if($limit && is_numeric($limit) && count($feed_array['entries']) > $limit) {
			$feed_array['entries'] = array_slice($feed_array['entries'],0,$limit);
		}
		return $feed_array;
	}
	public static function parseATOM($feed_cache = null, $limit = 0) {
		$feed_array = array();
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = true;
		$doc->formatDocument = true;
		if(!is_null($feed_cache) && $doc->loadXML($feed_cache)) {
			$xpath = new DOMXPath($doc);
			$xpath->registerNameSpace('atom', 'http://www.w3.org/2005/Atom');
			$title = $xpath->query('/atom:feed/atom:title')->item(0)->nodeValue;
			$entries = $xpath->query('/atom:feed/atom:entry');
			$feed_array['title'] = $title;
			foreach ($entries as $entry) {
				$title = $xpath->query('atom:title',$entry)->item(0)->nodeValue;
				$content = $xpath->query('atom:content',$entry)->item(0)->nodeValue;
				$link = $xpath->query("atom:link[@rel='alternate']/@href",$entry)->item(0)->nodeValue;
				$feed_array['entries'][] = array('title' => $title, 'content' => $content, 'link' => $link);
			}
		}
		if($limit && is_numeric($limit) && count($feed_array['entries']) > $limit) {
			$feed_array['entries'] = array_slice($feed_array['entries'],0,$limit);
		}
		return $feed_array;
	}
	public static function parseRDF($feed_cache = null, $limit = 0) {
		$feed_array = array();
		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = true;
		$doc->formatDocument = true;
		if(!is_null($feed_cache) && $doc->loadXML($feed_cache)) {
			$xpath = new DOMXPath($doc);
			$xpath->registerNameSpace('rss', 'http://my.netscape.com/rdf/simple/0.9/');
			$title = $xpath->query('/rdf:RDF/rss:channel/rss:title')->item(0)->nodeValue;
			$items = $xpath->query('/rdf:RDF/rss:item');
			$feed_array['title'] = $title;
			foreach ($items as $item) {
				$title = $xpath->query('rss:title',$item)->item(0)->nodeValue;
				$content = $xpath->query('rss:description',$item)->item(0)->nodeValue;
				$link = $xpath->query("rss:link",$item)->item(0)->nodeValue;
				$feed_array['entries'][] = array('title' => $title, 'content' => $content, 'link' => $link);
			}
		}
		if($limit && is_numeric($limit) && count($feed_array['entries']) > $limit) {
			$feed_array['entries'] = array_slice($feed_array['entries'],0,$limit);
		}
		return $feed_array;
	}
}

?>