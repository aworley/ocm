<?php

/****************************************/
/* Pika CMS	(C) 2011 Pika Software, LLC	*/
/* http://pikasoftware.com				*/
/****************************************/

set_time_limit(0);

require_once ('pika-danio.php');
pika_init();

require_once('pikaTempLib.php');
require_once('pikaUser.php');
require_once('pikaActivity.php');
require_once('pikaContact.php');
require_once('pikaCase.php');
require_once('pikaMenu.php');
require_once('pikaMisc.php');
require_once('pikaSettings.php');

$main_html = array();
$html = '';

$base_url =pl_settings_get('base_url');

$gen = pl_grab_post('gen');
$count = pl_grab_post('count');

function rand_zip()
{
	$zip = array();
	$sql = "SELECT city, state, zip, area_code, county 
			FROM zip_codes 
			WHERE 1
			AND city IS NOT NULL 
			AND state IS NOT NULL 
			AND zip IS NOT NULL 
			AND area_code IS NOT NULL 
			ORDER BY RAND() 
			LIMIT 1";
	$result = DB::query($sql) or trigger_error('SQL: ' . $sql . ' Error: ' . DB::error());
	if(DBResult::numRows($result) == 1)
	{
		$zip = DBResult::fetchRow($result);
	}
	return $zip;
}

