<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* pikaCounters
*
* @author Matthew Friedlander <matt@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaCounter extends plBase 
{
	function __construct($counter_id = null)
	{
		$this->db_table = 'counters';
		$this->use_next_id_counter = false;
		parent::__construct($counter_id);
		return true;
	}
	
	public static function getCountersDB() {
		$sql = "SELECT * FROM counters WHERE 1";
		return DB::query($sql);
	}
	
	public static function resetCounters() {
		
		$counters = self::getCurrentCounters();
		
		foreach ($counters as $row) {
			
			if(isset($row['count']) && $row['max_count'] != $row['count'] && $row['max_count'] > 0) {
				$counter = new pikaCounter($row['id']);
				$counter->count = $row['max_count'];
				$counter->save();
				$counter = null;
			}
		}
		return true;
	}
	
	public static function getCurrentCounters() {
			$counters = array();
			
			$sql = "SHOW tables;";
			$table_names = array();
			$result = DB::query($sql);
			while ($row = DBResult::fetchArray($result)) {
				$table_names[$row[0]] = $row[0];
			}
			$result = self::getCountersDB();
			while ($row = DBResult::fetchRow($result)) {
				if (isset($table_names[$row['id']])) {
					$counters[$row['id']]['id'] = $row['id']; 
					$counters[$row['id']]['count'] = $row['count']; 
					$sql = "DESCRIBE {$row['id']}";
					$table_result = DB::query($sql);
					while($desc_row = DBResult::fetchRow($table_result)) {
						if(isset($desc_row['Key']) && $desc_row['Key'] == 'PRI' && strpos($desc_row['Type'],'int') !== false) 
						{
							$sql = "SELECT MAX({$desc_row['Field']}) AS max_current_id
									FROM {$row['id']}";
							if($row['id'] == 'users')
							{
								$sql .= " WHERE 1 AND users.user_id != 999999";
							}
							$max_result = DB::query($sql) or trigger_error($sql);
							if(DBResult::numRows($max_result) > 0) {
								$max_counter = DBResult::fetchRow($max_result);
								$counters[$row['id']]['max_count'] = $max_counter['max_current_id'];
							}
						}
					}	
				}
				
			}
			
			
		return $counters;	
	}
		
}

?>