<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');


class pikaCaseTab extends plBase 
{
	
	public function __construct($tab_id = null)
	{
		$this->db_table = 'case_tabs';
		parent::__construct($tab_id);
		if(!is_numeric($tab_id)) { // New Record
			$this->created = date('YmdHis');
			$this->last_modified = date('YmdHis');	
			$this->tab_order = $this->getNextOrder();
		}
		
		
	}
	
	public static function getCaseTabsDB($enabled = null) 
	{
		$sql = "SELECT * 
				FROM case_tabs 
				WHERE 1";
		if(!is_null($enabled)) {$sql .= " AND enabled = '1'";}
		$sql .= " ORDER BY tab_order ASC";
		$result = DB::query($sql) or trigger_error(DB::error());
		return $result;
	}
	
	public static function getCaseTabFiles() {
		
		$main_dir = getcwd() . "/modules";
		$main = scandir($main_dir);
		
		$custom_dir = pl_custom_directory() . "/modules";
		$custom = scandir($custom_dir);
		
		$tabs = array();
		$excluded_files = array('autonumber.php');
		foreach ($main as $file) {
			if (!is_dir($file) && $file[0] != '.' && strpos($file,'case-') !== false) {
				$tabs[$file] = $file;
			}
		}
		foreach ($custom as $file) {
			if (!is_dir($file) && $file[0] != '.' && strpos($file,'case-') !== false) {
				$tabs[$file] = $file;
			}
		}
		
		ksort($tabs);
		
		return $tabs;
	}
	
	public function move_up() {
		$sql = "SELECT tab_id 
				FROM case_tabs 
				WHERE 1 AND tab_order < '{$this->tab_order}' 
				ORDER BY tab_order DESC 
				LIMIT 1";
		$result = DB::query($sql);
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			$tab = new pikaCaseTab($row['tab_id']);
			$new_order = $tab->tab_order;
			$tab->tab_order = $this->tab_order;
			$tab->save();
			$tab = null;
			$this->tab_order = $new_order;
			$this->save();
			return $this->tab_order;
		} else {
			return false;
		}
		
	}
	
	public function move_down() {
		$sql = "SELECT tab_id 
				FROM case_tabs 
				WHERE 1 AND tab_order > '{$this->tab_order}' 
				ORDER BY tab_order ASC 
				LIMIT 1";
		$result = DB::query($sql);
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			$tab = new pikaCaseTab($row['tab_id']);
			$new_order = $tab->tab_order;
			$tab->tab_order = $this->tab_order;
			$tab->save();
			$tab = null;
			$this->tab_order = $new_order;
			$this->save();
			return $this->tab_order;
		} else {
			return false;
		}
		
	}
	
	private function getNextOrder() {
		$sql = "SELECT tab_order 
				FROM case_tabs 
				WHERE 1
				ORDER BY tab_order DESC 
				LIMIT 1";
		$result = DB::query($sql);
		if(DBResult::numRows($result) == 1) {
			$row = DBResult::fetchRow($result);
			$next_order = 1 + $row['tab_order'];
			return $next_order;
		} else {
			return 1;
		}
	}
	
	public function save() {
		$this->last_modified = date('YmdHis');
		parent::save($show_sql);
		
	}
	
}

?>