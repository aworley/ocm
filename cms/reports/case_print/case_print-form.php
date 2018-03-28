<?php
include ('pl_report.php');

$case_id = pl_grab_var('case_id');

$rep = new pikaReport;
$rep->setFormat('html');
$rep->setAlign('portrait');


// display a single, case-related activity record in HTML
function case_note($contact)
{
	global $plMenus, $tmpstaff;
	$plMenus['user_id'] = $tmpstaff;
	
	$notes_found = FALSE;
	$hours = "error";
	$tmpname = "error";
	$C = '';
	
	if (isset($contact['user_id']) && $contact['user_id'])
	{
		if ($plMenus['user_id'][$contact['user_id']])
		{
			$tmpname = $plMenus['user_id'][$contact['user_id']];
		}

		else 
		{
			$tmpname = $contact['user_id'];
		}		
	}
	
	else if (isset($contact['pba_id']) && $contact['pba_id'])
	{
		if (isset($plMenus['pba_id']) && $plMenus['pba_id'][$contact['pba_id']])
		{
			$tmpname = $plMenus['pba_id'][$contact['pba_id']];
		}
		
		else 
		{
			$tmpname = $contact['pba_id'];
		}
	}
	
	else 
	{
		$tmpname = "Not Assigned!";
	}
	
	
	$C .= sprintf("<i>%s, %s</i><br>\n",
	pl_unmogrify_date($contact["act_date"]), $tmpname);
	

        // AMW - 2012-12-21 - added conditional display of
        // last_changed_user_id information.
        if ($contact['user_id'] != $contact['last_changed_user_id'] &&
            $contact['last_changed_user_id'] !== null)
        {
            $C .= '[i](Last edited by ' .
                  pl_array_lookup($contact['last_changed_user_id'], 
			$plMenus['user_id']) .
                        ")[/i]\n\n";
                }
                // End AMW


	if ($contact["summary"])
	{
		$C .= '<tt>' . pl_format_text($contact["summary"]) . '</tt><br>';
		
		$notes_found = TRUE;
	}
	
	if ($contact["notes"])
	{
		$C .= '<tt>' . pl_format_text($contact["notes"]) . '</tt><br>';
		
		$notes_found = TRUE;
	}
	
	if (FALSE == $notes_found)
	{
		$C .= '<em>No case notes entered</em><br>';
	}
	
	$C .= '&nbsp;<br>';
	
	return $C;
}


$plMenus['sp_problem'] = pl_table_array('sp_problem');
$plMenus['reject_code'] = pl_table_array('reject_code');
$plMenus['case_status'] = pl_table_array('case_status');
$plMenus['citizen'] = pl_table_array('citizen');
$plMenus['funding'] = pl_table_array('funding');
$plMenus['intake_type'] = pl_table_array('intake_type');
$plMenus['income_type'] = pl_table_array('income_type');
$plMenus['lsc_income_change'] = pl_table_array('lsc_income_change');
$plMenus['asset_type'] = pl_table_array('asset_type');
$plMenus['office'] = pl_table_array('office');
$plMenus['referred_by'] = pl_table_array('referred_by');
$plMenus['ethnicity'] = pl_table_array('ethnicity');
$plMenus['language'] = pl_table_array('language');
$plMenus['residence'] = pl_table_array('residence');
$plMenus['marital'] = pl_table_array('marital');
$plMenus['outcome'] = pl_table_array('outcome');
$plMenus['main_benefit'] = pl_table_array('main_benefit');
$plMenus['gender'] = pl_table_array('gender');
$plMenus['yes_no'] = pl_table_array('yes_no');
$plMenus['just_income'] = pl_table_array('just_income');

$pba_array = $pk->fetchPbAttorneyArray();

$tmpstaff = $pk->fetchStaffArray();


$result = $pk->fetchCase($case_id);
$a = $result->fetchRow();

$result = $pk->fetchContact($a['client_id']);
$b = $result->fetchRow();

$a = array_merge($a, $b);


// AMW - Begin of LSC 2008 CSR section.
//ini_set('display_errors', 'On');
$current_year = date('Y');
$current_datetime = date('U');
$cutoff_datetime = '1207022340';

$year_opened = substr($a['open_date'], 0, 4);
$year_closed = substr($a['close_date'], 0, 4);

/*
The following if clause chooses whether to use the 2007 or the 2008 closing and 
problem codes based on the case's open and closed dates.  TODO:  After March 2009
 it should revert back to the regular old closing and problem code menus.  The
2008 menu tables can be discarded and the 2007 menu tables can be kept around
for historical reporting purposes if desired.

Cases that are closed in 2007 or earlier will use the 2007 codes.  Cases that are
closed in 2008 or later will use the 2008 codes.
Cases that haven't been closed are more complicated.  If they were opened in 2008 or
later, they will use 2008 codes.  If not, they will use 2007 codes until March 31st,
after which they will change to the 2008 codes.
*/

