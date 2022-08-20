<?php
/*	The pikaCMS object encapsulates all data manipulation & retrieval logic for Pika CMS.
*/
/**
*
* pikaCms
*
* @author   Aaron Worley <amworley@pikasoftware.net>
* @version  v 3
* @access   public
*/
class pikaCms
{
	var $doc_library_path = '/library';
	
	// CASES
	
	function fetchCase($case_id, $number='')
	{
		if($number)
		{
			$sql = "SELECT * FROM cases WHERE number='$number' LIMIT 1";
		}
		
		else
		{
			$sql = "SELECT * FROM cases WHERE case_id='$case_id' LIMIT 1";
		}
		
		// echo $sql;
		
		return DB::query($sql);
	}
	
	
	function newCase($a)
	{
		global $plUserId, $pikaDefIntake;
		
		is_array($a) or
		die(pl_html_error_notice('Pika is sick', 'new case variable not an array'));
		
		$a['case_id'] = pl_new_id('cases');
		$a['intake_user_id'] = $plUserId;
		
		if (!isset($a['intake_type']) || !(strlen($a['intake_type']) > 0))
		{
			$a['intake_type'] = $pikaDefIntake;
		}
		
		if (!isset($a['fingerprint']) || !(strlen($a['fingerprint']) > 0))
		{
			$a['fingerprint'] = md5(uniqid(rand(), true));
		}
		
		// Open date always needs to be set, so that the client age can be calculated
		if (!array_key_exists('open_date', $a) || !$a['open_date'])
		{
			$a['open_date'] = date('Y-m-d');
		}
		
		if ($a["number"] == 'auto')
		{
			$a["number"] = $this->generateCaseNumber($a);
		}
		
		$a['created'] = date('YmdHis');
		
		$sql = pl_build_sql('INSERT', 'cases', $a);
		$result = DB::query($sql);
		
		return $a['case_id'];
	}
	
	
	function updateCase($a)
	{
		if (array_key_exists('number', $a) && $a["number"] == 'auto')
		{
			$a["number"] = $this->generateCaseNumber($a);
		}
		
		if (array_key_exists("open_date", $a))
		{
			$a["open_date"] = pl_mogrify_date($a["open_date"]);
		}
		
		if (array_key_exists("close_date", $a))
		{
			$a["close_date"] = pl_mogrify_date($a["close_date"]);
		}
				
		// Don't allow the close date to occur before the open date
		if (array_key_exists("open_date", $a) && array_key_exists("close_date", $a) 
			&& strlen($a['close_date']) > 0 && strlen($a['open_date']) > 0
			&& strtotime($a['close_date']) < strtotime($a['open_date']))
		{
			unset($a['close_date']);
		}
		
		$sql = pl_build_sql('UPDATE', 'cases', $a);
		
		$result = DB::query($sql);
		
		return 1;
	}
	
	
	/*
	Creates a duplicate of an existing case record with a new case_id.  
	Copies primary client, eligibility information.
	Sets the new case's status to New/Hold, assigns a new case number, ignores other case data.
	Ignores case notes.  Ignores non-primary client case contacts.
	*/
	function duplicateCase($case_id)
	{
		global $plSettings, $plFields;
		
		// Get copy of original case info
		$result = $this->fetchCase($case_id);
		$row = DBResult::fetchRow($result);

		// Only copy certain data from 'cases' table
		$case_info["client_id"] = $row['client_id'];
		$case_info["children"] = $row["children"];
		$case_info["adults"] = $row["adults"];
		
		$case_info["income_type0"] = $row["income_type0"];
		$case_info["annual0"] = $row["annual0"];
		$case_info["income_type1"] = $row["income_type1"];
		$case_info["annual1"] = $row["annual1"];
		$case_info["income_type2"] = $row["income_type2"];
		$case_info["annual2"] = $row["annual2"];
		$case_info["income_type3"] = $row["income_type3"];
		$case_info["annual3"] = $row["annual3"];
		$case_info["income_type4"] = $row["income_type4"];
		$case_info["annual4"] = $row["annual4"];
		
		$case_info["asset_type0"] = $row["asset_type0"];
		$case_info["asset0"] = $row["asset0"];
		$case_info["asset_type1"] = $row["asset_type1"];
		$case_info["asset1"] = $row["asset1"];
		$case_info["asset_type2"] = $row["asset_type2"];
		$case_info["asset2"] = $row["asset2"];
		$case_info["asset_type3"] = $row["asset_type3"];
		$case_info["asset3"] = $row["asset3"];
		$case_info["asset_type4"] = $row["asset_type4"];
		$case_info["asset4"] = $row["asset4"];
		
		$case_info["kids_ages"] = $row["kids_ages"];
		$case_info["citizen"] = $row["citizen"];
		$case_info["referred_by"] = $row["referred_by"];
		$case_info["county"] = $row["county"];
		$case_info["zip"] = $row["zip"];
				
		if ($plSettings['autonumber_on_new_case'])
		{
			// if this is unset, the case number won't generate properly
			$case_info["office"] = $row["office"];
			
			$case_info["number"] = 'auto';
		}		
		
		// create duplicated case, save its case_id
		$case_id = $this->newCase($case_info);
		
		// now take care of setting up the new conflict record for the primary client
		$this->addCaseContact($case_id, $case_info['client_id'], CLIENT);
		
		return $case_id;
	}
	
	
	/*
	When MySQL officially supports UNION statements, it will be possible to
	delete any 'primary_name' aliases records and to drop the primary_name field.
	Investigate performance first.
	*/
	function newAlias($data)
	{
		if (!is_array($data))
		{
			return false;
		}
		
		if ($data["first_name"])
		{
			$data["mp_first"] = metaphone(_pika_first_name_only($data["first_name"]));
		}
		
		if ($data["last_name"])
		{
			$data["mp_last"] = metaphone($data["last_name"]);
		}
		
		$data['first_name'] = ucfirst($data['first_name']);
		$data['middle_name'] = ucfirst($data['middle_name']);
		$data['extra_name'] = ucfirst($data['extra_name']);
		$data['last_name'] = ucfirst($data['last_name']);
		
		$data['alias_id'] = pl_new_id('aliases');
		
		$sql = pl_build_sql('INSERT', 'aliases', $data);
		DB::query($sql);
		
		return true;
	}
	
	function updateAlias($data)
	{
		if ($data["first_name"])
		{
			$data["mp_first"] = metaphone(_pika_first_name_only($data["first_name"]));
		}
		
		if ($data["last_name"])
		{
			$data["mp_last"] = metaphone($data["last_name"]);
		}
		
		$data['first_name'] = ucfirst($data['first_name']);
		$data['middle_name'] = ucfirst($data['middle_name']);
		$data['extra_name'] = ucfirst($data['extra_name']);
		$data['last_name'] = ucfirst($data['last_name']);

		
		if (is_numeric($data['alias_id']))
		{
			$sql = pl_build_sql('UPDATE', 'aliases', $data);
			DB::query($sql);
		
			return true;
		}
		
		else if ($data['contact_id'] && true == $data['primary_name'])
		{
			$sql = "SELECT alias_id FROM aliases WHERE contact_id={$data['contact_id']} AND primary_name=1";
			$result = DB::query($sql);
			$row = DBResult::fetchRow($result);
			
			$data['alias_id'] = $row['alias_id'];
			
			$sql = pl_build_sql('UPDATE', 'aliases', $data);
			DB::query($sql);
			
			return true;
		}
		
		else 
		{
			die(pl_html_error_notice('Pika Error', 'Alias update failed'));
		}
	}
	
	function fetchAlias($alias_id='')
	{
		if ($alias_id)
		{
			$sql = "SELECT * FROM aliases WHERE alias_id=$alias_id";
			return DB::query($sql);
		}
	}
	
	function fetchAliases($contact_id)
	{
		if ($contact_id)
		{
			$sql = "SELECT * FROM aliases WHERE contact_id=$contact_id";
			return DB::query($sql);
		}
	}
		
