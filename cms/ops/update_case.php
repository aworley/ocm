<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com       */
/**********************************/

chdir('../');

require_once ('pika-danio.php');
pika_init();

require_once('pikaCase.php');

// VARIABLES
$base_url = pl_settings_get('base_url');


if ( $_SERVER['REQUEST_METHOD'] === 'POST' ){ 
	$case_id = pl_grab_post('case_id', 0);
	$action = pl_grab_post('action');
	$screen = pl_grab_post('screen');
	$submitted_data = $_POST;
} else {
	$case_id = pl_grab_get('case_id', 0);
	$action = pl_grab_get('action');
	$screen = pl_grab_get('screen');
	$submitted_data = $_GET;
}


// BEGIN MAIN CODE...

if (!$case_id) 
{
	trigger_error('No case ID was provided.');
}

// The user is saving the case record.
$case_data = new pikaCase($case_id);

// Check permissions first.
$case_row = $case_data->getValues();
$allow_edits = pika_authorize('edit_case', $case_row);
	
if ($allow_edits) 
{
	if (pl_settings_get('open_outcomes') && $case_row['close_date'] === null 
		&& isset($submitted_data['close_date'])
		&& strlen($submitted_data['close_date']) > 0)
	{
		$screen = 'outcomes';
	}
	
	$case_data->setValues(pl_clean_form_input($submitted_data));
	$case_data->save();
	
	if (array_key_exists('outcomes', $_POST))
	{
		// AMW - It would be more efficient to pass all outcomes to a 
		// "pikaCase::recordOutcomes($array)" method that could run one
		// INSERT with multiple rows, but I don't think it needs to be
		// optimized at this point.
		$case_data->deleteOutcomes();
				
		foreach ($_POST['outcomes'] as $key => $val)
		{
			$case_data->addOutcome($key, $val);
		}
	}
	
	else if (array_key_exists('single_outcome', $_POST))
	{
		$case_data->deleteOutcomes();
		$case_data->addOutcome(pl_grab_post('single_outcome', null, 'number'), 1);
	}
	
	// AC - clear outcomes if the problem code changes
        if (array_key_exists('prior_problem', $_POST) && array_key_exists('problem', $_POST) && $_POST['prior_problem'] != $_POST['problem'])
        {
                $reset_sql = "DELETE FROM outcomes WHERE case_id = {$case_id}";
                DB::query($reset_sql) or trigger_error("SQL: " . $reset_sql . " Error: " . DB::error());
        }

}

$client_id = $case_data->getValue('client_id');
$case_id = $case_data->getValue('case_id');

if ('confirm_client' == $action)
{
	header("Location: {$base_url}/contact.php?contact_id={$client_id}&case_id={$case_id}");
}

else
{
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
}

exit();

?>
