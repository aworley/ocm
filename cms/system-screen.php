<?php

/***********************************/
/* Pika CMS (C) 2019 Pika Software */
/* https://pikasoftware.com         */
/***********************************/

require_once('pika-danio.php');
pika_init();
require_once('pikaTempLib.php');
require_once('pikaScreen.php');
require_once('pikaUdf.php');

$base_url = pl_settings_get('base_url');
$main_html = array();

if (!pika_authorize("system", array()))
{
	$main_html['content'] = "Access denied";
	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
						 <a href=\"{$base_url}site_map.php\">Site Map</a> &gt;
						 Menus";

	$default_template = new pikaTempLib('templates/default.html',$main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$action = pl_grab_post('action');
$screen_name = pl_grab_post('screen_name');
$screen_id = pl_grab_post('screen_id');
$edit = pl_grab_get('edit');

switch ($action)
{
	case 'add':
		$x = new pikaScreen();
		$x->screen_name = DB::escapeString($screen_name);
		$x->save();

		header("Location: {$base_url}/system-screen.php");
		exit();

	case 'save':
		$screen_fields = pl_grab_post('screen_fields');

		if (pl_grab_post('submit_button') == 'Add')
		{
			$label = pl_grab_post('label');
			// Remove anything not alpha-numeric or a space.
			$label = preg_replace("/[^[:alnum:][:space:]]/u", '', $label);
			// Replace spaces with underscores.
			$label = str_replace(' ', '_', $label);

			$y = new pikaUdf();
			$y->label = $label;
			$y->data_type = pl_grab_post('data_type');
			$y->table_name = 'cases';
			$y->save();

			if (strlen($screen_fields) > 0)
			{
				$screen_fields .= ",";
			}

			$screen_fields .= $label;
		}

		$x = new pikaScreen($screen_id);
		$x->screen_name = pl_grab_post('screen_name');
		$x->screen_fields = $screen_fields;
		$x->save();

		header("Location: {$base_url}/system-screen.php?edit={$screen_id}");
		exit();
		break;
}

if (strlen($edit) > 0)
{
	$s = new pikaScreen($edit);
	$screen_fields_delim = $s->screen_fields;
	if (strlen($screen_fields_delim) > 0) {
		$existing_fields = explode(',', $screen_fields_delim);
	}

	else
	{
		$existing_fields = array();
	}

	$main_html['content'] = '';
	$main_html['content'] .= '<h3>Screen Editor</h3>';
	$main_html['content'] .= '<div class="row"><div class="span4"><h4>Case Tab Properties</h4>';
	$main_html['content'] .= <<<EOF
		<form action="system-screen.php" method="POST">
		<input type="hidden" name="action" value="save">
		<input type="hidden" name="screen_id" value="{$edit}">
		Case Tab Name:<input type="text" name="screen_name" value="{$s->screen_name}">
		<input type="hidden" id="screen_fields" name="screen_fields" value="{$screen_fields_delim}">
		<input type="submit">
EOF;

	$main_html['content'] .= '<p>List of fields</p><div id="leftbox"><ul id="example2Left">';
	

	foreach ($existing_fields as $x)
	{
		$main_html['content'] .= '<li data-id="' . $x . '"><div class="btn">'. $x . '</div></li>';
	}

	$main_html['content'] .= '</ul></div></div>';

	$main_html['content'] .= <<<EOF
		<div class="span4">
		<h4>Add New Field</h4>
		Name of new field:<br>
		<input type="text" name="label" value="">
		<div>
		<label for="cb">
		<input type="radio" id="cb" name="data_type" value="cb" checked>
  		Checkbox</label>
  		</div>
  		<div>
  		<label for="t">
  		<input type="radio" id="t" name="data_type" value="t">
  		Text Field</label>
  		</div>
  		<div>
  		<label for="n">
  		<input type="radio" id="n" name="data_type" value="n">
  		Notes Field</label>
  		</div>
		<br><input type="submit" name="submit_button" value="Add">
		</form></div>
EOF;

	$main_html['content'] .= '';

	/*
	$cases_fields = array();
	$result = DB::query("DESCRIBE cases");

	while($row = DBResult::fetchRow($result))
	{
		$cases_fields[] = $row['Field'];
	}

	$udf_names = array('Key 0', 'Key 1', 'Key 2', 'Key 3');

	$cases_fields = array_merge($cases_fields, $udf_names);
	*/
	$cases_fields = pikaUdf::getAll('cases');

	$main_html['content'] .= '<div class="span4"><h4>Available Existing Fields</h4><div id="rightbox"><ul id="example2Right" class="col">';

	foreach ($cases_fields as $x)
	{
		if (!in_array($x['label'], $existing_fields))
		{
			$main_html['content'] .= '<li data-id="' . $x['label'] . '"><div class="btn">' . $x['label'] . '</div></li>';
		}
	}
	$main_html['content'] .= '</ul></div></div>';
	$main_html['content'] .= '';
	$main_html['content'] .= '';
	$main_html['content'] .= <<<EOF
			<script src="js/Sortable.min.js"></script>
			<script>
				new Sortable(example2Left, {
					group: 'shared', // set both lists to same group
					animation: 150,
					onSort: function(event, ui) {
						var sorted = this.toArray();
						document.getElementById('screen_fields').value = sorted;
						}
				});
				
				new Sortable(example2Right, {
					group: 'shared',
				animation: 150
				
				});
				
			</script>
EOF;

	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 <a href=\"{$base_url}/system-screen.php\">Screen Editor</a> &gt; {$s->screen_name}";
}

else
{

	$main_html['content'] = '';
	$main_html['content'] .= '<h3>Screen Editor</h3>';
	$screen_list = pikaScreen::getScreens();

	if (count($screen_list) > 0)
	{
		$main_html['content'] .= '<p>Click on a case tab screen to make changes.</p>';
	}

	foreach ($screen_list as $screen_id => $screen_name)
	{
		$main_html['content'] .= "<a href='system-screen.php?edit={$screen_id}' class='btn btn-large btn-success'>{$screen_name}</a><br><br>\n";
	}

	$main_html['content'] .= '</p><form action="system-screen.php" method="POST"><input type="hidden" name="action" value="add	">Add a new case tab screen named:<br><input type="text" name="screen_name"><br><input type="submit" value="Add Screen"> </form>';

	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 Screen Editor";
}

$main_html['rss'] = '<link href="css/system-screen.css" rel="stylesheet">';
$main_html['page_title'] = 'Screen Editor';
$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>