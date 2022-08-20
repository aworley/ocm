<?php

/***************************************/
/* Pika CMS (C) 2019 Pika Software LLC */
/* https://pikasoftware.com            */
/***************************************/

function mysql_query($sql)
{
	return DB::query($sql);
}

function mysql_real_escape_string($str)
{
	return DB::escapeString($str);
}

function mysql_fetch_assoc($result)
{
	return DBResult::fetchRow($result);
}

function mysql_num_rows($result)
{
	return DBResult::numRows($result);
}