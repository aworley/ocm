<?php

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika_cms.php');


// initialize variables
$pk = new pikaCms;
$dummy = array();

if (!pika_authorize("system", $dummy))
{
	$plTemplate["content"] = "Permission denied";
	$plTemplate["page_title"] = "System Operations";
	$plTemplate["nav"] = "<a href=\".\" class=light>$pikaNavRootLabel</a> &gt; System Operations";

	pl_template($plTemplate, 'templates/default.html');
	echo pl_bench('results');
	exit();
}

// determine what, if any, action to perform

switch($_POST['action'])
{
	case 'save_menu':

	$i = 0;
	$menu = pl_grab_var('menu', '', 'POST');
	$menu_elements = explode("\n", pl_grab_var('values', '', 'POST'));
	$menu_array = array();

	// build the new, updated menu
	while (list($key, $val) = each($menu_elements))
	{
		$a = explode("|", $val);

		if (sizeof($a) < 2)
		{
			$a = explode("\t", $val);
		}

		$a[0] = addslashes(trim($a[0]));

		if (isset($a[1]))
		{
			$a[1] = addslashes(trim($a[1]));
		}

		else
		{
			// use the value as both key and label
			$a[1] = $a[0];
		}

		if ('' != $a[0] && '' != $a[1])
		{
			$menu_array[] = array('value' => $a[0], 'label' => $a[1]);
		}
	}

	pl_menu_set($menu, $menu_array);

	header("Location: system-menus.php?screen=edit&menu=$menu");
	exit();

	break;



	case 'old_array_save_menu':

	$menu = $_POST['menu'];

	$menu_elements = explode("\n", $_POST['values']);

	unset($plMenus[$menu]);

	while (list($key, $val) = each($menu_elements))
	{
		$a = explode("=>", $val);

		$a[0] = trim($a[0]);
		$a[1] = trim($a[1]);

		if ('' != $a[0] && '' != $a[1])
		{
			$plMenus[$menu][$a[0]] = $a[1];
		}
	}

	ksort($plMenus);

	pl_save_settings();

	header("Location: system-menus.php");
	exit();

	break;




	case 'add_menu':

	$menu = $_POST['menu'];

	if (isset($plMenus[$menu]))
	{
		die(pika_error_notice("Misc. Error", "There is already a Menu by that name"));
	}

	$menu_elements = explode("\n", $_POST['values']);

	while (list($key, $val) = each($menu_elements))
	{
		$a = explode("=>", $val);

		$a[0] = trim($a[0]);
		$a[1] = trim($a[1]);

		if ('' != $a[0] && '' != $a[1])
		{
			$plMenus[$menu][$a[0]] = $a[1];
		}
	}

	ksort($plMenus);

	pl_save_settings();

	header("Location: system-menus.php");
	exit();

	break;


	case 'save_settings':

	while (list($key, $val) = each($plSettings))
	{
		$plSettings[$key] = pl_grab_var($key, null, 'POST');
	}

	reset($plSettings);

	pl_settings_save() or die(pika_error_notice('Couldn\'t save settings', 'File permission denied'));

	reset($plSettings);

	header("Location: system-settings.php");
	exit();

	break;

	case 'disable_field';

	unset($plFields[$_POST['table']][$_POST['field']]);

	pl_save_settings();

	header("Location: system-tables.php");
	exit();

	break;


	/*
	case 'save_table':

	$table = $_POST['table'];

	// get list of existing db columns
	$result = pl_query("DESCRIBE $table");
	while ($row = $result->fetchRow())
	{
	$column_names[] = $row['Field'];
	}

	$table_elements = explode("\n", $_POST['values']);

	unset($plFields[$table]);

	while (list($key, $val) = each($table_elements))
	{
	$a = explode("=>", $val);

	$a[0] = trim($a[0]);
	$a[1] = trim($a[1]);

	if ('' != $a[0] && '' != $a[1])
	{
	$plFields[$table][$a[0]] = $a[1];

	// create the column if it doesn't exist
	if (!in_array($a[0], $column_names))
	{
	switch ($a[1])
	{
	case 'date':

	$field_type = "DATE";
	break;

	case 'time':

	$field_type = "TIME";
	break;

	case 'number':

	$field_type = "FLOAT";
	break;

	case 'text':
	default:

	$field_type = "VARCHAR(50)";
	break;
	}

	pl_query("ALTER TABLE $table ADD {$a[0]} $field_type");
	}

	}
	}

	// alphabetize the order of the field lists
	ksort($plFields);

	pl_save_settings();

	header("Location: system-tables.php");
	exit();

	break;
	*/


	case 'assign_default':

	$pikaDvs[$_POST['table']][$_POST['field']] = pl_input_filter($_POST['def_value'], $plFields[$_POST['table']][$_POST['field']]);

	pl_save_settings();

	header("Location: system-tables.php?screen=edit&table={$_POST['table']}");
	exit();

	break;


	case 'add_field':
	case 'assign_type':

	if (in_array($_POST['field_type'], array('text', 'number', 'boolean', 'date')))
	{
		$plFields[$_POST['table']][$_POST['field']] = $_POST['field_type'];

		pl_save_settings();
	}

	header("Location: system-tables.php?screen=edit&table={$_POST['table']}");
	exit();

	break;


	case 'add_group':

	$a = pl_grab_vars('groups');
	$pk->addGroup($a);

	header('Location: system-groups.php');

	break;


	case 'update_group':

	$a = pl_grab_vars('groups');
	$pk->updateGroup($a);

	header('Location: system-groups.php');

	break;



	// Questionnaire options by DTK
	case 'save_questionnaire':
	//	echo "<pre>";
	$title = $_POST['title'];

	$problem = $_POST['code'];

	$special_problem = $_POST['sp_code'];

	$questionnaire = $_POST['questionnaire'];

	$init = $_POST['init'];

	if ($special_problem) {
		$problem = substr($special_problem, 0, 2);
	}


	$questions = explode("\n", $_POST['values']);

	$lastrev = pl_last_rev_get($title);

	$respcheck = pl_qresp_check($questionnaire);

	$revision = $lastrev+1;

	$sql = "SELECT question_id, question_text FROM q_questions WHERE questionnaire_id=$questionnaire";
	//	echo $sql . "<br>";
	$results = DB::query($sql);

	while ($row = DBResult::fetchRow($results)) {
		$old_questions[$row['question_id']] = $row['question_text'];
	}

	if ($respcheck) {
		$q_sql  = "INSERT INTO q_questionnaires (title, problem, special_problem, revision) VALUES ";
		$q_sql .= "('$title', '$problem', '$special_problem', $revision)";
		DB::query($q_sql);
		$sql  = "SELECT questionnaire_id FROM q_questionnaires WHERE title='$title' ";
		$sql .= "AND problem='$problem' AND special_problem='$special_problem' ORDER BY revision DESC LIMIT 1";
		$results = DB::query($sql);

		while ($row = DBResult::fetchRow($results)) {
			$new_questionnaire_id = $row['questionnaire_id'];
		}

	} else {
		$q_sql  = "UPDATE q_questionnaires SET title='$title', problem='$problem', ";
		$q_sql .= "special_problem='$special_problem' WHERE questionnaire_id=$questionnaire";
		DB::query($q_sql);
		$new_questionnaire_id = $questionnaire;
	}
	//	echo "$q_sql\n";
	//	echo "$sql\n";


	//	print_r($old_questions);
	//	print_r($questions);

	if (!$respcheck) {
		$sql = "DELETE FROM q_questions WHERE questionnaire_id=$questionnaire";
		DB::query($sql);
		//		echo "$sql \n";
	}
	for($i = 0; $i < count($questions); $i++) {
		//		echo "Loop iteration number $i \n";
		$questions[$i] = trim($questions[$i]);
		if ($questions[$i]) {
			$oldid = '';
			$oldid = array_search($questions[$i],$old_questions);

			$sql  = "INSERT INTO q_questions (questionnaire_id, question_text, question_order) ";
			$sql .= "VALUES ($new_questionnaire_id, '$questions[$i]', $i+1)";
			//			echo "$sql\n";

			DB::query($sql);

			if ($oldid) {
				//				echo "Question number $i matches old ID number $oldid\n";
				$sql  = "SELECT question_id FROM q_questions WHERE questionnaire_id=$new_questionnaire_id ";
				$sql .= "AND question_text='$questions[$i]' AND question_order=($i+1) LIMIT 1";
				//				echo "$sql\n";

				$results = DB::query($sql);
				while ($row = DBResult::fetchRow($results)) {
					$new_question_id = $row['question_id'];
				}
				if ($i == (count($questions)-1)) {
					$next_question = 0;
				} else {
					$next_question = $new_question_id+1;
				}
				$sql  = "INSERT INTO q_answers (question_id, answer_text, next_question, answer_order) ";
				$sql .= "SELECT $new_question_id, answer_text, $next_question, answer_order FROM q_answers ";
				$sql .= "WHERE question_id=$oldid";
				//				echo "$sql\n";
				DB::query($sql);

			}
		}
	}
	if (!$respcheck) {
		foreach($old_questions as $key => $value) {
			$sql  = "DELETE FROM q_answers WHERE question_id=$key";
			DB::query($sql);
			//			echo "$sql\n";
		}
	}


	//	echo "Location: admin_quest_view.php?questionnaire_id=$new_questionnaire_id\n";
	header("Location: admin_quest_view.php?questionnaire_id=$new_questionnaire_id&init=true");
	//	echo "</pre>\n";
	//	phpinfo(INFO_VARIABLES);

	exit();

	break;



	case 'add_questionnaire':

	$title = $_POST['title'];

	$problem = $_POST['code'];

	$special_problem = $_POST['sp_code'];

	if ($special_problem) {
		$problem = substr($special_problem, 0, 2);
	}

	$questions = explode("\n", $_POST['values']);

	$titlecheck = pl_qtitle_check($title);
	if ($titlecheck) {
		die(pika_error_notice("Misc. Error", "There is already a questionnaire by that name"));
	} else {
		$q_sql  = "INSERT INTO q_questionnaires (title, problem, special_problem) VALUES ";
		$q_sql .= "('$title', '$problem', '$special_problem')";
		DB::query($q_sql);
	}

	$last_id_sql = "SELECT questionnaire_id FROM q_questionnaires WHERE title = '$title' ORDER BY revision DESC LIMIT 1";

	$results = DB::query($last_id_sql);
	while ($row = DBResult::fetchRow($results)) {
		$a = $row;
	}
	$last_id = $a['questionnaire_id'];

	//	echo "$last_id<br>$questions[0]";

	for($i = 0; $i < count($questions); $i++) {
		$questions[$i] = trim($questions[$i]);
		if ($questions[$i]) {
			$sql  = "INSERT INTO q_questions (questionnaire_id, question_text, question_order) ";
			$sql .= "VALUES ($last_id, '$questions[$i]', $i+1 )";
			DB::query($sql);
		}
	}



	header("Location: admin_quest_view.php?questionnaire_id=$last_id&init=true");
	exit();

	break;

	case 'toggle_questionnaires':

	$q = $_POST['q'];
	$todo = $_POST['todo'];
	//	echo "<pre>";
	//	print_r($q);
	//	echo "</pre>";

	if (stristr($todo, 'deactivate')) {
		$active = 0;
	} else {
		$active = 1;
	}

	while (list($key, $val) = each($q)) {
		$sql = "UPDATE q_questionnaires SET active=$active WHERE questionnaire_id=$key";
		DB::query($sql);
		//		echo "<br>$sql\n";
	}
	header("Location: questionnaires.php");

	exit();

	break;



	case 'diag':

	$resp = $_POST['resp'];
	$completed_id = $_POST['completed_id'];
	echo "<pre>";
	print_r($resp);
	echo "</pre>";

	if ($completed_id)

	exit();

	break;



	case "update_answers":
	//	echo "<pre>\n";
	$questionnaire_id = $_POST['questionnaire_id'];
	$answers = $_POST['answers'];
	$init = $_POST['init'];
	$last_item_array = $answers;
	krsort($last_item_array);
	$final_question = key($last_item_array);
	//	echo "Last key is " . key($last_item_array) . "<br>";
	foreach ($answers as $key=>$value) 
	{
		$question_id = $key;
		//		echo "<br>$key - $value<br>\n";
		$indiv_answers = explode("\n",$value);
		$answer_order = 1;
		$delsql = "DELETE FROM q_answers WHERE question_id=$question_id";
		//		echo $delsql . "<br>\n";
		DB::query($delsql);
		for ($i = 0; $i < count($indiv_answers); $i++) 
		{
			$jump_array = explode(' | ',$indiv_answers[$i]);
			$jumpto = trim($jump_array[1]);
			if ($jumpto) {
				$next_sql  = "SELECT * FROM q_questions WHERE questionnaire_id=$questionnaire_id AND ";
				$next_sql .= "question_order=$jumpto ORDER BY question_id DESC LIMIT 1";
				//				echo "$next_sql<br>\n";
				$results = DB::query($next_sql);
				while ($row = DBResult::fetchRow($results)) {
					$next_question = $row['question_id'];
				}
			} else {
				$next_question = 0;
			}
			$answer_text = trim($jump_array[0]);
			if ($answer_text) {
				if (!$next_question && !$init) {
					$next_question = 0;
				}

				if (!$next_question && $init) {
					$next_question = $key+1;
				}
				if ($question_id == $final_question) {
					$next_question = 0;
				}
				$sql  = "INSERT INTO q_answers (question_id, answer_text, next_question, answer_order) ";
				$sql .= "VALUES ($question_id, '$answer_text', $next_question, $answer_order)";
				//				echo $sql . "<br>\n";
				DB::query($sql);
				$answer_order++;
				if ($i > 0) {
					$question_type = "radio";
				} else {
					$question_type = "text";
				}
				$next_question = '';
			}
		}
		//		echo "Question Type = $question_type<br>";
		$quest_sql = "UPDATE q_questions SET question_type='$question_type' WHERE question_id=$question_id";
		//		echo "$quest_sql<br>";
		DB::query($quest_sql);
	}
	header("Location: admin_quest_view.php?questionnaire_id=$questionnaire_id");
	//	echo "</pre>\n";
	//	phpinfo(INFO_VARIABLES);
	exit();
	break;


	default:

	die(pika_error_notice("$window_title", "Error:  invalid action was specified."));

	break;
}

// end of 'action' section

exit();

?>
