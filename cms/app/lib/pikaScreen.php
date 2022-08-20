<?php

require_once('pikaUdf.php');

class pikaScreen extends plBase
{
	function __construct($screen_id = null)
	{
		$this->db_table = 'screens';
		$this->db_table_id_column = 'screen_id';
		parent::__construct($screen_id);
	}

	public static function exists($screen_id)
	{
		$clean_screen_id = DB::escapeString($screen_id);
		$result = DB::query("SELECT 1 FROM screens WHERE screen_id = '{$clean_screen_id}'");
		return DBResult::numRows($result);
	}

	public static function getScreens()
	{
		$s = array();
		$result = DB::query("SELECT screen_id, screen_name FROM screens ORDER BY screen_name ASC");

		while ($row = DBResult::fetchRow($result))
		{
			$s[$row['screen_id']] = $row['screen_name'];
		}

		return $s;
	}

	public function htmlForm($a)
	{
		$udfs = pikaUdf::getByTable('cases');
		$buffer = '';

		$buffer .= '<form action="%%[base_url]%%/ops/update_case.php" method="post" name="ws">';

		if (strlen($this->screen_fields) == 0)
		{
			return "<p>This screen does not have any fields yet.</p>";
		}

		$fields =  explode(',', $this->screen_fields);

		foreach ($fields as $field_name)
		{
			$buffer .= str_replace("_", " ", $field_name) . ":<br>\n";

			switch ($udfs[$field_name])
			{
				case 'cb':
					$buffer .= pl_html_checkbox($field_name, $a[$field_name]) . "<br>";
					break;

				case 't':
					$buffer .= "<input type=\"text\" id=\"{$field_name}\" name=\"{$field_name}\" value=\"" . $a[$field_name] . "\" tabindex=\"1\">";
					break;

				case 'n':
					$buffer .= "<textarea id=\"{$field_name}\" name=\"{$field_name}\" tabindex=\"1\">" . $a[$field_name] . "</textarea>";
					break;

				default:
					$buffer .= $field_name;
			}

			$buffer .= "<br>\n";
		}

		$buffer .= '<input type="hidden" name="screen" value="' . $this->screen_id . '">';
		$buffer .= '<input type="hidden" name="case_id" value="' . $a['case_id'] . '">';
		$buffer .= '<input type=submit name="update_case" value="Save" tabindex=1 class="btn" accesskey="s">';

		return $buffer;
	}
}