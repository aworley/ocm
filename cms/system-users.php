<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/


require_once('pika-danio.php');

pika_init();

require_once('pikaTempLib.php');
require_once('plFlexList.php');
require_once('pikaUser.php');
require_once('pikaUserSession.php');
require_once('pikaGroup.php');

// Menus

$menu_pass_length = array(	'6' => '6',
							'7' => '7',
							'8' => '8',
							'9' => '9',
							'10' => '10');
$menu_pass_method = array(	'1' => "Lowercase Letters &amp Numbers",
							'2' => "All Letters, Numbers",
							'3' => "All Characters");

// Variables

$action = pl_grab_var('action');
$user_id = pl_grab_var('user_id');
$order = pl_grab_get('order');
$order_field = pl_grab_get('order_field');
$offset = pl_grab_get('offset');
$page_size = $_SESSION['paging'];

$filter = array();
$filter['enabled'] = $enabled = pl_grab_get('enabled');
$filter['last_name'] = $last_name = pl_grab_get('last_name');
$filter['first_name'] = $first_name = pl_grab_get('first_name');
$filter['attorney'] = $attorney = pl_grab_get('attorney');
$filter['firm'] = $firm = pl_grab_get('firm');
$filter['city'] = $city = pl_grab_get('city');
$filter['county'] = $county = pl_grab_get('county');
$filter['group_id'] = $group_id = pl_grab_get('group_id');

$timeout_value = date('U') - (3600 * 48000000);  // Replaces PL_AUTH_TIMEOUT constant
$main_html = array();
$a = array();

$menu_enabled = array('0' => 'Disabled', '1' => 'Enabled');

$base_url = pl_settings_get('base_url');



