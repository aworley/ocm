<?php

$C .= "<h1>Outcomes Tracking</h1>\n";

if (strlen($case_row['problem']) < 1)
{
	$C .= "<div>Please assign a Problem Code to this case before recording outcomes.</div>\n";
	//$C .= pikaTempLib::plugin('lsc_problem','problem',$case_row,null,array('onchange=problem_code_lookup(this.value)'));
}

else
{
	// AMW - I think there are some single digit problem codes floating around.
	// This is a workaround for them.
	$problem_code = str_pad($case_row['problem'], 2, "0", STR_PAD_LEFT);
	$problem_category = substr($problem_code, 0, 1) . 'X';
	$problem_code = DB::escapeString($problem_code);
	$problem_category = DB::escapeString($problem_category);
	$sql = "select outcome_goal_id, goal, NULL as result 
			from outcome_goals 
			where problem in ('{$problem_category}', '{$problem_code}') AND 
			active = 1
			order by problem ASC, outcome_goal_order ASC";
	$result = DB::query($sql) or trigger_error(DB::error());
	$x = array();
	
	while ($row = DBResult::fetchRow($result))
	{
		$x[$row['outcome_goal_id']] = $row;
	}
	
	$sql = "select b.outcome_goal_id, a.goal, b.result 
			from outcomes as b 
			left join outcome_goals as a using (outcome_goal_id) 
			where case_id = {$case_row['case_id']}";
	$result = DB::query($sql) or trigger_error(DB::error());
	
	while ($row = DBResult::fetchRow($result))
	{
		$x[$row['outcome_goal_id']] = $row;
	}
	
	$C .= "<form action=\"{$base_url}/ops/update_case.php\" method=\"POST\" name=\"ws\">";
	$C .= "<table class=\"table table-striped\">\n";
	$i = 0;
	
	foreach ($x as $row)
	{
		$yes_checked = "";
		$no_checked = "";
		$na_checked = "";

		if (pl_settings_get('multi_outcomes'))
		{
			if ("1" == $row['result'])
			{
				$yes_checked = " checked";
			}
			
			else if ("0" == $row['result'])
			{
				$no_checked = " checked";
			}

			else if ("2" == $row['result'])
			{
				$na_checked = " checked";
			}
			
			
			$C .= "<tr>\n";
			$C .= "<td>
			<label class=\"radio inline\">
			<input type=\"radio\" name=\"outcomes[{$row['outcome_goal_id']}]\" id=\"optionsRadios{$i}\" value=\"1\"{$yes_checked}>
			Yes</label>
			<label class=\"radio inline\">
			<input type=\"radio\" name=\"outcomes[{$row['outcome_goal_id']}]\" id=\"optionsRadios{$i}\" value=\"0\"{$no_checked}>
			No</label>
			<label class=\"radio inline\">
			<input type=\"radio\" name=\"outcomes[{$row['outcome_goal_id']}]\" id=\"optionsRadios{$i}\" value=\"2\"{$na_checked}>
			N/A</label>
			</td>\n";
			$C .= "<td>{$row['goal']}</td>\n";
			$C .= "</tr>\n";
		}
		
		else
		{
			if ($row['result'] == 1)
			{
				$checked = " checked";
			}
			
			else
			{
				$checked = "";
			}
			
			$C .= "<tr>\n";
			$C .= "<td>
			<label class=\"radio inline\">
			<input type=\"radio\" name=\"single_outcome\" id=\"optionsRadios{$i}\" value=\"{$row['outcome_goal_id']}\"{$checked}>
			{$row['goal']}</label>
			</td>\n";
			$C .= "<td></td>\n";
			$C .= "</tr>\n";
		}

		$i++;
	}

	if ($i == 0)
	{
		$C .= "<tr>\n";
		$C .= "<td colspan=\"2\">No goals exist for problem code {$problem_code}.
				<a href=\"{$base_url}/system-outcomes.php\">Add some here.</a></td>\n";
		$C .= "</tr>\n";

	}

	$C .= "<tr>\n";
	$C .= "<td><textarea rows=\"5\" class=\"span3\" name=\"outcome_notes\"
			tabindex=\"1\">{$case_row['outcome_notes']}</textarea></td>\n";
	$C .= "<td>What other significant outcome?</td>\n";
	$C .= "</tr>\n";

	$C .= "</table>\n";

	$C .= "<table class=\"table table-striped\">\n";

	function draw_outcome_row($label, $column)
	{
		global $case_row;
		$C = '';
		$C .= "<tr>\n";
		$C .= "<td>{$label}</td>
		<td><div class=\"input-prepend input-append\">
		<span class=\"add-on\">$</span>
		<input class=\"span2\" id=\"{$column}\" name=\"{$column}\" value=\"{$case_row[$column]}\" type=\"text\" tabindex=\"1\">
		</div></td>\n";
		$C .= "</tr>\n";
		return $C;
	}

	if (true == pl_settings_get('ca_iolta_outcomes'))
	{
		$C .= draw_outcome_row('Back Awards and Lump-Sum Settlement.', 'ca_outcome_amount_obtained');
		$C .= draw_outcome_row('Monthly Benefits Obtained - Do not Annualize.', 'ca_outcome_monthly_obtained');
		$C .= draw_outcome_row('Reduction or Elimination of Claimed Amounts.', 'ca_outcome_amount_reduced');
		$C .= draw_outcome_row('Monthly Cost Savings and Payment Reductions.', 'ca_outcome_monthly_reduced');
	}

	else
	{
		$C .= "<tr><td colspan=\"2\">If income was an issue, answer these two questions</td></tr>\n";
		$C .= draw_outcome_row('Actual monthly income at the time the case was closed.', 'outcome_income_after_service');
		$C .= draw_outcome_row('If Legal Aid had not been involved, what would current monthly income be at the time case was closed?', 'outcome_income_no_service');

		$C .= "</table>\n";


		$C .= "<table class=\"table table-striped\">\n";
		$C .= "<tr><td colspan=\"2\">If assets were an issue, answer these two questions</td></tr>\n";

		$C .= draw_outcome_row('Actual value of assets at the time the case was closed.', 'outcome_assets_after_service');
		$C .= draw_outcome_row('If Legal Aid had not been involved, what would the value of assets be at the time case was closed?', 'outcome_assets_no_service');

		$C .= "</table>\n";


		$C .= "<table class=\"table table-striped\">\n";
		$C .= "<tr><td colspan=\"2\">If debt was an issue, answer these two questions</td></tr>\n";

		$C .= draw_outcome_row('Actual amount of debt at the time the case was closed.', 'outcome_debt_after_service');
		$C .= draw_outcome_row('If Legal Aid had not been involved, what would the amount of debt be at the time case was closed?', 'outcome_debt_no_service');
	}

	$C .= "</table>\n";
	$C .= "<input type=\"hidden\" name=\"case_id\" value=\"{$case_row['case_id']}\">\n";
	$C .= "<input type=\"hidden\" name=\"screen\" value=\"outcomes\">\n";
	$C .= "<input type=\"submit\" value=\"Save\" tabindex=\"1\" class=\"save\" accesskey=\"s\"></form>\n";
}

?>
