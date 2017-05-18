<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

chdir('../');
require_once ('pika-danio.php');
pika_init();


// LIBRARIES
require_once('pikaCase.php');
require_once('pikaContact.php');

$next_tab = pl_settings_get('default_case_tab');

if (is_null($next_tab))
{
	$next_tab = 'elig';
}

// VARIABLES
$thiscon = pl_grab_get('thiscon');
$base_url = pl_settings_get('base_url');
$screen = pl_grab_get('screen', $next_tab);
$safe_screen = pl_clean_html($screen);


// BEGIN MAIN CODE...

/*  The user is coming from intake2.php, and wants to create a new case
for an contact who has no previous records.
*/

// Add the contact record.
$client = new pikaContact();
$client->setValues(pl_clean_form_input($_GET));
$client->save();

// add the case record...
$case1 = new pikaCase();
$case1->setValues(pl_clean_form_input($_GET));

// Now link the client to the case and set the first client as the primary client
$case1->addContact($client->getValue('contact_id'), 1);
$case1->save();

/*	Go to the contact screen, then the eligibility tab.
*/
$contact_id = $client->getValue('contact_id');
$case_id = $case1->getValue('case_id');
header("Location: {$base_url}/contact.php?contact_id={$contact_id}&case_id={$case_id}&screen={$screen}");
exit();

?>