if ($year_opened >= 2008) {
        // Use 2008 codes.
        $plMenus['problem'] = pl_table_array('problem_2008');
        $plMenus['close_code'] = pl_table_array('close_code_2008');
} else if ($year_closed < 2008 ||
	(strlen($case_row['close_date']) == 0 && $year_opened < 2008 && $current_datetime < $cutoff_datetime))
{
	// Use 2007 codes.
	$plMenus['problem'] = pl_table_array('problem_2007');
	$plMenus['close_code'] = pl_table_array('close_code_2007');
}else {
	// Use 2008 codes.
	$plMenus['problem'] = pl_table_array('problem_2008');
    $plMenus['close_code'] = pl_table_array('close_code_2008');
}


// AMW - End of LSC 2008 CSR.

/*
while (list($key, $val) = each($a))
{
	echo "'$key' => '$val'<br>\n";
}

exit();
*/


if (is_null($a['conflicts']))
{
	$a['conflict_descr'] = 'No information';
}

elseif ($a['conflicts'])
{
	$a['conflict_descr'] = 'One or more conflicts exist';
}

else
{
	$a['conflict_descr'] = 'No conflicts exist';
}



$a['primary_client_name'] = $a['last_name'];
if ($a['first_name'])
{
	$a['primary_client_name'] .= ", {$a['first_name']} {$a['middle_name']} {$a['extra_name']}";
}

$a['org_name'] = $plTemplate['org_name'];
$a['username'] = $auth_row['username'];

$a['full_address'] = "{$a['address']}";
if ($a['address2'])
{
	$a['full_address'] .= "<br>{$a['address2']}";
}
$a['full_address'] .= "<br>{$a['city']} {$a['state']} {$a['zip']}";

// Phone number
$a['phone_number'] = $a['phone'];
if ($a['area_code'])
{
	$a['phone_number'] = "({$a['area_code']})" . $a['phone_number'];
}
if ($a['phone_notes'])
{
	$a['phone_number'] .= "<br>" . $a['phone_notes'];
}

// Altername phone number
$a['phone_number_alt'] = $a['phone_alt'];
if ($a['area_code_alt'])
{
	$a['phone_number_alt'] = "({$a['area_code_alt']})" . $a['phone_number_alt'];
}
if ($a['phone_notes_alt'])
{
	$a['phone_number_alt'] .= "<br>" . $a['phone_notes_alt'];
}

$a['birth_date'] = pl_unmogrify_date($a['birth_date']);
$a['open_date'] = pl_unmogrify_date($a['open_date']);
$a['close_date'] = pl_unmogrify_date($a['close_date']);
$a['close_code'] = pl_array_lookup($a['close_code'],$plMenus['close_code']);
$a['problem'] = pl_array_lookup($a['problem'],$plMenus['problem']);
$a['sp_problem'] = pl_array_lookup($a['sp_problem'],$plMenus['sp_problem']);
$a['reject_code'] = pl_array_lookup($a['reject_code'],$plMenus['reject_code']);
$a['status'] = pl_array_lookup($a['status'],$plMenus['case_status']);
$a['conflicts'] = pl_array_lookup($a['conflicts'],$plMenus['yes_no']);
$a['citizen'] = pl_array_lookup($a['citizen'],$plMenus['citizen']);
$a['dom_viol'] = pl_array_lookup($a['dom_viol'],$plMenus['yes_no']);
$a['sex_assault'] = pl_array_lookup($a['sex_assault'],$plMenus['yes_no']);
$a['stalking'] = pl_array_lookup($a['stalking'],$plMenus['yes_no']);
$a['funding'] = pl_array_lookup($a['funding'],$plMenus['funding']);
$a['undup'] = pl_array_lookup($a['undup'],$plMenus['yes_no']);
$a['intake_type'] = pl_array_lookup($a['intake_type'],$plMenus['intake_type']);
$a['lsc_income_change'] = pl_array_lookup($a['lsc_income_change'],$plMenus['lsc_income_change']);
//$a['whereitsat'] = pl_array_lookup($a['whereitsat'],$plMenus['whereitsat']);
$a['office'] = pl_array_lookup($a['office'],$plMenus['office']);
$a['referred_by'] = pl_array_lookup($a['referred_by'],$plMenus['referred_by']);
$a['ethnicity'] = pl_array_lookup($a['ethnicity'],$plMenus['ethnicity']);
$a['gender'] = pl_array_lookup($a['gender'],$plMenus['gender']);
$a['residence'] = pl_array_lookup($a['residence'],$plMenus['residence']);
$a['language'] = pl_array_lookup($a['language'],$plMenus['language']);
$a['intake_user_id'] = pl_array_lookup($a['intake_user_id'],$tmpstaff);
$a['marital'] = pl_array_lookup($a['marital'],$plMenus['marital']);
$a['outcome'] = pl_array_lookup($a['outcome'],$plMenus['outcome']);
$a['main_benefit'] = pl_array_lookup($a['main_benefit'],$plMenus['main_benefit']);
$a['just_income'] = pl_array_lookup($a['just_income'],$plMenus['just_income']);

