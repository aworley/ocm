<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/**
* Something.
*
* @author Aaron Worley <amworley@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaMisc
{
	public function batchTimeLoop()
	{
		$a = array();
		
		for ($i = 0; $i < 10; $i++)
		{
			$a[] = array();
		}
		
		return $a;
	}
	
	
	// TODO - deprecate this function.
	public static function fetchPbAttorneyArray()
	{
		$sql = "SELECT * FROM pb_attorneys ORDER BY last_name";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$a = array();  // make sure we return an empty array if no attys are found

		while ($row = mysql_fetch_assoc($result))
		{
			$a[$row['pba_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}

		return $a;
	}
	
	
	// TODO - deprecate this function.
	public static function fetchStaffArray()
	{
		$sql = "SELECT * FROM users ORDER BY last_name";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());

		while ($row = mysql_fetch_assoc($result))
		{
			$a[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}

		return $a;
	}
	
	
	public static function getCaseHandlerArray($v1 = 0, $v2 = 0, $v3 = 0)
	{
		$a = array();
		$sql = "SELECT user_id, first_name, middle_name, last_name, extra_name FROM users WHERE enabled = '1' AND attorney = '1' OR user_id IN ('{$v1}', '{$v2}', '{$v3}') ORDER BY last_name, first_name, middle_name";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());

		while ($row = mysql_fetch_assoc($result))
		{
			$a[$row['user_id']] = "{$row['last_name']}, {$row['first_name']} {$row['middle_name']} {$row['extra_name']}";
		}

		return $a;
	}

	
	public static function firstNameOnly($str)
	{
		$pos = strpos($str, ' ');

		if (!($pos === false))
		{
			return substr($str, 0, $pos);
		}

		else
		{
			return $str;
		}
	}

	
	public static function getActivitiesByText($text_str, &$row_count, $order_field='act_date', $order='ASC', $first_row='0', $list_length='50')
	{
		
		$clean_text_str = mysql_real_escape_string($text_str);
		
		$sql = "SELECT COUNT(*) as nbr
				FROM activities
				WHERE summary LIKE '%{$clean_text_str}%'
				OR notes LIKE '%{$clean_text_str}%'";
		//echo $sql;
		$result = mysql_query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . mysql_error());
		if(mysql_num_rows($result) == 1) {
			$row = mysql_fetch_assoc($result);
			$row_count = $row['nbr'];
		} else { $row_count = 0; }
		
		$sql = "SELECT activities.*, cases.number
				FROM activities
				LEFT JOIN cases on activities.case_id = cases.case_id
				WHERE summary LIKE '%{$clean_text_str}%'
				OR notes LIKE '%{$clean_text_str}%'";
		if($order == 'DESC') {
			$safe_order = 'DESC';
		} else { $safe_order = 'ASC'; }
		if($order_field) {
			$safe_order_field = mysql_real_escape_string($order_field);
			$sql .= " ORDER BY {$safe_order_field} {$safe_order}";
		}
		if(!$first_row || !is_numeric($first_row)) {
			$first_row = 0;
		}
		$safe_first_row = mysql_real_escape_string($first_row);
		if(!$list_length || !is_numeric($list_length)) {
			$list_length = 50;
		}
		$safe_list_length = mysql_real_escape_string($list_length);
		$sql .= " LIMIT $safe_first_row, $safe_list_length";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	public function getCasesAll()
	{
		$sql = "SELECT * FROM cases ORDER BY number ASC LIMIT 5000";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$a = array();
		
		while ($row = mysql_fetch_assoc($result)) 
		{
			$a[] = $row;
		}
		
		return $a;
	}
	
	
	public static function getCases($filter, &$row_count, $order_field='',
	$order='ASC', $first_row='0', $list_length='100')
	{
		$sql = ' FROM cases
			LEFT JOIN contacts ON cases.client_id=contacts.contact_id
			LEFT JOIN users ON cases.user_id=users.user_id
			WHERE 1 ';
		// Performance optimization.  Avoid JOINs when running the case COUNT() when possible.
		$skip_joins_during_row_count = true;

		foreach ($filter as $key => $val)
		{
			$filter[$key] = mysql_real_escape_string($val);
			
			// Determine whether the row count SQL can be used or not.
			if ('show_cases' == $key)
			{
				if ("2" != $val)
				{
						$skip_joins_during_row_count = false;
				}
			}
			
			else if ("" != $val)
			{
				$skip_joins_during_row_count = false;
			}
		}

		if (isset($filter['case_id']) && $filter['case_id'])
		{
			$sql .= " AND cases.case_id={$filter['case_id']}";
		}

		if (isset($filter['last_name']) && $filter['last_name'])
		{
			$sql .= " AND contacts.last_name LIKE '{$filter['last_name']}%'";
		}


		if (isset($filter["first_name"]) && $filter["first_name"])
		{
			$sql .= " AND contacts.first_name LIKE '{$filter['first_name']}%'";
		}


		if (isset($filter["user_id"]) && $filter["user_id"])
		{
			// Filter using a UNION to improve performance.  See below...
		}
		
		// MDF 2/11/10
		
		if (isset($filter["supervisor"]) && $filter["supervisor"])
		{
						$sql .= " AND cases.supervisor = '{$filter['supervisor']}'";
		}
		
		if (isset($filter["closer"]) && $filter["closer"])
		{
						$sql .= " AND cases.closer = '{$filter['closer']}'";
		}

		// End
		
		// 06-29-2012 - caw - added additional search criteria
		if (isset($filter["unit"]) && $filter["unit"])
		{
						$sql .= " AND cases.unit = '{$filter['unit']}'";
		}

		if (isset($filter["subunit"]) && $filter["subunit"])
		{
						$sql .= " AND cases.subunit = '{$filter['subunit']}'";
		}               
		// end of add
		
		if (isset($filter["pba_id"]) && $filter["pba_id"])
		{
			$sql .= " AND (cases.pba_id1='{$filter["pba_id"]}' OR cases.pba_id2='{$filter["pba_id"]}' OR cases.pba_id3='{$filter["pba_id"]}')";
		}

		if (isset($filter["client_id"]) && $filter["client_id"])
		{
			$sql .= " AND cases.client_id='{$filter["client_id"]}'";
		}


		if (isset($filter["office"]) && $filter["office"])
		{
			$sql .= " AND office='{$filter["office"]}'";
		}


		if (isset($filter["status"]) && $filter["status"])
		{
			$sql .= " AND status={$filter["status"]}";
		}


		if (isset($filter["opened_before"]) && $filter["opened_before"])
		{
			$sql .= " AND open_date < '{$filter["opened_before"]}'";
		}


		if (isset($filter["closed_before"]) && $filter["closed_before"])
		{
			$sql .= " AND close_date < '{$filter["closed_before"]}'";
		}


		if (isset($filter["opened_on_after"]) && $filter["opened_on_after"])
		{
			$sql .= " AND open_date >= '{$filter["opened_on_after"]}'";
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
		}

		if (isset($filter["funding"]) && $filter["funding"])
		{
			$sql .= " AND funding='{$filter["funding"]}'";
		}

        if (isset($filter["sp_problem"]) && $filter["sp_problem"])
        {
                $sql .= " AND sp_problem='{$filter["sp_problem"]}'";
        }
        
        if (isset($filter['number']) && $filter['number'])
		{
			$sql .= " AND number LIKE '{$filter['number']}%'";
		}

		// AMW 2014-07-23 - Added for SMRLS and ILCM
        if (isset($filter["supervisor"]) && $filter["supervisor"])
        {
                $sql .= " AND cases.supervisor = '{$filter['supervisor']}'";
        }

		if (isset($filter['show_cases']))
		{
			if (0 == $filter['show_cases'])
			{
				$sql .= " AND close_date IS NULL";
			}

			else if (1 == $filter['show_cases'])
			{
				$sql .= " AND close_date IS NOT NULL";
			}
		}

		if (isset($filter["user_id"]) && $filter["user_id"])
		{
			// Use UNION to improve performance.
			$mini_sql = "SELECT SUM(case_sub_count) AS case_count FROM ";
			$mini_sql .= "(SELECT COUNT(case_id) AS case_sub_count {$sql} AND cases.user_id = '{$filter["user_id"]}'";
			$mini_sql .= " UNION ALL SELECT COUNT(case_id) AS case_sub_count {$sql} AND cases.cocounsel1 = '{$filter["user_id"]}'";
			$mini_sql .= " UNION ALL SELECT COUNT(case_id) AS case_sub_count {$sql} AND cases.cocounsel2 = '{$filter["user_id"]}') AS case_count_tmp";
			// Reminder/note:  case_count_tmp is the name of the table created by the UNIONs.
			$result = mysql_query($mini_sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			$row = mysql_fetch_assoc($result);
			$row_count = $row['case_count'];
		}
		
		else
		{
			// Optimization:  If not filters are specified, we can omit the table joins 
			// and the query will run much faster.
			if ($skip_joins_during_row_count)
			{
				$count_sql = 'SELECT COUNT(case_id) AS count FROM cases';
			}
			
			else
			{
				$count_sql = 'SELECT COUNT(case_id) AS count' . $sql;
			}
			
			$result = mysql_query($count_sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			$row = mysql_fetch_assoc($result);
			$row_count = $row['count'];
		}

		/*	next, re-run the query, this time sorting the results and only
		retrieving those records that will be displayed on this screen.
		*/

		// Determine whether to use the supervisor field.
		$sup = "";
		$sresult = mysql_query("DESCRIBE cases supervisor");
		if (mysql_num_rows($sresult) == 1)
		{
			$sup = " cases.supervisor,";
		}

		if (isset($filter["user_id"]) && $filter["user_id"])
		{
			// Use UNION to improve performance.
			$full_sql = '(SELECT case_id, number, problem, status, cases.user_id,' .$sup .' cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, sp_problem,
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\', contacts.last_name AS client_last_name,
				contacts.first_name AS client_first_name, unread_sms ' . $sql . " AND cases.user_id = '{$filter["user_id"]}')";
			$full_sql .= ' UNION (SELECT case_id, number, problem, status, cases.user_id,' . $sup . ' cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, sp_problem,
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\', contacts.last_name AS client_last_name,
				contacts.first_name AS client_first_name, unread_sms ' . $sql . " AND cases.cocounsel1 = '{$filter["user_id"]}')";
			$full_sql .= ' UNION (SELECT case_id, number, problem, status, cases.user_id,' . $sup . ' cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, sp_problem,
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\', contacts.last_name AS client_last_name,
				contacts.first_name AS client_first_name, unread_sms ' . $sql . " AND cases.cocounsel2 = '{$filter["user_id"]}')";
		}
		
		else 
		{
			$full_sql = 'SELECT case_id, number, problem, status, cases.user_id,' . $sup . ' cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, sp_problem,
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\' , contacts.last_name AS client_last_name,
				contacts.first_name AS client_first_name, unread_sms ' . $sql;
		}
		
		if ($order_field && $order)
		{
			if ('client_name' == $order_field)
			{
				$full_sql .= ' ORDER BY client_last_name ' . $order . ', client_first_name ' . $order;
			}

			else
			{
				$full_sql .= " ORDER BY {$order_field} {$order}";
			}
		}
		
		
		if ($first_row && $list_length){
			$full_sql .= " LIMIT $first_row, $list_length";
		} elseif ($list_length){
			$full_sql .= " LIMIT $list_length";
		}
		$result = mysql_query($full_sql) or trigger_error("SQL: " . $full_sql . " Error: " . mysql_error());
		return $result;
	}

	/* static */ /*function getUsers($order_field = 'user_name', $order = 'ASC', $get_disabled = true, &$row_count = null)
	{
		$disabled_sql = '';

		if ($order != 'ASC')
		{
			$order = 'DESC';
		}

		switch ($order_field)
		{
			case 'user_name':

			$order_sql = "last_name {$order}, first_name {$order}, middle_name {$order}, extra_name {$order}";
			break;

			case 'description':
			case 'email':

			$order_sql = "{$order_field} {$order}";
			break;

			default:

			// A bad value was passed.
			trigger_error('');
			exit();
			break;
		}

		if (!$get_disabled)
		{
			$disabled_sql = 'WHERE enabled = \'1\'';
		}

		$result = mysql_query("SELECT count(*) AS tally FROM users {$disabled_sql}") or die('');
		$row = mysql_fetch_assoc($result);
		$row_count = $row['tally'];

		return mysql_query("SELECT * FROM users {$disabled_sql} ORDER BY {$order_sql}");
	}*/
	
	/* Does not work as written! MDF 20091123
	function getCasesByNumber($case_no, $records_per_page = 30, $offset = 0)
	{
		$clean_case_no = mysql_real_escape_string($case_no);
		$clean_records_per_page = mysql_real_escape_string($records_per_page);
		$clean_offset = mysql_real_escape_string($offset);
		return mysql_query("SELECT * FROM ");
	}*/
	

	/*	Search contacts and aliases for phonetic matches (using metaphone).
	Ignore exact matches, these are handled by getContacts().
	*/
	public static function getContactsPhonetically($last_name, $first_name = '')
	{
		/*
		$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS contact_cases
				(INDEX contact_id (contact_id))
				SELECT	MAX(conflict_id), 
						conflict.contact_id, 
						relation_code, 
						conflict.case_id, 
						number, 
						open_date 
				FROM conflict 
				LEFT JOIN cases ON conflict.case_id = cases.case_id 
				WHERE 0
				GROUP BY conflict.contact_id";
		mysql_query($sql) or trigger_error('');
		*/

		$mp_last = metaphone($last_name);
		$mp_first = metaphone(pikaMisc::firstNameOnly($first_name));

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

		$clean_mp_first = mysql_real_escape_string($mp_first);
		$clean_mp_last = mysql_real_escape_string($mp_last);

		/*	Organizations will only have a $last_name, which makes them a
		special case.
		*/

		// If $mp_last has a trailing wild card, it will generate too many false hits
		if (!$mp_first && $mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE aliases.{$match_last} LIKE '{$clean_mp_last}' 
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}

		else if ($mp_last)
		{
			$sql = "SELECT contacts.*
				    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id
				    WHERE (aliases.{$match_last} LIKE '{$clean_mp_last}' 
				    AND aliases.{$match_first} LIKE '{$clean_mp_first}')
				    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name";
		}

		else
		{
			trigger_error('Missing last name - cannot search');
		}
		
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}


	public static function getContactsWeighted($last_name, $first_name = null, 
			$middle_name = null, $extra_name = null, $birth_date = null, $ssn = null)
	{
		if (strlen($last_name) == 0)
		{
			trigger_error('Missing last name - cannot search');
		}
		
		/*	Only the metaphone strings are using in the FULLTEXT search string.  The
				plain text names are not included.  When plain text names were included,
				phonetically identical matches were being bumped below the cutoff in 
				some test cases because the letter-for-letter matches were weighted
				higher.  All phonetically identical matches	should be weighted equally 
				so if there are different spellings they all make if on the list every
				time.
				*/
		
		/*  There were a lot of squirrelly details when 9-digit SSNs were included
				in the FULLTEXT search weighting.  Weighting for SSN matches is instead
				done in SQL but outside of FULLTEXT.
				*/
		
		$x = pl_keywords_build($first_name, $middle_name, $last_name, $extra_name);
		$clean_x = mysql_real_escape_string($x);
		
		if (strlen($first_name) > 0)
		{
			$first_name_weight = "IF(a.first_name LIKE '" .
				mysql_real_escape_string($first_name) . "', 1.05, 1.0) * ";
		}
		
		if (strlen($last_name) > 0)
		{
			$last_name_weight = "IF(a.last_name LIKE '" .
				mysql_real_escape_string($last_name) . "', 1.05, 1.0) * ";
		}
		
		if (strlen($birth_date) == 10)
		{
			$birth_date_weight = "IF(birth_date = '" .
				mysql_real_escape_string($birth_date) . "', 1.20, 1.0) * ";
		}
		
		if (strlen($ssn) > 3)
		{
			$ssn_weight = "IF(a.ssn LIKE '" .
				mysql_real_escape_string($ssn) . "', 1.02, 1.0) * ";
		}
		
		$clean_last_name = mysql_real_escape_string($last_name);
		$sql = "SELECT contacts.*,
					{$first_name_weight}{$last_name_weight}{$birth_date_weight}{$ssn_weight}
					match(keywords) against('{$clean_x}') as score,
					a.first_name as a_first_name, a.middle_name as a_middle_name, 
					a.last_name as a_last_name, a.extra_name as a_extra_name
					FROM aliases as a LEFT JOIN contacts ON a.contact_id=contacts.contact_id
					where match(keywords) against('{$clean_x}') 
					order by score desc";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		if (mysql_num_rows($result) == 0)
		{
			$sql = "SELECT count(*) as a FROM aliases";
			$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
			$row = mysql_fetch_assoc($result);
			
			if ($row['a'] > 0)
			{
				$sql = "SELECT count(*) as a FROM aliases WHERE keywords IS NOT NULL";
				$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
				$row = mysql_fetch_assoc($result);
				
				if ($row['a'] == 0)
				{
					trigger_error("The database is not ready to be searched.  Please go to Site Map, then System Maintenance, then run the metaphone button and try again.");
				}
			}
			
		}
		
		return $result;
	}


	public static function getContactOffset($last_name, $first_name = '', $middle_name = '')
	{
		// Note:  names should all be handled case-insensitively.  :)
		$clean_last_name = mysql_real_escape_string(strtolower($last_name));
		$clean_first_name = mysql_real_escape_string(strtolower($first_name));
		$clean_middle_name = mysql_real_escape_string(strtolower($middle_name));
		$clean_letter = mysql_real_escape_string(strtolower($last_name[0]));

		if (strlen($first_name) > 1)
		{
			$sql = "SELECT COUNT(*) AS 'position' FROM aliases WHERE last_name LIKE '{$clean_letter}%' AND ((last_name < '{$clean_last_name}') OR (last_name <= '{$clean_last_name}' AND first_name < '{$clean_first_name}'))";
		}

		else
		{
			$sql = "SELECT COUNT(*) AS 'position' FROM aliases WHERE last_name LIKE '{$clean_letter}%' AND last_name < '{$clean_last_name}'";
		}

		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		$row = mysql_fetch_assoc($result);
		return $row['position'];
	}

	public static function getContactsAlphabetically(&$dataset_size, $letter, $offset, $limit = 5)
	{
		$clean_letter = mysql_real_escape_string($letter);
		$clean_offset = mysql_real_escape_string($offset);
		$clean_limit = mysql_real_escape_string($limit);

		// get the total number of contacts
		$result = mysql_query("SELECT COUNT(*) AS Rows FROM aliases WHERE last_name LIKE '{$clean_letter}%'")
			or trigger_error("Could not count the full list of alias records.");
		$row = mysql_fetch_assoc($result);
		$dataset_size = $row['Rows'];

		$sql = "SELECT contacts.*, aliases.last_name AS last_name, aliases.first_name AS first_name, aliases.extra_name AS extra_name, aliases.middle_name AS middle_name
			    FROM aliases LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id 
				WHERE aliases.last_name LIKE '{$clean_letter}%'
			    ORDER BY aliases.last_name, aliases.first_name, aliases.extra_name, aliases.middle_name
			    LIMIT {$clean_offset}, {$clean_limit}";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}

	public static function getContacts($filter)
	{
		$full_sql = "";
		$sql = "SELECT contacts.*
				FROM aliases 
				LEFT JOIN contacts ON aliases.contact_id=contacts.contact_id 
				WHERE 1";
		
		foreach ($filter as $key => $val)
		{
			$filter[$key] = mysql_real_escape_string($val);
		}

		if (isset($filter['notes']))
		{
			$sql .= " AND notes LIKE '%{$filter['notes']}%'";
		}

		if (isset($filter['state_id']))
		{
			$sql .= " AND aliases.state_id='{$filter['state_id']}'";
		}

		if (isset($filter['ssn']))
		{
			$sql .= " AND aliases.ssn LIKE '%{$filter['ssn']}%'";
		}

		if (isset($filter['telephone']))
		{
			// Filter using a UNION to improve performance.  See below...
			/*
			$sql .= " AND (phone = '{$filter['telephone']}'
					OR phone_alt = '{$filter['telephone']}')";
			*/
			$full_sql .= "({$sql} AND phone = '{$filter['telephone']}')";
			$full_sql .= " UNION ({$sql} AND phone_alt = '{$filter['telephone']}')";
		}
		
		else 
		{
			$full_sql = $sql;
		}
		
		$full_sql .= " ORDER BY last_name, first_name";
		$result = mysql_query($full_sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}


	public static function getHomePageCalendar()
	{
		global $auth_row;
		$a = array();
		$sql = "SELECT summary, act_time FROM activities WHERE user_id={$auth_row['user_id']} LIMIT 10";
		//$sql = "SELECT summary, act_time FROM activities";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		while ($row = mysql_fetch_assoc($result)) 
		{
			$a[] = $row;
		}
		
		return $a;
	}
	
	
	public static function getIntakes()
	{
		$result = mysql_query('SELECT intakes.*, contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
			contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
			area_code, phone FROM intakes 
			LEFT JOIN contacts ON intakes.client_id=contacts.contact_id
			ORDER BY last_name, first_name') or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}

	// display a single, case-related activity record in HTML
	public static function htmlCaseNote($contact)
	{
		/* static */ $user_id_menu = null;
		/* static */ $pba_id_menu = null;

		if (is_null($user_id_menu))
		{
			// 'user_id' should, hopefully, be init'ed before this function is called
			$user_id_menu = pikaMisc::fetchStaffArray();
			$pba_id_menu = array();
		}

		$base_url = pl_settings_get('base_url');
		$notes_found = FALSE;
		$hours = "error";
		$tmpname = "error";
		$C = '';

		$C .= "<p>\n";

		if ($contact['user_id'])
		{
			if ($user_id_menu[$contact['user_id']])
			{
				$tmpname = $user_id_menu[$contact['user_id']];
			}

			else
			{
				$tmpname = $contact['user_id'];
			}
		}

		else if ($contact['pba_id'])
		{
			/*
			if ($pba_id_menu[$contact['pba_id']])
			{
			$tmpname = $pba_id_menu[$contact['pba_id']] . " - pro bono";
			}

			else
			{
			$tmpname = $contact['pba_id'] . " - pro bono";
			}
			*/
		}

		else
		{
			$tmpname = "No name provided";
		}


		$C .= "{$tmpname}<br/>";

		if (1.0 == $contact['hours'])
		{
			$hours = "{$contact['hours']} hour";
		}

		else if (is_null($contact['hours']))
		{
			$hours = '';
		}

		else
		{
			$hours = "{$contact['hours']} hours";
		}

		$C .= "<span class=\"small\">";
		$C .= pl_date_unmogrify($contact["act_date"]) . ' ' . pl_time_unmogrify($contact["act_time"]);
		if (strlen($hours) > 0)
		{
			$C .= " &nbsp; | &nbsp; {$hours}";
		}

		$C .= " &nbsp; | &nbsp; </span><a href='{$base_url}/activity.php?act_id={$contact['act_id']}&screen=edit&case_id={$contact['case_id']}' class=\"small\">Edit this record</a>\n";

		$C .= '<blockquote><span style="font-family: georgia, times, serif; font-size: 14px;">' . "\n";

		if ($contact["summary"])
		{
			$C .= pl_clean_html($contact["summary"]);
			$notes_found = TRUE;
		}

		if ($contact["summary"] && $contact["notes"])
		{
			$C .= "<br/>\n";
		}

		if ($contact["notes"])
		{
			$C .= pl_html_text($contact["notes"]);
			$notes_found = TRUE;
		}

		if (FALSE == $notes_found)
		{
			$C .= '<em>No case notes entered</em>';
		}

		$C .= '</span></blockquote>' . "\n";

		$C .= "<div style=\"border-top: 1px solid #999999;\"></div>";

		return $C;
	}

	public static function htmlRedFlag($message)
	{
		$base_url = pl_settings_get('base_url');
		$C = '';

		$message2 = str_replace(' ', '&nbsp;', $message);

		$C .= "\n<span style=\"line-height: 25px; border: 1px solid #999999; padding: 1px; white-space: nowrap;\" class=\"thinborder\">\n";
		$C .= "<img width=\"16\" height=\"16\" src=\"{$base_url}/images/redflag.gif\" alt=\"red flag\"/>&nbsp;{$message2}</span> &nbsp; &nbsp;\n";

		return $C;
	}

	// Every time this is called, synchronize the directory contents to the metadata.
	// TODO - make sure the case_id for every file matches $case_id.

	
	// Return array of names of installed reports
	public static function htmlReportList() {
		$reports = pikaMisc::reportList(true,true);
		require_once('pikaTempLib.php');
		return pikaTempLib::plugin('ul','report_list','report_list',$reports);
	}
	
	public static function reportList($links = false,$descriptions = false) {
		$base_url = pl_settings_get('base_url');
		$dh = opendir('reports');
		$ban_list = array('case_print', 'conflict', 'compen_bill', 'survey', 
							'index.php', 'case_closing', 'fpp_probono',
							'intake_snapshot');
		$reports = array();
		$html_str = '';

		if (!is_readable('reports'))
		{
			return $reports;
		}
		
		while ($file = readdir($dh))
		{
			/*
			Ignore hidden files, current and parent directories, any reports on the banned
			list, and any with a dont_list.txt file present.
			*/
			
			if ($file[0] != '.' && !in_array($file, $ban_list)
			&& !file_exists("reports/{$file}/dont_list.txt"))
			{
				
				$title = '';
				$replace = array("\n","\r");

				// Determine the title.
				if (file_exists("reports/{$file}/title.txt"))
				{
					$title = file_get_contents("reports/{$file}/title.txt");
					$title = str_replace($replace,'',$title);
					$title = htmlentities($title);
				}
				else
				{
					$title = $file;
				}

				// Build the link.
				if($links) {
					if (file_exists("reports/{$file}/{$file}-form.php")) {
						$reports[$file] = "<a href=\"{$base_url}/legacy_report.php?report={$file}\">{$title}</a>\n";
					}else
					{
						$reports[$file] = "<a href=\"{$base_url}/reports/{$file}/\">{$title}</a>\n";
					}
				} else {
					$reports[$file] = $title;
				}
				// Find descriptions
				if($descriptions)
				{
					if (file_exists("reports/{$file}/description.txt"))
					{
						$description = file_get_contents("reports/{$file}/description.txt");
						$description = str_replace($replace,'',$description);
						$description = htmlentities($description);
						$reports[$file] .= "<br>\n{$description}\n";
					}
				}
			}
		}

		closedir($dh);
		
		// AMW - 2012-11-20 - Include reports from "-custom/extensions" folder.
		$ext_report_urls = explode(":", pl_settings_get('extensions_report_urls'));
		$ext_report_titles = explode(":", pl_settings_get('extensions_report_titles'));
		// 2013-08-13 AMW - These two lines eliminate the blank ghost entry at the bottom of the extensions list.
		array_pop($ext_report_urls);
		array_pop($ext_report_titles);
		
		for ($i =0; $i < sizeof($ext_report_urls); $i++)
		{
			$k = 'pm.php/reports' . str_replace('/index.php', '', $ext_report_urls[$i]);
			//$reports[$k] = $ext_report_titles[$i];
			//$reports[] = "<a href=\"{$base_url}/pm.php/reports{$ext_report_urls[$i]}\">{$ext_report_titles[$i]}</a>";
			$reports[$k] = "<a href=\"{$base_url}/pm.php/reports{$ext_report_urls[$i]}\">{$ext_report_titles[$i]}</a>";
		}
		// End AMW
		
		ksort($reports);
		
		return $reports;
	}


	public static function getMatters($user_id = 0)
	{
		if (0 == $user_id)
		{
			trigger_error("No user_id supplied to getMatters");
			die();
		}

		$list_length = 1000;

		/*
		this little hack will save a few fractions of a second on case
		lists w/o filters.  Instead of doing a "COUNT(*)" to determine
		the number of records in the resulting list, it uses "SHOW STATUS
		TABLES" to get the number of records in the 'cases' table.  This
		is of course MySQL-specific.
		*/
		$db_name = pl_settings_get('db_name');
		$result = mysql_query("SELECT COUNT(*) AS Rows FROM cases WHERE matter = '1' AND active_matter = '1'")
		or trigger_error("Count not count all matters records");
		$row = mysql_fetch_assoc($result);
		$row_count = $row['Rows'];


		/*	next, run a query on matter-mode case record, sorting the results and only
		retrieving those records that will be displayed on this screen.
		*/
		// Use UNION to improve performance.
		$sql = '(SELECT case_id, number, problem, status, cases.user_id, cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, 
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\' ' . $sql . " AND cases.user_id = '{$filter["user_id"]}')";
		$sql .= ' UNION (SELECT case_id, number, problem, status, cases.user_id, cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, 
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\' ' . $sql . " AND cases.cocounsel1 = '{$filter["user_id"]}')";
		$sql .= ' UNION (SELECT case_id, number, problem, status, cases.user_id, cocounsel1,
				cocounsel2, office, open_date, close_date, funding, client_id, 
				contacts.first_name as \'contacts.first_name\', contacts.middle_name AS \'contacts.middle_name\',
				contacts.last_name AS \'contacts.last_name\', contacts.extra_name AS \'contacts.extra_name\', 
				area_code, phone, users.first_name as \'users.first_name\', 
				users.middle_name as \'users.middle_name\',	users.last_name as \'users.last_name\',
				users.extra_name as \'users.extra_name\' ' . $sql . " AND cases.cocounsel2 = '{$filter["user_id"]}')";
		$sql .= " FROM cases
			LEFT JOIN contacts ON cases.client_id=contacts.contact_id
			LEFT JOIN users ON cases.user_id=users.user_id
			WHERE matter = '1' AND active_matter = '1'";

		$sql .= " LIMIT {$list_length}";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	
	public static function getMotdEntries()
	{
		$sql = "SELECT motd.*, users.* FROM motd LEFT JOIN users ON motd.user_id = users.user_id";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
	
	
	public static function getMotd()
	{
		$a = array();
		pikaMisc::getMotdEntries();
		
		while ($row = mysql_fetch_assoc($result)) 
		{
			$a[] = $row;
		}
		
		if (sizeof($a) == 0) 
		{
			$a[] = array('content' => "Welcome to the Pika Case Management System!");
		}
		
		return $a;
	}
	
	
	public static function getMegaReports()
	{
		$a = array();
		$sql = "SELECT * FROM megareports ORDER BY report_title ASC";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		
		while ($row = mysql_fetch_assoc($result)) 
		{
			$a[] = $row;
		}
		
		return $a;
	}
	
	public static function getStuff()
	{
		return true;
	}


	public static function htmlContactList($mode = 'contacts')
	{
		require_once('plFlexList.php');
		
		$case_id = null;
		$relation_code = null;
		
		$filter = array();
		$filter['first_name'] = pl_grab_get('first_name');
		$filter['middle_name'] = pl_grab_get('middle_name');
		$filter['last_name'] = pl_grab_get('last_name');
		$filter['extra_name'] = pl_grab_get('extra_name');
		$filter['birth_date'] = pl_grab_get('birth_date', null, 'date');
		$filter['ssn'] = pl_grab_get('ssn');
		$filter['area_code'] = pl_grab_get('area_code');
		$filter['phone'] = pl_grab_get('phone');
		// These are not used for SQL filtering, but are needed on the pager's get_url.
		$filter['case_id'] = pl_grab_get('case_id');
		$filter['number'] = pl_grab_get('number');
		$filter['relation_code'] = pl_grab_get('relation_code');
		

		$content_t = $filter;
		$content_t['search_list'] = '';
		$content_t['ab_list'] = '';
		$content_t['birth_date'] = pl_date_unmogrify($content_t['birth_date']);
		/* The offset specified by the user if they viewed this page
		by clicking on the pager from a previous page.
		*/
		$offset = pl_grab_get('offset');
		$search_performed = false;
		$matches_found = 0;
		$base_url = pl_settings_get('base_url');
		$dmode = 'search'; // Controls whether search or browse results are displayed.
		$total_records = 0;
		$screen = pl_grab_get('screen');
		
		if (strlen(pl_grab_get('dmodeb')) > 0) 
		{
			$dmode = 'browse';
		}
		
		switch ($mode)
		{
			case 'intake':
			$pager_url = 'intake2.php';
			$template_file = 'subtemplates/intake_contact_list.html';
			// 2014-01-14 AMW
			//$case_id = pl_grab_get('case_id');
			break;
			
			case 'contacts':
			default:
			$pager_url = 'addressbook.php';
			$template_file = 'subtemplates/contact_list.html';
			break;
			
			case 'case_contact':
			default:
			$pager_url = 'case_contact.php';
			$template_file = 'subtemplates/case_contact_list.html';
			$case_id = pl_grab_get('case_id');
			$relation_code = pl_grab_get('relation_code');
			break;
		}

		//$phon_str = '';
		//$phone_str = '';
		//$ssn_str = '';
		//$alpha_str = '';
		
		// Look for contacts that match the search parameters.
		if (strlen($filter['last_name']) > 0)
		{
			$search_performed = true;
			
			if ('search' == $dmode) 
			{
				
			
			// PHONETIC MATCHES TABLE
			$phonetic_table = new plFlexList();
			$phonetic_table->template_file = $template_file;
			
			if (pl_mysql_column_exists('aliases', 'keywords'))
			{
				$result = pikaMisc::getContactsWeighted($filter['last_name'], 
						$filter['first_name'], $filter['middle_name'], $filter['extra_name'],
						$filter['birth_date'], $filter['ssn']);
			}
			
			else 
			{
				$result = pikaMisc::getContactsPhonetically($filter['last_name'], 
						$filter['first_name']);
			}

			$i = 1;
			$matches_found = 0;
			$high_score = null;
			$cutoff_score = null;
			
			while ($row = mysql_fetch_assoc($result))
			{
				if (pl_mysql_column_exists('aliases', 'keywords'))
				{
					if (null === $high_score)
					{
						$high_score = $row['score'];
						$cutoff_score = $high_score / 2;
					}
					
					else 
					{
						if ($row['score'] < $cutoff_score)
						{
							if ($matches_found < 10)
							{
								$cutoff_score = $row['score'] - 1;
							}
							
							else
							{
								break;
							}
						}
					}
					
					if (($row['score'] / $high_score) > 0.9)
					{
						$row['search_rank'] = 'search_rank_likely';
					}
					
					else if (($row['score'] / $high_score) > 0.8 || $row['score'] > 30)
					{
						$row['search_rank'] = 'search_rank_extra';
					}
					
					else
					{
						$row['search_rank'] = '';
					}
				}
				
				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}

				$matches_found++;
				
				$row['arrow_img'] = 0;
				$row['client_name'] = pl_text_name($row);
				$row['client_phone'] = pl_text_phone($row);
				$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
				$row['case_id'] = $case_id;
				$row['relation_code'] = $relation_code;
				$row['screen'] = $screen;
				
				if (pl_mysql_column_exists('aliases', 'keywords'))
				{
					if ($row['first_name'] != $row['a_first_name'] ||
							$row['middle_name'] != $row['a_middle_name'] ||
							$row['last_name'] != $row['a_last_name'] ||
							$row['extra_name'] != $row['a_extra_name'])
					{
						$row['client_name'] .= "(or {$row['a_first_name']} {$row['a_middle_name']} {$row['a_last_name']} {$row['a_extra_name']})";
					}
				}
				
				$phonetic_table->addRow($row);


				//$phon_str .= pl_template('subtemplates/intake.html', $row, 'contacts');
			}
			
			if ($matches_found > 0) 
			{
				$content_t['search_list'] .= "<h2>Phonetic Matches</h2>\n" . $phonetic_table->draw();
			}
			
			else 
			{
				$content_t['search_list'] .= "<h2>Phonetic Matches</h2>\n<p><em>No matches found.</em></p>\n";
			}
			//$content_t['flex_header'] .= "<h2>Phonetic Matches</h2>\n{$phon_str}\n";
			}




			if ('browse' == $dmode) 
			{
			
			
			// ADDRESS BOOK MATCHES TABLE
			$contacts_table = new plFlexList();
			$contacts_table->template_file = $template_file;
			
			$filter['dmodeb'] = true;

			// Determine the offset where the list should start, if the user did not specify.
			$specified_offset = $offset;
			$name_offset = pikaMisc::getContactOffset($filter['last_name'], $filter['first_name'], $filter['middle_name']);
			
			if (is_numeric($specified_offset))
			{
				$arrow_offset = $name_offset - $specified_offset;
				$sql_offset = $specified_offset;
				
			}
			
			else 
			{
				if ($name_offset < 4)
				{
					$arrow_offset = $name_offset;
					$sql_offset = 0;
				}
	
				else
				{
					$sql_offset = $name_offset - 4;
					$arrow_offset = 4;
				}
			}
			
			$address_table_size = $_SESSION['paging'];
			
			$result = pikaMisc::getContactsAlphabetically($total_records, $filter['last_name'][0], $sql_offset, $address_table_size);
			$contacts_table->total_records = $total_records;
			$contacts_table->page_offset = $sql_offset;
			$contacts_table->records_per_page = $address_table_size;
			$contacts_table->table_url = "{$base_url}/{$pager_url}/";
			$contacts_table->setFilterParams($filter);

			$i = 1;
			$j = 0;
			$matches_found = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				$row['row_class'] = $i;
				
				if ($i > 1)
				{
					$i = 1;
				}
				
				else
				{
					$i++;
				}
				
				if ($j == $arrow_offset)
				{
					$row['arrow_img'] = 8;
				}
				
				else
				{
					$row['arrow_img'] = 0;
				}

				$matches_found++;
				$j++;

				$row['client_name'] = pl_text_name($row);
				$row['client_phone'] = pl_text_phone($row);
				$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
				$row['case_id'] = $case_id;
				$row['relation_code'] = $relation_code;
				$row['screen'] = $screen;
				$contacts_table->addRow($row);


				//$alpha_str .= pl_template('subtemplates/intake.html', $row, 'contacts');
			}

			if ($matches_found > 0) 
			{
				$content_t['ab_list'] .= "<h2>Address Book Matches</h2>\n" . $contacts_table->draw();
			}
			
			else 
			{
				$content_t['ab_list'] .= "<h2>Address Book Matches</h2>\n<p><em>No matches found.</em></p>\n";
			}
			//$content_t['flex_header'] .= "<h2>Address Book Matches</h2>\n{$alpha_str}\n";
			}
		}

		if ('search' == $dmode && strlen($filter['ssn']) > 0)
		{
			$search_performed = true;

			// SSN MATCHES TABLE
			$phonetic_table = new plFlexList();
			$phonetic_table->template_file = $template_file;

			$result = pikaMisc::getContacts(array('ssn' => $filter['ssn']));

			$i = 1;
			$matches_found = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}

				$matches_found++;
				
				$row['arrow_img'] = 0;
				$row['client_name'] = pl_text_name($row);
				$row['client_phone'] = pl_text_phone($row);
				$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
				$row['case_id'] = $case_id;
				$row['relation_code'] = $relation_code;
				$row['screen'] = $screen;
				$phonetic_table->addRow($row);
			}

			if ($matches_found > 0) 
			{
				$content_t['search_list'] .= "<h2>SSN Matches</h2>\n" . $phonetic_table->draw();
			}
			
			else 
			{
				$content_t['search_list'] .= "<h2>SSN Matches</h2>\n<p><em>No matches found.</em></p>\n";
			}
		}

		if ('search' == $dmode && strlen($filter['phone']) > 0)
		{
			$search_performed = true;

			// TELEPHONE MATCHES
			$phonetic_table = new plFlexList();
			$phonetic_table->template_file = $template_file;

			$result = pikaMisc::getContacts(array('telephone' => $filter['phone']));

			$i = 1;
			$matches_found = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				$row['row_class'] = $i;
				if ($i > 1)
				{
					$i = 1;
				}
				else
				{
					$i++;
				}

				$matches_found++;
				
				$row['arrow_img'] = 0;
				$row['client_name'] = pl_text_name($row);
				$row['client_phone'] = pl_text_phone($row);
				$row['birth_date'] = pl_date_unmogrify($row['birth_date']);
				$row['case_id'] = $case_id;
				$row['relation_code'] = $relation_code;
				$row['screen'] = $screen;
				$phonetic_table->addRow($row);
			}

			if ($matches_found > 0) 
			{
				$content_t['search_list'] .= "<h2>Telephone Matches</h2>\n" . $phonetic_table->draw();
			}
			
			else 
			{
				$content_t['search_list'] .= "<h2>Telephone Matches</h2>\n<p><em>No matches found.</em></p>\n";
			}
		}
		
		
		// 2014-01-14 AMW
		if ('case_contact' == $mode)
		{
			$content_t['case_contacts'] = '<h4>Case Contacts</h4>';
			$content_t['case_conflicts'] = '';

			require_once('pikaCase.php');
			$intake = new pikaCase($case_id);

			$result = $intake->getContactsDb();
			
			while ($r = mysql_fetch_assoc($result))
			{
				$content_t['case_contacts'] .= "<i class=\"icon-user\"></i> " . pl_text_name($r) 
				. " (" . $r['role'] . ")&nbsp;&nbsp;&nbsp;";
			}
			
			if($intake->resetConflictStatus(false))
			{
				$cons = $intake->fuzzyConflictCheck(1000);
				$content_t['case_conflicts'] .= '<br><br><h4>Potential Conflicts of Interest</h4>';
				$base_url = pl_settings_get('base_url');
				
				foreach($cons as $z)
				{
					//var_dump($z);
					$content_t['case_conflicts'] .= "<a href=\"{$base_url}/contacts.php?contact_id={$z['contact_id']}\">" 
					. pl_text_name($z) . "</a> was a(n) {$z['role']} on "
					. "<a href=\"{$base_url}/case_id={$z['case_id']}\">{$z['number']}</a>\n<br>";
				}
				
				$content_t['case_conflicts'] .= "<br>";
			}
			
			else
			{
				$content_t['case_conflicts'] .= '<p> No potential conflicts of interest identified</p>';
			}
			

		}
		// End AMW
		

		if (true == $search_performed && 'intake' == $mode)
		{
			$content_t['intake_text'] = pl_template($template_file, $content_t, 'intake_text');
		}

		if (true == $search_performed && ('intake' == $mode || 'case_contact' == $mode))
		{
			$content_t['case_id'] = $case_id;
			$content_t['relation_code'] = $relation_code;
			$content_t['screen'] = $screen;
			$content_t['new_contact_link'] = pl_template($template_file, $content_t, 'new_contact_link');
		}
		
		return $content_t;
	}
	
	public static function getCompens($case_id)
	{
		$sql = "SELECT compens.*
						FROM compens
						WHERE compens.case_id=$case_id";
		$result = mysql_query($sql) or trigger_error("SQL: " . $sql . " Error: " . mysql_error());
		return $result;
	}
}
