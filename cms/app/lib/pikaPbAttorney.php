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
class pikaPbAttorney extends plBase
{


	function __construct($id = null)
	{
		$this->db_table = 'pb_attorneys';
		parent::__construct($id);
	}
	
	public static function getPbAttorneyDB(){
		$sql = "SELECT * FROM pb_attorneys WHERE 1";
		$result = DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
		return $result;
	}

	public static function getPbAttorneys($filter, &$row_count, $order_field='',
	$order='ASC', $first_row='0', $list_length='100') {
		$sql_filter = $limit_sql = $order_sql = "";

		// Filter elements need to be escaped
		foreach ($filter as $key => $val)
		{
			$filter[$key] = DB::escapeString($val);
		}

		if (isset($filter['county']) && $filter['county']){
			$sql_filter .= " AND county LIKE '%{$filter['county']}%'";
		}

		if (isset($filter['languages']) && $filter['languages']){
			$sql_filter .= " AND languages LIKE \"%{$filter['languages']}%\"";
		}

		if (isset($filter['practice_areas']) && $filter['practice_areas']){
			$sql_filter .= " AND practice_areas LIKE '%{$filter['practice_areas']}%'";
		}

		if (isset($filter['last_name']) && $filter['last_name']){
			$sql_filter .= " AND last_name LIKE '%{$filter['last_name']}%'";
		}
		if($order != 'ASC') {$order = 'DESC'; }
		if ($order_field && $order){
			if ('atty_name' == $order_field){
				$order_sql = " ORDER BY last_name {$order}, first_name {$order}";
			} else {
				$order_sql = " ORDER BY {$order_field} {$order}";
			}
		}
		if ($first_row && $list_length){
			$limit_sql = " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$limit_sql = " LIMIT $list_length";
		}



		$sql = "SELECT count(*) as nbr
				FROM pb_attorneys 
				WHERE 1 $sql_filter";

		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			$row_count = $row['nbr'];
		} else { $row_count = 0; }
		
		$sql = "SELECT * 
				FROM pb_attorneys 
				WHERE 1 $sql_filter $order_sql $limit_sql";

		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
	public static function getPbAttorneyArray($filter = array()) {
		$row_count = 0;
		$pba_array = array();
		$result = self::getPbAttorneys($filter,$row_count,'name');
		
		while ($row = DBResult::fetchRow($result))
		{
			$pba_array[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}
		return $pba_array;
	}
	
	
}


?>