$a['probono'] = pl_array_lookup($a['pba_id1'],$pba_array);
if($a['pba_id2'])
{
	$a['probono'] .= "<br>".pl_array_lookup($a['pba_id2'],$pba_array);
}
if($a['pba_id3'])
{
	$a['probono'] .= "<br>".pl_array_lookup($a['pba_id3'],$pba_array);
}

/*
if ($a['conflicts'] == 1)
{
	$a['conflicts'] = 'Yes';
}
else
{
	$a['conflicts'] = 'No';
}
*/



// Paul Mundt suggests using Monthly figures, not Annual
if ($a['annual0'])
	$a['monthly0'] = round($a['annual0'] / 12, 2);

if ($a['annual1'])
	$a['monthly1'] = round($a['annual1'] / 12, 2);

if ($a['annual2'])
	$a['monthly2'] = round($a['annual2'] / 12, 2);

if ($a['annual3'])
	$a['monthly3'] = round($a['annual3'] / 12, 2);

if ($a['annual4'])
	$a['monthly4'] = round($a['annual4'] / 12, 2);

if ($a['annual5'])
	$a['monthly5'] = round($a['annual5'] / 12, 2);

if ($a['annual6'])
	$a['monthly6'] = round($a['annual6'] / 12, 2);

if ($a['annual7'])
	$a['monthly7'] = round($a['annual7'] / 12, 2);

if ($a['income'])
	$a['monthly_income'] = round($a['income'] / 12, 2);

$a['income_type0'] = pl_array_lookup($a['income_type0'],$plMenus['income_type']);
$a['income_type1'] = pl_array_lookup($a['income_type1'],$plMenus['income_type']);
$a['income_type2'] = pl_array_lookup($a['income_type2'],$plMenus['income_type']);
$a['income_type3'] = pl_array_lookup($a['income_type3'],$plMenus['income_type']);
$a['income_type4'] = pl_array_lookup($a['income_type4'],$plMenus['income_type']);
$a['income_type5'] = pl_array_lookup($a['income_type5'],$plMenus['income_type']);
$a['income_type6'] = pl_array_lookup($a['income_type6'],$plMenus['income_type']);
$a['income_type7'] = pl_array_lookup($a['income_type7'],$plMenus['income_type']);


$a['asset_type0'] = pl_array_lookup($a['asset_type0'],$plMenus['asset_type']);
$a['asset_type1'] = pl_array_lookup($a['asset_type1'],$plMenus['asset_type']);
$a['asset_type2'] = pl_array_lookup($a['asset_type2'],$plMenus['asset_type']);
$a['asset_type3'] = pl_array_lookup($a['asset_type3'],$plMenus['asset_type']);
$a['asset_type4'] = pl_array_lookup($a['asset_type4'],$plMenus['asset_type']);


$x = $y = $z = 0;

$a['additionals'] = "<ul>\n";
$a['opposings'] = "<ul>\n";
$a['other_contacts'] = "<ul>\n";

// 02-16-2012 - caw - added a table of additional cases where current client was/is also a client
$a['other_cases'] = "<table><tr><td><u>Case Num</u></td><td><u>Date</u></td><td><u>Date</u></td><td><u>Code</u></td></tr>";
$other_cases_found = false;
$dummy = null;
if($a['client_id'])
{
	$result = $pk->fetchCaseList(array('client_id' => $a['client_id']), $dummy);
	while($row = $result->fetchRow())
	{
		if($a['case_id']<>$row['case_id'])
		{
			$other_cases_found = true;
			if($row['number'])
			{
				$number_temp = $row['number'];
			}
			else
			{
				$number_temp = "Case Number Missing";
			}
			if($row['open_date'])
			{
				$open_temp = pl_unmogrify_date($row['open_date']);
			}
			else
			{
				$open_temp = "Unknown";
			}
			if($row['close_date'])
			{
				$close_temp = pl_unmogrify_date($row['close_date']);
			}
			else
			{
				$close_temp = "Not Closed";
			}
			if($row['problem'])
			{
				$problem_temp = $row['problem'];
			}
			else
			{
				$problem_temp = "n/a";
			}
			$a['other_cases'] .= "<tr><td><h5>$number_temp &nbsp</h5></td><td><h5>$open_temp </h5></td><td><h5>$close_temp &nbsp</h5></td><td><h5>$problem_temp</h5></td></tr>";
		}  // end of not current case
	}  // end of while
} // end of if client exists
if(!$other_cases_found)
{
	$a['other_cases'] = "None Found";
}
else
{
	$a['other_cases'] .= "</table>";
}
// 02-16-2012 - caw - end of additional table of other cases

