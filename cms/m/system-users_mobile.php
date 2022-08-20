<?php 

/**********************************/
/* Pika CMS (C) 2002 Aaron Worley */
/* http://pikasoftware.com        */
/**********************************/
/* 06-24-2010 - caw - copied from system-user.php for Pika Mobile */
chdir('..');

require_once('pika-danio.php');
pika_init();

require_once('pikaTempLib.php');
require_once('plFlexList.php');
require_once('pikaUser.php');
require_once('pikaGroup.php');

// FUNCTIONS


$action = pl_grab_get('action');
$user_id = pl_grab_get('user_id');
// 06-25-2010 - caw - modified for Pika Mobile
$order = 'ASC';
$order_field = 'name';
//$order = pl_grab_get('order');
//$order_field = pl_grab_get('order_field');
// end of add
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
	
	$default_template = new pikaTempLib('m/default.html', $main_html);
	$buffer = $default_template->draw();
	pika_exit($buffer);
}

$result = pikaGroup::getGroupsDB();
$groups = array();
while ($row = DBResult::fetchRow($result))
{
	$groups[$row['group_id']] = $row['group_id'];
}

switch ($action)
{
	case 'edit':
	
		$user = new pikaUser($user_id);
		$a = $user->getValues();
		$a['user_id'] = $user_id;
		
		if($a['last_active']){
			$a['last_active'] = date('n/d/y g:i A',$a['last_active']);
		} else {
			$a['last_active'] = "Never logged in";
		}
		if(!$a['last_addr']) { $a['last_addr'] = "Never logged in";}
		$template = new pikaTempLib('subtemplates/system-users.html', $a, 'edit_user');
		$template->addMenu('groups',$groups);
		$main_html['content'] = $template->draw();
		$name = pikaTempLib::plugin('text_name','name',$a,array(),array("nomiddle","noextra"));
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 <a href=\"{$base_url}/system-users.php\">User Accounts</a> &gt;
							 {$name}";

		break;
		
	case 'update':
		
		$a['username'] = pl_grab_get('username');
		$password = pl_grab_get('password');
		if(strlen($password) > 0) {
			$a['password'] = md5($password);
		}
		$a['first_name'] = pl_grab_get('first_name');
		$a['middle_name'] = pl_grab_get('middle_name');
		$a['last_name'] = pl_grab_get('last_name');
		$a['extra_name'] = pl_grab_get('extra_name');
		$a['enabled'] = pl_grab_get('enabled');
		$a['group_id'] = pl_grab_get('group_id');
		$a['description'] = pl_grab_get('description');
		$a['email'] = pl_grab_get('email');
		$a['attorney'] = pl_grab_get('attorney');
		$a['atty_id'] = pl_grab_get('atty_id');
		$a['firm'] = pl_grab_get('firm');
		$a['address'] = pl_grab_get('address');
		$a['address2'] = pl_grab_get('address2');
		$a['city'] = pl_grab_get('city');
		$a['state'] = pl_grab_get('state');
		$a['zip'] = pl_grab_get('zip');
		$a['county'] = pl_grab_get('county');
		$a['phone_notes'] = pl_grab_get('phone_notes');
		$a['languages'] = pl_grab_get('languages');
		$a['practice_areas'] = pl_grab_get('practice_areas');
		$a['notes'] = pl_grab_get('notes');
		
		
		$user = new pikaUser($user_id);
		$user->setValues($a);
		$user->save();
		header("Location:{$base_url}/system-users.php");
		
		break;

	default:		
		
		
		$user_list = new plFlexList();
		$user_list->template_file = 'm/system-users_mobile.html';
	    $user_list->column_names = array('name','enabled','description','email','username','user_id','last_active');
		$user_list->table_url = "{$base_url}/m/system-users_mobile.php";	
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
			if (!$row['enabled'])
			{
				$r['enabled'] = "<em><font color=\"red\">" . $r['enabled'] . "</font></em>";
			} 
			else 
			{
				$r['enabled'] = "<font color=\"green\">" . $r['enabled'] . "</font>";
			}
			$r['description'] = $row["description"];
// 06-24-2010 - caw - added for Pika Mobile			
			$r['phone_notes'] = $row["phone_notes"];
			
			if(strlen($r['phone_notes']) > 0)
			{
				$r['phone_notes'] .= "<br />";
			}
			
// end of add			
			$r['email'] = '<a href=mailto:' . $row["email"] . '>' . $row["email"] . '</a>';
			$r['username'] = $row["username"];
			
			$r['last_active'] = "Never logged in";
			if($row['last_active']){
				$r['last_active'] = date('n/d/y g:i A',$row['last_active']);
			}
			$active_value = time() - 300;
			if ($row['last_active'] > $active_value){
				$r['last_active'] = "<strong>" . $r['last_active'] . "</strong>";
			}
			$user_list->addHtmlRow($r);
		}
		
		$a['enabled'] = $enabled;
		$a['last_name'] = $last_name;
		$a['attorney'] = $attorney;
		$a['group_id'] = $group_id;
		$a['firm'] = $firm;
		$a['city'] = $city;
		$a['county'] = $county;
		$a['user_list'] = $user_list->draw();
		$a['row_count'] = $row_count;
		
		$template = new pikaTempLib('m/system-users_mobile.html',$a,'user_list');
		$template->addMenu('groups',$groups);
		$template->addMenu('enabled',$menu_enabled);
		$main_html['content'] = $template->draw();
				
		$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> &gt;
							 <a href=\"{$base_url}/site_map.php\">Site Map</a> &gt;
							 User Accounts";

		break;
}




$main_html['page_title'] = 'User Accounts';
$default_template = new pikaTempLib('m/default.html',$main_html);
$buffer = $default_template->draw();

pika_exit($buffer);

?>