function rand_contact()
{
	// First initialize the defaults
	$male_first = array();
	$male_first[] = 'Thomas';
	$male_first[] = 'Richard';
	$male_first[] = 'Harold';
	$male_first[] = 'Marcus';
	$male_first[] = 'Juan';
	$male_first[] = 'James';
	$male_first[] = 'Daniel';
	$male_first[] = 'Jason';
	$male_first[] = 'Pedro';
	$male_first[] = 'Peter';
	$male_first[] = 'Michael';
	$male_first[] = 'Carlos';
	$male_first[] = 'John';
	$male_first[] = 'William';
	$male_first[] = 'Charles';
	$male_first[] = 'George';
	$male_first[] = 'Joseph';
	$male_first[] = 'Frank';
	$male_first[] = 'Henry';	
	$male_first[] = 'Edward';
	$male_first[] = 'Robert';
	$male_first[] = 'Donald';
	$male_first[] = 'Gary';
	$male_first[] = 'David';
	$male_first[] = 'Mark';
	$male_first[] = 'Drew';
	$male_first[] = 'Steven';
	$male_first[] = 'Christopher';
	$male_first[] = 'Brian';
	$male_first[] = 'Joshua';
	$male_first[] = 'Andrew';
	$male_first[] = 'Justin';
	$male_first[] = 'Jacob';
	$male_first[] = 'Matthew';
	$male_first[] = 'Nicholas';
	$male_first[] = 'Mathew';
	$male_first[] = 'Tyler';
	$male_first[] = 'Mustafa';
	$male_first[] = 'Rod';
	$male_first[] = 'Peter';
	$male_first[] = 'Stone';
	$male_first[] = 'Gus';
	$male_first[] = 'Olandis';
	$male_first[] = 'Eric';
	$male_first[] = 'Terrell';
	$male_first[] = 'George';
	$male_first[] = 'Kent';
	$male_first[] = 'Kirby';
	$male_first[] = 'Marcus';
	$male_first[] = 'Quincy';
	$male_first[] = 'Neil';
	$male_first[] = 'Benjamin';
	$male_first[] = 'Rudolph';
	$male_first[] = 'Noel';
	$male_first[] = 'Preston';
	$male_first[] = 'Tucker';
	$male_first[] = 'Vern';
	$male_first[] = 'Ernest';
	$male_first[] = 'Abdul';
	$male_first[] = 'Muhammad';

	$fem_first = array();
	$fem_first[] = 'Jane';
	$fem_first[] = 'Anne';
	$fem_first[] = 'Mary';
	$fem_first[] = 'Jennifer';
	$fem_first[] = 'Jessica';
	$fem_first[] = 'Linda';
	$fem_first[] = 'Elizabeth';
	$fem_first[] = 'Judith';
	$fem_first[] = 'Martha';
	$fem_first[] = 'Mildred';
	$fem_first[] = 'Margaret';
	$fem_first[] = 'Minnie';
	$fem_first[] = 'Emma';
	$fem_first[] = 'Alice';
	$fem_first[] = 'Marie';
	$fem_first[] = 'Annie';
	$fem_first[] = 'Sarah';
	$fem_first[] = 'Rose';
	$fem_first[] = 'Ethel';
	$fem_first[] = 'Florence';
	$fem_first[] = 'Ida';
	$fem_first[] = 'Bertha';
	$fem_first[] = 'Ruth';
	$fem_first[] = 'Dorothy';
	$fem_first[] = 'Virginia';
	$fem_first[] = 'Betty';
	$fem_first[] = 'Barbara';
	$fem_first[] = 'Patricia';
	$fem_first[] = 'Doris';
	$fem_first[] = 'Joan';
	$fem_first[] = 'Carol';
	$fem_first[] = 'Nancy';
	$fem_first[] = 'Maria';
	$fem_first[] = 'Susan';
	$fem_first[] = 'Karen';
	$fem_first[] = 'Lisa';
	$fem_first[] = 'Donna';
	$fem_first[] = 'Debra';
	$fem_first[] = 'Kimberly';
	$fem_first[] = 'Amanda';
	$fem_first[] = 'Melissa';
	$fem_first[] = 'Angela';
	$fem_first[] = 'Tracy';
	$fem_first[] = 'Nicole';
	$fem_first[] = 'Heather';
	$fem_first[] = 'Michelle';
	$fem_first[] = 'Ashley';
	$fem_first[] = 'Brittany';
	$fem_first[] = 'Stephanie';
	$fem_first[] = 'Samantha';
	$fem_first[] = 'Megan';
	$fem_first[] = 'Lauren';
	$fem_first[] = 'Emily';
	$fem_first[] = 'Hannah';
	$fem_first[] = 'Alexis';
	$fem_first[] = 'Madison';
	$fem_first[] = 'Taylor';
	$fem_first[] = 'Cher';
	$fem_first[] = 'Tamara';
	$fem_first[] = 'Lynn';
	$fem_first[] = 'Natasha';
	$fem_first[] = 'Reagan';
	$fem_first[] = 'Marcia';
	$fem_first[] = 'Veloria';
	$fem_first[] = 'Cecilia';
	$fem_first[] = 'Allison';
	$fem_first[] = 'Havalina';
	$fem_first[] = 'Xuelei';

	$last = array();
	$last[] = 'Jackson';
	$last[] = 'Green';
	$last[] = 'White';
	$last[] = 'Black';
	$last[] = 'Wolcott';
	$last[] = 'Anderson';	
	$last[] = 'Schmidt';
	$last[] = 'Johnson';
	$last[] = 'Davis';
	$last[] = 'Martinez';
	$last[] = 'Garcia';
	$last[] = 'Wu';
	$last[] = 'Hernandez';
	$last[] = 'King';
	$last[] = 'Martin';
	$last[] = 'Lee';
	$last[] = 'Jones';
	$last[] = 'Brown';
	$last[] = 'Miller';
	$last[] = 'Wilson';
	$last[] = 'Moore';
	$last[] = 'Smith';
	$last[] = 'Williams';
	$last[] = 'Taylor';
	$last[] = 'Thomas';
	$last[] = 'Harris';
	$last[] = 'Thompson';
	$last[] = 'Robinson';
	$last[] = 'Clark';
	$last[] = 'Rodriguez';
	$last[] = 'Lewis';
	$last[] = 'Walker';
	$last[] = 'Hall';
	$last[] = 'Allen';
	$last[] = 'Young';
	$last[] = 'Trujillo';
	$last[] = 'Perez';
	$last[] = 'Edwards';
	$last[] = 'Franklin';
	$last[] = 'Ivanov';
	$last[] = 'Neagle';
	$last[] = 'O\'Brian';
	$last[] = 'Wang';
	$last[] = 'Quincy';
	$last[] = 'Underwood';
	$last[] = 'Vangilder';
	$last[] = 'Xiao';
	$last[] = 'Banks';
	$last[] = 'Rose';
	$last[] = 'Chan';
	$last[] = 'McDowell';
	$last[] = 'Perry';
	$last[] = 'Kuchler';
	$last[] = 'Garrett';
	$last[] = 'Hays';
	$last[] = 'Segedi';
	$last[] = 'Torvalds';
	$last[] = 'Jennings';
	$last[] = 'Pena';
	$last[] = 'Phillips';
	$last[] = 'Kinney';
	$last[] = 'Cain';
	$last[] = 'Keane';
	$last[] = 'Gary';
	$last[] = 'Weber';
	$last[] = 'Dominguez';
	$last[] = 'Hamilton';
	$last[] = 'Gold';
	$last[] = 'Mobley';
	$last[] = 'Diaz';
	$last[] = 'Berry';
	$last[] = 'Clark';
	$last[] = 'Kennison';
	$last[] = 'Cole';
	$last[] = 'Poole';
	$last[] = 'Pittman';
	$last[] = 'Pryce';
	$last[] = 'Washington';
	$last[] = 'Montgomery';
	$last[] = 'Hrbek';
	$last[] = 'McCaffrey';
	$last[] = 'Cruz';
	$last[] = 'Bennett';
	$last[] = 'Hampton';
	$last[] = 'Norton';
	$last[] = 'Ortiz';
	$last[] = 'Tucker';
	$last[] = 'Vandelay';
	$last[] = 'Archuleta';
	$last[] = 'Moeller';
	$last[] = 'Mullane';

	$street = array();
	$street[] = 'Main St.';
	$street[] = 'Union St.';
	$street[] = 'Pine Valley Blvd.';
	$street[] = 'Union St.';
	$street[] = 'Broadway';
	$street[] = '10th St.';
	$street[] = '20th St.';
	$street[] = '100th St.';
	$street[] = '1000th St.';
	$street[] = 'County Route 16';
	$street[] = 'Lovegrove Lane';
	$street[] = 'Mangrove Blvd';
	$street[] = 'Arlington Road';
	$street[] = 'Wabash Ave';

	$menu_language = pikaMenu::getMenu('language');
	$menu_residence = pikaMenu::getMenu('residence');
	$menu_ethnicity = pikaMenu::getMenu('ethnicity');
	$menu_marital = pikaMenu::getMenu('marital');
	$menu_gender = pikaMenu::getMenu('gender');
	
	$contact = new pikaContact();
	$contact->setValues(rand_zip());

	$contact->gender = array_rand($menu_gender);
	if ($menu_gender[$contact->gender] == 'Female')
	{ // female
		$contact->first_name = $fem_first[array_rand($fem_first)];
		$contact->middle_name = $fem_first[array_rand($fem_first)];
	}
	else
	{ // male
		$contact->first_name = $male_first[array_rand($male_first)];
		$contact->middle_name = $male_first[array_rand($male_first)];
	}
	
	$contact->last_name = $last[array_rand($last)];
	$contact->address = mt_rand(10, 9999) . ' ' . $street[array_rand($street)];
	$contact->area_code = $contact->area_code_alt = mt_rand(100,999);
	$contact->phone = mt_rand(100,999) . '-' . mt_rand(1000,9999);
	$contact->phone_alt = mt_rand(100,999) . '-' . mt_rand(1000,9999);
	$contact->language = array_rand($menu_language);
	$contact->birth_date = mt_rand(1900, 1975) .'-'. mt_rand(1, 12) .'-'. mt_rand(1, 27);
	$contact->ssn = mt_rand(100, 999) .'-'. mt_rand(10, 99) .'-'. mt_rand(1000, 9999);
	$contact->marital = array_rand($menu_marital);
	$contact->residence = array_rand($menu_residence);
	$contact->ethnicity = array_rand($menu_ethnicity);
	$contact->disabled = mt_rand(0, 1);
	$contact->frail = mt_rand(0, 1);
	
	$contact->save();
	return $contact->contact_id;
}

