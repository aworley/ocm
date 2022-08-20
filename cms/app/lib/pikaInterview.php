<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/*
	2016-06-30 AMW - This library is used by a custom feature that we did for 
	a client.
*/

require_once('plBase.php');

/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.net>;
* @version 1.0
* @package Danio
*/
class pikaInterview extends plBase 
{
	
	public function __construct($interview_id = null)
	{
		$this->db_table = 'interviews';
		parent::__construct($interview_id);
		
		if(is_null($interview_id)) {
			$this->created = date('YmdHis');
		}
		
	}
	
	public static function getInterviewsDB($enabled = 0) {
		
		$sql = "SELECT * FROM interviews WHERE 1";
		
		if($enabled) {$sql .= " AND enabled = 1";}
		
		$sql .= " ORDER BY name ASC, enabled DESC;";
		
		return DB::query($sql);
	}
	
	public function save() {
		$this->last_modified = date('YmdHis');
		parent::save();
	}

	
	
}

