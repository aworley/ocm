<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once('plBase.php');
require_once('pikaAlias.php');

/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaContact extends plBase
{
	public $aliases = array();
	
	public function __construct($contact_id = null)
	{
		$this->db_table = 'contacts';
		parent::__construct($contact_id);
	}
	
	public function save()
	{
		$alias_op = 0;
		
		if ($this->is_new == true) 
		{
			$alias_op = 1;
		}
		
		else if ($this->is_modified == true) 
		{
			$alias_op = 2;
		}
		
		if ($this->is_modified == true || $this->is_new == true) 
		{
			$this->genMetaphone();
			$this->capitolizeNames();
			$this->capitolizeAddress();
			
			/*	This used to autofill the City/State/County based on
				ZIP code, but this is now down in Javascript by the 
				client.
			*/

			// There *must* be a last name.
			if (strlen($this->last_name) < 1) 
			{
				$this->last_name ='NONAME';
			}
		}

		parent::save();
		
		if (1 == $alias_op) 
		{
			$alias = new pikaAlias();
			$alias->setValues($this->getValues());
			$alias->primary_name = '1';
			$alias->save();
		}
		
		else if (2 == $alias_op) 
		{
			$contact_id = $this->contact_id;
			$sql = "SELECT alias_id 
					FROM aliases 
					WHERE 1 
					AND contact_id='{$contact_id}' 
					AND primary_name='1'";
			$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			
			if(mysql_num_rows($result) == 1) {
				$row = mysql_fetch_assoc($result);
				$alias_id = $row['alias_id'];
				$alias = new pikaAlias($row['alias_id']);
			} else { 
				$alias = new pikaAlias();
			}
			$alias->setValues($this->getValues());
			$alias->primary_name = '1';
			$alias->save();
		}
	}
	
	public function addAlias($a)
	{
			$alias = new pikaAlias();
			$alias->setValues($a);
			return $alias;		
	}
	
	
	public function getAliasesDb()
	{
		$sql = "SELECT * 
				FROM aliases 
				WHERE 1
				AND contact_id = '{$this->contact_id}'";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}

	public function getCasesDb()
	{
		$sql = "SELECT cases.*, conflict.conflict_id, conflict.relation_code, menu_relation_codes.label AS role
				FROM conflict LEFT JOIN cases ON conflict.case_id=cases.case_id
				LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
				WHERE conflict.contact_id = '{$this->values['contact_id']}' ORDER BY cases.open_date ASC";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}

	public function getIntakesDb()
	{
		$sql = "SELECT intakes.*
				FROM intakes
				WHERE intakes.client_id = '{$this->values['contact_id']}' ORDER BY intake_id ASC";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
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
		
		$this->mp_first = metaphone($first);
		$this->mp_last = metaphone($last);
	}
		
	public function capitolizeAddress()
	{
		// Automatically make the first letter of these fields uppercase.
		$this->city = ucfirst($this->city);
		$this->county = ucfirst($this->county);
		// States are always uppercase.
		$this->state = strtoupper($this->state);
		return true;
	}
	
	public function metaphoneContactCheck()
	{
		// Ensure that mp_first & mp_last are populated
		if(strlen($this->mp_first) < 1 && strlen($this->first_name) > 1) {
			$this->mp_first = metaphone($this->first_name);
			$this->save();
		}
		if(strlen($this->mp_last) < 1 || strlen($this->last_name) > 1) {
			$this->mp_last = metaphone($this->last_name);
			$this->save();
		}
		
		if (strlen($this->mp_last) > 1)
		{
			// metaphone fields are only 8 chars in size
			$mp_first = substr($this->mp_first, 0, 8);
			$mp_last = substr($this->mp_last, 0, 8);
			
			$match_first = 'mp_first';
			$match_last = 'mp_last';
		}
		
		else
		{
			// just use the entire name if $mp_last is extremely small
			$mp_last = $this->last_name;
			$mp_first = $this->first_name;
			
			$match_first = 'first_name';
			$match_last = 'last_name';
		}
		$ssn_sql = '';
		if (strlen($this->ssn) > 0)
		{
			$ssn_sql = "OR aliases.ssn='{$this->ssn}' ";
		}
		
		
		/*
		Organizations will only have a $last_name, which makes them a
		special case.
		*/
		
		// If $mp_last has a trailing wild card, it will generate too many false hits
		if (!$mp_first && $mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases 
				    LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE 1 AND 
				    (aliases.{$match_last} LIKE '{$mp_last}'
				    {$ssn_sql})
				    AND aliases.contact_id != '{$this->contact_id}'
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		else if ($mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases 
				    LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE 1
				    AND (aliases.{$match_last} LIKE '{$mp_last}' 
				    AND aliases.{$match_first} LIKE '{$mp_first}'
				    {$ssn_sql})
				    AND aliases.contact_id != '{$this->contact_id}'
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		else
		{
			$sql = "SELECT contacts.*, aliases.ssn AS ssn
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE 1 
				    AND aliases.ssn='{$this->ssn}'
				    AND aliases.contact_id != '{$this->contact_id}'
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	public function merge($contact_id = null) {
		if(is_numeric($contact_id)) {
			$merge_contact = new pikaContact($contact_id);
			if(!$merge_contact->is_new) {
				require_once('pikaTempLib.php');
				$merge_contact_row = $merge_contact->getValues();
				$full_name = pikaTempLib::plugin('text_name','full_name',$merge_contact_row);
				$full_phone = pikaTempLib::plugin('text_phone','full_phone',$merge_contact_row);
				$full_address = pikaTempLib::plugin('text_address','full_address',$merge_contact_row);
				
				$notes_addendum = "\n\nMerged with:\n" 
								. $full_name . "\n" 
								. $full_phone . "\n" 
								. $full_address;				
				$this->notes .= $notes_addendum . "\n\n" . $merge_contact->notes;
				$this->save();
				$result = $merge_contact->getCasesDb();
				require_once('pikaCase.php');
				while ($row = mysql_fetch_assoc($result)) {
					//print_r($row);
					$case = new pikaCase($row['case_id']);
					if($case->client_id == $merge_contact->contact_id) {
						$case->client_id = $this->contact_id;
						$case->save();
					}
			
				}
				
				// Begin section to handle custom fields used at a program.
				$result = mysql_query("DESCRIBE cases child_id");
				
				if (mysql_num_rows($result) == 1)
				{
					$sql = "UPDATE cases
						SET child_id='{$this->contact_id}'
						WHERE child_id='{$merge_contact->contact_id}';";
					mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
				}
				// End custom fields section.
				
				$sql = "UPDATE conflict 
						SET contact_id='{$this->contact_id}' 
						WHERE contact_id='{$merge_contact->contact_id}';";
				mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
				
				$result = $merge_contact->getAliasesDb();
				while($row = mysql_fetch_assoc($result)) {
					//print_r($row);
					$alias = new pikaAlias($row['alias_id']);
					if($alias->primary_name != 1) {
						$alias->contact_id = $this->contact_id;
						$alias->save();
					}
				}
				$merge_contact->delete();
				return true;
			}
			return false;
		}
		return false;
	}
	
	public static function calcAge($birth_date = null, $as_of_date = null) {
		$contact_age = 0;
		if(!is_null($birth_date) && $birth_date && $birth_date != '0000-00-00') {
			if(is_null($as_of_date)) {
				$as_of_date = date('Y-m-d');
			}
			
			$as_of_date = strtotime($as_of_date);
			$birth_date = strtotime($birth_date);
			
			if (-3786822000 > $birth_date)
			{
				/* Very early timestamps will crash getdate().  If the birth date
					is earlier than 1850-01-01, assume a data entry error has 
					occured and do not return an age. */
				return;
			}
			
			$as_of_date_array = getdate($as_of_date);
			$birth_date_array = getdate($birth_date);
			$as_of_year = $as_of_date_array['year'];
			$as_of_month = $as_of_date_array['mon'];
			$as_of_day = $as_of_date_array['mday'];
			$birth_year = $birth_date_array['year'];
			$birth_month = $birth_date_array['mon'];
			$birth_day = $birth_date_array['mday'];
			
			$contact_age = $as_of_date_array['year'] - $birth_date_array['year'];
			if($birth_date_array['mon'] > $as_of_date_array['mon'] 
			|| ($as_of_date_array['mon'] == $birth_date_array['mon'] && $birth_date_array['mday'] > $as_of_date_array['mday'])) 
			{
				$contact_age = $contact_age-1;
			} 
			if ($contact_age < 1) {
				$contact_age = 0;
			}
			
		}
		return $contact_age;
	}
	
	public function delete() {
		$result = $this->getAliasesDb();
		while ($row = mysql_fetch_assoc($result)) {
			$alias = new pikaAlias($row['alias_id']);
			$alias->delete();
		}
		$result = $this->getCasesDb();
		require_once('pikaCase.php');
		while ($row = mysql_fetch_assoc($result)) {
			$case = new pikaCase($row['case_id']);
			$case->removeContact($row['conflict_id']);
			if($case->client_id == $this->contact_id) {
				$case->client_id = '';
				$case->save();
			}
			
		}
		parent::delete();
	}
}

?>