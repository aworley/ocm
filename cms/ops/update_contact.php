<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../');

require_once ('pika-danio.php');
pika_init();

require_once('pikaContact.php');

$next_tab = pl_settings_get('default_case_tab');

if (is_null($next_tab))
{
	$next_tab = 'elig';
}

// VARIABLES
$base_url = pl_settings_get('base_url');
$contact_id = pl_grab_post('contact_id');
$case_id = pl_grab_post('case_id');
$intake_id = pl_grab_post('intake_id');
$screen = pl_grab_post('screen', $next_tab);

// BEGIN MAIN CODE...

// The user is saving the case record. 

$contact = new pikaContact($contact_id);
$contact->setValues(pl_clean_form_input($_POST));
$contact->save();

if ($case_id)
{
	header("Location: {$base_url}/case.php?case_id={$case_id}&screen={$screen}");
}

else if ($intake_id)
{
	header("Location: {$base_url}/intakes.php/{$intake_id}/");
}

else 
{
	$contact_id = $contact->getValue('contact_id');
	header("Location: {$base_url}/contact.php?contact_id={$contact_id}");
}

exit();

?>