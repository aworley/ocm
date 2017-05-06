<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/
// 2012 point release update appending url so user will return to case listing instead of case when updating case related activity from case listing

chdir('../');

require_once ('pika-danio.php');
pika_init();

require_once('pikaActivity.php');

// VARIABLES
$base_url = pl_settings_get('base_url');
$act_interval = pl_settings_get('act_interval');

$act_id = pl_grab_post('act_id');
$act_date = pl_grab_post('act_date', date('Y-m-d'));
$act_url = pl_grab_post('act_url');
$act_type = pl_grab_post('act_type', 'C');
$funding = pl_grab_post('funding');
$case_id = pl_grab_post('case_id');
$screen = pl_grab_post('screen', 'act');
$user_id = pl_grab_post('user_id');
$pba_id = pl_grab_post('pba_id');

$next_act = pl_grab_post('next_act');
$close_act = pl_grab_post('close_act');
$cancel = pl_grab_post('cancel');

$a = pl_clean_form_input($_POST);


// BEGIN MAIN CODE...

// AMW 2013-05-03 - $act_url is getting set to "case.php" in some cases, which causes a error due to the missing case_id.  This is 
// an ugly workaround for that.  Future TODO: fix the code that sets $act_url in activity.php
/*
if ("case.php" == $act_url)
{
	$act_url = "index.php";
}
*/


// The user is saving the activity record.
if($act_id && is_numeric($act_id)) {
	$activity = new pikaActivity($act_id);
	$act_row = $activity->getValues();
	if (pika_authorize('edit_act', $act_row)) 
	{	
		$activity->setValues($a);
		$activity->hours = pikaActivity::roundHoursByInterval($activity->hours,$act_interval);
		$activity->save();
	}
} else if (!$cancel) {
	$activity = new pikaActivity();
	unset($a['act_id']);
	$activity->setValues($a);
	$activity->hours = pikaActivity::roundHoursByInterval($activity->hours,$act_interval);
	
	if ($activity->act_type == 'K' && file_exists(pl_custom_directory() . '/extensions/create_tickler/create_tickler.php'))
	{
		require_once(pl_custom_directory() . '/extensions/create_tickler/create_tickler.php');
		$z = $activity->getValues();
		$z['case_number'] = null;
		$z['client_name'] = null;
		$z['case_status'] = null;
		$z['tickler_email'] = array();

		if (isset($z['user_id']))
		{
			require_once('pikaContact.php');
			$tickler_owner = new pikaUser($z['user_id']);
			$z['summary'] .= " (" . substr($tickler_owner->first_name, 0, 1);
			//$z['summary'] .= substr($tickler_owner->middle_name, 0, 1);
			$z['summary'] .= substr($tickler_owner->last_name, 0, 1) . ")";
		}
		
		if ($z['case_id'] > 0)
		{
			require_once('pikaCase.php');
			$case0 = new pikaCase($z['case_id']);
			$z['case_number'] = $case0->number;
			$z['case_status'] = $case0->status;

			if ($case0->client_id > 0)
			{
				require_once('pikaContact.php');
				$client = new pikaContact($case0->client_id);
				$z['client_name'] = $client->first_name . " ";
				$z['client_name'] .= $client->middle_name . " ";
				$z['client_name'] .= $client->last_name . " ";
				$z['client_name'] .= $client->extra_name;
			}
			
			$z['case_link'] = $_SERVER['REQUEST_SCHEME'];
			
			/* AMW 2015-02-10 - I am using SERVER_NAME instead of HTTP_HOST
				here.  I believe SERVER_NAME may be less reliable if the
				server is misconfigured, or (possibly) when using VirtualHosts.
				But since HTTP_HOST is user-provided, close attention would need
				to be paid to security.  The only "secret" information provided 
				by case_link is the $base_url, so that part doesn't appear to be
				particularly dangerous in it's current state.  SQL injection
				or XSS are probably not a concern at first glance, either.
				But SERVER_NAME provides no additional attack surface so I'm 
				going with that method for now.
			*/
			$z['case_link'] .= '://' . $_SERVER['SERVER_NAME'];
			$z['case_link'] .= pl_settings_get('base_url');
			$z['case_link'] .= '/case.php?case_id=' . $case0->case_id;

			if ($case0->user_id > 0)
			{
				require_once('pikaContact.php');
				$user0 = new pikaUser($case0->user_id);
				$z['tickler_email'][] = $user0->email;
			}
			
			if ($case0->cocounsel1 > 0)
			{
				require_once('pikaContact.php');
				$user1 = new pikaUser($case0->cocounsel1);
				$z['tickler_email'][] = $user1->email;
			}
			
			if ($case0->cocounsel2 > 0)
			{
				require_once('pikaContact.php');
				$user2 = new pikaUser($case0->cocounsel2);
				$z['tickler_email'][] = $user2->email;
			}

			if ($case0->cocounsel3 > 0)
			{
				require_once('pikaContact.php');
				$user3 = new pikaUser($case0->cocounsel3);
				$z['tickler_email'][] = $user3->email;
			}
			
			if (strlen($case0->office) > 0)
			{
				if (mysql_num_rows(mysql_query("SHOW TABLES LIKE 'office_email'")) == 1)
				{
					$x = mysql_real_escape_string($case0->office);
					$y = mysql_fetch_assoc(mysql_query("SELECT label FROM office_email WHERE value = '{$x}'"));
					$z['tickler_email'][] = $y['label'];
				}
			}
		}
				
		create_tickler($z) or trigger_error('Extension failed.');
	}
	
	$activity->save();
}

// 2012 point release update appending url so user will return to case listing instead of case when updating case related activity from case listing 
/* if ($next_act) {
	header("Location: {$base_url}/activity.php?act_type={$act_type}&act_date={$act_date}&case_id={$case_id}&funding={$funding}&user_id={$user_id}&pba_id={$pba_id}&act_url={$act_url}");
} else if ($close_act){
	if ($case_id && is_numeric($case_id)) {
		header("Location: {$base_url}/case.php?case_id={$a['case_id']}");
	} else {
		if($act_url == 'case.php') { $act_url = 'cal_day.php';}
		header("Location: {$base_url}/{$act_url}?cal_date={$act_date}");
	}
} else {
	if ($case_id && is_numeric($case_id)) {
		header("Location: {$base_url}/case.php?case_id={$case_id}");
	} else {
		header("Location: {$base_url}/cal_day.php?cal_date={$act_date}");
	}
}
*/

if ($next_act) 
{
	header("Location: {$base_url}/activity.php?act_type={$act_type}&act_date={$act_date}&case_id={$case_id}&funding={$funding}&user_id={$user_id}&pba_id={$pba_id}&act_url={$act_url}");
} 

else if ($close_act)
{
	if ($case_id && is_numeric($case_id) && strpos($act_url,'case.php') !== false) 
	{
		if (strpos($act_url,'case_id') !== false)
		{	
			header("Location: {$base_url}/{$act_url}");	
		}
		
		else 
		{
			header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
		}
	}
	
	else if(preg_match('/cal_(day|week|adv).php$/',$act_url)) 
	{
		header("Location: {$base_url}/{$act_url}?cal_date={$act_date}");
	}
	
	else 
	{
		header("Location: {$base_url}/{$act_url}");
	}
} 

else 
{
	if ($case_id && is_numeric($case_id)) 
	{
		header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
	}
	
	else 
	{
		header("Location: {$base_url}/cal_day.php?cal_date={$act_date}");
	}
}

exit();

?>