if (!pika_authorize('users', $a))
{
	$main_html['page_title'] = 'User Accounts';
	$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt; 
						<a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
						User Accounts";
	$main_html['content'] = 'Access denied';
	
	$default_template = new pikaTempLib('templates/default.html', $main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$result = pikaGroup::getGroupsDB();
$groups = array();
while ($row = DBResult::fetchRow($result)) {
	$groups[$row['group_id']] = $row['group_id'];
}

switch ($action)
{
	case 'edit':
	
		if ($user_id)
		{
			$user = new pikaUser($user_id);
			$a = $user->getValues();
			unset($a['password']);
		}
		
		else
		{
			$a = array();
		}
		
		$a['p_len'] = '10';
		$a['p_method'] = '1';
		
		$result = pikaUserSession::getSessions(array('user_id' => $user_id),$row_count,'last_updated','DESC',0,1);
	 	$a['last_addr'] = "Never logged in";
	 	$a['last_active'] = "Never logged in";
	 	
		if(DBResult::numRows($result) == 1)
		{
	 		$row = DBResult::fetchRow($result);
			$a['last_addr'] = $row['ip_address'];
			$a['last_active'] = date('n/d/Y g:i A', $row['last_updated']);
		}
		
		$template = new pikaTempLib('subtemplates/system-users.html', $a, 'edit_user');
		$template->addMenu('groups',$groups);
		$template->addMenu('p_len',$menu_pass_length);
		$template->addMenu('p_method',$menu_pass_method);
		$main_html['content'] = $template->draw();
		$name = pikaTempLib::plugin('text_name','name',$a,array(),array("nomiddle","noextra"));
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 <a href=\"{$base_url}/system-users.php\">User Accounts</a> &gt;
							 {$name}";

		break;
		
	case 'update':
		
		$a['username'] = pl_grab_post('username');
		$password = pl_grab_post('password');
		if(strlen($password) > 0) {
			$a['password'] = md5($password);
		}
		$a['first_name'] = pl_grab_post('first_name');
		$a['middle_name'] = pl_grab_post('middle_name');
		$a['last_name'] = pl_grab_post('last_name');
		$a['extra_name'] = pl_grab_post('extra_name');
		$a['enabled'] = pl_grab_post('enabled');
		$a['group_id'] = pl_grab_post('group_id');
		$a['description'] = pl_grab_post('description');
		$a['email'] = pl_grab_post('email');
		$a['attorney'] = pl_grab_post('attorney');
		$a['atty_id'] = pl_grab_post('atty_id');
		$a['firm'] = pl_grab_post('firm');
		$a['address'] = pl_grab_post('address');
		$a['address2'] = pl_grab_post('address2');
		$a['city'] = pl_grab_post('city');
		$a['state'] = pl_grab_post('state');
		$a['zip'] = pl_grab_post('zip');
		$a['county'] = pl_grab_post('county');
		$a['phone_notes'] = pl_grab_post('phone_notes');
		$a['languages'] = pl_grab_post('languages');
		$a['practice_areas'] = pl_grab_post('practice_areas');
		$a['notes'] = pl_grab_post('notes');
		// These next two fields were added for the third-party HUD module.
        $a['emp_start_date'] = pl_grab_post('emp_start_date');
        $a['emp_end_date'] = pl_grab_post('emp_end_date');		
		
		$user = new pikaUser($user_id);
		$user->setValues($a);
		$user->save();
		header("Location:{$base_url}/system-users.php");
		break;

	default:
		
		
		
		$user_list = new plFlexList();
		$user_list->template_file = 'subtemplates/system-users.html';
		$user_list->column_names = array('name','enabled','description','email','username','user_id','last_active');
		$user_list->table_url = "{$base_url}/system-users.php";
		$user_list->get_url = "enabled={$enabled}&last_name={$last_name}&attorney={$attorney}&firm={$firm}&city={$city}&county={$county}&group_id={$group_id}&";
		$user_list->order_field = $order_field;
		$user_list->order = $order;
		$user_list->records_per_page = $page_size;
		$user_list->page_offset = $offset;
		
		$row_count = 0;
		$result = pikaUser::getUsers($filter,$row_count,$order_field,$order,$offset,$page_size);
		
		while ($row = DBResult::fetchRow($result))
		{
			$r = array();
			$r['user_id'] = $row['user_id'];
			$name = pikaTempLib::plugin('text_name','name',$row,array(),array("order=last"));
			$r['name'] = $name;
			$r['enabled'] = pl_array_lookup($row['enabled'],$menu_enabled);
			if (!$row['enabled']){
				$r['enabled'] = "<em><font color=\"red\">" . $r['enabled'] . "</font></em>";
			} else {$r['enabled'] = "<font color=\"green\">" . $r['enabled'] . "</font>";}
			$r['description'] = $row["description"];
			$r['email'] = '<a href=mailto:' . $row["email"] . '>' . $row["email"] . '</a>';
			$r['username'] = $row["username"];
			
			
			$r['last_active'] = "Never logged in";
			if(strlen($row['last_active']) > 0)
			{
				$r['last_active'] = date('n/d/Y g:i A',strtotime($row['last_active']));
			}
			$user_list->addHtmlRow($r);
		}
		
		$user_list->total_records = $row_count;
		
		$a['enabled'] = $enabled;
		$a['last_name'] = $last_name;
		$a['attorney'] = $attorney;
		$a['group_id'] = $group_id;
		$a['firm'] = $firm;
		$a['city'] = $city;
		$a['county'] = $county;
		$a['order_field'] = $order_field;
		$a['order'] = $order;
		$a['user_list'] = $user_list->draw();
		$a['row_count'] = $row_count;

		$template = new pikaTempLib('subtemplates/system-users.html',$a,'user_list');
		$template->addMenu('groups',$groups);
		$template->addMenu('enabled',$menu_enabled);
		$main_html['content'] = $template->draw();
				
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 User Accounts";

		break;
}







$main_html['page_title'] = 'User Accounts';
$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();

pika_exit($buffer);

?>
