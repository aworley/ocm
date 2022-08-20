<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* Something.
*
* @author Aaron Worley <aaron@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaGroup extends plBase 
{
	public function __construct($group_id = null)
	{
		$this->db_table = '`groups`';
		parent::__construct($group_id);
		return true;
	}
	
	public static function getGroupsDB() {
		$sql = "SELECT * FROM `groups` WHERE 1";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
}

?>
