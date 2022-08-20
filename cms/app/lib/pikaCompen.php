<?php

/**********************************/
/* Pika CMS (C) 2010 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* pikaCompen - implements data interaction for the attorney
* compensation (compen) table 
*
* @author Matthew Friedlander <matt@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaCompen extends plBase 
{
	
	public function __construct($compen_id = null)
	{
		$this->db_table = 'compens';
		parent::__construct($compen_id);
	}
	
	public static function getCaseCompen($case_id = null) {
		$safe_case_id = DB::escapeString($case_id);
		$sql = "SELECT * FROM compens WHERE 1 AND case_id = '{$safe_case_id}'";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
	public static function getCaseCompenBill($case_id = null) {
		$safe_case_id = DB::escapeString($case_id);
		$sql = "SELECT * FROM compens WHERE 1 AND payment_date IS NULL AND case_id = '{$safe_case_id}'";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
}

?>