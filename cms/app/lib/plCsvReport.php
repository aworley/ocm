<?php

require_once('plCsvReportTable.php');

/**
* plCsvReport - Creates an CSV report display containing one or more instances
* of plCsvReportTable.  Creates first table automatically - for legacy support.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class plCsvReport
{
	public $tables = array();
	public $current_table_index;
	public $title = '';
	public $parameters = array();
	
	public function __construct(){
		$this->title = 'A Report';
		// 2013-08-13 AMW - Removed =& for compatibility with PHP 5.3.
		$this->tables[] = new plCsvReportTable();
		$this->current_table_index = '0';
	}
	
	public function add_table() {
		// 2013-08-13 AMW - Removed =& for compatibility with PHP 5.3.
		$this->tables[] = new plCsvReportTable();
		$this->current_table_index += 1;
	}
	
	public function add_row($a = array()) {
		$this->tables[$this->current_table_index]->add_row($a);
	}
	
	public function add_parameter($name,$parameter) {
		$parameters = $this->parameters;
		$parameters[] = array('name'=>$name,'param'=>$parameter);
		$this->parameters = $parameters;
	}
	
	public function set_sql($sql) {
	}
	
	public function set_header($a = array()) {
		$this->tables[$this->current_table_index]->set_header($a);
	}
	
	public function set_footer($footer = '') {}
	
	public function set_title($title) {
		$this->title = strip_tags($title);
	}
	
	public function set_table_title($title) {
		$this->tables[$this->current_table_index]->set_title($title);
	}
	
	public function display_row_count($value) {
	}
	
	public function display(){
		$buffer = '"' . addslashes($this->title) . '",' . "\n";
		foreach ($this->parameters as $parameter) {
			if(isset($parameter['name']) && $parameter['name'] && isset($parameter['param'])) {
				$buffer .= '"' . addslashes($parameter['name']) . ': ' . addslashes($parameter['param']) . '",' . "\n";
			}
		}
		
		for ($i=0;$i<count($this->tables);$i++) {
			$buffer .= $this->tables[$i]->build(); 	
		}
		
		header("Pragma: public");
		header("Cache-Control: cache, must-revalidate");
		header("Content-type: application/force-download");
		header("Content-Type: text/x-comma-separated-values");
		
		if (pl_settings_get("enable_compression") == false)
		{
			header("Content-Length: " . mb_strlen($buffer));
		}

		// AMW 2013-10-16 - Workaround for new Chrome/CSV behavior.
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false  || 
				strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false)
		{
		    header("Content-Disposition: attachment; filename=\"{$this->title}.csv\"");
		}
		
		else
		{
			header("Content-Disposition: inline; filename=\"{$this->title}.csv\"");
		}
		
		echo $buffer;
		exit();
	}
}

?>