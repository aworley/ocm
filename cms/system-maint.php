<?php

/************************************/
/* Pika CMS (C) 2008 Aaron Worley   */
/* http://pikasoftware.com          */
/************************************/

require_once('pika-danio.php');
pika_init();

require_once('pikaTempLib.php');
require_once('plFlexList.php');
require_once('pikaCounter.php');


$action = pl_grab_post('action');
$base_url = pl_settings_get('base_url');

$main_html = array();
$main_html['content'] = '';

if (!pika_authorize("system", array()))
{
	$temp["content"] = "Access denied";
	$temp["nav"] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"site_map.php\">Site Map</a> &gt;
					 System Maintenance";

	$default_template = new pikaTempLib('templates/default.html',$temp);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

switch ($action) {
	case 'calc_age':
		set_time_limit(0);
		$start_time = time();
		$sql = "SELECT case_id, birth_date, open_date
			FROM contacts 
			LEFT JOIN cases ON cases.client_id = contacts.contact_id
			WHERE 1 
			AND birth_date IS NOT NULL 
			AND birth_date <> '0000-00-00'
			AND open_date IS NOT NULL
			AND open_date <> '0000-00-00'
			AND client_age IS NULL";
		//$sql .= " LIMIT 1000";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " ERROR: " . DB::error());
		$a['num_found'] = DBResult::numRows($result);
		$a['num_updated'] = 0;
		while($row = DBResult::fetchRow($result)) {
			if(function_exists('date_parse')) {
				$dob_array = date_parse($row['birth_date']);
			} else {
				$dob_array = getdate(strtotime($row['birth_date']));
				$dob_array['month'] = $dob_array['mon'];
				$dob_array['day'] = $dob_array['mday'];
			}
			if(function_exists('date_parse')) {
				$open_array = date_parse($row['open_date']);
			} else {
				$open_array = getdate(strtotime($row['open_date']));
				$open_array['month'] = $open_array['mon'];
				$open_array['day'] = $open_array['mday'];
			}

			$years = $open_array['year'] - $dob_array['year'];
			if($years > 0) {
				if($dob_array['month'] > $open_array['month']) {$years = $years - 1;}
				elseif ($dob_array['month'] == $open_array['month'] && $dob_array['day'] > $open_array['day']) {
					$years = $years - 1;
				}
			}
			$sql = "UPDATE LOW_PRIORITY cases
					SET client_age = '{$years}' 
					WHERE 1
					AND case_id = '{$row['case_id']}'
					LIMIT 1;";
			DB::query($sql) or trigger_error("SQL: " . $sql . " ERROR: " . DB::error());
			$a['num_updated']++;
		}
		$a['duration'] = time() - $start_time;
		$template = new pikaTempLib('subtemplates/system-maint.html',$a,'calc_age');
		$main_html['content'] .= $template->draw();
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
					 <a href=\"{$base_url}/system-maint.php\">System Maintenance</a> &gt;
					 Calculate Age";
		break;
	case 'metaphone':
		require_once('pikaAlias.php');
		require_once('pikaContact.php');
		
		set_time_limit(0);
		$start_time = time();
		$sql = "SELECT alias_id FROM aliases";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " ERROR: " . DB::error());
		$a['num_found'] = DBResult::numRows($result);
		$a['num_updated'] = 0;
		
		while ($row = DBResult::fetchRow($result))
		{
						$x = new pikaAlias($row['alias_id']);
						$x->genMetaphone();
						$x->save();
						$a['num_updated']++;
		}
		
		$sql = "SELECT contact_id FROM contacts";
		$result = DB::query($sql) or trigger_error("SQL: " . $sql . " ERROR: " . DB::error());
		$a['num_found'] += DBResult::numRows($result);
		
		while ($row = DBResult::fetchRow($result))
		{
						$x = new pikaContact($row['contact_id']);
						$x->genMetaphone();
						$x->save();
						$a['num_updated']++;
		}
		
		$a['duration'] = time() - $start_time;
		$template = new pikaTempLib('subtemplates/system-maint.html',$a,'metaphone');
		$main_html['content'] .= $template->draw();
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
					 <a href=\"{$base_url}/system-maint.php\">System Maintenance</a> &gt;
					 Populate Metaphone";
		break;
		
	case 'ssn_truncate':
		set_time_limit(0);
		$result = DB::query("UPDATE contacts SET ssn = RIGHT(ssn, 4)") or trigger_error(DB::error());
		$result = DB::query("ALTER TABLE contacts MODIFY ssn CHAR(4)") or trigger_error(DB::error());
		$result = DB::query("UPDATE aliases SET ssn = RIGHT(ssn, 4)") or trigger_error(DB::error());
		$result = DB::query("ALTER TABLE aliases MODIFY ssn CHAR(4)") or trigger_error(DB::error());
		$main_html['content'] .= 'SSNs have been truncated to the last 4 digits.';
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
					 <a href=\"{$base_url}/system-maint.php\">System Maintenance</a> &gt;
					 Populate Metaphone";
		break;
		
	case 'ssn_remove':
		set_time_limit(0);
		$result = DB::query("UPDATE contacts SET ssn = NULL") or trigger_error(DB::error());
		$result = DB::query("ALTER TABLE contacts MODIFY ssn char(0)") or trigger_error(DB::error());
		$result = DB::query("UPDATE aliases SET ssn = NULL") or trigger_error(DB::error());
		$result = DB::query("ALTER TABLE aliases MODIFY ssn char(0)") or trigger_error(DB::error());
		$main_html['content'] .= 'SSNs have been removed from the data.';
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
					 <a href=\"{$base_url}/system-maint.php\">System Maintenance</a> &gt;
					 Populate Metaphone";
		break;
		
	default:
		$template = new pikaTempLib('subtemplates/system-maint.html',array());
		$main_html['content'] .= $template->draw();
		
		$disabled = '';
		$not_available = '';
		
		if (pika_ssn_mode() != 9)
		{
			$disabled = ' disabled';
			$not_available = '<p>The SSN Truncate function is not available because this database does not have a full length, 11-character SSN field.</p>';
		}
		
		if (!pl_mysql_column_exists('aliases', 'keywords'))
		{
			$disabled = ' disabled';
			$not_available = '<p>The SSN Truncate function is not available because this database does not have the weighted name search installed.</p>';
		}
		
		$main_html['content'] .= '<form action="' . $base_url . '/system-maint.php" method="POST">
		<h2>Truncate SSNs</h2>';
		$main_html['content'] .= $not_available . 
		'<input type="submit" name="submit" value="Truncate SSNs" onclick="if ( confirm(\'Are you sure you want to shorten all SSNs to the last four digits?  This operation can not be undone.\')) { return confirm(\'Click OK to truncate all SSNs.\'); } else {return false;}"';
		$main_html['content'] .= $disabled . '>
		<input type="hidden" name="action" value="ssn_truncate">
		</form>';

		$disabled = '';
		$not_available = '';
		
		if (pika_ssn_mode() == 0)
		{
			$disabled = ' disabled';
			$not_available = '<p>The SSN Delete function is not available because the SSNs in this database have already been deleted.</p>';
		}

		$main_html['content'] .= '<form action="' . $base_url . '/system-maint.php" method="POST">
		<h2>Remove SSNs</h2>';
		$main_html['content'] .= $not_available . 
		'<input type="submit" name="submit" value="Remove SSNs" onclick="if ( confirm(\'Are you sure you want to remove all SSNs?  This operation can not be undone.\')) { return confirm(\'Click OK to delete all SSNs.\'); } else {return false;}"';
		$main_html['content'] .= $disabled . '>
		<input type="hidden" name="action" value="ssn_remove">
		</form>';
		
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
					 System Maintenance";

		break;

}






// Display a screen
$main_html['page_title'] = "System Maintenance";

$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();

pika_exit($buffer);

?>