	function deleteAlias($alias_id='', $contact_id='')
	{
		if ($alias_id && !$contact_id)
		{
			DB::query("DELETE FROM aliases WHERE alias_id=$alias_id LIMIT 1");
		}
		
		else if ($contact_id && !$alias_id)
		{
			DB::query("DELETE FROM aliases WHERE contact_id=$contact_id");
		}
		
		return true;
	}
			
	
	
	// TODO - split this into fetchContact() and fetchContactsByMetaphone()?
	function fetchContact($contact_id='', $last_name='', $first_name='')
	{
		if ($last_name && $first_name)
		{
			$mp_last = metaphone($last_name);
			$mp_first = metaphone(_pika_first_name_only($first_name));
			
			$sql = "SELECT contacts.* FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE aliases.mp_last LIKE '$mp_last' 
				    AND aliases.mp_first LIKE '$mp_first'
				    ORDER BY last_name, first_name";
		}
		
		else if ($last_name)
		{
			$mp_last = metaphone($last_name);
			$sql = "SELECT contacts.* FROM aliases
					LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
					WHERE aliases.mp_last LIKE '$mp_last' ORDER
		    		BY last_name, first_name";
		}
		
		/*
		since there's a specific contact we're looking for, grab the case number
		*/
		else
		{
			$sql = "SELECT * FROM contacts WHERE contact_id='$contact_id'
					LIMIT 1";
		}
		
		// echo $sql;
		
		return DB::query($sql);
	}
	
	function fetchContacts($filter)
	{
		$sql = "SELECT contacts.* FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id WHERE 1";
		
		if ($filter['telephone'])
		{
			/*
			$mp_last = metaphone($last_name);
			$mp_first = metaphone(_pika_first_name_only($first_name));
			*/
			
			$sql .= " AND (phone = '{$filter['telephone']}'
					OR phone_alt = '{$filter['telephone']}')";
		}
		
		if ($filter['notes'])
		{
			$sql .= " AND notes LIKE '%{$filter['notes']}%'";
		}
		
		if ($filter['state_id'])
		{
			$sql .= " AND aliases.state_id='{$filter['state_id']}'";
		}

		$sql .= " ORDER BY last_name, first_name";
		
		// echo $sql;
		
