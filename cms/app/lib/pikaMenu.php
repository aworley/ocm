<?php

/***********************************/
/* Pika CMS (C) 2010 Pika Software */
/* http://pikasoftware.com         */
/***********************************/

require_once('plBase.php');

/**
* pikaMenu - Interface to the pika Menu system
*
* @author Matthew Friedlander <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaMenu extends plBase 
{
	
	public $old_menu_value;
	
	public function __construct($menu_name = null, $value = null)
	{
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$this->db_table = $menu_name;
		// Override normal plBase operation disable counter & manually set "primary" key
		$this->db_table_id_column = 'value';
		$this->use_next_id_counter = false;
		
		
		/**
		 * Need to do a little dancing here - the schema of the menu
		 * system is by no means consistent and there is no guarantee
		 * that there will be a primary key.  However, in order to ensure
		 * that options can be created/updated consistently we need to pre-
		 * validate that a value exists at instatiation
		 */
		
		parent::__construct();
		$this->value = $value;
		$this->old_menu_value = $value;
		$this->label = 'Menu Item Label';
		$this->menu_order = self::getNextOrderNumber($menu_name);
		$clean_value = DB::escapeString($value);
		$sql = "SELECT * FROM {$this->db_table} WHERE {$this->db_table_id_column} = '{$clean_value}' LIMIT 1";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		$this->last_query = $sql;
		if (DBResult::numRows($result) == 1)
		{
			$row = DBResult::fetchRow($result);
			$this->loadValues($row);		
		}
			
		
	}
	
	/**
	 * public static getMenuAllDB()
	 * 
	 * Returns mysql result set containing all tables with the 'menu_' prefix.
	 *
	 * @return mysql $result set
	 */
	public static function getMenuAllDB() {
		$sql = "SHOW TABLES LIKE 'menu_%';";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
	/**
	 * public static getMenuAll()
	 * 
	 * Returns list of all menus in an array that result from getMenuAllDB
	 *
	 * @return array $menu_array
	 */
	public static function getMenuAll() 
	{
		$result = self::getMenuAllDB();
		$menu_array = array();
		while($row = DBResult::fetchArray($result))
		{
			$menu_array[$row[0]] = $row[0];
		}
		return $menu_array;
	}
	
	
	/**
	 * public static getMenuDB()
	 *
	 * Returns the list of menu items as a mysql $result
	 * 
	 * @param string $menu_name
	 * @return object mysql $result set
	 */
	public static function getMenuDB($menu_name = null) 
	{
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$safe_menu_name = DB::escapeString($menu_name);
		
		$sql = "SELECT * 
				FROM {$safe_menu_name} WHERE 1 
				ORDER BY menu_order;";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		return $result;
	}
	
	/**
	 * public static getMenu()
	 *
	 * Returns an array of menu items organized by array(value1=>menu row,value2=>menu row,...etc)
	 * organized sequentially by menu_order.
	 * 
	 * @todo implement ability to filter menu based on pika rules (ex if funding = something only return these)
	 * @param string $menu_name
	 * @return unknown
	 */
	public static function getMenu($menu_name = null) 
	{
		$result = self::getMenuDB($menu_name);
		$menu_array = array();
		while($row = DBResult::fetchRow($result))
		{
			$menu_array[$row['value']] = $row['label'];
		}
		return $menu_array;
	}
	
	
	/**
	 * public static setMenu()
	 *
	 * Replacement for pl_menu_set.  Replaces all values in menu with provided array.
	 * 
	 * @param string $menu_name
	 * @param array $menu_array
	 * @return boolean true
	 */
	public static function setMenu($menu_name = null,$menu_array = array())
	{
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$safe_menu_name = DB::escapeString($menu_name);
		$sql = "DELETE FROM {$safe_menu_name};";
		DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
		if(is_array($menu_array) && count($menu_array) > 0)
		{
			$menu_order = 0;
			foreach ($menu_array as $value => $label) {
				$menu_item = new pikaMenu($menu_name,$value);
				if(is_array($label)) 
				{
					$label['menu_order'] = $menu_order;
					$menu_item->setValues($label);	
				}
				else 
				{
					$menu_item->label = $label;
					$menu_item->menu_order = $menu_order;	
				}
				$menu_item->save();
				$menu_order++; 	
			}
		}
		return true;
	}
	
	/**
	 * public function resetMenuOrder()
	 * 
	 * Sets the menu_order to be in numerical sequence.  Normally
	 * this would be automatically called from delete(), however,
	 * in some instances menu_order has been set to the primary key
	 * so we need to remember to call this on a delete call.
	 * 
	 * @return bool true
	 */
	public static function resetMenuOrder($menu_name)
	{
		$menu_array = array();
		$result = self::getMenuDB($menu_name);
		while($row = DBResult::fetchRow($result))
		{
			$value = $row['value'];
			unset($row['value']);
			unset($row['menu_order']);
			$menu_array[$value] = $row; 
		}
		return self::setMenu($menu_name,$menu_array);
	}
	
	
	/**
	 * 
	 */
	public static function describeTableDB($menu_name)
	{
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$result = parent::describeTableDB($menu_name);
		return $result;
	}
	
	public static function describeTable($menu_name) {
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$describe_array = parent::describeTable($menu_name);
		return $describe_array;
	}
	
	/**
	 * public static menuValueExists()
	 * 
	 * This function is necessary during obj instatiation to determine whether
	 * to insert or update a newly created instance of pikaMenu.  Normally this
	 * could be done by the base plBase class, however, the schema for menus is not
	 * consistent and there is no guarantee that there is a primary key set or what field
	 * it is set on in the database.
	 *
	 * @param string $menu_name
	 * @param string $value
	 * @return boolean true|false
	 */
	public static function menuValueExists($menu_name,$value) 
	{
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$safe_menu_name = DB::escapeString($menu_name);
		$safe_value = DB::escapeString($value);
		
		$sql = "SELECT * 
				FROM {$safe_menu_name} 
				WHERE 1
				AND value='{$safe_value}'
				LIMIT 1;";
		
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		if(DBResult::numRows($result) == 1)
		{
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * public static menuExists()
	 * 
	 * This function determines if a supplied menu name exists in the database
	 *
	 * @param string $menu_name - Can either be full name (ex menu_yes_no) or name w/o
	 * prefix (ex. yes_no)
	 * @return boolean true|false
	 */
	public static function menuExists($menu_name) 
	{
		$menu_exists = false;
		if(!is_null($menu_name) && strlen($menu_name) > 0) 
		{
			if(substr($menu_name,0,5) !== 'menu_') 
			{
				$menu_name = 'menu_' . $menu_name;
			}
			$sql = "SHOW TABLES LIKE 'menu_%';";
			$result = DB::query($sql);
			while ($result !== false && $row = DBResult::fetchRow($result))
			{
				foreach ($row as $key => $val) 
				{
					if ($val == $menu_name) 
					{
						$menu_exists = true;
					}
				}
			}
		}
		return $menu_exists;
	}
	
	/**
	 * public static getNextOrderNumber()
	 *
	 * Determines the next sequential menu_order for menu item creation
	 * 
	 * @todo create another library class that implements ordering
	 * @param string $menu_name
	 * @return int $next_order
	 */
	public static function getNextOrderNumber($menu_name) {
		if(substr($menu_name,0,5) !== 'menu_') 
		{
			$menu_name = 'menu_' . $menu_name;
		}
		$safe_menu_name = DB::escapeString($menu_name);
		$sql = "SELECT MAX(menu_order) as next_order
				FROM {$safe_menu_name}";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		if(DBResult::numRows($result) == 1)
		{
			$row = DBResult::fetchRow($result);
			$next_order = $row['next_order'] + 1;
			return $next_order;
		}
		else 
		{
			return '0';
		}
		
	}
	
	/**
	 * public moveUp()
	 *
	 * Moves a menu_item up in the order list
	 * 
	 * @return bool true|false
	 */
	public function moveUp() {
		$sql = "SELECT value
				FROM  {$this->db_table}
				WHERE 1 AND menu_order < '{$this->menu_order}' 
				ORDER BY menu_order DESC 
				LIMIT 1";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		if(DBResult::numRows($result) == 1) {
			
			$row = DBResult::fetchRow($result);
			
			$menu_item = new pikaMenu($this->db_table,$row['value']);
			$new_order = $menu_item->menu_order;
			$menu_item_row = $menu_item->getValues();
			$menu_item_row['menu_order'] = $this->menu_order;
			$menu_item->delete();
			$menu_item = null;
			$this->menu_order = $new_order;
			
			$this->save();
			
			$menu_item = new pikaMenu($this->db_table,$menu_item_row[$this->db_table_id_column]);
			$menu_item->setValues($menu_item_row);
			
			$menu_item->save();
			$menu_item = null;
			
			return $this->menu_order;
		} else {
			return false;
		}
		
	}
	
	/**
	 * public moveDown()
	 * 
	 * Moves a menu item down in the order list.
	 *
	 * @return bool true|false
	 */
	public function moveDown() {
		$sql = "SELECT value 
				FROM {$this->db_table} 
				WHERE 1 AND menu_order > '{$this->menu_order}' 
				ORDER BY menu_order ASC 
				LIMIT 1";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());;
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			
			$menu_item = new pikaMenu($this->db_table,$row['value']);
			$new_order = $menu_item->menu_order;
			$menu_item_row = $menu_item->getValues();
			$menu_item_row['menu_order'] = $this->menu_order;
			$menu_item->delete();
			$menu_item = null;
			
			$this->menu_order = $new_order;
			$this->save();
			
			$menu_item = new pikaMenu($this->db_table,$menu_item_row[$this->db_table_id_column]);
			$menu_item->setValues($menu_item_row);
			$menu_item->save();
			$menu_item = null;
			
			
			
			return $this->menu_order;
		} else {
			return false;
		}
		
	}
	
	/**
	 * private function tableAutosqlInsert()
	 *
	 * * Builds SQL INSERT statement tailored to the current table
	 * 
	 * @param array $data
	 * @return unknown
	 */
	private function tableAutosqlInsert($data)
	{
		if(!is_array($data)) 
		{
			trigger_error('Invalid data array supplied to tableAutosqlUpdate');
		}
		$primary_key = $this->db_table_id_column;
		
		
		$sql = "INSERT {$this->db_table} SET ";
		$sql .= $this->dataBuildFieldList($data,array());
		$sql .= ";";
		
		return $sql;
	}
	
	public function save()
	{
		if(strlen($this->value) == 0)
		{
			return true;
		}
		
		if(!is_numeric($this->menu_order))
		{
			$this->menu_order = self::getNextOrderNumber($this->db_table);
		}
		
		$sql = "DELETE FROM {$this->db_table} 
				WHERE `value` = '{$this->old_menu_value}' 
				LIMIT 1;";
		DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		echo $sql;
		
		$sql = $this->tableAutosqlInsert($this->values);
		echo $sql;
		DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
		$this->last_query = $sql;
		$this->is_new = false;
		$this->is_modified = false;
		return DB::affectedRows();
		
		
	}
	
	
	
	
}

?>