<?php

/***************************************/
/* Pika CMS (C) 2019 Pika Software LLC */
/* https://pikasoftware.com            */
/***************************************/

class plQueryResult
{
	private $rows = null;

	public function __construct($a)
	{
		$this->rows = $a;
	}

	public function fetchRow()
	{
		return array_shift($this->rows);
	}
}