		return DB::query($sql);
	}
	
	/*
	If ZIP is present and other address fields are not, try to fill them in
	based on the ZIP provided.  If ZIP is not present, attempt to fill in ZIP and county 
	information based on City and State.
	
	The mp_first/_last fields should never be user-specified.  They are instead
	calculated and saved in this method.
	*/
	function newContact($a)
	{
		$contact_id = pl_new_id('contacts');
		
		$a["contact_id"] = $contact_id;
		
		if (isset($a["first_name"]))
		{
			$a["mp_first"] = metaphone(_pika_first_name_only($a["first_name"]));
		}
		
		if (isset($a["last_name"]))
		{
			$a["mp_last"] = metaphone($a["last_name"]);
		}

		if ($a["zip"] && (!$a["city"] || !$a["state"] || !$a["county"]))
		{
			$sql = "SELECT * FROM zip_codes WHERE zip='{$a["zip"]}'";
			$result = DB::query($sql);
			
			if (DBResult::numRows($result) >= 1)
			{
				$r = DBResult::fetchRow($result);
				
				if (!$a["city"])
				$a["city"] = $r["city"];
				
				if (!$a["state"])
				$a["state"] = $r["state"];
				
				if (!$a["county"])
				$a["county"] = $r["county"];
			}
		}
		
		else if (!$a['zip'])
		{
			/*
			$city = pl_rm_control($a["city"]);
			$state = pl_rm_control($a["state"]);
			$sql = "SELECT * FROM zip_codes WHERE city='{$city}' AND state='{$state}'";
			*/
			$sql = "SELECT * FROM zip_codes WHERE city='{$a["city"]}' AND state='{$a["state"]}'";
			$result = DB::query($sql);
			
			// if there's more than one zip code in that city, don't auto-fill
			if (DBResult::numRows($result) == 1)
			{
				$r = DBResult::fetchRow($result);
				
				$a["zip"] = $r["zip"];
				
				if (!$a["county"])
				$a["county"] = $r["county"];
			}
		}
		
		// Automatically make the first letter of these fields uppercase
		$a['first_name'] = ucfirst($a['first_name']);
		$a['middle_name'] = ucfirst($a['middle_name']);
		$a['extra_name'] = ucfirst($a['extra_name']);
		$a['last_name'] = ucfirst($a['last_name']);
		$a['city'] = ucfirst($a['city']);
		$a['county'] = ucfirst($a['county']);
		// States are always uppercase
		$a['state'] = strtoupper($a['state']);
		
		$sql = pl_build_sql('INSERT', 'contacts', $a);
		DB::query($sql);
		
		// create the corresponding alias record for this contact record
		$a['primary_name'] = '1'; // true;
		$this->newAlias($a);
		
		return $contact_id;
	}
	
	
	/*
	If ZIP is present and other address fields are not, try to fill them in
	based on the ZIP provided.  If ZIP is not present, attempt to fill in ZIP and county 
	information based on City and State.
	
	The mp_first/_last fields should never be user-specified.  They are instead
	calculated and saved in this method.
	*/
	function updateContact($a)
	{
		/*
		else 
		{
			$a['mp_first'] = '';
		}
		*/
		
		/*
		else 
		{
			$a['mp_last'] = '';
		}
		*/
		
		// Do not allow a user to delete the entire contact name; last_name is the bare minimum
		if ('' == $a['last_name'])
		{
			return 0;
		}
		
		
		if ($a["zip"] && (!$a["city"] || !$a["state"] || !$a["county"]))
		{
			// Weed out 9 digit ZIP codes
			$five_digit_zip = substr($a['zip'], 0, 5);
			
			$sql = "SELECT * FROM zip_codes WHERE zip='{$five_digit_zip}'";
			$result = DB::query($sql);
			
			if (DBResult::numRows($result) >= 1)
			{
				$r = DBResult::fetchRow($result);
				
				if (!$a["city"])
				$a["city"] = $r["city"];
				
				if (!$a["state"])
				$a["state"] = $r["state"];
				
				if (!$a["county"])
				$a["county"] = $r["county"];
			}
		}
		
		else if (!$a['zip'])
		{
			/*
			$city = pl_rm_control($a["city"]);
			$state = pl_rm_control($a["state"]);
			$sql = "SELECT * FROM zip_codes WHERE city='{$city}' AND state='{$state}'";
			*/
			$sql = "SELECT * FROM zip_codes WHERE city='{$a["city"]}' AND state='{$a["state"]}'";
			$result = DB::query($sql);
			
			// if there's more than one zip code in that city, don't auto-fill
			if (DBResult::numRows($result) == 1)
			{
				$r = DBResult::fetchRow($result);
				
				$a["zip"] = $r["zip"];
				
				if (!$a["county"])
				$a["county"] = $r["county"];
			}
		}
		
		if ($a["first_name"])
		{
			$a["mp_first"] = metaphone(_pika_first_name_only($a["first_name"]));
		}
		
		if ($a["last_name"])
		{
			$a["mp_last"] = metaphone($a["last_name"]);
		}
		
		$a['first_name'] = ucfirst($a['first_name']);
		$a['middle_name'] = ucfirst($a['middle_name']);
		$a['extra_name'] = ucfirst($a['extra_name']);
		$a['last_name'] = ucfirst($a['last_name']);
		$a['city'] = ucfirst($a['city']);
		$a['county'] = ucfirst($a['county']);
		
		$a['state'] = strtoupper($a['state']);
		
		
		$sql = pl_build_sql('UPDATE', 'contacts', $a);
		
		$result = DB::query($sql);
		
		// handle this contact's primary alias
		$a['primary_name'] = true;
		$this->updateAlias($a);
		
		return 1;
	}
	
	
	/* 
	Return index # of first record with a last name that matches $str, or is greater
	(alphabetically) than $str.
	*/
	function fetchContactOffset($str)
	{
		// this should all be handled case-insensitively :)
		$str = strtolower($str);
		
		$letter = substr($str, 0, 1);
		
		$vals = explode(",", $str);
		$vals[0] = ltrim($vals[0]);
		
		if (sizeof($vals) > 1)
		{
			$vals[1] = ltrim($vals[1]);
			
			$sql = "SELECT COUNT(*) AS 'position' FROM aliases WHERE last_name LIKE '$letter%' AND
((last_name < '{$vals[0]}') OR (last_name <= '{$vals[0]}' AND first_name < '{$vals[1]}'))";
		}
		
		else
		{
			$sql = "SELECT COUNT(*) AS 'position' FROM aliases WHERE last_name LIKE '$letter%' AND last_name < '{$vals[0]}'";
		}

		$result = DB::query($sql);
		$row = DBResult::fetchRow($result);
			
		return $row['position'];
	}
	
	/*
	function fetchContactOffsetOldSlow($str)
	{
		// this should all be handled case-insensitively :)
		$str = strtolower($str);
		
		$letter = substr($str, 0, 1);
		
		$vals = explode(",", $str);
		
		if (sizeof($vals) > 1)
		{
			$vals[1] = ltrim($vals[1]);
			
			$sql = "SELECT last_name, first_name FROM aliases 
				WHERE last_name LIKE '$letter%' ORDER BY last_name, first_name";

			// echo $sql;

			$result = pl_query($sql);
			
			$z = 0;
			
			while ($row = $result->fetchRow())
			{
				if ($vals[0] <= strtolower($row['last_name'])
					&& $vals[1] <= strtolower($row['first_name']))
				{
					return $z;
				}
				
				elseif ($vals[0] < strtolower($row['last_name']))
				{
					return $z;
				}
				
				$z++;
			}
		}
		
		else
		{
			$sql = "SELECT last_name FROM aliases WHERE last_name LIKE '$letter%' ORDER BY last_name";
			//echo $sql;
			
			$result = pl_query($sql);
			
			$z = 0;
			
			while ($row = $result->fetchRow())
			{
				if ($str <= strtolower($row['last_name']))
				{
					return $z;
				}
				
				$z++;
			}
		}
		
		return $z;
	}
	*/	
	
	// get all contact records, in alphabetical order (within a range)
	function fetchLetterContacts($letter, &$dataset_size, $offset='0', $limit='5')
	{
		// get the total number of contacts
		$result = DB::query("SELECT COUNT(*) AS count FROM aliases WHERE last_name LIKE '$letter%'");
		$row = DBResult::fetchRow($result);
		$dataset_size = $row["count"];
		
		$sql = "SELECT contacts.*, aliases.last_name AS last_name, aliases.first_name AS first_name, aliases.extra_name AS extra_name, aliases.middle_name AS middle_name
			    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id 
				WHERE aliases.last_name LIKE '$letter%'
			    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name
			    LIMIT $offset, $limit";
		
		//echo $sql;
		
		return DB::query($sql);
	}
	
	
	
	
	// CASE-RELATED METHODS
	
	/*
	Fetch information about all cases a contact is involved in
	*/
	function fetchContactCases($contact_id)
	{
		$sql = "SELECT cases.*, conflict.relation_code, menu_relation_codes.label AS role
				FROM conflict LEFT JOIN cases ON conflict.case_id=cases.case_id
				LEFT JOIN menu_relation_codes ON conflict.relation_code=menu_relation_codes.value
				WHERE conflict.contact_id=$contact_id ORDER BY conflict.relation_code ASC";
		return DB::query($sql);
	}
	
	
	// get all contact records related to a case
	function fetchCaseContacts($case_id, $r_type='')
	{
		// sort these by order they were added to the case
		
		$sql = "SELECT conflict.conflict_id, conflict.relation_code, contacts.*, menu_relation_codes.label
			    FROM conflict
			    LEFT JOIN contacts
			    ON conflict.contact_id=contacts.contact_id 
				LEFT JOIN menu_relation_codes
				ON conflict.relation_code=menu_relation_codes.value
				WHERE conflict.case_id=$case_id";
		
		if ($r_type)
		{
			$sql .= " AND conflict.relation_code=$r_type";
		}
		
		$sql .= " ORDER BY relation_code ASC, conflict_id ASC";
		// echo $sql;
		
		return DB::query($sql);
	}
	
	// add a contact to a case
	function addCaseContact($case_id, $contact_id, $relation_code)
	{
		// this prevents duplicate conflict records
		// it slows things down, though
		/*
		$sql = "SELECT conflict_id FROM conflict
				WHERE contact_id=$contact_id
				AND case_id=$case_id
				AND relation_code=$relation_code";
		$res = pl_query($sql);
		if ($res->numRows() != 0)
		{
			return FALSE;
		}
		*/
		$extra_sql = '';
		$conflict_id = pl_new_id('conflict');
		
		$sql = "INSERT INTO conflict
			    (conflict_id, contact_id, case_id, relation_code) VALUES
			    ($conflict_id, $contact_id, $case_id, $relation_code)";
		$result = DB::query($sql);
		
		
		// Make certain that the case's potential conflict field is up-to-date
		/*
		$poten_conflicts = $this->conflictCheck($case_id);

		if (sizeof($poten_conflicts) > 0)
		{
			pl_query("UPDATE cases SET poten_conflicts = 1, conflicts = NULL WHERE case_id=$case_id LIMIT 1");
		}
		
		else 
		{
			pl_query("UPDATE cases SET poten_conflicts = 0, conflicts = NULL WHERE case_id=$case_id LIMIT 1");
		}
		*/
		$this->resetConflictStatus($case_id);
		
		// If this is the first client for this case, make them the primary client and set age, county, ZIP code fields
		if (1 == $relation_code)
		{
			$result = DB::query("SELECT birth_date, open_date, county, zip 
								FROM conflict 
								LEFT JOIN cases ON conflict.case_id=cases.case_id
								LEFT JOIN contacts ON conflict.contact_id=contacts.contact_id 
								WHERE conflict.case_id=$case_id AND relation_code=1");
			
			if (DBResult::numRows($result) == 1)
			{
				$row = DBResult::fetchRow($result);
				
				if ($row['birth_date'] && $row['open_date'])
				{
					$client_age = pl_calc_age($row['birth_date'], $row['open_date']);
					$extra_sql .= ", client_age={$client_age}";
				}
				
				if ($row['zip'])
				{
					$extra_sql .= ", case_zip='{$row['zip']}'";
				}
				
				if ($row['county'])
				{
					$extra_sql .= ", case_county='{$row['county']}'";
				}
				
				DB::query("UPDATE cases SET client_id={$contact_id}{$extra_sql} WHERE case_id='$case_id' LIMIT 1");
			}
		}
		
		// TODO: optimize by merging the 2 cases UPDATEs
		
		return TRUE;
	}
	
	
	// removes a contact from a case
	function deleteConflict($conflict_id, $case_id)
	{
		$sql = "DELETE FROM conflict WHERE conflict_id=$conflict_id LIMIT 1";
		$result = DB::query($sql);
		
		/*
		$poten_conflicts = $this->conflictCheck($case_id);
		if (sizeof($poten_conflicts) > 0)
		{
			pl_query("UPDATE cases SET poten_conflicts = 1, conflicts = NULL WHERE case_id=$case_id LIMIT 1");
		}
		
		else 
		{
			pl_query("UPDATE cases SET poten_conflicts = 0, conflicts = NULL WHERE case_id=$case_id LIMIT 1");
		}
		*/
		
		$this->resetConflictStatus($case_id);
		
		return $result;
	}

	
	// look for other contacts matching a name (using metaphone)
	function metaphoneContactCheck($last_name="", $first_name='', $ssn='')
	{
		$ssn_sql = '';
		$mp_last = metaphone($last_name);
		$mp_first = metaphone(_pika_first_name_only($first_name));
		
		if (strlen($mp_last) > 1)
		{
			// metaphone fields are only 8 chars in size
			$mp_first = substr($mp_first, 0, 8);
			$mp_last = substr($mp_last, 0, 8);
			
			$match_first = 'mp_first';
			$match_last = 'mp_last';
		}
		
		else
		{
			// just use the entire name if $mp_last is extremely small
			$mp_last = $last_name;
			$mp_first = $first_name;
			
			$match_first = 'first_name';
			$match_last = 'last_name';
		}
		
		if ($ssn)
		{
			$ssn_sql = "OR aliases.ssn='$ssn' ";
		}
		
		
		/*
		Organizations will only have a $last_name, which makes them a
		special case.
		*/
		
		// If $mp_last has a trailing wild card, it will generate too many false hits
		if (!$mp_first && $mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE aliases.$match_last LIKE '$mp_last' 
					$ssn_sql
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		else if ($mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE (aliases.$match_last LIKE '$mp_last' 
				    AND aliases.$match_first LIKE '$mp_first')
					$ssn_sql
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		else
		{
			$sql = "SELECT contacts.*, aliases.ssn AS ssn
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE aliases.ssn='$ssn'
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}
		
		//		echo $sql;
		
		return DB::query($sql);
	}
	
	/*
	Return an array of contact_id's for individuals who may pose a risk of conflict
	of interest for a given case
	*/
	function conflictCheck($case_id)
	{
		$conflict_array = array();
		
		$result = DB::query("SELECT contact_id, relation_code FROM conflict WHERE case_id='$case_id'");
		while ($row = DBResult::fetchRow($result))
		{
			$result_b = DB::query("SELECT COUNT(*) AS tally FROM conflict
				WHERE contact_id = {$row['contact_id']} AND relation_code != {$row['relation_code']}");
			$row_b = DBResult::fetchRow($result_b);
			
			if ($row_b['tally'] > 0)
			{
				$conflict_array[] = $row['contact_id'];
			}
		}
		
		return $conflict_array;
	}

	function resetConflictStatus($case_id, $reset_verification = true)
	{
		$tally = 0;
		$result = DB::query("SELECT contact_id, relation_code FROM conflict WHERE case_id='$case_id'");
		$conflict_reset_sql = '';
		
		if ($reset_verification)
		{
			$conflict_reset_sql = ', conflicts = NULL';
		}
		
		while ($row = DBResult::fetchRow($result))
		{
			$result_b = DB::query("SELECT COUNT(*) AS tally FROM conflict
                                WHERE contact_id = {$row['contact_id']} AND relation_code != {$row['relation_code']}");
			$row_b = DBResult::fetchRow($result_b);
			
			$tally += $row_b['tally'];
		}
		
		if ($tally > 0)
		{
			DB::query("UPDATE cases SET poten_conflicts = 1{$conflict_reset_sql} WHERE case_id='$case_id' LIMIT 1");
		}
		
		else
		{
			DB::query("UPDATE cases SET poten_conflicts = 0{$conflict_reset_sql} WHERE case_id='$case_id' LIMIT 1");
		}
			
		return $row_b['tally'];
	}
	
	
	// Return info on contacts that may be duplicates of case contactss
	function fuzzyConflictCheck($case_id, $lim = 10)
	{
		$conflict_array = array();
		$sql = "SELECT conflict.contact_id, relation_code, aliases.mp_first, aliases.mp_last, aliases.ssn, birth_date 
							FROM conflict 
							LEFT JOIN aliases ON conflict.contact_id=aliases.contact_id 
							LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id 
							WHERE case_id='{$case_id}'";		
		$result = DB::query($sql);
		
		while ($row = DBResult::fetchRow($result))
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
			$result_b = DB::query($sql);
			
			while($tmp_row = DBResult::fetchRow($result_b))
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
			$result_b = DB::query($sql);
			
			while($tmp_row = DBResult::fetchRow($result_b))
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
				$result_b = DB::query($sql);
				
				while($tmp_row = DBResult::fetchRow($result_b))
				{
					$tmp_row['match'] = 'SSN';
					$conflict_array[] = $tmp_row;
				}
			}
		}
		
		return $conflict_array;
	}

	
	// look for potential conflicts of interest
	function fetchConflicts($contact_ids)
	{
		if (!is_array($contact_ids))
		{
			die(pl_html_error_notice('Pika is sick', 'No array provided to fetchConflicts()'));
		}
		
		$sql = "SELECT conflict.*, cases.number, cases.problem, cases.status
			    FROM conflict 
				LEFT JOIN cases 
				ON conflict.case_id=cases.case_id WHERE (";
		
		$i = 0;
		while (list($key, $val) = each($contact_ids))
		{
			if (0 == $i)
			{
				$sql .= " contact_id=$val";
			}
			
			else
			{
				$sql .= " OR contact_id=$val";
			}
			
			$i++;
		}
		
		$sql .= ') ORDER BY contact_id';
		
		// echo $sql;
		
		return DB::query($sql);
	}
	
	/*
	function metaphoneConflictCheck($case_id)
	{
		$conflict_array = array();
		
		$sql = "SELECT * FROM conflict LEFT JOIN contacts ON conflict.contact_id=contacts.contact_id
				WHERE relation_code != $rel_code AND ssn='$ssn'";
		$sql = "SELECT * FROM conflict LEFT JOIN contacts ON conflict.contact_id=contacts.contact_id
				WHERE relation_code != $rel_code AND mp_last='' AND mp_first=''";
		
		$result = pl_query("SELECT contact_id, relation_code FROM conflict WHERE case_id=$case_id");
		
		while ($row = $result->fetchRow())
		{
			$result_b = pl_query("SELECT COUNT(*) AS tally FROM conflict
				WHERE contact_id = {$row['contact_id']} AND relation_code != {$row['relation_code']}");
			$row_b = $result_b->fetchRow();
			
			if ($row_b['tally'] > 0)
			{
				$conflict_array[] = $row['contact_id'];
			}
		}
		
		return $conflict_array;
	}
	*/
	
	function fetchNotes($case_id, $order='ASC')
	{
			$sql = "SELECT activities.*,
								users.first_name, 
								users.last_name
					FROM activities
					LEFT JOIN users ON activities.user_id=users.user_id
					WHERE case_id='$case_id'
					ORDER BY act_date $order, act_time $order, last_changed $order";

		// echo $sql;
		
		return DB::query($sql);
	}
	
	/*
	Invoke the case autonumber module, which will generate a new case number
	*/
	function generateCaseNumber($a)
	{
		// Handle custom templates.
		if (file_exists(pl_custom_directory() . "/modules/autonumber.php")) {
			require_once(pl_custom_directory() . '/modules/autonumber.php');
		} else {
			require_once('modules/autonumber.php');	
		}
		
		return autonumber($a);
	}
	
	function fetchOpenCaseList($user_id)
	{
		global $plFields;
		
		// Hack to get the Iowa matching funding field to work
		$mf = '';
		if (isset($plFields['cases']['matching_funding']))
		{
			$mf = ', matching_funding';
		}
		
		$sql = "(SELECT case_id, number, problem, status, cases.user_id, cocounsel1, 
			cocounsel2, office, open_date, close_date, funding, contacts.first_name, 
			contacts.middle_name, contacts.last_name, contacts.extra_name, 
			area_code, phone{$mf} FROM cases LEFT JOIN contacts ON 
			cases.client_id=contacts.contact_id
			WHERE close_date IS NULL AND status IN (1, 2) AND 
			user_id = '{$user_id}')
			UNION
			(SELECT case_id, number, problem, status, cases.user_id, cocounsel1, 
			cocounsel2, office, open_date, close_date, funding, contacts.first_name, 
			contacts.middle_name, contacts.last_name, contacts.extra_name, 
			area_code, phone{$mf} FROM cases LEFT JOIN contacts ON 
			cases.client_id=contacts.contact_id
			WHERE close_date IS NULL AND status IN (1, 2) AND 
			cocounsel1 = '{$user_id}')
			UNION
			(SELECT case_id, number, problem, status, cases.user_id, cocounsel1, 
			cocounsel2, office, open_date, close_date, funding, contacts.first_name, 
			contacts.middle_name, contacts.last_name, contacts.extra_name, 
			area_code, phone{$mf} FROM cases LEFT JOIN contacts ON 
			cases.client_id=contacts.contact_id
			WHERE close_date IS NULL AND status IN (1, 2) AND 
			cocounsel2 = '{$user_id}')
			ORDER BY last_name, first_name ASC";
		
		return DB::query($sql);
	}
	
	function fetchCaseList($filter, &$row_count, $order_field='',
	$order='ASC', $first_row='0', $list_length='100')
	{
		global $pikaOldMysqlMode, $plSettings;
		
		/*	
		this little hack will save a few fractions of a second on case
		lists w/o filters.  Instead of doing a "COUNT(*)" to determine
		the number of records in the resulting list, it uses "SHOW STATUS
		TABLES" to get the number of records in the 'cases' table.  This
		is of course MySQL-specific.
		*/
		$no_filters = true;
		
		$sql = ' FROM cases 
			LEFT JOIN contacts ON cases.client_id=contacts.contact_id
			LEFT JOIN users ON cases.user_id=users.user_id
			WHERE 1 ';
		
		if (isset($filter["case_id"]) && $filter["case_id"])
		{
			$sql .= " AND cases.case_id={$filter['case_id']}";
			$no_filters = false;
		}
		
		if (isset($filter["last_name"]) && $filter["last_name"])
		{
			$filter["last_name"] = pl_double_quotes($filter["last_name"]);
			$sql .= " AND contacts.last_name LIKE '{$filter['last_name']}%'";
			$no_filters = false;
		}
		
		
		if (isset($filter["first_name"]) && $filter["first_name"])
		{
			$filter["first_name"] = pl_double_quotes($filter["first_name"]);
			$sql .= " AND contacts.first_name LIKE '{$filter['first_name']}%'";
			$no_filters = false;
		}
		
		
		if (isset($filter["user_id"]) && $filter["user_id"])
		{
			$sql .= " AND (cases.user_id='{$filter["user_id"]}' OR cases.cocounsel1='{$filter["user_id"]}' OR cases.cocounsel2='{$filter["user_id"]}')";
			$no_filters = false;
		}
		
		
		if (isset($filter["pba_id"]) && $filter["pba_id"])
		{
			$sql .= " AND (cases.pba_id1='{$filter["pba_id"]}' OR cases.pba_id2='{$filter["pba_id"]}' OR cases.pba_id3='{$filter["pba_id"]}')";
			$no_filters = false;
		}

		
		if (isset($filter["client_id"]) && $filter["client_id"])
		{
			$sql .= " AND cases.client_id='{$filter["client_id"]}'";
			$no_filters = false;
		}
		
		
		if (isset($filter["office"]) && $filter["office"])
		{
			$sql .= " AND office='{$filter["office"]}'";
			$no_filters = false;
		}
		
		
		if (isset($filter["status"]) && is_numeric($filter["status"]))
		{
			$sql .= " AND status={$filter["status"]}";
			$no_filters = false;
		}
		
		
		if (isset($filter["opened_before"]) && $filter["opened_before"])
		{
			$sql .= " AND open_date < '{$filter["opened_before"]}'";
			$no_filters = false;
		}
		
		
		if (isset($filter["closed_before"]) && $filter["closed_before"])
		{
			$sql .= " AND close_date < '{$filter["closed_before"]}'";
			$no_filters = false;
		}
		
		
		if (isset($filter["opened_on_after"]) && $filter["opened_on_after"])
		{
			$sql .= " AND open_date >= '{$filter["opened_on_after"]}'";
			$no_filters = false;
		}
		
		
		if (isset($filter["closed_on_after"]) && $filter["closed_on_after"])
		{
			if ('NULL' == $filter["closed_on_after"])
			{
				$sql .= " AND close_date IS NULL";
			}
			
			else if ('NOT NULL' == $filter["closed_on_after"])
			{
				$sql .= " AND close_date IS NOT NULL";
			}
			
			else
			{
				$sql .= " AND close_date >= '{$filter["closed_on_after"]}'";
			}
			
			$no_filters = false;
		}
		
		if (isset($filter["funding"]) && $filter["funding"])
		{
			$sql .= " AND funding='{$filter["funding"]}'";
			
			$no_filters = false;
		}
		
		if ($no_filters == false)
		{
			$result = DB::query('SELECT COUNT(case_id) AS count' . $sql);
			$r = DBResult::fetchRow($result);
			$row_count = $r["count"];
		}
		
		else
		{
			$result = DB::query("SHOW TABLE STATUS FROM {$plSettings['db_name']} LIKE 'cases'");
			$r = DBResult::fetchRow($result);
			$row_count = $r["Rows"];
		}
		
		/*	next, re-run the query, this time sorting the results and only
		retrieving those records that will be displayed on this screen.
		*/
		if ($order_field && $order)
		{
			if ('last_name' == $order_field)
			{
				$order_field = 'contacts.last_name';
			}
			
			$sql .= " ORDER BY $order_field $order";
		}
		
		$sql .= " LIMIT $first_row, $list_length";
		
		$full_sql = 'SELECT case_id, number, problem, status, cases.user_id, cocounsel1, 
			cocounsel2, office, open_date, close_date, funding, client_id, 
			contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
			contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
			area_code, phone, users.first_name as \'users.first_name\', 
			users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
			users.extra_name as \'users.extra_name\' ' . $sql;
		
		// echo "$full_sql";
		
		return DB::query($full_sql);
	}
	
	
	
	// STAFF
	
	function fetchStaff($user_id='')
	{
		if ($user_id)
		$sql = "SELECT * FROM users WHERE user_id='$user_id' LIMIT 1";
		else
		$sql = "SELECT * FROM users ORDER BY last_name";
		
		return DB::query($sql);
	}
	
	
	function newStaff($a)
	{
		// this should only be used when converting data, never when adding an new
		// user to an existing system
		if (!$a['user_id'])
		{
			$a['user_id'] = pl_new_id('users');
		}
		
		$sql = pl_build_sql("INSERT", "users", $a);
		DB::query($sql);
		
		$this->setPassword($a['user_id'], $a['password']);
		
		return $a['user_id'];
	}
	
	
	function updateStaff($a)
	{
		$sql = pl_build_sql("UPDATE", "users", $a);
		$result = DB::query($sql);
		
		if (isset($a['password']))
		{
			$this->setPassword($a['user_id'], $a['password']);
		}
		
		return true;
	}
	
	function updateUserPrefs($user_id, $pref_data)
	{
		global $auth_row;
		$pref_sql = '';
		$themes = array();
		
		// Only system users may edit other user's preferences
		if ('system' != $auth_row['group_id'])
		{
			$user_id = $auth_row['user_id'];
		}

	// Check for valid theme name.  $theme is used on an include(), so it must be carefully screened
	$dh = opendir('themes');
	while ($file = readdir($dh))
	{
		if ($file[0] != '.')
		{
			$themes[] = str_replace('.php', '', $file);
		}
	}

	closedir($dh);
	
	if (!in_array($pref_data['up_theme'], $themes))
	{
		$pref_data['up_theme'] = 'Blue';
	}		
		
		$a = array_merge($auth_row, $pref_data);
		
		foreach ($a as $key => $val)
		{
			if (substr($key, 0, 3) == 'up_')
			{
				$pref_sql .= "$key=$val,";
			}
		}

		$sql = "UPDATE users
			    SET session_data = '$pref_sql'
				WHERE user_id=$user_id LIMIT 1";	
		
		//echo $sql;
		
		$result = DB::query($sql);
		
		return true;
	}
	
	function setPassword($user_id, $password)
	{
		$password_md5 = md5($password);
		
		$sql = "UPDATE users SET password='$password_md5' WHERE user_id=$user_id LIMIT 1";
		
		$result = DB::query($sql);
	}
	
	function fetchStaffArray()
	{
		$sql = "SELECT * FROM users ORDER BY last_name";
		$result = DB::query($sql);
		
		while ($row = DBResult::fetchRow($result))
		{
			$a[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}
		
		return $a;
	}
	
	// returns only "active" staff - determined by who has login access enabled
	function fetchEnabledStaffArray()
	{
		$sql = "SELECT * FROM users where enabled=1 ORDER BY last_name";
		$result = DB::query($sql);
		
		while ($row = DBResult::fetchRow($result))
		{
			$a[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}
		
		return $a;
	}
	
	// GROUPS
	function fetchGroups()
	{
		$sql = "SELECT * FROM `groups`";
		$result = DB::query($sql);
		
		return $result;
	}
	
	function getGroupsMenuArray()
	{
		$a = array();
		$sql = "SELECT group_id FROM `groups`";
		$result = DB::query($sql);
		
		while ($row = DBResult::fetchRow($result))
		{
			$a[$row['group_id']] = $row['group_id'];
		}
		
		return $a;
	}
	
	function addGroup($a)
	{
		$sql = pl_build_sql('INSERT', '`groups`', $a);
		$result = DB::query($sql);
		return $result;
	}
	
	function updateGroup($a)
	{
		$sql = pl_build_sql('UPDATE', '`groups`:', $a);
		$result = DB::query($sql);
		return $result;
	}
	
	// PB ATTORNEYS
	
	function fetchPbAttorney($filter, &$pba_count, $first_row="", $list_length="")
	{
		$sql_filter = "";
		
		// Filter elements need to be escaped
		foreach ($filter as $key => $val)
		{
			$filter[$key] = DB::escapeString($val);
		}
		
		
		if (isset($filter['pba_id']) && $filter['pba_id'])
		{
			$sql = "SELECT * FROM pb_attorneys WHERE pba_id='{$filter['pba_id']}'
				    LIMIT 1";
		}
		else
		{
			if ($first_row && $list_length)
			{
				$sql_limit = " LIMIT $first_row, $list_length";
			}
			
			elseif ($list_length)
			{
				$sql_limit = " LIMIT $list_length";
			}
			
			// handle filter options
			if (isset($filter['county']) && $filter['county'])
			{
				$sql_filter .= " AND county LIKE '%{$filter['county']}%'";
			}
			
			if (isset($filter['languages']) && $filter['languages'])
			{
				$sql_filter .= " AND languages LIKE \"%{$filter['languages']}%\"";
			}
			
			if (isset($filter['practice_areas']) && $filter['practice_areas'])
			{
				$sql_filter .= " AND practice_areas LIKE '%{$filter['practice_areas']}%'";
			}
			
			if (isset($filter['last_name']) && $filter['last_name'])
			{
				$sql_filter .= " AND last_name LIKE '%{$filter['last_name']}%'";
			}

			$sql = "SELECT count(*) FROM pb_attorneys WHERE 1" . $sql_filter;
			
			$result = DB::query($sql);
			
			$row = DBResult::fetchRow($result);
			
			$pba_count = $row["count(*)"];
			
			
			$sql = "SELECT * FROM pb_attorneys WHERE 1" . $sql_filter . " ORDER BY last_name, first_name" . $sql_limit;
		}
		
		return DB::query($sql);
	}
	
	
	
	function newPbAttorney($a)
	{
		global $plMenus;
		
		$a['pba_id'] = pl_new_id("pb_attorneys");
		
		$sql = pl_build_sql('INSERT', 'pb_attorneys', $a);
		$result = DB::query($sql);
		
		return $a['pba_id'];
	}
	
	
	function updatePbAttorney($a)
	{
		global $plMenus;
		
		$sql = pl_build_sql('UPDATE', 'pb_attorneys', $a);
		
		$result = DB::query($sql);
	}
	
	function fetchPbAttorneyArray()
	{
		$sql = "SELECT * FROM pb_attorneys ORDER BY last_name";
		$result = DB::query($sql);
		$a = array();  // make sure we return an empty array if no attys are found
		
		while ($row = DBResult::fetchRow($result))
		{
			$a[$row['pba_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}
		
		return $a;
	}
	
	function setPbAttorneyLastCase($pba_id, $last_case_date)
	{
		$lc = pl_mogrify_date($last_case_date);
		$sql = "UPDATE pb_attorneys SET last_case='$lc' WHERE pba_id='$pba_id' LIMIT 1";
		DB::query($sql);
	}
	
	
	
	// ACTIVITIES
	
	function fetchActivity($act_id='', $act_code='', $start_date='',
		$end_date='', $user_id='', $case_id='')
	{
		if ($act_id)
		{
			$sql = "SELECT activities.*, cases.number FROM activities LEFT JOIN cases ON activities.case_id=cases.case_id WHERE act_id='$act_id' LIMIT 1";
			
			return DB::query($sql);
		}
		
		else if ($start_date && $end_date)
		{
			$sql = "SELECT *
		    FROM activities	
		    WHERE act_date>='$start_date'
		    AND act_date<='$end_date'";
			
			if ($case_id)
			{
				$sql .= " AND case_id='$case_id' ";
			}
			
			if ($user_id)
			{
				$sql .= " AND user_id='$user_id' ";
			}
			
			$sql .= ' ORDER BY user_id, act_time';
			
			// echo $sql;
			
			return DB::query($sql);
		}
		
		else
		{
			$sql = "SELECT *
				    FROM activities
				    WHERE act_code='$act_code'";
			return DB::query($sql);
		}
		
	}
	
	
	function fetchActivities($filter, &$contact_count, $order_field='act_date', $order='ASC',
	$first_row='0', $list_length='30')
	{
		$sql = ' FROM activities WHERE 1';
		
		if (isset($filter["act_date"]) && $filter["act_date"])
		{
			if ('NULL' == $filter["act_date"])
			{
				$sql .= " AND act_date IS NULL";
			}
			
			else 
			{
				$sql .= " AND act_date='{$filter["act_date"]}'";
			}
		}
		
		if (isset($filter['user_id']) && $filter['user_id'])
		{
			$sql .= " AND activities.user_id='{$filter['user_id']}'";
		}
		
		if (isset($filter["starting"]) && $filter["starting"])
		{
			$sql .= " AND act_date >= '{$filter["starting"]}'";
		}
		
		if (isset($filter["ending"]) && $filter["ending"])
		{
			$sql .= " AND act_date <= '{$filter["ending"]}'";
		}
		
		if (isset($filter["funding"]) && $filter["funding"])
		{
			$sql .= " AND funding='{$filter["funding"]}'";
		}
		
		$result = DB::query('SELECT COUNT(*) AS count' . $sql);
		$r = DBResult::fetchRow($result);
		$contact_count = $r["count"];
		
		// next, re-run the query, and only retrieve the records that will be
		// displayed on this screen.
		if ($order_field == 'last_name' && $order)
		{
			$sql .= " ORDER BY last_name, first_name $order";
		}
		
		else if ($order_field && $order)
		{
			$sql .= " ORDER BY $order_field $order";
		}
		
		$sql .= " LIMIT $first_row, $list_length";
		
		$full_sql = 'SELECT act_id, act_date, act_time, act_end_time, hours, completed,
				user_id, case_id, category, funding, summary' . $sql;
		
		//echo $full_sql;
		
		return DB::query($full_sql);
	}
	
	
	function getActivitiesTodo($user_id)
	{
		$sql = "SELECT act_id, act_date, act_time, hours, completed, location, activities.funding,
		activities.user_id, category, summary, cases.case_id, number, client_id, last_name, 
		first_name, phone, area_code, phone_notes, act_type
		FROM activities 
		LEFT JOIN cases ON activities.case_id=cases.case_id 
		LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
		WHERE activities.user_id=$user_id
		AND act_date IS NULL
		AND completed = 0
		ORDER BY act_id ASC LIMIT 1000";
		
		return DB::query($sql);
	}
	
	
	function getActivitiesOverdue($user_id)
	{
		$act_date = date('Y-m-d');
		$act_time = date("H:i:00");  // 20121228 MDF
		
		$sql = "SELECT act_id, act_date, act_time, act_end_time, hours, completed, location, activities.funding,
		activities.user_id, category, summary, cases.case_id, number, client_id, last_name, 
		first_name, phone, area_code, phone_notes, act_type
		FROM activities 
		LEFT JOIN cases ON activities.case_id=cases.case_id 
		LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
		WHERE activities.user_id=$user_id
		AND (act_date < '$act_date' OR (act_date = '$act_date' && act_time <= '$act_time'))
		AND completed = 0
		ORDER BY act_date ASC, act_time ASC, act_id ASC LIMIT 1000";

		return DB::query($sql);
	}
	
	
	function getActivitiesPending($user_id, $act_date, $act_time = null)
	{
		if (is_null($act_date))
		{
			$act_date = date('Y-m-d');
		}
		
		$sql = "SELECT act_id, act_date, act_time, act_end_time, hours, completed, location, activities.funding,
		activities.user_id, category, summary, cases.case_id, number, client_id, last_name, 
		first_name, phone, area_code, phone_notes, act_type
		FROM activities 
		LEFT JOIN cases ON activities.case_id=cases.case_id 
		LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
		WHERE activities.user_id=$user_id
		AND act_date = '$act_date'";
		/*
				AND (act_date = '$act_date' OR 
			((repeat_period = 'D' AND DAYOFWEEK(act_date) != 1 AND DAYOFWEEK(act_date) !=7) OR
			(repeat_period = 'W' AND DAYOFWEEK('$act_date') = DAYOFWEEK(act_date)) OR
			(repeat_period = 'M' AND DAYOFMONTH('$act_date') = DAYOFMONTH(act_date)) OR
			(repeat_period = 'Y' AND DAYOFMONTH('$act_date') = DAYOFMONTH(act_date) AND MONTH('$act_date') = MONTH(act_date))
			))
			*/
		/* If a time is specified, consider any records scheduled before that time to be overdue,
		not pending. */
		if (!is_null($act_time))
		{
			$sql .= " AND (act_time > '$act_time' OR act_time IS NULL)";
		}
		
		$sql .= " AND completed = 0
		ORDER BY act_time ASC, act_id ASC LIMIT 1000";
		
		//echo $sql;
		
		/*
		(
select act_id AS table_id, 'activities' AS label, user_id, act_date, act_time
	FROM activities 
	WHERE act_date = '2003-07-04'
	AND user_id=43
) UNION (
select events.event_id AS table_id, 'events' AS label, user_id, CURRENT_DATE AS act_date, event_time AS act_time
	from events
	LEFT JOIN event_users ON events.event_id=event_users.event_id
	WHERE (user_id = 43 OR all_users = 1)
	AND ((repeat_period='D')
		OR
		(repeat_period='W' AND DAYOFWEEK(event_date) = DAYOFWEEK(NOW()))
		OR
		(repeat_period='M' AND DAYOFMONTH(event_date) = DAYOFMONTH(NOW()))
		OR
		(repeat_period='Y' AND DAYOFMONTH(event_date) = DAYOFMONTH(NOW()) AND MONTH(event_date) = MONTH(NOW()))
	)
) order by act_date ASC, act_time ASC;
*/
		//echo $sql;
		return DB::query($sql);
	}

	
	function getActivitiesCompleted($user_id, $act_date = null)
	{
		if (is_null($act_date))
		{
			$act_date = date('Y-m-d');
		}

		$sql = "SELECT act_id, act_date, act_time, act_end_time, hours, completed, location, activities.funding,
		activities.user_id, category, summary, cases.case_id, number, client_id, last_name, 
		first_name, phone, area_code, phone_notes, act_type
		FROM activities 
		LEFT JOIN cases ON activities.case_id=cases.case_id 
		LEFT JOIN contacts ON cases.client_id=contacts.contact_id 
		WHERE activities.user_id=$user_id
		AND act_date = '$act_date'
		AND completed = 1
		ORDER BY act_time ASC, act_id ASC LIMIT 1000";
		return DB::query($sql);
	}
	
	
	function fetchActivitiesCaseClient($filter, &$contact_count, $order_field='act_date', 
		$order='ASC', $first_row='0', $list_length='30')
	{
		$sql = ' FROM activities LEFT JOIN cases ON activities.case_id=cases.case_id LEFT JOIN contacts ON cases.client_id=contacts.contact_id WHERE 1';
		
		if (isset($filter["act_date"]) && $filter["act_date"])
		{
			$sql .= " AND act_date='{$filter["act_date"]}'";
		}
		
		if (isset($filter['user_list']) && is_array($filter['user_list']))
		{
			$tmpa = "0";
			
			foreach ($filter['user_list'] AS $val)
			{
				$tmpa .= ",$val";
			}
			
			$sql .= " AND activities.user_id IN ($tmpa)";
		}

		else if ($filter['user_id'])
		{
			$sql .= " AND activities.user_id='{$filter['user_id']}'";
		}
		
		if ($filter["starting"])
		{
			$sql .= " AND act_date >= '{$filter["starting"]}'";
		}
		
		if ($filter["ending"])
		{
			$sql .= " AND act_date <= '{$filter["ending"]}'";
		}
		
		if (isset($filter['no_date']) && $filter['no_date'])
		{
			$sql .= " AND act_date IS NULL";
		}
		
		if (isset($filter["funding"]) && $filter["funding"])
		{
			$sql .= " AND activities.funding='{$filter["funding"]}'";
		}
		
		if (isset($filter["act_type"]) && $filter["act_type"])
		{
			$sql .= " AND act_type='{$filter["act_type"]}'";
		}
		
		if (isset($filter['completed']) && is_numeric($filter['completed']))
		{
			$sql .= " AND completed={$filter['completed']}";
		}
		
		if (isset($filter['office']) && strlen($filter['office']) > 0)
		{
			$sql .= " AND office='{$filter['office']}'";
		}

		if (isset($filter['number']) && strlen($filter['number']) > 0)
		{
			$sql .= " AND number LIKE '{$filter['number']}'";
		}
		
		if (isset($filter['category']) && strlen($filter['category']) > 0)
		{
			$e = explode(',', $filter['category']);
			foreach ($e as $key => $val)
			{
				$e[$key] = "'" . DB::escapeString(trim($val)) . "'";
			}
			$f = implode(', ', $e);
			
			$sql .= " AND category IN ({$f})";
		}


		$result = DB::query('SELECT COUNT(*) AS count' . $sql);
		$r = DBResult::fetchRow($result);
		$contact_count = $r["count"];
		
		// next, re-run the query, and only retrieve the records that will be
		// displayed on this screen.
		if ($order_field == 'last_name' && $order)
		{
			$sql .= " ORDER BY last_name, first_name $order";
		}
		
		else if ($order_field == 'date-user-time' && $order)
		{
			$sql .= " ORDER BY act_date $order, user_id $order, act_time $order";
		}
			
		else if ($order_field && $order)
		{
			$sql .= " ORDER BY $order_field $order";
		}
		
		$sql .= " LIMIT $first_row, $list_length";
		
		$full_sql = 'SELECT act_id, act_type, act_date, act_time, act_end_time, hours, completed, location, activities.funding,
				activities.user_id, category, summary, cases.case_id, number, office, client_id, last_name, first_name, phone, area_code, phone_notes' . $sql;
		
		return DB::query($full_sql);
	}
	
	
	function newActivity($a)
	{
		global $plSettings;
		global $auth_row;
		
		$act_interval = (int) $plSettings['act_interval'];
		
		$a["act_id"] = pl_new_id('activities');
		$a['hours'] = pika_round_decimal_hours($a['hours'], $act_interval);
		
		if (!isset($a['user_id']) && !isset($a['pba_id']))
		{
			$a['user_id'] = $auth_row['user_id'];
		}
		
		/*
		Round off 'hours' based on act_interval, if hours were entered,
		and if a valid act_interval was specified.
		*/
		/*
		if ($a['hours'] > 0 && $act_interval > 0)
		{
			// the number of hours
			$hours = floor($a['hours']);
			// the number of minutes
			$minutes = ($a['hours'] - $hours) * 60.0;
			// the rounded number of minutes
			$rounded_minutes = ((int) ($minutes / $act_interval)) * $act_interval / 60;
			
			$a['hours'] = $hours + $rounded_minutes;
			
			
			//If the number of hours and minutes rounded down to zero, set 'hours'
			//to the minimum amount of minutes.
			
			if (0 == $a['hours'])
			{
				$a['hours'] = $act_interval / 60;
			}
		}
		*/
		
		$sql = pl_build_sql('INSERT', 'activities', $a);
		$result = DB::query($sql);
		
		// echo $sql;
		
		return $a["act_id"];
	}
	
	
	function updateActivity($a)
	{
		global $plSettings;
		
		if (!isset($a['hours']))
		{
			$a['hours'] = null;
		}
		
		$act_interval = (int) $plSettings['act_interval'];
		$a['hours'] = pika_round_decimal_hours($a['hours'], $act_interval);

		/*
		Round off 'hours' based on act_interval, if hours were entered,
		and if a valid act_interval was specified.
		*/
/*		if ($a['hours'] > 0 && $act_interval > 0)
		{
			// the number of hours
			$hours = floor($a['hours']);
			// the number of minutes
			$minutes = ($a['hours'] - $hours) * 60.0;
			// the rounded number of minutes
			$rounded_minutes = ((int) ($minutes / $act_interval)) * $act_interval / 60;
			
			$a['hours'] = $hours + $rounded_minutes;
			
			
			If the number of hours and minutes rounded down to zero, set 'hours'
			to the minimum amount of minutes.
			
			if (0 == $a['hours'])
			{
				$a['hours'] = $act_interval / 60;
			}
		}
		*/
		$sql = pl_build_sql('UPDATE', 'activities', $a);
		$result = DB::query($sql);
	}
	
	
	function duplicateActivity($act_id, $override_vals)
	{
		$result = $this->fetchActivity($act_id);
				
		return $this->newActivity(array_merge(DBResult::fetchRow($result), $override_vals));
	}

	
	function searchActivity($s)
	{
		$a = explode(' ', $s);
		$a_count = sizeof($a);
		
		$sql = "SELECT *
			    FROM activities
			    WHERE notes LIKE '%{$a[0]}%'";
		
		for($i = 1; $i < $a_count; $i++)
		$sql .= " AND notes LIKE '%{$a[$i]}%'";
		
		return DB::query($sql);
	}
	
	
	function deleteActivity($act_id='')
	{
		$sql = "DELETE FROM activities WHERE act_id=$act_id LIMIT 1";
		return DB::query($sql);
	}
	
	
	
	// MOTD
	
	function fetchMotd($motd_id='')
	{
		if ($motd_id)
		{
			$sql = "SELECT * FROM motd WHERE motd_id=$motd_id LIMIT 1";
			return DB::query($sql);
		}
		
		else
		{
			$sql = "SELECT motd.*, users.* FROM motd LEFT JOIN users ON motd.user_id=users.user_id";
			return DB::query($sql);
		}
	}
	
	
	function newMotd($a)
	{
		$motd_id = pl_new_id('motd');
		$a["motd_id"] = $motd_id;
		$result = DB::query(pl_build_sql('INSERT', 'motd', $a));
	}
	
	
	function updateMotd($a)
	{
		$result = DB::query(pl_build_sql('UPDATE', 'motd', $a));
	}
	
	
	function deleteMotd($a)
	{
		$result = DB::query("DELETE FROM motd WHERE motd_id=$a LIMIT 1");
	}
	
	
	function fetchCompens($case_id)
	{
		$sql = "SELECT compens.*
						FROM compens
						WHERE compens.case_id=$case_id";
		return DB::query($sql);
	}

	
	function addCompen($data)
	{
		$data['compen_id'] = pl_new_id('compens');
		$sql = pl_build_sql('INSERT', 'compens', $data);
		
		return DB::query($sql);
	}

	
	function fetchCaseCharges($case_id)
	{
		$sql = "SELECT case_charges.*, charges.charge_label, charges.statute,
							 menu_disposition.label AS disposition_label 
						FROM case_charges 
						LEFT JOIN charges ON case_charges.charge_id=charges.charge_id 
						LEFT JOIN menu_disposition ON case_charges.disposition=menu_disposition.value 
						WHERE case_id='$case_id'";
		return DB::query($sql);
	}
	

	function lookupChargeByStatute($statute)
	{
		// find the statute's charge_id
		$sql = "SELECT charge_id FROM charges WHERE statute='$statute' LIMIT 1";
		$result = DB::query($sql);
		$row = DBResult::fetchRow($result);
		return $row['charge_id'];
	}
	
	
	function addCaseCharge($case_id, $charge_id, $incident_date, $dispo_id)
	{
		// generate a new case_charge id
		$id = pl_new_id('case_charges');

		// initially assign the case's first charge as the primary charge
		$sql = "SELECT COUNT(*) AS tally FROM case_charges WHERE case_id='$case_id'";
		$result = DB::query($sql);
		$row = DBResult::fetchRow($result);
		
		if ($row['tally'] < 1)
		{
			$sql = "UPDATE cases SET primary_charge_id=$id WHERE case_id='$case_id' LIMIT 1";
		}
		
		// add the case_charge record
		if ($incident_date)
		{
			$incident_date_str = ", incident_date='$incident_date'";
		}
		
		if ($dispo_id)
		{
			$dispo_id_str = ", disposition=$dispo_id";
		}

		DB::query("INSERT INTO case_charges SET case_charge_id=$id, case_id=$case_id, charge_id=$charge_id$incident_date_str$dispo_id_str");
		
		return true;
	}
	
	function updateDisposition($case_charge_id, $disposition=null)
	{
		if (!$disposition)
		{
			$disposition = 'null';
		}
		
		$sql = "UPDATE case_charges SET disposition=$disposition WHERE case_charge_id=$case_charge_id
				LIMIT 1";
		
		//echo $sql;
		
		DB::query($sql);
		
		return true;
	}
	
	function deleteCaseCharge($case_charge_id)
	{
		$sql = "DELETE FROM case_charges WHERE case_charge_id=$case_charge_id LIMIT 1";
		DB::query($sql);
		return true;
	}
	
	function fetchSurveyQuestions()
	{
		$a = array();
		$sql = "SELECT * FROM survey_questions LIMIT 50";
		
		$result = DB::query($sql);
		
		while ($row = DBResult::fetchRow($result))
		{
			$a[] = $row;
		}
		
		return $a;
	}
	
	function addSurveyResponse($q_id, $case_id, $answer)
	{
		if (!is_numeric($q_id) || !is_numeric($case_id))
		{
			return false;
		}
		
		$a_id = pl_new_id('survey_answers');
		
		$sql = "INSERT INTO survey_answers SET a_id=$a_id, q_id=$q_id, case_id=$case_id, answer='$answer'";
		
		DB::query($sql);
		
		return true;
	}
	
	
}
?>
