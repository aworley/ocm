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

class pikaTransferOption extends plBase 
{
	function __construct($id = null)
	{
		$this->db_table = 'transfer_options';
		parent::__construct($id);
		return true;
	}
	
	function getTransferOptionDB() {
		$sql = "SELECT * FROM transfer_options;";
		return DB::query($sql);
	}
	
	
}

?>