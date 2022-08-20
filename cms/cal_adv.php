<?php 

/***********************************/
/* Pika CMS (C) 2010 Pika Software */
/* http://pikasoftware.com         */
/***********************************/
// 2012 point release modification to protect against invalid UNIX time stamp values in act_date and blank time entered  preventing succcessful link to activity record for user

require_once('pika-danio.php');
pika_init();
require_once('pikaActivity.php');
require_once('pikaMisc.php');
require_once('pikaTempLib.php');
require_once('plFlexList.php');


$base_url = pl_settings_get('base_url');

// report form variables
$action = pl_grab_get('action');
$contact_id = pl_grab_get('contact_id');
$user_list = pl_grab_get('user_list');
$pba_list = pl_grab_get('pba_list');

$cal_date = pl_grab_get('cal_date');
$user_id = pl_grab_get('user_id');

$start_date = pl_grab_get('start_date');
$end_date = pl_grab_get('end_date');
$funding = pl_grab_get('funding');
$row_limit = pl_grab_get('row_limit',100);
$act_type = pl_grab_get('act_type');
$office = pl_grab_get('office');
$sort_order = pl_grab_get('sort_order');
$completed = pl_grab_get('completed');
$case_number = pl_grab_get('number');
$category = pl_grab_get('category');

$menu_act_type = pl_menu_get('act_type');
$menu_yes_no = pl_menu_get('yes_no');
$staff_array = pikaMisc::fetchStaffArray();
$pba_array = pikaMisc::fetchPbAttorneyArray();
$menu_order = array('DESC' => 'Newest to Oldest','ASC' => 'Oldest to Newest');
$menu_limit = array('100'=>'100','500'=>'500','1000'=>'1000');


if (sizeof($user_list) == 0 && sizeof($pba_list) == 0){
	$user_list[] = $auth_row['user_id'];
}



//$tpl['cal_date'] = $cal_date;
//$tpl['user_id'] = $user_id;





// START query results table


$prev_date = '';
$prev_user = '';

switch ($action) {
	case 'run_report':
		$filter['start_date'] = $start_date;
		$filter['end_date'] = $end_date;
		$filter['user_list'] = $user_list;
		$filter['pba_list'] = $pba_list;
		$filter['act_type'] = $act_type;
		$filter['office'] = $office;
		$filter['completed'] = $completed;
		$filter['number'] = $case_number;
		$filter['category'] = $category;
		$filter['funding'] = $funding;
		
		$result = pikaActivity::getActivitiesCaseClient($filter, $dummy, 'date-user-time', $sort_order, 0, $row_limit);
		$a['time_list'] = "";
		if(DBResult::numRows($result) < 1) {
			$a['time_list'] .= "No records found.";
		}
		
		$cal_array = array();
		while($row = DBResult::fetchRow($result)) {
			//$cal_array[$row['act_date']][$row['user_id']][] = $row;	
			if(is_numeric($row['user_id']))
			{
				$owner = pl_array_lookup($row['user_id'],$staff_array);
				$cal_array[$row['act_date']]["Staff: $owner"][] = $row;
			}
			elseif(is_numeric($row['pba_id']))
			{
				$owner = pl_array_lookup($row['pba_id'],$pba_array);
				$cal_array[$row['act_date']]["Pro Bono Attorney: $owner"][] = $row;
			}
		}
		
		$count = 1;
		foreach ($cal_array as $tmp_act_date => $date_array) 
		{
			// 2012 point release modification
			// protect against invalid UNIX time stamp value
			if(strtotime($tmp_act_date))
			{
				$tmp_act_date = date('l, F j Y', strtotime($tmp_act_date));
			}
			else
			{
				$tmp_act_date = "Warning - Date entered on Activity may be incorrect (".$tmp_act_date.")";
			}
			$a['time_list'] .= "\n<h2>{$tmp_act_date}</h2>\n";
			
			foreach ($date_array as $owner => $user_array) 
			{
				$a['time_list'] .= "\n{$owner}<br/>\n<blockquote>\n";
				$user_time_list = new plFlexList();
				$user_time_list->template_file = 'subtemplates/cal_adv.html';
				$total_user_hours = 0;
				foreach ($user_array as $user_row) 
				{
					$user_row['count'] = $count++;
					$user_row['act_type'] = pl_array_lookup($user_row['act_type'], $menu_act_type);
					// 2012 point release - if time not entered insert something so user can link to record
					if($user_row['act_time'])
					{
						$time_label = pl_time_unmogrify($user_row['act_time']);
					}
					else
					{
						$time_label = "Time Not Entered";
					}	
					// end of 2012 point release addition
					if ($user_row['act_end_time']) 
					{
						$time_label .= " - " . pl_time_unmogrify($user_row['act_end_time']);
					}
					$user_row['time'] = $time_label;
					$total_user_hours += $user_row['hours'];
					$user_time_list->addRow($user_row);
				}
				$a['time_list'] .= $user_time_list->draw();
				$a['time_list'] .= "<p>Total Hours: <strong>{$total_user_hours}</strong></p></blockquote>\n";
			}
		}
			
		
		
	
	
	default:
		
		$a['start_date'] = $start_date;
		$a['end_date'] = $end_date;
		$a['user_list'] = array();
		$a['pba_list'] = array();
		//$tpl['cal_tabs'] = pika_calendar_tabs('adv', date('Y-m-d'), $auth_row['user_id']);
		$a['cal_tabs'] = pikaTempLib::plugin('calendar_tabs','adv', array('cal_date' => $cal_date, 'user_id' => $user_id));
		$a['row_limit'] = $row_limit;
		$a['act_type'] = $act_type;
		$a['sort_order'] = 'ASC';
		if($sort_order == 'DESC') 
		{
			$a['sort_order'] = 'DESC';
		}
		$a['sort_order'] = $sort_order ? $sort_order : 'DESC';
		$a['completed'] = $completed;
		$a['number'] = $case_number;
		$a['office'] = $office;
		$a['category'] = $category;
		$a['user_list'] = $user_list;
		$a['pba_list'] = $pba_list;
		$a['row_limit'] = $row_limit;
		
		if(!$action) 
		{
			$a['time_list'] = "Please select search parameters from the form above and click on the View button.";
		}
		
		$template = new pikaTempLib('subtemplates/cal_adv.html',$a);
		$template->addMenu('sort_order',$menu_order);
		$template->addMenu('limit',$menu_limit);
		$main_html['content'] = $template->draw();
		break;
}




$main_html['page_title'] = "Calendar - Advanced View";
$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
					<a href=\"{$base_url}/cal_day.php\">Calendar</a> &gt; 
					Advanced View";

$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);

?>
