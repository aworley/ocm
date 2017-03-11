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
class pikaCase extends plBase 
{
	private $contacts = array();
	private $attorneys = array();
	private $activities = array();
	private $docs = array();
	private $compens = array();
	
	
	
	public function __construct($case_id = null)
	{
		global $auth_row;		
		$autonumber_on_new_case = pl_settings_get('autonumber_on_new_case');
		$this->db_table = 'cases';

		parent::__construct($case_id);

		if (is_null($case_id)) 
		{
			// MySQL 4.1+ will accept the old TIMESTAMP syntax, so use that.
			$this->setValue('created', date('YmdHis'));
			$this->setValue('office', $_SESSION['def_office']);
			$this->setValue('intake_user_id', $auth_row['user_id']);
			
			if (strlen($this->getValue('open_date')) < 1)
			{
				$this->setValue('open_date', date('Y-m-d'));
			}
			
			if (strlen($this->getValue('intake_type')) < 1)
			{
				$this->setValue('intake_type', $_SESSION['def_intake_type']);
			}
			
		}
		
		// other stuff
		if ((true == $autonumber_on_new_case && is_null($case_id)) || 'auto' == $this->getValue('number'))
		{
			$this->generateCaseNumber();
		}
		
		
		return true;
	}
	
	public function setValue($value_name, $value)
	{
		if ('number' == $value_name && 'auto' == $value)
		{
			$this->generateCaseNumber();
		}
		
		// Don't allow the close date to occur before the open date.
		else if ('close_date' == $value_name) 
		{
			if (strlen($value) < 1 || (strlen($this->values['open_date']) < 1 && strlen($value) < 1))
			{
				parent::setValue($value_name, $value);
			}
			elseif (strlen($this->values['open_date']) > 0 && strtotime($value) >= strtotime($this->values['open_date'])) 
			{
				parent::setValue($value_name, $value);	
			}
			
		}
		
		else if ('outcome_goals' == $value_name)
		{
			// AMW - Do nothing.  I decided it will be more consistant to handle
			// outcomes by adding new methods to pikaCase.  I could have processed
			// them here by extracting the data from the array, but no other
			// data are passed as an array so methods will better match
			// existing conventions.
		}
		
		else 
		{
			parent::setValue($value_name, $value);
		}
	}
	
	
	protected function snapshotClientDataColumn($column, $v)
	{
		//echo $column;
		$case_val = $this->getValue('case_' . $column);
		
		if ($case_val === null || $case_val == '')
		{
			//echo "snapshot triggered";
						
			if (strlen($v) > 0)
			{
				//echo $column . "=" . $v . " ";
				$this->setValue('case_' . $column, $v);
				$this->save();
			}
		}
	}
	/**
	* Add a contact to a case.
	*
	* $c can be a pikaContact object or the contact's ID number.
	* $role is the relation_code describing the contact's relationship to the case.
	*
	* @return boolean
	* @param mixed $c
	* @param integer $role
	*/
	public function addContact($c, $role)
	{
		$contact_id = null;
		$conflict_id = pl_mysql_next_id('conflict');
		
		if (is_object($c))
		{
			if (!isset($c->contact_id))
			{
				trigger_error('Passed object is not a pikaContact: ' . get_class($c));
				return false;
			}
			
			$contact_id = $c->contact_id;
		}
		
		else if (is_numeric($c) && $c > 0)
		{
			$contact_id = $c;
		}
		
		else 
		{
			trigger_error('Passed value is not a valid Contact');
			return false;
		}
		
		/*	Now that all the variables are determined, save the new contact to the db
		and to this object.
		*/
		$case_id = $this->getValue('case_id');
		$sql = "INSERT INTO conflict (conflict_id, contact_id, case_id, relation_code)
					VALUES ('{$conflict_id}', '{$contact_id}', '{$case_id}', '{$role}')";
		mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$this->contacts[$contact_id] = $role;
		
		// Update the conflict of interest information.
		$this->resetConflictStatus();
		
		// If this is the first client for this case, make them the primary client and set age, county, ZIP code fields
		if (1 == $role)
		{
			$sql = "SELECT birth_date, open_date, county, zip 
								FROM conflict 
								LEFT JOIN cases ON conflict.case_id=cases.case_id
								LEFT JOIN contacts ON conflict.contact_id=contacts.contact_id 
								WHERE conflict.case_id={$case_id} AND relation_code='1'";
			$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			
			if (mysql_num_rows($result) == 1)
			{
				$this->setValue('client_id', $contact_id);
				$row = mysql_fetch_assoc($result);
				
				if ($row['birth_date'] && $row['open_date'])
				{
					$this->setValue('client_age', pl_calc_age($row['birth_date'], $row['open_date']));
				}
				
				if ($row['zip'])
				{
				//	$this->setValue('case_zip', $row['zip']);
				}
				
				if ($row['county'])
				{
				//	$this->setValue('case_county', $row['county']);
				}
			}
		}
		
		$this->save();		
		// TODO: optimize by merging the 2 cases UPDATEs
		return true;
	}
	
