<?php
/***********************************/
/* Pika CMS (C) 2002 Pika Software */
/* http://pikasoftware.com         */
/***********************************/

require_once('plBase.php');

class pikaUserSession extends plBase 
{
	
	public function __construct($user_session_id = null)
	{
		$this->db_table = 'user_sessions';
		parent::__construct($user_session_id);
		if(is_null($user_session_id))
		{
			
			$this->ip_address = $_SERVER['REMOTE_ADDR'];
			$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
			$this->last_updated = time();
			$this->created = time();
		}
		else 
		{
			$this->last_modified = date('YmdHis');
		}
	}
	
	public static function getSessions($filter, &$row_count = 0, $order_field = 'created', $order = 'DESC', $first_row = 0, $list_length = 0)
	{
		$sql_filter = '';
		if(is_array($filter)) {
			foreach ($filter as $key => $val)
			{
				$filter[$key] = DB::escapeString($val);
			}
			
			if (isset($filter['user_id']) && is_numeric($filter['user_id']))
			{
				$sql_filter .= " AND user_sessions.user_id='{$filter['user_id']}'";
			}
			
			if (isset($filter['session_id']) && strlen($filter['session_id']) > 0)
			{
				$sql_filter .= " AND user_sessions.session_id='{$filter['session_id']}'";
			}
			
		}
		// AMW - 2011-08-02 - Added users.password_expire.
		$sql = "SELECT user_sessions.*, users.username, users.enabled, users.session_data, users.group_id AS group_name, `groups`.*, users.password_expire,
				(UNIX_TIMESTAMP() - last_updated) as seconds_elapsed
				FROM user_sessions 
				JOIN users ON user_sessions.user_id = users.user_id 
				LEFT JOIN `groups` on `groups`.group_id = users.group_id 
				WHERE 1{$sql_filter}";

		if($order != 'ASC') {$order = 'DESC'; }
		if ($order_field && $order){
			$safe_order_field = DB::escapeString($order_field);
			$safe_order = DB::escapeString($order);
			$sql .= " ORDER BY {$safe_order_field} {$safe_order}";
		}

		if ($first_row && $list_length){
			$sql .= " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$sql .= " LIMIT $list_length";
		}
		//echo $sql;
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
		
	}
	
	public function save()
	{
		$this->last_updated = time();
		parent::save();
	}
	
	
	
}

