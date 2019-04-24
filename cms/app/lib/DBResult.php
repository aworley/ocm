<?php

class DBResult
{
	protected static $mysqli_mode = PIKACMS_MYSQLI_MODE;

	protected function __construct()
	{
		//
	}

	protected function __clone()
	{
		//
	}

	public static function fetchArray($result)
	{
		if (self::$mysqli_mode) {
			return mysqli_fetch_array($result);
		}

		else {
			return mysql_fetch_array($result);
		}
	}

	public static function fetchRow($result)
	{
		if (self::$mysqli_mode) {
			return mysqli_fetch_assoc($result);
		}

		else {
			return mysql_fetch_assoc($result);
		}
	}

	public static function numRows($result)
	{
		if (self::$mysqli_mode) {
			return mysqli_num_rows($result);
		}

		else {
			return mysql_num_rows($result);
		}
	}
}