function rand_case_act(pikaCase $case)
{
	
	$menu_act_type = pikaMenu::getMenu('act_type');
	$menu_category = pikaMenu::getMenu('category');
	$menu_users = array($case->user_id, $case->cocounsel1, $case->cocounsel2, $case->intake_user_id);
	
	$activity = new pikaActivity();
	
	$activity->case_id = $case->case_id;
	$activity->user_id = $menu_users[array_rand($menu_users)];
	$activity->funding = $case->funding;
	$activity->act_type = array_rand($menu_act_type);
	$activity->category = array_rand($menu_category);

	$notes_length = mt_rand(5, 15);
	for ($i=0;$i<$notes_length;$i++)
	{
		$activity->notes .= "case notes \n";
	}
	
	$open_timestamp = strtotime($case->open_date);
	$activity->act_date = date('m-d-Y',rand($open_timestamp,time()));
	if(strlen($case->close_date) > 0)
	{
		$close_timestamp = strtotime($case->close_date);
		$activity->act_date = date('m-d-Y',rand($open_timestamp,$close_timestamp));
	}
	$activity->act_time = mt_rand(0,12) . ':' . str_pad(rand(0,59),2,'0');
	$activity->completed = 1;
	
	$interval = pl_settings_get('act_interval');
	$activity->hours = pikaActivity::roundHoursByInterval((float)(rand(1,12)/4.0),$interval);
	

	$activity->save();

}