	/**
	 * Runs the primary conflict check against all case contacts.
	 *
	 * If a potential conflict is found, the poten_conflicts value is set to 1.
	 * Otherwise it is set to 0.  If the '$reset_verification' argument is
	 * 'true', then the case's conflicts value is set to NULL, meaning the user
	 * will be prompted to re-affirm whether a case has conflicts or not.
	 *
	 * @return boolean
	 * @param boolean $reset_verification
	*/
	public function resetConflictStatus($reset_verification = true)
	{
		// New method: use secondary conflict checks in addition to primary conflict check.
		$potentials = $this->fuzzyConflictCheck();
		$tally = sizeof($potentials);

		if ($tally > 0)
		{
			$this->setValue('poten_conflicts', 1);
		}
		
		else
		{
			$this->setValue('poten_conflicts', '0');
		}
		
		if ($reset_verification)
		{
			$this->setValue('conflicts', null);
		}
		
		$this->save();			
		return $this->getValue('poten_conflicts');
	}
	
	
	// describe contacts?  getContactsArray?
	public function getContactsDb()
	{
		$sql = "SELECT	conflict_id, 
						conflict.case_id, 
						conflict.relation_code, 
						menu_relation_codes.label as role,
						contacts.* 
				FROM conflict
				LEFT JOIN contacts ON conflict.contact_id = contacts.contact_id
				LEFT JOIN menu_relation_codes ON conflict.relation_code = menu_relation_codes.value
				WHERE conflict.case_id = '{$this->values['case_id']}'
				ORDER BY menu_relation_codes.value ASC, last_name ASC, first_name ASC, extra_name ASC, middle_name ASC";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	
	public function getNotes($order = 'ASC', $list_length = 50, $first_row = 0, &$row_count, &$total_hours)
	{
		$clean_order = mysql_real_escape_string($order);
		//$clean_first_row = mysql_real_escape_string($first_row);
		//$clean_list_length = mysql_real_escape_string($list_length);
		
		$sql = "SELECT COUNT(*) AS count
				FROM activities
				WHERE case_id='{$this->values['case_id']}';";
		
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$row = mysql_fetch_assoc($result);
		$row_count = $row['count'];
		
		$sql = "SELECT SUM(hours) AS hours
				FROM activities
				WHERE case_id='{$this->values['case_id']}'
				AND completed = '1';";
		
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$row = mysql_fetch_assoc($result);
		$total_hours = $row['hours'];
		
		$sql = "SELECT activities.*,
							users.first_name, 
							users.last_name
				FROM activities
				LEFT JOIN users ON activities.user_id=users.user_id
				WHERE case_id='{$this->values['case_id']}'
				ORDER BY act_date {$clean_order}, act_time {$clean_order}, last_changed {$clean_order}";
		if ($first_row && $list_length){
			$sql .= " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$sql .= " LIMIT $list_length";
		}
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	public function makeClientDataSnapshot($primary_client)
	{
		$this->snapshotClientDataColumn('address', $primary_client->address);
		$this->snapshotClientDataColumn('address2', $primary_client->address2);
		$this->snapshotClientDataColumn('city', $primary_client->city);
		$this->snapshotClientDataColumn('state', $primary_client->state);
		$this->snapshotClientDataColumn('zip', $primary_client->zip);
		$this->snapshotClientDataColumn('county', $primary_client->county);
		
		// It would be more efficient to run $this->save here, but the changes
		// don't actually get saved.
	}
	

	protected function generateCaseNumber()
	{
		// Handle custom templates.
		if (file_exists(pl_custom_directory() . "/modules/autonumber.php")) {
			require_once(pl_custom_directory() . '/modules/autonumber.php');
		} else {
			require_once('modules/autonumber.php');	
		}
		
		$new_case_no = autonumber($this->getValues());
		
		// Before proceeding, make certain that this case number does not already exist.
		$clean_new_case_no = mysql_real_escape_string($new_case_no);
		$sql = "SELECT count(*) AS tally FROM cases WHERE number = '{$clean_new_case_no}'";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$row = mysql_fetch_assoc($result);
		
		if ($row['tally'] > 0) 
		{
			$new_case_no = null;
		}
		
		$this->setValue('number', $new_case_no);
	}
	
	
	public function save()
	{
		if ($this->is_modified) 
		{
			parent::setValue('last_changed', null);
			//$this->last_changed = null;
		}
		
		parent::save();
	}
	
	
	
	public function delete()
	{
		require_once('pikaDocument.php');
		
		// Delete conflict records.
		$sql = "DELETE FROM conflict WHERE case_id = '{$this->case_id}'";
		mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		// Delete documents.
		$sql = "DELETE FROM doc_storage WHERE 1 AND case_id = '{$this->case_id}';";
		mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		// Clean up orphaned activities
		$sql = "UPDATE activities SET case_id = NULL WHERE 1 AND case_id = '{$this->case_id}';";
		mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		parent::delete();
	}

	
	// Return info on contacts that may be duplicates of case contactss
	public function fuzzyConflictCheck($lim = 10)
	{
		$case_id = $this->getValue('case_id');
		$conflict_array = array();
		$sql = "SELECT conflict.contact_id, relation_code, aliases.mp_first, aliases.mp_last, aliases.ssn, birth_date 
							FROM conflict 
							LEFT JOIN aliases ON conflict.contact_id=aliases.contact_id 
							LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id 
							WHERE case_id='{$case_id}'";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		while ($row = mysql_fetch_assoc($result))
		{
			// Match by contact ID
			$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, status, label AS role
					FROM conflict
					LEFT JOIN contacts ON conflict.contact_id=contacts.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']}
					AND conflict.contact_id = {$row['contact_id']}
					LIMIT $lim";
			$sub_result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			
			while($tmp_row = mysql_fetch_assoc($sub_result))
			{
				$tmp_row['match'] = 'ID';
				$conflict_array[] = $tmp_row;
			}
			
			
			// Match by metaphone name/birth date
			if (strlen($row['mp_first']) > 0)
			{
				$mp_first = " AND aliases.mp_first='{$row['mp_first']}'";
			}
			
			else
			{
				$mp_first = '';
			}
			
			if ($row['birth_date'])
			{
				$mp_first .= " AND (birth_date='{$row['birth_date']}' OR birth_date IS NULL)";
			}
			
			$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, status, label AS role
					FROM contacts
					LEFT JOIN conflict ON contacts.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND mp_last='{$row['mp_last']}'{$mp_first}
					AND conflict.contact_id != {$row['contact_id']}
					LIMIT $lim";
			$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, status, label AS role
					FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
					LEFT JOIN conflict ON aliases.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND aliases.mp_last='{$row['mp_last']}'{$mp_first}
					AND conflict.contact_id != {$row['contact_id']}
					LIMIT $lim";
			$sub_result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			
			while($tmp_row = mysql_fetch_assoc($sub_result))
			{
				$tmp_row['match'] = 'NAME';
				$conflict_array[] = $tmp_row;
			}
			
			// Match by SSN
			if (strlen($row['ssn'] > 0))
			{
				$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, status, label AS role
					FROM contacts
					LEFT JOIN conflict ON contacts.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND ssn='{$row['ssn']}'
					AND conflict.contact_id != {$row['contact_id']} AND mp_last!='{$row['mp_last']}'
					LIMIT $lim";
				$sql = "SELECT conflict.*, contacts.*, number, cases.case_id, problem, status, label AS role
					FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
					LEFT JOIN conflict ON aliases.contact_id=conflict.contact_id
					LEFT JOIN cases ON conflict.case_id=cases.case_id
					LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
					WHERE relation_code != {$row['relation_code']} AND aliases.ssn='{$row['ssn']}'
					AND conflict.contact_id != {$row['contact_id']} AND aliases.mp_last!='{$row['mp_last']}'
					LIMIT $lim";
				$sub_result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
				
				while($tmp_row = mysql_fetch_assoc($sub_result))
				{
					$tmp_row['match'] = 'SSN';
					$conflict_array[] = $tmp_row;
				}
			}
		}
		
		return $conflict_array;
	}
	
	
	/*
	Creates a duplicate of an existing case record with a new case_id.  
	Copies primary client, eligibility information.
	Sets the new case's status to New/Hold, assigns a new case number, ignores other case data.
	Ignores case notes.  Ignores non-primary client case contacts.
	*/
	public function duplicate()
	{
		$autonumber_on_new_case = pl_settings_get('autonumber_on_new_case');
		$dup = new pikaCase();
		
		// Only copy certain data from 'cases' table.
		$dup->setValue('client_id', $this->getValue('client_id'));
		$dup->setValue('children', $this->getValue('children'));
		$dup->setValue('adults', $this->getValue('adults'));
		$dup->setValue('persons_helped', $this->getValue('persons_helped'));
		
		$dup->setValue('income_type0', $this->getValue('income_type0'));
		$dup->setValue('annual0', $this->getValue('annual0'));
		$dup->setValue('income_type1', $this->getValue('income_type1'));
		$dup->setValue('annual1', $this->getValue('annual1'));
		$dup->setValue('income_type2', $this->getValue('income_type2'));
		$dup->setValue('annual2', $this->getValue('annual2'));
		$dup->setValue('income_type3', $this->getValue('income_type3'));
		$dup->setValue('annual3', $this->getValue('annual3'));
		$dup->setValue('income_type4', $this->getValue('income_type4'));
		$dup->setValue('annual4', $this->getValue('annual4'));
		$dup->setValue('income', $this->getValue('income'));
		$dup->setValue('poverty', $this->getValue('poverty'));
		
		$dup->setValue('asset_type0', $this->getValue('asset_type0'));
		$dup->setValue('asset0', $this->getValue('asset0'));
		$dup->setValue('asset_type1', $this->getValue('asset_type1'));
		$dup->setValue('asset1', $this->getValue('asset1'));
		$dup->setValue('asset_type2', $this->getValue('asset_type2'));
		$dup->setValue('asset2', $this->getValue('asset2'));
		$dup->setValue('asset_type3', $this->getValue('asset_type3'));
		$dup->setValue('asset3', $this->getValue('asset3'));
		$dup->setValue('asset_type4', $this->getValue('asset_type4'));
		$dup->setValue('asset4', $this->getValue('asset4'));
		$dup->setValue('assets', $this->getValue('assets'));
		
		$dup->setValue('citizen', $this->getValue('citizen'));
		$dup->setValue('referred_by', $this->getValue('referred_by'));
		$dup->setValue('case_county', $this->getValue('case_county'));
		$dup->setValue('case_zip', $this->getValue('case_zip'));
		
		if ($this->valueExists('kids_ages')) 
		{
			$dup->setValue('kids_ages', $this->getValue('kids_ages'));
		}
		
		if ($autonumber_on_new_case)
		{
			// if this is unset, the case number won't generate properly
			$dup->setValue('office', $this->getValue('office'));
		}
		
		// now take care of setting up the new conflict record for the primary client
		$dup->addContact($this->getValue('client_id'), 1);
		$dup->save();
		return $dup;
	}
	
	
	public function removeContact($conflict_id)
	{
		$sql = "DELETE FROM conflict WHERE conflict_id='{$conflict_id}' AND case_id='{$this->case_id}' LIMIT 1";
		mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		if (mysql_affected_rows() != 1) 
		{
			trigger_error("Error: " . mysql_affected_rows() . " rows deleted");
		}
		
		$this->resetConflictStatus();
		return true;
	}
	
	public function getCaseAttorneysDB()
	{
		$sql = "(SELECT users.* FROM users JOIN cases ON cases.user_id = users.user_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1)
				UNION
				(SELECT users.* FROM users JOIN cases ON cases.cocounsel1 = users.user_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1)
				UNION
				(SELECT users.* FROM users JOIN cases ON cases.cocounsel2 = users.user_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1);";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	public function getCasePbAttorneysDB()
	{
		$sql = "(SELECT pb_attorneys.* FROM pb_attorneys JOIN cases ON cases.pba_id1 = pb_attorneys.pba_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1)
				UNION
				(SELECT pb_attorneys.* FROM pb_attorneys JOIN cases ON cases.pba_id2 = pb_attorneys.pba_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1)
				UNION
				(SELECT pb_attorneys.* FROM pb_attorneys JOIN cases ON cases.pba_id3 = pb_attorneys.pba_id WHERE 1 AND case_id = '{$this->case_id}' LIMIT 1);";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	public function deleteOutcomes()
	{
		$sql = "DELETE FROM outcomes WHERE case_id = {$this->case_id}";		
		return mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
	}
	
	public function addOutcome($outcome_goal_id, $result)
	{
		require_once('pikaOutcome.php');
		
		$o = new pikaOutcome();
		$o->case_id = $this->case_id;
		$o->outcome_goal_id = $outcome_goal_id;
		$o->result = $result;
		$o->save();
		return true;	
	}
}


?>
