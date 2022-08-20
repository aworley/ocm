<?php

/**********************************/
/* Pika CMS (C) 2009 Aaron Worley */
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
class pikaFlags extends plBase 
{
	
	function __construct($flag_id = null)
	{
		$this->db_table = 'flags';
		parent::__construct($flag_id);
		if(is_null($flag_id)) {
			// New record
			$this->rules = serialize(array());
			$this->created = date('YmdHis');
		} else {
			// Existing Record
			if(strlen($this->rules) > 0) {
				$this->rules = unserialize($this->rules);
			}
		}
		
	}
	
	public static function getFlagsDB($enabled = null) {
		$sql = 'SELECT * FROM flags WHERE 1';
		if (!is_null($enabled)) {
			$sql .= ' AND enabled = 1';
		}
		//echo $sql;
		$result = DB::query($sql) or trigger_error($sql);
		return $result;
	}
	
	
	public static function generateFlags($case_id = null) {
		$flags_triggered = array();
		if(!is_null($case_id)) {
			require_once('pikaCase.php');
			
			$case = new pikaCase($case_id);
			foreach ($case->getValues() as $key => $val) {
				$case_row["cases." . $key] = $val;
			}
		
			$contacts = $case->getContactsDb();
			$client = $contact_counts = array();
			$relation_codes = pl_menu_get('relation_codes');
			foreach ($relation_codes as $code => $label) {
				$contact_counts["relation_code.{$code}"] = 0;
			}
			while($row = DBResult::fetchRow($contacts)) {
				if ($row['contact_id'] == $case->client_id) {
					$client = array();
					foreach ($row as $key => $val) {
						$client["primary_client.{$key}"] = $val;
					}
				}
				$contact_counts["relation_code.{$row['relation_code']}"] += 1;
			}
			$values = array_merge($case_row,$client);
			$values = array_merge($values,$contact_counts);
			//print_r($values);
			$result = self::getFlagsDB('1');
			while ($row = DBResult::fetchRow($result)) {
				$row['rules'] = unserialize($row['rules']);
				foreach ($row['rules'] as $rule) {
					if(self::validateRule($rule,$values)) {
						$flags_triggered[$row['flag_id']] = $row;
						break;
					}
				}
			}
		}
		return $flags_triggered;
	}
	
	
	// validateRule - determines whether a single rule is true or false
	public static function validateRule($rule = null,$values = null) {
		$comparison = true;  // Assume true any violations will break AND condition
		
		if(is_array($rule) && is_array($values)) {
			
			if(isset($rule['and']) && is_array($rule['and'])) {
				$and_rules = $rule['and'];
				unset ($rule['and']);
				$rules[] = $rule;
				foreach ($and_rules as $and_rule) {
					$rules[] = array('field_name' => $and_rule['and_field_name'], 'comparison' => $and_rule['and_comparison'], 'value' => $and_rule['and_value']);
				}
			}
			else { $rules[] = $rule; }
			//print_r($rules);
			//print_r($values);
			foreach ($rules as $current_rule) {
				// Determine if all necessary fields exist for comparison
				if(isset($current_rule['field_name']) && isset($current_rule['comparison'])) {
					//echo "i ran!";
					switch ($current_rule['comparison']) {
						case 1: // is blank
							if (strlen($values[$current_rule['field_name']]) > 0) 
							{
								$comparison = false;
							}
							break;
							
						case 2: // is not blank
							if (strlen($values[$current_rule['field_name']]) < 1) 
							{
								$comparison = false;
							}
							break;
							
						case 3: // !=
							if($values[$current_rule['field_name']] == $current_rule['value']) {
								$comparison = false;
							}
							break;
						case 4: // ==
							if($values[$current_rule['field_name']] != $current_rule['value']) {
								$comparison = false;
							}
							break;
						case 5: // >
							if($values[$current_rule['field_name']] <= $current_rule['value']) {
								$comparison = false;
							}
						case 6: // >=
							if($values[$current_rule['field_name']] < $current_rule['value']) {
								$comparison = false;
							}
							break;
						case 7: // <
							if($values[$current_rule['field_name']] >= $current_rule['value']) {
								$comparison = false;
							}
							break;
						case 8: // <=
							if($values[$current_rule['field_name']] > $current_rule['value']) {
								$comparison = false;
							}
							break;
						case 9: // between
							$comparison = false;
							if(strpos($current_rule['value'],',')) {
								$between = explode(',',$current_rule['value']);
								if(isset($between[0]) && $between[0] && isset($between[1]) && $between[1]) {
									if($between[0] < $values[$current_rule['field_name']] && $between[1] > $values[$current_rule['field_name']]) {
										$comparison = true;
									}
								}
							}
							break;
						case 10: // contains
							$comparison = false;
							if(strpos($values[$current_rule['field_name']],$current_rule['value']) !== false)
							{
								$comparison = true;
							}
							break;
						default:  // Missing or Unknown comparison
							$comparison = false;
							break;
					}
					
				} else {
					$comparison = false;
				}
				if(!$comparison) { break; }
			}
		}
		return $comparison;
	}
	
	public static function generateFields($no_field_prefix = false) {
		$fields_menu = array();
		$sql = "DESCRIBE cases;";
		$table_prefix = 'cases.';
		if($no_field_prefix) {$table_prefix = '';}
		$result = DB::query($sql) or trigger_error($sql);
		while ($row = DBResult::fetchRow($result)) {
			if($row['Key'] != 'PRI') {  // Don't allow Primary Keys  to be shown
				$fields_menu['Cases']["{$table_prefix}{$row['Field']}"] = $row['Field'];
			}
		}
		$table_prefix = 'primary_client.';
		if($no_field_prefix) {$table_prefix = '';}
		$sql = "DESCRIBE contacts;";
		$result = DB::query($sql) or trigger_error($sql);
		while ($row = DBResult::fetchRow($result)) {
			if($row['Key'] != 'PRI') {  // Don't allow Primary Keys  to be shown
				$fields_menu['Client']["{$table_prefix}{$row['Field']}"] = $row['Field'];
			}
		}
		$table_prefix = 'relation_code.';
		if($no_field_prefix) {$table_prefix = '';}
		$relation_codes = pl_menu_get('relation_codes');
		foreach ($relation_codes as $key => $label) {
			$fields_menu['Contact_Counts']["{$table_prefix}{$key}"] = $label;
		}
		return $fields_menu;
	}
	
	public function save() {
		if (is_array($this->rules)) {
			$this->rules = serialize($this->rules);
		} if(strlen($this->rules) < 1) { // empty rules
			$this->rules = serialize(array());
		}
		parent::save();
	}
	
}

?>