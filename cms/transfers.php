<?php

/**********************************/
/* Pika CMS (C) 2015 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika-danio.php');
pika_init();

function potential_conflicts($row, $relation_code, $description)
{
	$base_url = pl_settings_get('base_url');
	$z = "<h2>Conflict Check for {$description}</h2>";
	$row['relation_code'] = $relation_code;
	$row['contact_id'] = '0';  // Placeholder value.
	$row['mp_first'] = substr(metaphone($row['first_name']), 0, 8);
	$row['mp_last'] = substr(metaphone($row['last_name']), 0, 8);
	$lim = 10000;
	$tmp_row = array();
	$conflict_array = array();
	
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
		
		$z .= "<p>{$tmp_row['first_name']} {$tmp_row['last_name']} was a(n) {$tmp_row['role']} on ";
		$z .= "<a href=\"{$base_url}/case.php?case_id={$tmp_row['case_id']}\">";
		$z .= "{$tmp_row['number']}</a></p>";
	}
	
	// Match by SSN
	if (strlen($row['ssn'] > 0))
	{
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

		$z .= "<p>{$tmp_row['first_name']} {$tmp_row['last_name']} was a(n) {$tmp_row['role']} on ";
		$z .= "<a href=\"{$base_url}/case.php?case_id={$tmp_row['case_id']}\">";
		$z .= "{$tmp_row['number']}</a></p>";
		}
	}
	
	if (sizeof($conflict_array) < 1)
	{
		$z .= "<p>Nothing found.</p>";
	}
	
	return $z;
}


$transfer_id = pl_grab_get('transfer_id', 0);

$z = '';
$base_url = pl_settings_get('base_url');

if (strlen(pl_grab_post('accept')) > 0)
{
	$safe_transfer_id = mysql_real_escape_string(pl_grab_post('transfer_id'));
	require_once('pikaTransfer.php');
	$tx = new pikaTransfer($safe_transfer_id);	
	$x = json_decode($tx->getValue('json_data'), 1);
	
	require_once('pikaContact.php');
	$client = new pikaContact();
	$client->setValues($x['client']);
	$client->save();
	
	require_once('pikaCase.php');
	$case0 = new pikaCase();
	$case0->setValues($x['case']);
	$case0->addContact($client->getValue('contact_id'), 1);
	$case0->save();

	// Opposing Party
	if (isset($x['op']))
	{
		$op = new pikaContact();
		$op->setValues($x['op']);
		$op->save();
		$case0->addContact($op->getValue('contact_id'), 2);
	}

	// Opposing Party Attorney
	if (isset($x['opa']))
	{
		$opa = new pikaContact();
		$opa->setValues($x['opa']);
		$opa->save();
		$case0->addContact($opa->getValue('contact_id'), 3);
	}

	// Case notes
	if (isset($x['notes']))
	{
		require_once('pikaActivity.php');
		
		for ($i = 0; $i < 10; $i++)
		{
			if (isset($x['notes']['notes' . $i]))
			{
				$note = new pikaActivity();
				$note->setValue('summary', 'Online Intake Notes');
				$note->setValue('notes', $x['notes']['notes' . $i]);
				$note->setValue('case_id', $case0->getValue('case_id'));
				$note->save();
			}
		}
	}
	
	$tx->setValue('accepted', true);
	$tx->save();
	
	header("Location:  {$base_url}/case.php?case_id={$case0->case_id}&screen=elig");
	exit();
}

else if (strlen(pl_grab_post('reject')) > 0)
{
	$safe_transfer_id = mysql_real_escape_string(pl_grab_post('transfer_id'));
	require_once('pikaTransfer.php');
	$tx = new pikaTransfer($safe_transfer_id);
	$tx->setValue('accepted', false);
	$tx->save();
	
	$z .= "case rejected.";
}

else if (!$transfer_id)
{
	$z .= "<table class=\"table\">";
		$z .= "<thead><tr><th></th><th>Record ID</th><th>Last Name</th><th>First Name</th><th>County</th><th>City</th><th>Problem Code</th><th>Date Received</th></tr></thead><tbody>";
	$result = mysql_query("SELECT * FROM transfers WHERE accepted = '2'");
	
	while ($row = mysql_fetch_assoc($result))
	{
		$j = json_decode($row['json_data'], true);
		
		$safe_transfer_id = mysql_real_escape_string($row['transfer_id']);
		$safe_date = mysql_real_escape_string($row['created']);
		/*
		foreach ($j as $key => $val)
		{
			$j[$key] = pl_clean_html($val);
		}
		*/
		
		$safe_transfer_id = pl_clean_html($row['transfer_id']);
		$safe_date = pl_clean_html($row['created']);
		$z .= "<tr><td><a href=\"{$base_url}/transfers.php?transfer_id={$safe_transfer_id}\" class=\"btn\">";
		$z .= "Review</a></td><td>{$safe_transfer_id}</td>";
		$z .= "<td>{$j['client']['last_name']}</td>";
		$z .= "<td>{$j['client']['first_name']}</td>";
		$z .= "<td>{$j['client']['county']}</td>";
		$z .= "<td>{$j['client']['city']}</td>";
		$z .= "<td>{$j['client']['problem_code']}</td>";
		$z .= "<td>{$safe_date}</td></tr>";
	}
	
	$z .= "</tbody></table>";
}

