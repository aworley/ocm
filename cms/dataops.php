<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

/*
This file handles new/update/delete data requests, and redirects the user to the appropriate
screen after the data operation is completed.
*/

require_once ('pika_cms.php');

// VARIABLES
$pk = new pikaCms;
$action = pl_grab_var('action', null, 'REQUEST');
$screen = pl_grab_var('screen', 'info', 'REQUEST');
$case_id = pl_grab_var('case_id', null, 'REQUEST');
$relation_code = pl_grab_var('relation_code', null, 'REQUEST');
$base_url = pl_settings_get('base_url');

// this will store the current case data, to be used to fill out forms
$case_row = NULL;
// end VARIABLES


// BEGIN MAIN CODE...

// Enforce security level permissions
if (array_key_exists('case_id', $_REQUEST) && $_REQUEST['case_id'])
{
	$result = $pk->fetchCase($_REQUEST['case_id']);
	$case_row = DBResult::fetchRow($result);
	
	$allow_edits = pika_authorize('edit_case', $case_row);
	
	if ($action && !$allow_edits)
	{
		$action = 'not_allowed';
	}
}

// determine what, if any, action to perform
switch($action)
{
	case 'add_activity':
	
	if ($_REQUEST['cancel'])
	{
		header("Location: {$_REQUEST['act_url']}");
		break;
	}
	
	// TODO:  this may be a bug
	if ($_REQUEST['user_id'] != $auth_row['user_id']
	&& !$auth_row['edit_all'])
	{
		$action = 'not_allowed';
	}
	
	$a = pl_grab_vars('activities');
	
	$act_id = $pk->newActivity($a);
	
	unset($a);
	
	// decide where to go from here
	if ($_REQUEST['close_act'])
	{
		header("Location: {$_REQUEST['act_url']}");
	}
	
	else
	{
		$act_url = urlencode($_REQUEST['act_url']);
		$act_date_tmp = pl_date_mogrify($_REQUEST['act_date']);
		header("Location: activity.php?screen=compose&user_id={$_REQUEST['user_id']}&pba_id={$_REQUEST['pba_id']}&case_id={$_REQUEST['case_id']}&funding={$_REQUEST['funding']}&act_date=$act_date_tmp&completed={$_REQUEST['completed']}&act_url=$act_url&act_type={$_REQUEST['act_type']}");
	}
	
	break;
	
	
	case 'update_activity':
	
	$act_url = pl_grab_post('act_url');
	if ($_POST['action']
	&& $_REQUEST['user_id'] != $auth_row['user_id']
	&& !$auth_row['edit_all'])
	{
		$action = 'not_allowed';
	}
	
	$a = pl_grab_vars('activities');
	
	// enforce security level permissions
	if (!pika_authorize('edit_act', $a))
	{
		// set up template, then display page
		$plTemplate["page_title"] = "Editing activity record";
		$plTemplate["content"] = 'access denied';
		
		echo pl_template($plTemplate, 'templates/default.html');
		echo pl_bench('results');
		exit();
	}

	$pk->updateActivity($a);
	
	if (FALSE == $plEnableSpellcheck || !$spellcheck)
	{
		header("Location: $act_url");
		
	}
	
	// activate dictionary function
	if (TRUE == $plEnableSpellcheck)
	{
		$pspell_link = pspell_new("en");
		
		/*	this code parses the 'notes' field.  As it comes across words,
		it will spellcheck them.  If the word doesn't pass the
		spellcheck, it is marked with "not spelled right" tags
		*/
		unset($word);
		unset($sc);
		
		// 2013-08-13 AMW - Removed =& for compatibility with PHP 5.3.
		$k = $a['notes'];
		for ($j = 0; $j < strlen($k); $j++)
		{
			if (($k[$j] >= 'a' && $k[$j] <= 'z') ||
			($k[$j] >= 'A' && $k[$j] <= 'Z'))
			{
				$word = $word . $k[$j];
			}
			
			else
			{
				if ($word)
				{
					$sc = $sc . spellcheck($word);
					unset($word);
				}
				
				$sc = $sc . $k[$j];
			}
		}
		
		$sc = $sc . spellcheck($word);
	}
	
	header("Location: $act_url");
	
	break;
	
	
	case 'update_activity_bulk':
	
	$act_date = pl_grab_var('act_date', date('Y-m-d'), 'POST', 'date');
	
	/*
	if ($_REQUEST['user_id'] != $auth_row['user_id'] && !$auth_row['edit_all'])
	{
		header("Location: cal_day.php?act_date=$act_date");
	}
	*/
	
	$completed = pl_grab_var('completed', array(), 'POST', 'array');
	$hours = pl_grab_var('hours', array(), 'POST', 'array');
	
	foreach ($hours as $key => $val)
	{
		// Update checked records to be completed
		if (isset($completed[$key]) && true == $completed[$key])
		{
			$pk->updateActivity(array('act_id' => $key, 'completed' => true));
		}
		
		// Create time slips for any records which had time entered
		if ($val)
		{
			$pk->duplicateActivity($key, array('hours' => $val, 'completed' => "1", 'act_type' => 'T', 'act_date' => date('Y-m-d'), 'act_time' => date('H:m:00')));
		}
	}
	
	header("Location: cal_day.php?act_date=$act_date");
	break;
	
	
	case 'update_case':
	
	if (!$case_id)
	{
		die(pika_error_notice('Pika is sick',
		'Ran into trouble when updating the case record'));
	}
	
	$x = pl_grab_vars('cases');
	$pk->updateCase($x);
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
	
	break;
	
	
	// add a new contact, or link an existing contact, to a case
	case 'add_case_contact':
	
	$screen = pl_grab_var('screen', 'REQUEST', 'act');
	
	if (is_null($case_id) || is_null($relation_code))
	{
		die(pika_error_notice('Pika is sick',
		'Ran into trouble when adding the case contact'));
	}
	
	$thiscon = pl_grab_var('thiscon', null, 'POST');
	if (is_numeric($thiscon))
	{
		// An existing contact record is being added to this case
		if (!$pk->addCaseContact($case_id, $thiscon, $relation_code))
		{
			die(pika_error_notice('Pika is sick', "This contact is already added to this case."));
		}
		
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
		
	}
	
	else
	{
		// A new contact record is being added to this case.
		
		$a = pl_grab_vars('contacts');
		
		// take care of those nasty "masked" fields
		if ($phone_a || $phone_b)
		{
			$a["phone"] = "$phone_a-$phone_b";
		}
		
		else if (!isset($a['phone']))
		{
			$a["phone"] = "";
		}
		
		if ($phone_alt_a || $phone_alt_b)
		{
			$a["phone_alt"] = "$phone_alt_a-$phone_alt_b";
		}
		
		else if (!isset($a['phone_alt']))
		{
			$a["phone_alt"] = "";
		}
		
		if ($_POST['ssn0'] || $_POST['ssn1'] || $_POST['ssn2'])
		{
			$a['ssn'] = "{$_POST['ssn0']}-{$_POST['ssn1']}-{$_POST['ssn2']}";
		}
		
		else if (!isset($a['ssn']))
		{
			$a['ssn'] = "";
		}
		
		$contact_id = $pk->newContact($a);
		
		/*
		If we get past the first pl_query(), then it should be safe to add
		the relation
		*/
		if (!$pk->addCaseContact($case_id, $contact_id, $relation_code))
		{
			die(pika_error_notice('Pika is sick', "This contact is already added to this case."));
		}
		
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
		
		
	}
	
	break;
	
	/*
	The user is coming from intake.php, and wants to create a new case.  The client
	is either an existing contact or needs a new contact record created.
	*/
	case 'new_case':
	
	$thiscon = pl_grab_var('thiscon', null, 'POST');
	
	/*	used when adding a new case for a client with an existing contact record
	don't import any data from previous cases, however
	*/
	if (is_numeric($thiscon))
	{
		// add the case record...
		$a = $plDvs['cases'];
		
		// set default office value
		$a['office'] = $pikaDefOffice;
		
		// set the first client as the primary client
		$a['client_id'] = $thiscon;
		
		if (true == $plSettings['autonumber_on_new_case'])
		{
			$a['number'] = 'auto';
		}
		
		$a = array_merge($a, pl_grab_vars('cases'));
		
		$case_id = $pk->newCase($a);
		
		// Now link the client to the case
		$pk->addCaseContact($case_id, $thiscon, CLIENT);
		
		/*	the user probably came here from the list of existing contacts on
		the intake process, which means "action=dup_contact_last" is on the
		URL.  We don't want this get pulled into $con_url (where it will
		get re-executed), so do a HTTP redirect to a "clean" url.
		
		Alternatively, the links on the existing contacts list could be
		changed to POST forms.
		
		Go directly into the intake tab.
		*/
		
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=elig");
	}
	
	/*	The user is creating a new case record, but first a new contact record for the primary
	client.  When done, redirect to the new case on case.php.
	*/
	else
	{
		// Add the new contact record...
		$con = pl_grab_vars('contacts');
		
		// handle input masks
		if ($_POST['phone_a'] || $_POST['phone_b'])
		{
			$con["phone"] = "{$_POST['phone_a']}-{$_POST['phone_b']}";
		}
		
		else if (!isset($con['phone']))
		{
			$con["phone"] = "";
		}
		
		if ($phone_alt_a || $phone_alt_b)
		{
			$con["phone_alt"] = "$phone_alt_a-$phone_alt_b";
		}
		
		else if (!isset($con['phone_alt']))
		{
			$con["phone_alt"] = "";
		}
		
		if ($_POST['ssn0'] || $_POST['ssn1'] || $_POST['ssn2'])
		{
			$con['ssn'] = "{$_POST['ssn0']}-{$_POST['ssn1']}-{$_POST['ssn2']}";
		}
		
		else if (!isset($con['ssn']))
		{
			$con['ssn'] = "";
		}
		
		$contact_id = $pk->newContact($con);
		
		
		// Add any user-supplied aliases
		$al_first_name = pl_grab_var('al_first_name', array(), 'POST', 'array');
		$al_middle_name = pl_grab_var('al_middle_name', array(), 'POST', 'array');
		$al_last_name = pl_grab_var('al_last_name', array(), 'POST', 'array');
		$al_extra_name = pl_grab_var('al_extra_name', array(), 'POST', 'array');
		$for_limit = sizeof($al_first_name);
		$j = 0;
		for ($j = 0; $j < $for_limit; $j++)
		{
			if ($al_first_name[$j] || $al_middle_name[$j] || $al_last_name[$j] || $al_extra_name[$j]
			|| $al_ssn[$j] || $al_state_id[$j])
			{
				$alias_data['first_name'] = $al_first_name[$j];
				$alias_data['middle_name'] = $al_middle_name[$j];
				$alias_data['last_name'] = $al_last_name[$j];
				$alias_data['extra_name'] = $al_extra_name[$j];
				$alias_data['ssn'] = $al_ssn[$j];
				$alias_data['state_id'] = $al_state_id[$j];
				$alias_data['contact_id'] = $contact_id;
				
				$pk->newAlias($alias_data);
			}
		}
		
		
		// Now add the case record...
		$a = $pikaDvs['cases'];
		
		// set default office value
		$a['office'] = $pikaDefOffice;
		
		// set the first client as the primary client
		$a['client_id'] = $contact_id;
		
		if (true == $plSettings['autonumber_on_new_case'])
		{
			$a['number'] = 'auto';
		}
		
		$a = array_merge($a, pl_grab_vars('cases'));
		
		$case_id = $pk->newCase($a);
		
		// Now link the client to the case
		$pk->addCaseContact($case_id, $contact_id, CLIENT);
		
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=elig");
	}
	
	break;
	
	
	
	/*
	The user is coming from a "New Case" link, and wants to create a new case and bypass the
	client intake process, going instead straight to eligibility screening.
	*/
	case 'new_case_no_client':
	
	$screen = pl_grab_var('screen');
	
	pl_table_init('cases');
	$a = $plDvs['cases'];
	
	// set default office value
	$a['office'] = $pikaDefOffice;
	
	if (true == $plSettings['autonumber_on_new_case'])
	{
		$a['number'] = 'auto';
	}
	
	$a = array_merge($a, pl_grab_vars('cases'));
	
	/*
	Since no client is added, and no conflict check will be performed, poten_conflicts
	will still be set to its default value of 1.  Fix this.
	*/
	$a['poten_conflicts'] = '0';
	
	$case_id = $pk->newCase($a);
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
	
	break;
	
	
	
	// used when duplicated an existing case (ie. client comes in with 2 issues)
	case 'dup':
	
	if (is_null($old_case_id))
	{
		die(pika_error_notice('Pika is sick',
		'Ran into trouble when duplicating this case'));
	}
	
	$case_id = $pk->duplicateCase($old_case_id);
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
	
	break;
	
	
	// I think this is DEPRECATED now...
	/*	used when adding a new case for a previous client
	don't import any data from previous cases, however
	*/
	case 'dup_contact_last999':
	
	if (is_null($contact_id))
	{
		die(pika_error_notice('Pika is sick',
		'Ran into trouble when adding the new case'));
	}
	
	// add the case record...
	$a = $pikaDvs['cases'];
	
	// set default office value
	$a['office'] = $pikaDefOffice;
	
	// set the first client as the primary client
	$a['client_id'] = $contact_id;
	
	if (true == $plSettings['autonumber_on_new_case'])
	{
		$a['number'] = 'auto';
	}
	
	$a['referred_by'] = $referred_by;
	
	/*	should probably just run pl_grab_vars(cases) if any more case
	fields are	needed
	*/
	
	$case_id = $pk->newCase($a);
	
	
	// Now link the client to the case
	$pk->addCaseContact($case_id, $contact_id, CLIENT);
	
	
	/*	the user probably came here from the list of existing contacts on
	the intake process, which means "action=dup_contact_last" is on the
	URL.  We don't want this get pulled into $con_url (where it will
	get re-executed), so do a HTTP redirect to a "clean" url.
	
	Alternatively, the links on the existing contacts list could be
	changed to POST forms.
	
	Go directly into the intake tab.
	*/
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
	
	break;
	
	
	// I think this is DEPRECATED now...
	
	/*	The user is creating a new case record, but first a new contact record for the primary
	client.  When done, redirect to the new case on case.php.
	*/
	
	case 'new_contact_new_case999':
	
	// Add the new contact record...
	$con = pl_grab_vars('contacts');
	
	// handle input masks
	if ($_POST['phone_a'] || $_POST['phone_b'])
	{
		$con["phone"] = "{$_POST['phone_a']}-{$_POST['phone_b']}";
	}
	
	else
	{
		$con["phone"] = "";
	}
	
	if ($phone_alt_a || $phone_alt_b)
	{
		$con["phone_alt"] = "$phone_alt_a-$phone_alt_b";
	}
	
	else
	{
		$con["phone_alt"] = "";
	}
	
	if ($_POST['ssn0'] || $_POST['ssn1'] || $_POST['ssn2'])
	{
		$con['ssn'] = "{$_POST['ssn0']}-{$_POST['ssn1']}-{$_POST['ssn2']}";
	}
	
	else
	{
		$con['ssn'] = "";
	}
	
	$contact_id = $pk->newContact($con);
	
	
	// Now add the case record...
	$a = $pikaDvs['cases'];
	
	// set default office value
	$a['office'] = $pikaDefOffice;
	
	// set the first client as the primary client
	$a['client_id'] = $contact_id;
	
	if (true == $plSettings['autonumber_on_new_case'])
	{
		$a['number'] = 'auto';
	}
	
	/*	for SMRLS (should probably just run pl_grab_vars(cases) if any more case fields are
	needed)
	*/
	$a['referred_by'] = $referred_by;
	
	$case_id = $pk->newCase($a);
	
	
	// Now link the client to the case
	$pk->addCaseContact($case_id, $contact_id, CLIENT);
	
	
	
	$plIntakeType = 'a';
	if ('fast' == $plIntakeType)
	{
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=fast");
	}
	
	else
	{
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=info");
	}
	
	break;
	
	
	/*	Update an existing contact record with the data submitted, then redirect
	to the previous screen.
	*/
	case 'update_contact':
	
	$a = pl_grab_vars('contacts');
	
	if ($phone_a || $phone_b)
	{
		$a["phone"] = "$phone_a-$phone_b";
	}
	
	else if (!isset($a['phone']))
	{
		$a["phone"] = "";
	}
	
	if ($phone_alt_a || $phone_alt_b)
	{
		$a["phone_alt"] = "$phone_alt_a-$phone_alt_b";
	}
	
	else if (!isset($a['phone']))
	{
		$a["phone_alt"] = "";
	}
	
	if ($_POST['ssn0'] || $_POST['ssn1'] || $_POST['ssn2'])
	{
		$a['ssn'] = "{$_POST['ssn0']}-{$_POST['ssn1']}-{$_POST['ssn2']}";
	}
	
	else if (!isset($a['ssn']))
	{
		$a['ssn'] = "";
	}
	
	$pk->updateContact($a);
	
	header("Location: $con_url");
	
	break;
	
	
	case 'add_pb':
	
	$a = pl_grab_vars('pb_attorneys');
	$pba_id = $pk->newPbAttorney($a);
	
	header("Location: pb_attorneys.php?screen=edit_pb&pba_id=$pba_id");
	
	break;
	
	
	case 'update_pb':
	
	$a = pl_grab_vars('pb_attorneys');
	
	$result = $pk->updatePbAttorney($a);
	
	header("Location: pb_attorneys.php");
	
	break;
	
	
	case 'set_case_user':
	
	$case_id = pl_grab_var('case_id');
	$user_id = pl_grab_var('user_id');
	$field = pl_grab_var('field');
	$x = "";
	
	if ($case_id && $user_id && 'user_id' == $field || 'cocounsel1' == $field || 'cocounsel2' == $field)
	{
		$result = $pk->fetchCase($case_id);
		$a = DBResult::fetchRow($result);
		$a[$field] = $user_id;
		$pk->updateCase($a);
		// also update this attorney's last_case_assign field
		$pk->updateStaff(array('user_id' => $user_id, 'last_case' => date("Y-m-d")));
	}
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=info");
	
	break;

	
	case 'set_case_pba':
	
	$result = $pk->fetchCase($case_id);
	$a = DBResult::fetchRow($result);
	
	$x = "";
	
	if ('pba_id1' == $field || 'pba_id2' == $field || 'pba_id3' == $field)
	{
		$x = $field;
	}
	
	if ($x)
	{
		$a[$x] = $pba_id;
	}
	
	$pk->updateCase($a);
	
	// also update this PBA's last_case_assign field
	$pk->setPbAttorneyLastCase($pba_id, date("Y-m-d"));
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=pb");
	
	break;
	
	
	case 'set_password':
	
	if ($auth_row['password'] != $_POST['oldpass'])
	{
		header('Location: password.php?error_code=1');
		
	}
	
	if ($_POST['newpass1'] != $_POST['newpass2'])
	{
		header('Location: password.php?error_code=2');
		
	}
	
	$pk->setPassword($auth_row['user_id'], $_POST['newpass1']);
	
	header('Location: index.php');
	
	break;
	
	
	case 'delete_act':
	
	$act_id = pl_grab_var('act_id', null, 'POST');
	$result = $pk->deleteActivity($act_id);
	
	header('Location: cal_day.php');
	
	break;
	
	
	case 'delete_conflict':
	
	$conflict_id = pl_grab_var('conflict_id');
	$case_id = pl_grab_var('case_id');
	
	$result = $pk->deleteConflict($conflict_id, $case_id);
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=info");
	
	break;
	
	
	case 'new_alias':
	
	$data = pl_grab_vars('aliases');
	$pk->newAlias($data);
	
	header("Location: contact.php?contact_id={$data['contact_id']}");
	
	break;
	
	
	case 'add_case_charges':
	
	$j = 0;
	
	$case_id = pl_grab_var('case_id');
	while ($j < 5)
	{
		$statute = pl_grab_var("statute$j");
		$incident_date = pl_grab_var("incident_date$j", null, 'GET', 'date');
		$disposition = pl_grab_var("disposition$j");
		
		$charge_id = $pk->lookupChargeByStatute($statute);
		
		if ($charge_id)
		{
			$pk->addCaseCharge($case_id, $charge_id, $incident_date, $disposition);
		}
		$j++;
	}
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=charges");
	
	break;
	
	
	case 'update_case_charges':
	
	$delete = $_POST['delete'];
	if (!is_array($delete))
	{
		$delete = array();
	}
	$disposition = $_POST['disposition'];
	if (!is_array($disposition))
	{
		$disposition = array();
	}
	$ids = $_POST['ids'];
	if (!is_array($ids))
	{
		$ids = array();
	}
	$case_id = pl_grab_var('case_id', null, 'POST');
	
	$i = 0;
	foreach ($disposition as $val)
	{
		$pk->updateDisposition($ids[$i], $val);
		$i++;
	}
	
	foreach ($delete as $val)
	{
		$pk->deleteCaseCharge($val);
	}
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=charges");
	
	break;
	
	
	case 'add_compen':
	
	$a = pl_grab_vars('compens');
	$pk->addCompen($a);
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen=compen");
	
	
	break;
	
	
	case 'add_survey_response':
	
	$q_ids = pl_grab_var('q_ids', array(), 'POST', 'array');
	$answers = pl_grab_var('answers', array(), 'POST', 'array');
	$case_id = pl_grab_var('case_id', null, 'POST');
	
	if (sizeof($q_ids) != sizeof($answers))
	{
		echo "An error has occurred";
		exit();
	}
	
	$j = sizeof($q_ids);
	
	for ($i = 0; $i < $j; $i++)
	{
		$pk->addSurveyResponse($q_ids[$i], $case_id, $answers[$i]);
	}
	
	header("Location: survey.php?action=close");
	
	break;

	
	case 'save_prefs':
	
	$_SESSION['def_office'] =  pl_grab_var('def_office', null, 'POST');
	$_SESSION['intake'] =  pl_grab_var('intake', null, 'POST');
	$_SESSION['paging'] =  pl_grab_var('paging', null, 'POST', 'number');
	$_SESSION['font_size'] =  pl_grab_var('font_size', null, 'POST');
	$_SESSION['popup'] =  pl_grab_var('popup', null, 'POST', 'boolean');
	$_SESSION['theme'] = pl_grab_var('theme', null, 'POST');
	$_SESSION['r_format'] = pl_grab_var('r_format', null, 'POST');
	session_write_close();
	header("Location: prefs.php?user_id={$auth_row['user_id']}");
	//pl_session_freeze();
	
	break;
	
	
	case 'not_allowed':
	
	die(pika_error_notice("$window_title", 'Editing of this case is not allowed'));
	break;
	
	
	case 'add_event':
	
	if (array_key_exists('cancel', $_REQUEST))
	{
		header("Location: {$_REQUEST['act_url']}");
		break;
	}
	
	// TODO - security?
	
	$a = pl_grab_vars('events');
	//$user_array = pl_grab_var('{user_id}', array(), 'POST', 'array');
	$a['user_ids'] = ','. implode($user_id, ',') . ',';
	$event = new event();
	$event->setValues($a);
	
	// decide where to go from here
	$act_url = urlencode($_REQUEST['act_url']);
	$act_date_tmp = pl_date_mogrify($_REQUEST['act_date']);
	header("Location: event.php?screen=compose&user_id={$_REQUEST['user_id']}&pba_id={$_REQUEST['pba_id']}&case_id={$_REQUEST['case_id']}&funding={$_REQUEST['funding']}&act_date=$act_date_tmp&completed={$_REQUEST['completed']}&act_url=$act_url&act_type={$_REQUEST['act_type']}");
	
	break;
	
	case 'add_ex_appt':
	
	/*
	$case_id = pl_grab_var('case_id');
	$data = array();
	$data = pl_grab_var('sched_select', $data, 'POST', 'array');
	$summary = pl_grab_var('summary');
	list($dtstart, $dtend, $contact) = explode(',', $data);
	$contact = strtolower($contact);
	list($first_name, $last_name) = explode(" ", $contact);
	$username = substr($first_name, 0, 1) . $last_name;
	
	pika_xchg_appt_add($username, $dtstart, $dtend, $summary);
	*/
	
	$sched_select = $_POST['sched_select'];  // an array
	$case_id = pl_grab_var('case_id', null, 'POST');
	$summary = pl_grab_var('summary', null, 'POST');
	$case_number = pl_grab_var('number', null, 'POST');
	
	// Extract the filename, username from $sched_select.
	$n = explode('|', $sched_select[0]);
	$record_id = $n[0];
	$ex_username = $n[1];
	
	if ($case_id > 0 && strlen($sched_select) > 0)
	{
		require_once('nusoap.php');
		$parameters = array('server_name' => 'NEWTON', 
				'username' => $ex_username, 
				'record_id' => $record_id, 
				'summary' => $case_number . ': ' . $summary);
		$s = new soapclient('http://newton.freelawyers.org:8000/exchange4pika/ex_tol_assign.php');
		$result = $s->call('ex_tol_assign', $parameters);
		if ($s->fault)
		{
			die(pika_error_notice('Error', "Case transfer error: $error" . $client->faultstring));
		}
		
		else if (0 == $result)
		{
			header("Location: {$base_url}/case.php?case_id={$case_id}&screen=sched&failure=1");
		}
		
		else 
		{
			header("Location: case.php?case_id={$case_id}&screen=sched&status=result");
		}
	}
	
	else 
	{
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen=sched&failure=1");
	}
		
	break;
	
	
	// Hack.
	case 'toledo_holding':
	
	$x = pl_grab_vars('cases');
	
	if (!$x['case_id'])
	{
		die(pika_error_notice('Pika is sick',
		'No case ID and/or Office ID supplied.'));
	}
	
	// Set LAL Transfer on old case.
	
	$u = array();
	$u['case_id'] = $x['case_id'];
	$u['transfer_to'] = pl_grab_var('trans_office', 'POST', 'X');
	$pk->updateCase($u);
	
	// Create new transfer cases with appropriate defaults.
	
	$res = $pk->fetchCase($x['case_id']);
	$y = DBResult::fetchRow($res);
	$y['office'] = pl_grab_var('trans_office', 'POST', 'X');
	$y['number'] = 'auto';
	$y['in_holding_pen'] = '1';
	$y['status'] = '1';
	$y['user_id'] = '1000004';
	$y['cocounsel'] = '';
	$y['cocounsel2'] = '';
	$y['transfer_to'] = '';
	$new_case_id = $pk->newCase($y);
	
	$res = DB::query("SELECT * FROM conflict WHERE case_id='{$x['case_id']}'");
	while ($row = DBResult::fetchRow($res))
	{
		$pk->addCaseContact($new_case_id, $row['contact_id'], $row['relation_code']);
	}
	
	$res = $pk->fetchNotes($x['case_id']);
	while ($row = DBResult::fetchRow($res))
	{
		$row['case_id'] = $new_case_id;
		$row['hours'] = '0';
		$pk->newActivity($row);
	}

	$x['status'] = '4';
	$pk->updateCase($x);

	header("Location: toledo_holding.php?case_id={$x['case_id']}&new_case_id=$new_case_id&office={$y['office']}");

	break;
	
	
	case 'reopen_case':
	
	$case_id = pl_grab_post('case_id');
	$screen = pl_clean_html(pl_grab_post('screen'));
	
	if (!$case_id)
	{
		die(pika_error_notice('Pika is sick',
		'No case ID and/or Office ID supplied.'));
	}
	
	$u = array();
	$u['case_id'] = $case_id;
	$u['status'] = '1';
	$u['close_date'] = '';
	$u['close_code'] = '';
	$u['reject_code'] = '';
	$u['destroy_date'] = '';
	// Toledo-specific field.
	$u['disposition_status'] = '';
	$pk->updateCase($u);
	
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");

	break;
	
	
	case 'transfer-soap':

	require_once('nusoap.php');
	
	function transfer_error($section_name)
	{
		global $s;
		$error = $s->getError();
		
		$plTemplate['content'] = "Case transfer error ($section_name): $error";
		$plTemplate['page_title'] = 'Case Transfer';
		$plTemplate['nav'] = "<a href=\".\" class=light>$pikaNavRootLabel</a> &gt; Case Transfer";
		echo pl_template($plTemplate, 'templates/default.html');
		echo pl_bench('results');
		exit();
	}
	
	$case_id = pl_grab_var('case_id', 'GET');
	$s = new soapclient('http://skunky/gila/transfer-soap.php');
	$staff_array = $pk->fetchStaffArray();
	$owner_name = pl_settings_get('owner_name');
	
	// cases record.
	$r = $pk->fetchCase($case_id);
	$case_row = $case_row2 = DBResult::fetchRow($r);
	
	unset($case_row['user_id']);
	unset($case_row['cocounsel1']);
	unset($case_row['cocounsel2']);
	unset($case_row['intake_user_id']);
		
	// This hack prevents zeros from converting to NULLs during transfer.
	foreach ($case_row as $key => $val)
	{
		if ($val == "0.00")
		{
			$case_row[$key] = "zero";
		}
	}
	
	$parameters = array($case_row);
	$t_case_id = $s->call('newCase', $parameters);

	if ($s->getError())
	{
		transfer_error('newCase');
	}
	
	// contacts and conflicts.
	$r = $pk->fetchCaseContacts($case_id);
	while ($row = DBResult::fetchRow($r))
	{		
		$parameters = array($row);
		$t_contact_id = $s->call('newContact', $parameters);
		if ($s->getError())
		{
			transfer_error('newContact');
		}
		
		$parameters = array($t_case_id, $t_contact_id, $row['relation_code']);
		$result = $s->call('addCaseContact', $parameters);
		if ($s->getError())
		{
			transfer_error('addCaseContact');
		}
	}
	
	// activities - notes and timekeeping.
	$r = $pk->fetchNotes($case_id);
	while ($notes = DBResult::fetchRow($r))
	{
		$notes['case_id'] = $t_case_id;

		$atty_name = pl_array_lookup($notes['user_id'], $staff_array);
		$notes['notes'] .= "\n\n===\nEntered by {$atty_name}, {$owner_name}";
		$notes['notes'] .= ", {$case_row['number']}";
		unset($notes['user_id']);
		
		$parameters = array($notes);
		$result = $s->call('newActivity', $parameters);
		if ($s->getError())
		{
			transfer_error('newActivity');
		}
	}
	
	// Set the original case to transferred status.
	$case_row2['status'] = 4;
	$pk->updateCase($case_row2);

	$plTemplate['content'] = "Transfer of case # '{$case_id}' completed, new case id # is '$t_case_id' $t_contact_id.";
	$plTemplate['page_title'] = 'Case Transfer';
	$plTemplate['nav'] = "<a href=\".\" class=light>$pikaNavRootLabel</a> &gt; Case Transfer";
	echo pl_template($plTemplate, 'templates/default.html');
	echo pl_bench('results');
	
	break;
	
	
	//Questionnaire by DTK

	case "save_quest":
		$user_id = $auth_row["user_id"];
		$questionnaire_id = pl_grab_var('questionnaire_id', null, 'REQUEST');
		$completed_id = pl_grab_var('completed_id', null, 'REQUEST');
		$case_id = pl_grab_var('case_id', null, 'REQUEST');
		$answer = pl_grab_var('answer', null, 'REQUEST');
		$answer_id = pl_grab_var('answer_id', null, 'REQUEST');
		$q_action = pl_grab_var('q_action', null, 'REQUEST');
		$response_text = pl_grab_var('response_text', null, 'REQUEST');
		$response_text = addslashes($response_text);
		
		if (!$completed_id) {
			$completed_sql  = "SELECT completed_id FROM q_completed WHERE questionnaire_id=$questionnaire_id ";
			$completed_sql .= "AND case_id=$case_id ORDER BY completed_time DESC LIMIT 1";
//			echo $completed_sql . "<br>";
			$results = DB::query($completed_sql);
			while ($row = DBResult::fetchRow($results)) {
				$completed_id = $row["completed_id"];
			}

			if (!$completed_id) {
				$completed_sql = "INSERT INTO q_completed (questionnaire_id, case_id, user_id, completed_time) ";
				$completed_sql .= "VALUES ($questionnaire_id, $case_id, $user_id, CURDATE())";
//				echo $completed_sql . "<br>";
				DB::query($completed_sql);
				$completed_sql  = "SELECT completed_id FROM q_completed WHERE questionnaire_id=$questionnaire_id ";
				$completed_sql .= "AND case_id=$case_id AND user_id=$user_id ORDER BY completed_time DESC LIMIT 1";
//				echo $completed_sql . "<br>";
				$results = DB::query($completed_sql);
				while ($row = DBResult::fetchRow($results)) {
					$completed_id = $row["completed_id"];
				}
			} else {
				$completed_sql = "UPDATE q_completed SET ";
				$completed_sql .= "user_id=$user_id, completed_time=CURDATE() WHERE completed_id=$completed_id";
				DB::query($completed_sql);
				echo $completed_sql . "<br>";
			}
		}
		
		$response_sql  = "SELECT response_id FROM q_responses WHERE completed_id=$completed_id AND question_id=$question_id ORDER BY response_id DESC LIMIT 1";
//		echo $response_sql . "<br>";
		$results = DB::query($response_sql);
		while ($row = DBResult::fetchRow($results)) {
			$response_id = $row["response_id"];
		}
		
		if (!$response_id) {
			$response_sql  = "INSERT INTO q_responses (completed_id, question_id, response_text, answer_id) ";
			$response_sql .= "VALUES ($completed_id, $question_id, '$response_text', $answer_id) ";
		} else {
			$response_sql  = "UPDATE q_responses SET response_text='$response_text', answer_id=$answer_id ";
			$response_sql .= "WHERE response_id=$response_id";
		}
//		echo $response_sql . "<br>";
		DB::query($response_sql);
//		echo "<a href=quest_answer.php?case_id=$case_id&questionnaire_id=$questionnaire_id&answer_id=$answer_id&case_id=$case_id&completed_id=$completed_id>Next</a>";
		header("Location: quest_answer.php?case_id=$case_id&questionnaire_id=$questionnaire_id&answer_id=$answer_id&case_id=$case_id&completed_id=$completed_id");
	break;

	
	default:
	
	die(pika_error_notice("$window_title", "Error:  invalid action was specified."));
	
	break;
}

// end of 'action' section

exit();

?>
