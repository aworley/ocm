<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaMotd extends plBase 
{

	public function __construct($motd_id = null)
	{
		$this->db_table = 'motd';
		parent::__construct($motd_id);
		if(is_null($motd_id)) {
			// New record
			$this->title = "A Message Title";
			$this->created = date('YmdHis');
		} 
	}
	
	public static function getMotdDB() {
		$result = DB::query('SELECT motd.*, users.* FROM motd LEFT JOIN users ON motd.user_id = users.user_id');
		return $result;
	}
}

?>