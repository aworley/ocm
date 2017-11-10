<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
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
class pikaAlias extends plBase 
{
	
	public function __construct($alias_id = null)
	{
		$this->db_table = 'aliases';
		parent::__construct($alias_id);
	}
	
	public function save()
	{
		if ($this->is_modified || $this->is_new) 
		{
			$this->genMetaphone();
			$this->capitolizeNames();
			
			/*	This used to autofill the City/State/County based on
				ZIP code, but this is now down in Javascript by the 
				client.
			*/

			// There *must* be a last name.
			if (strlen($this->last_name) < 1) 
			{
				$this->last_name = 'NONAME';
			}
		}

		parent::save();
	}
	
	
	public function capitolizeNames()
	{
		// Automatically make the first letter of these fields uppercase.
		$this->first_name = ucfirst($this->first_name);
		$this->middle_name = ucfirst($this->middle_name);
		$this->extra_name = ucfirst($this->extra_name);
		$this->last_name = ucfirst($this->last_name);
	}
	
	public function firstNameOnly($str)
	{
		$pos = strpos($str, " ");
		
		if (!($pos === false))
		{
			return substr($str, 0, $pos);
		}
		
		else
		{
			return $str;
		}
	}
	
	public function genMetaphone()
	{
		$first = $this->firstNameOnly($this->first_name);
		$last = $this->last_name;
		
		$this->mp_first = metaphone($first, 8);
		$this->mp_last = metaphone($last, 8);
		
		if (pl_mysql_column_exists('aliases', 'keywords'))
		{
			$this->keywords = pl_keywords_build($this->first_name, $this->middle_name,
					$this->last_name, $this->extra_name);
		}
	}
	
	public function setValue($value_name, $value)
	{
		if ('ssn' == $value_name)
		{
			if (pika_ssn_mode() == 0)
			{
				$value = null;
			}
			
			else if (pika_ssn_mode() == 4)
			{
				$value = substr($value, -4);
			}
		}
		
		parent::setValue($value_name, $value);
	}
}

?>