else
{
	$safe_transfer_id = mysql_real_escape_string($transfer_id);
	$result = mysql_query("SELECT * FROM transfers WHERE transfer_id = '{$safe_transfer_id}'");
	$single_row = mysql_fetch_assoc($result);
	$safe_transfer_id = html_entity_decode($safe_transfer_id);
	
	$x = json_decode($single_row['json_data'], 1);
	$z .= "<h1>Incoming Transfer &#35;{$safe_transfer_id}</h1>";
	$z .= "<div class=\"row\">\n";
	$z .= "<div class=\"span4\">\n";
	$z .= "<h2>Client, Notes, and Case Info</h2>";
	$z .= "<table class=\"table\">";
	foreach ($x['client'] as $key => $value)
	{
		$z .= "<tr><td>$key</td><td>$value</td></tr>";
	}
	
	foreach ($x['notes'] as $key => $value)
	{
		$z .= "<tr><td>notes.$key</td><td>$value</td></tr>";
	}
	
	foreach ($x['case'] as $key => $value)
	{
		$z .= "<tr><td>$key</td><td>$value</td></tr>";
	}

	$z .= "</table>";
	$z .= "</div>\n";
  $z .= "<div class=\"span4\">\n";
	$z .= "<h2>Opposing Party</h2>";
	$z .= "<table class=\"table\">";
	
	foreach ($x['op'] as $key => $value)
	{
		$z .= "<tr><td>opposing_party.$key</td><td>$value</td></tr>";
	}
	
	$z .= "</table>";
	$z .= "</div>\n";
  $z .= "<div class=\"span4\">\n";
	$z .= "<h2>Opposing Party's Attorney</h2>";
	$z .= "<table class=\"table\">";
	
	foreach ($x['opa'] as $key => $value)
	{
		$z .= "<tr><td>opposing_party_attorney.$key</td><td>$value</td></tr>";
	}

	$z .= "</table>";
	$z .= "</div>\n";
	$z .= "</div>\n";
	
	
	$z .= "<div class=\"row\">\n";
	$z .= "<div class=\"span4\">\n";
	$z .= potential_conflicts($x['client'], 1, 'Client');
	$z .= "</div>\n";
	$z .= "<div class=\"span4\">\n";
	$z .= potential_conflicts($x['op'], 2, 'Opposing Party');
	$z .= "</div>\n";
	$z .= "<div class=\"span4\">\n";
	$z .= potential_conflicts($x['opa'], 3, 'Opposing Party\'s Attorney');
	$z .= "</div>\n";
	$z .= "</div>\n";

	$z .= "<div class=\"well\">\n";
	$z .= "<form method=\"POST\" action=\"{$base_url}/transfers.php\">";
	$z .= "<input type=\"hidden\" name=\"transfer_id\" value=\"{$safe_transfer_id}\">";
	$z .= "<input type=\"submit\" name=\"accept\" value=\"Accept\" class=\"btn btn-success\">&nbsp;";
	$z .= "<input type=\"submit\" name=\"reject\" value=\"Reject\" class=\"btn\"></form>";
	$z .= "</div>\n";
}

$plTemplate["content"] = '<div id="page_content" class="container">' . $z . '</div>';
$plTemplate["page_title"] = "Incoming Case Transfers";
$plTemplate['nav'] = "<a href=\"{$base_url}\">Pika Home</a>
						&gt; <a href=\"{$base_url}/site_map.php\">Site Map</a>
						&gt; About Pika";

$buffer = pl_template($plTemplate, 'templates/default.html');
pika_exit($buffer);

?>
