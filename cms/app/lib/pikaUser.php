<?php

/**********************************/
/* Pika CMS (C) 2011              */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');

/**
* pikaUser
*
* @author Aaron Worley <aaron@pikasoftware.com>;
* @author Matthew Friedlander <matt@pikasoftware.com>;
* @version 5.0
* @package Danio
*/
class pikaUser extends plBase 
{	
	// AMW - save() compares "password" to this to determine if the password
	// has changed.
	private $current_password = null;
	
	public function __construct($user_id = null)
	{
		$this->db_table = 'users';
		parent::__construct($user_id);
		if(is_null($user_id)) {
			$this->last_name = "NONAME";
			$this->group_id = "NOGROUP";
			$this->enabled = '0';
			$this->username = "NONAME";
			$this->session_data = serialize(array());
		}
		
		// AMW - $current_password is used to track whether the password
		// is changed, which triggers a reset on the password expiration.
		$this->current_password = $this->password;
		
		return true;
	}
	
	public static function getUserDB() {
		$sql = "SELECT * FROM users WHERE 1";
		$result = DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
		return $result;
	}
	
	public static function getUsers($filter=array(),&$row_count = 0,$order_field='',$order='ASC',$first_row = 0,$list_length = 0) {
		$sql_filter = '';
		if(is_array($filter)) {
			foreach ($filter as $key => $val)
			{
				$filter[$key] = DB::escapeString($val);
			}
			
			if (isset($filter['enabled']) && strlen($filter['enabled']))
			{
				$enabled = 0;
				if($filter['enabled']) {
					$enabled = 1;
				}
				$sql_filter .= " AND users.enabled='{$enabled}'";
			}
			
			if (isset($filter['last_name']) && $filter['last_name'])
			{
				$sql_filter .= " AND users.last_name LIKE '{$filter['last_name']}%'";
			}
			
			if (isset($filter['first_name']) && $filter['first_name'])
			{
				$sql_filter .= " AND users.first_name LIKE '{$filter['first_name']}%'";
			}
			
			if (isset($filter['attorney']) && strlen($filter['attorney']))
			{
				$sql_filter .= " AND users.attorney='{$filter['attorney']}'";
			}
			
			if (isset($filter['username']) && $filter['username'])
			{
				$sql_filter .= " AND users.username LIKE '{$filter['username']}%'";
			}
			
			if (isset($filter['firm']) && $filter['firm'])
			{
				$sql_filter .= " AND users.firm='{$filter['firm']}'";
			}
			
			if (isset($filter['city']) && $filter['city'])
			{
				$sql_filter .= " AND users.city='{$filter['city']}'";
			}
			
			if (isset($filter['county']) && $filter['county'])
			{
				$sql_filter .= " AND users.county='{$filter['county']}'";
			}
			
			if (isset($filter['group_id']) && $filter['group_id'])
			{
				$sql_filter .= " AND users.group_id='{$filter['group_id']}'";
			}
			
			if (isset($filter['user_id']) && is_numeric($filter['user_id'])) {
				$sql_filter .= " OR users.user_id = {$filter['user_id']}";
			}
			
		}
		
		
		$sql_count = "SELECT COUNT(*) AS nbr FROM users WHERE 1" . $sql_filter;
		$result = DB::query($sql_count) or trigger_error('SQL: ' . $sql_count . " Error: " . DB::error());
		$row_count = 0;
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			$row_count = $row['nbr'];
		}
		$sql = 	"SELECT users.*, FROM_UNIXTIME(MAX(user_sessions.last_updated)) as last_active 
				FROM users 
				LEFT JOIN user_sessions ON user_sessions.user_id = users.user_id 
				WHERE 1" . $sql_filter . " GROUP BY users.user_id";
		
		$sql_order = '';
		if ($order_field && $order)
		{
			if ('name' == $order_field)
			{
				$sql_order .= ' ORDER BY users.last_name ' . $order . ', users.first_name ' . $order;
			}
			elseif ('last_active' == $order_field)
			{
				$sql_order .= ' ORDER BY last_active ' . $order; 
			}
			else
			{
				$sql_order .= " ORDER BY {$order_field} {$order}";
			}
		}
		
