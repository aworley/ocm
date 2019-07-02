<?php

/**********************************/
/* Pika CMS (C) 2019 Aaron Worley */
/* https://pikasoftware.com        */
/**********************************/

require_once('plBase.php');
require_once('pikaUdf.php');

/**
* class plUdf
* 
* Extends plBase with user-defined fields.
*
* @author Aaron Worley <aaron@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class plBaseWithUdf extends plBase
{
	protected $udf_names = array();

	public function __construct($id = null)
	{
		parent::__construct($id);

		// Load UDF values if this is an existing record with JSON data.
		if (!is_null($id) && strlen($id) > 0 && strlen($this->getValue('udf')) > 0)
		{
			$udfs = json_decode($this->getValue('udf'), true);
			$this->values = array_merge($this->values, $udfs);
		}

		// TODO - Redesign this so this class is not dependent on pikaUdf.
		$this->udf_names = pikaUdf::getLabelsByTable('cases');
	}
	
	/**
	 * protected function dataBuildFieldList()
	 * 
	 * This function iterates through the db_table_columns and generates
	 * the appropriate SQL friendly key=value pairs for INSERT and UPDATE
	 * queries.  Calls dataBuildField for each value to handle empty strings
	 * and escaping
	 *
	 * @param array $data
	 * @return string of key=value pairs separated by commas in SQL
	 * format (ex value1=NULL,value2='2',... etc)
	 */
	protected function dataBuildFieldList($data,$excluded_fields = array()) {
		$sql = '';

		if(!is_array($excluded_fields)) {
			$excluded_fields = array();
		}

		// 'udf' is a JSON data field and should be handled separately.
		$excluded_fields[] = 'udf';

		// Determine whether 'udf' needs to be initialized, or whether we can
		// use MySQL's JSON_SET() to modify variables in 'udf'.
		if (strlen($this->getValue('udf')) == 0)
		{
			$init_udf = true;
			$data_for_udf_init = array();
		}

		else
		{
			$init_udf = false;
		}

		$tmp_data = array();

		foreach ($this->db_table_columns as $key => $field_property)
		{
			$field_sql = '';
			// make sure the data's column name is valid
			if (array_key_exists($key, $data) && !in_array($key,$excluded_fields))
			{
				$field_sql = $this->dataBuildField($key,$data[$key],$field_property['Type']);
				if($field_sql !== false) {
					$tmp_data[] = $field_sql;
				} 
			}
		}

		foreach ($this->udf_names as $udf_field_name)
		{
			// make sure the data's column name is valid
			if (array_key_exists($udf_field_name, $data) && !in_array($udf_field_name,$excluded_fields))
			{
				if ($init_udf)
				{
					$data_for_udf_init[$udf_field_name] = $data[$udf_field_name];
				}

				else
				{
					$safe_data = DB::escapeString($data[$udf_field_name]);
					$tmp_data[] = "udf = JSON_SET(udf, '$.{$udf_field_name}', '{$safe_data}')";
				}
			}
		}

		// If we're initializing the 'udf' field, $tmp_data doesn't have the
		// UDF data yet.  Add it here.
		if ($init_udf && count($data_for_udf_init) > 0)
		{
			$tmp_data[] = 'udf = \'' . DB::escapeString(json_encode($data_for_udf_init)) . '\'';
		}

		$sql = implode(', ',$tmp_data);
		return $sql;
	}

	/**
	 * public function setValue()
	 *
	 * Set a new value for the selected data variable.  Note that UDF variables
	 * cannot be set if the record doesn't yet exist in the database.  Only
	 * "stock" variables are used during intake so this should not pose any
	 * problems.
	 *
	 * @return boolean true
	 */
	public function setValue($name, $value)
	{
		if (array_key_exists($name, $this->db_table_columns)
			|| in_array($name, $this->udf_names))
		{
			$this->values[$name] = $value;
			$this->is_modified = true;
			return true;
		}

		else
		{
			return false;
		}
	}
}

?>