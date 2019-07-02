<?php

class pikaUdf extends plBase
{
	function __construct($udf_id = null)
	{
		$this->db_table = 'udfs';
		$this->db_table_id_column = 'udf_id';
		parent::__construct($udf_id);
	}

	public static function getAll($table_name)
	{
		if ($table_name != 'cases')
		{
			return array();
		}

		$u = array();
		$safe_table_name = DB::escapeString($table_name);
		$sql = "SELECT * FROM udfs WHERE table_name = '{$safe_table_name}'";
		$result = DB::query($sql);

		while ($row = DBResult::fetchRow($result))
		{
			$u[$row['udf_id']] = array('label' => $row['label'], 'data_type' => $row['data_type']);
		}

		return $u;
	}

	public static function getByTable($table_name)
	{
		if ($table_name != 'cases')
		{
			return array();
		}

		$u = array();
		$safe_table_name = DB::escapeString($table_name);
		$sql = "SELECT * FROM udfs WHERE table_name = '{$safe_table_name}'";
		$result = DB::query($sql);

		while ($row = DBResult::fetchRow($result))
		{
			$u[$row['label']] = $row['data_type'];
		}

		return $u;
	}

	public static function getLabelsByTable($table_name)
	{
		if ($table_name != 'cases')
		{
			return array();
		}

		$u = array();
		$safe_table_name = DB::escapeString($table_name);
		$sql = "SELECT * FROM udfs WHERE table_name = '{$safe_table_name}'";
		$result = DB::query($sql);

		while ($row = DBResult::fetchRow($result))
		{
			$u[] = $row['label'];
		}

		return $u;
	}
}