$menu_problem = pikaMenu::getMenu('problem');
$menu_close_code = pikaMenu::getMenu('close_code');
$menu_outcome = pikaMenu::getMenu('outcome');
$menu_status = pikaMenu::getMenu('case_status');
$menu_office = pikaMenu::getMenu('office');
$menu_income_type = pikaMenu::getMenu('income_type');
$menu_funding = pikaMenu::getMenu('funding');
$menu_referred_by = pikaMenu::getMenu('referred_by');
$menu_users = pikaUser::getUserArray();




if($gen && strlen($gen) > 0)
{
	if(!$count || !is_numeric($count))
	{
		$count = 0;
	}
	for ($i=0; $i<$count; $i++)
	{
		// CASE
		$case = new pikaCase();
	
		$case->user_id = array_rand($menu_users);
		$case->intake_user_id = array_rand($menu_users);	
		$case->problem = array_rand($menu_problem);
		$case->office = array_rand($menu_office);
		$case->status = array_rand($menu_status);
		$case->funding = array_rand($menu_funding);

		$open_timestamp = mt_rand(0, time());
		$open_year = date('y',$open_timestamp);
		$case->open_date =  date('Y-m-d', $open_timestamp);
		if(function_exists('autonumber'))
		{ // Use custom numbering if exists
			$case->number = autonumber($case->getValues());
		}
		else 
		{ // Use default numbering
			$case->number = $case->office . '-' . $open_year . '-' . str_pad(rand(0,99999),5,'0');	
		}

		$result = pikaMisc::getCases(array('number' => $case->number),$row_count);
		if(DBResult::numRows($result) > 0)
		{
			$case->number = null;
		}
	
		$current_year = date('Y');
		$case_year = date('Y',$open_timestamp);
		$close_timestamp = mt_rand($open_timestamp,time());
	
		if ($case->status >= 4 || $current_year > $case_year) // case is over a year old, must be closed
		{
			$case->close_date = date('Y-m-d', $close_timestamp);
			$case->close_code = array_rand($menu_close_code);
			$case->outcome = array_rand($menu_outcome);
		}
	
		
		$case->undup = 1;
		$case->citizen = 'A';
		$case->citizen_check = 1;
		$case->income_type0 = array_rand($menu_income_type);
		$case->income = $case->annual0 = mt_rand(1,50000);
		$case->assets = $case->asset0 = mt_rand(1,20000);
		$case->asset1 = 0;
		$case->asset2 = 0;
		$case->asset3 = 0;
		$case->asset4 = 0;
		$case->children = mt_rand(0,5);
		$case->adults = mt_rand(1,3);
		$case->persons_helped = $case->children + $case->adults;
		$case->referred_by = array_rand($menu_referred_by);
		//$case->dom_abuse = mt_rand(0,1);
		$case->sex_assault = mt_rand(0,1);
		$case->stalking = mt_rand(0,1);
		
		// Add primary client
		$client_id = rand_contact();
		$case->client_id = $client_id;
		
		$case->save();
		$case->addContact($client_id,1);
		
		// Add opposing party
		$opposing_id = rand_contact();
		$case->addContact($opposing_id,2);
	
		// Add opposing counsel
		$opp_counsel_id = rand_contact();
		$case->addContact($opp_counsel_id,3);
	
		// Case Notes
		$case_act_count = mt_rand(0,10);
		for($j=0;$j<$case_act_count;$j++)
		{
			rand_case_act($case,$menu_users);
		}
		$html .= "<p>Added case_id <a href=\"{$base_url}/case.php?case_id={$case->case_id}\">{$case->case_id}</a></p>\n";
	}	
	
}

$form = <<<FORM
<h2>Generate Sample Data</h2>
<form method="POST">
Number of Cases:<br/>
%%[count,input_text]%%
<input type=submit name="gen" value="Generate" tabindex="">
</form>
FORM;



// Display form

$template = new pikaTempLib($form,array());
$main_html['content'] = $template->draw();
$main_html['content'] .= $html;
$main_html['page_title'] = "Generate Sample Data";
$main_html['nav'] = "<a href=\".\">Pika Home</a> &gt; Generate Sample Data";

$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);


?>