// populate the additional client, opposing party tables
/*$result = $pk->fetchCaseContacts($case_id);
while ($row = $result->fetchRow())
{
	if ($row["relation_code"] == CLIENT)
	{
		if ($row['contact_id'] != $a['client_id'])
		$a['additionals'] .=
		"        <li>{$row['first_name']} {$row['middle_name']} {$row['last_name']}<br>
          ({$row['area_code']}) {$row['phone']}<br>
		{$row['notes']}</li>";
		
		$x++;
	}
	
	else if ($row["relation_code"] == OPPOSING)
	{
		$a['opposings'] .=
		"          <li>{$row['first_name']} {$row['middle_name']} {$row['last_name']}<br>
          ({$row['area_code']}) {$row['phone']}<br>
		{$row['notes']}</li>";
		
		$y++;
	}
	
	else
	{
		$a['other_contacts'] .= "<li>{$row['first_name']} {$row['middle_name']} {$row['last_name']}<br>\n";
		$a['other_contacts'] .= "<em>{$row['label']}</em><br>\n";

		if ($row['area_code'])
		{
			$a['other_contacts'] .= "({$row['area_code']})";
		}

		$a['other_contacts'] .= " {$row['phone']}<br>\n{$row['notes']}</li>\n";
		
		$z++;
	}
}



$a['additionals'] .= "</ul>\n";
$a['opposings'] .= "</ul>\n";
$a['other_contacts'] .= "</ul>\n";

if (!$x)
{
		$a['additionals'] = "None";
}

if (!$y)
{
		$a['opposings'] = "        None";
}

if (!$z)
{
	$a['other_contacts'] = "        None";
}
*/

$a['additional'] = '';
$rc = pl_menu_get('relation_codes');

$result = $pk->fetchCaseContacts($case_id);
while ($row = $result->fetchRow())
{
	$nametmp = pl_format_name($row);
	$phonetmp = pl_format_phone($row);
	
	switch ($row["relation_code"])
	{
		case CLIENT:
		
		$relatmp = "Client";
		break;
		
		case OPPOSING:
		
		$relatmp = "Opposing Party";
		break;
		
		default:
		
		$relatmp = pl_array_lookup($row["relation_code"], $rc);
		break;
	}
	
	if ($row['contact_id'] != $a['client_id'])
	{
		$a['additional'] .= "$relatmp:  <b>$nametmp</b><br>\n";
		
		if ($phonetmp)
		{
			$a['additional'] .= "$phonetmp<br>\n";
		}
		
		if (strlen(trim($row['phone_notes'])) > 0)
		{
			$a['additional'] .= $row['phone_notes'] . "<br>\n";
		}
		
		if (strlen(trim($row['notes'])) > 0)
		{
			$a['additional'] .= $row['notes'] . "<br>\n";
		}
		
		$a['additional'] .= "<br>\n";
	}
}


$a['user_id'] = pl_array_lookup($a['user_id'],$tmpstaff);
$a['cocounsel1'] = pl_array_lookup($a['cocounsel1'],$tmpstaff);
$a['cocounsel2'] = pl_array_lookup($a['cocounsel2'],$tmpstaff);

	$result = $pk->fetchNotes($case_id);
	
	$i = $hours_worked = 0;
	
	// A link to reverse the current display order of activities
	// Note:  Show this only if there are cases to display
	$notes_count = $result->numRows();
	if (0 == $notes_count)

	{
		$a['case_notes'] = "No case notes exist for this case.";
	}

	else  // only show these if there are actually activities to sort...
	{
		$a['case_notes'] = '';
		while ($row = $result->fetchRow())
		{
			$a['case_notes'] .= case_note($row);

			if($row["completed"])
				$hours_worked += $row["hours"];
		}

		$a['case_notes'] .= "Total of <b>$hours_worked</b> hours completed on this case";
	}

if (pl_grab_var('info'))
{
	$a['sub_info'] = pl_template($a, 'subtemplates/sub_info.html');
}

if (pl_grab_var('notes'))
{
	$a['sub_notes'] = pl_template($a, 'subtemplates/sub_notes.html');
}

$buffer = pl_template($a, 'reports/case_print/case_print.html');

$rep->display($buffer);

exit();
?>
