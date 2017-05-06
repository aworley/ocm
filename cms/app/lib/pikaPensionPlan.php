<?php

/*****************************************/
/* Pika CMS (C) 2011 Pika Software, LLC. */
/* http://pikasoftware.com               */
/*****************************************/

require_once('plBase.php');


class pikaPensionPlan extends plBase 
{
	
	public function __construct($pension_plan_id = null)
	{
		$this->db_table = 'pension_plans';
		parent::__construct($pension_plan_id);
		if(!is_numeric($pension_plan_id)) { // New Record
			$this->created = date('YmdHis');
			$this->last_updated = date('YmdHis');	
		}
	}
	
	public static function getPensionPlansDB($filter = array(), &$row_count, $order_field='plan_name', $order='ASC', $first_row='0', $list_length='100') 
	{
		$where_sql = '';
		foreach ($filter as $field_name => $field_value)
		{
			$filter[$field_name] = mysql_real_escape_string($field_value);
		}
		
		if(isset($filter['plan_name']) && strlen($filter['plan_name']))
		{
			$where_sql .= " AND `plan_name` LIKE '{$filter['plan_name']}%'";
		}
		
		if(isset($filter['sponsor_name']) && strlen($filter['sponsor_name']))
		{
			$where_sql .= " AND `plan_name` LIKE '{$filter['sponsor_name']}%'";
		}
		
		if(isset($filter['plan_sn']) && strlen($filter['plan_sn']))
		{
			$where_sql .= " AND `plan_sn` = '{$filter['plan_sn']}'";
		}
		
		if($order != 'ASC') {$order = 'DESC'; }
		if ($order_field && $order){
			$order_sql = " ORDER BY {$order_field} {$order}";
		}
		if ($first_row && $list_length){
			$limit_sql = " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$limit_sql = " LIMIT $list_length";
		}
		
		$sql = "SELECT COUNT(*) as nbr FROM pension_plans WHERE 1{$where_sql};";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		if(mysql_num_rows($result) == 1) { 
			$row = mysql_fetch_assoc($result);
			$row_count = $row['nbr'];
		} else { $row_count = 0; }
		
		
		$sql = "SELECT * 
				FROM pension_plans 
				WHERE 1{$where_sql}
				{$order_sql}
				{$limit_sql}";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		return $result;
	}
	
	public static function getPensionPlanCases($filter = array(), &$row_count, $order_field='open_date', $order='ASC', $first_row='0', $list_length='100')
	{
		$where_sql = '';
		foreach ($filter as $field_name => $field_value)
		{
			$filter[$field_name] = mysql_real_escape_string($field_value);
		}
		
		if(isset($filter['closed']))
		{
			$where_sql .= " AND (`cases.close_date` IS NOT NULL OR `cases.close_date` != '0000-00-00')";
		}
		
		if(isset($filter['open']))
		{
			$where_sql .= " AND (`cases.close_date` IS NULL OR `cases.close_date` = '0000-00-00')";
		}
		
		if(isset($filter['pension_plan_id']) && is_numeric($filter['pension_plan_id']))
		{
			$where_sql .= " AND cases.pension_plan_id = '{$filter['pension_plan_id']}'";
		}
		
		$order_sql = $limit_sql = '';
		if($order != 'ASC') {$order = 'DESC'; }
		if ($order_field && $order){
			$order_sql = " ORDER BY {$order_field} {$order}";
		}
		if ($first_row && $list_length){
			$limit_sql = " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$limit_sql = " LIMIT $list_length";
		}
		
		$sql = "SELECT COUNT(*) as nbr FROM cases WHERE 1{$where_sql};";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		if(mysql_num_rows($result) == 1) { 
			$row = mysql_fetch_assoc($result);
			$row_count = $row['nbr'];
		} else { $row_count = 0; }
		
		
		$sql = "SELECT cases.*, pension_plans.* 
				FROM cases
				LEFT JOIN pension_plans ON pension_plans.pension_plan_id = cases.pension_plan_id
				WHERE 1{$where_sql}
				{$order_sql}
				{$limit_sql}";
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		
		return $result;
	}
	
	public function save() {
		$this->last_modified = date('YmdHis');
		parent::save($show_sql);
		
	}
	
}

?>