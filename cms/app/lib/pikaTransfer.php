<?php

/**********************************/
/* Pika CMS (C) 2015 Aaron Worley */
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

class pikaTransfer extends plBase 
{	
	public function __construct($transfer_id = null)
	{
		$this->db_table = 'transfers';
		parent::__construct($transfer_id);
		$this->created = date('Y-m-d G:i:s');
	}
}


?>
