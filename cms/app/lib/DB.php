<?php

/***************************************/
/* Pika CMS (C) 2019 Pika Software LLC */
/* https://pikasoftware.com            */
/***************************************/

class DB
{
	protected static $link = null;
	protected static $mysqli_mode = PIKACMS_MYSQLI_MODE;

	protected function __construct()
	{
		//
	}

	protected function __clone()
	{
		//
	}

	public static function error()
	{
		if (self::$mysqli_mode) {
			return mysqli_error(self::$link);
		}

		else {
			return mysql_error();
		}

	}

	public static function escapeString($str)
	{
		if (self::$mysqli_mode) {
			return mysqli_real_escape_string(self::$link, $str);
		}

		else {
			return mysql_real_escape_string($str);
		}
	}

	public static function affectedRows()
	{
		if (self::$mysqli_mode) {
			return mysqli_affected_rows(self::$link);
		}

		else {
			return mysql_affected_rows();
		}
	}

	public static function init($host, $db_name, $user, $password)
	{
		static $connection_is_live = false;

		if (self::$mysqli_mode) {
			self::$link = mysqli_connect($host, $user, $password, $db_name);
			return true;
		}

		else {
			/*  Don't trigger any errors if the connection fails, just return false
        		and let the app. code handle the error.
    			*/
			if (false == $connection_is_live)
			{
				$status = mysql_connect($host, $user, $password);

				if ($status !== false)
				{
					$connection_is_live = mysql_select_db($db_name) or trigger_error(mysql_error());
				}
			}

			return $connection_is_live;
		}
	}

	public static function query($sql)
	{
		if (self::$mysqli_mode) {
			return mysqli_query(self::$link, $sql);
		}

		else {
			return mysql_query($sql);
		}
	}
}