		$sql_limit = '';
		if($list_length) {
			$sql_limit = " LIMIT ";
			if($first_row) { $sql_limit .= $first_row . ", "; }
			else { $sql_limit .= "0, "; }
			$sql_limit .= $list_length;
		}
		$sql .= $sql_order . $sql_limit;
		
		$result = DB::query($sql) or trigger_error('SQL: ' . $sql . " Error: " . DB::error());
		
		return $result;
	}
	
	/**
	 * public function getUserArray()
	 *
	 * @param array $filter - field list of parameters to filter sql query - passed to getUsers
	 * @return array - Listing of users keyed by user_id and arranged alphabetically. 
	 * Formatted (Last, First Middle Extra)
	 * 
	 */
	public static function getUserArray($filter = array()) {
		$row_count = 0;
		$user_array = array();
		$result = self::getUsers($filter,$row_count,'name');
		
		while ($row = DBResult::fetchRow($result))
		{
			$user_array[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}
		return $user_array;
	}
	
	
	/**
	 * public function getUserPrefs()
	 * 
	 * Retrieves the serialized array of preferences stored under the session_data variable
	 * and transforms them into an array.  
	 * There are two formats that the array is stored in that this function can translate:
	 * - php serialize()/unserialize()
	 * - session handler serialize *LEGACY* pl_session_write()
	 * 
	 *
	 */
	public function getUserPrefs()
	{
		$prefs_array = array();
		if(strlen($this->session_data) > 0 && is_array(unserialize($this->session_data)))
		{
			$prefs_array = unserialize($this->session_data);
		}	
		else if (strlen($this->session_data) > 0)
		{
			// Deal with legacy values
			$temp = explode(';',$this->session_data);
			foreach ($temp as $field_name_value_string)
			{
				if(strpos($field_name_value_string,'|') !== true)
				{
					$field_name_value = explode('|',$field_name_value_string);
					if(count($field_name_value) == 2 && isset($field_name_value[0]))
					{
						$field_value = explode(":",$field_name_value[1]);
						if(isset($field_value[0]))
						{
							if($field_value[0] == 's')
							{// Its a string index 1 = length index 2 = value 
								$prefs_array[$field_name_value[0]] = trim($field_value[2],'"');
							}
							elseif ($field_value[0] == 'i')
							{// its a number index 1 = value
								$prefs_array[$field_name_value[0]] = $field_value[1];
							}
						}				
					}
				}
			}
		}
		return $prefs_array;	
	}
	
	public static function passStrength($pass = null) 
	{
		$pass_strength = 0;
		if(is_null($pass) || strlen($pass) < 1)
		{
			return $pass_strength;
		}
		if(preg_match('/[a-z]/',$pass))
		{ // All Lower Case Characters
			$pass_strength++;
		}
		if(preg_match('/[A-Z]/',$pass))
		{ // All Upper Case Characters
			$pass_strength++;
		}
		if(preg_match('/[0-9]/',$pass))
		{ // All Numbers
			$pass_strength++;
		}
		if(preg_match('/[^a-z0-9]/i',$pass))
		{ // Any Character that isn't a number or letter a-z,A-Z
			$pass_strength++;
		}
		return $pass_strength;
	}
	
	public function save()
	{
		require_once('pikaSettings.php');
		$password_expire = pl_settings_get('password_expire');
		// AMW - Reset the password expiration if the password has changed.
		if ($this->current_password != $this->password)
		{
			// MDF - If password_expire setting is Unlimited set password_expire to 0
			$this->password_expire = 0;
			if($password_expire > 0)
			{
				// Set the password to expire in X days on 11:59 PM, where X is
				// the system's standard password expiration period.
				$this->password_expire = mktime(23, 59, 00) + ($password_expire * 24 * 60 * 60);
			}
		}
		
		parent::save();
	}
	
}

?>