<?php 
/**********************************/
/* Pika CMS						  */ 
/* (C) 2011 Pika Software, LLC.   */
/* http://pikasoftware.com        */
/**********************************/

require_once ('pika-danio.php'); 
pika_init();
require_once('pikaTempLib.php');
require_once('pikaUser.php');
if (PHP_VERSION_ID >= 50303)
{
	require_once('password_hash_compat.php');
}

$main_html = $html = array();

$settings = pikaSettings::getInstance();
$base_url = $settings['base_url'];
$html['pass_min_length'] = $pass_min_length = $settings['pass_min_length'];
$html['flags'] = '';

$action = pl_grab_post('action');
$menu_pass_strength = array('' => 'None - No Strength Requirement',
							'0' => 'None - No Strength Requirement',
							'1' => '(1) Light',
							'2' => '(2) Light',
							'3' => '(3) Moderate',
							'4' => '(4) Strong');
$menu_pass_length = array(	'6' => '6',
							'7' => '7',
							'8' => '8',
							'9' => '9',
							'10' => '10');
$menu_pass_method = array(	'1' => "Lowercase Letters &amp Numbers",
							'2' => "All Letters, Numbers",
							'3' => "All Characters");
$html['p_len'] = '10';
$html['p_method'] = '1';
$html['pass_min_strength_label'] = $pass_min_strength_label = pl_array_lookup($settings['pass_min_strength'],$menu_pass_strength);
$html['pass_min_length_label'] = $pass_min_length_label = "None - No Length Requirement";
if($pass_min_length)
{
	$html['pass_min_length_label'] = $pass_min_length_label = "({$pass_min_length}) Characters";
}


if($action == 'update')
{
	$oldpass = pl_grab_post('oldpass');
	$newpass1 = pl_grab_post('newpass1');
	$newpass2 = pl_grab_post('newpass2');
	
	$user = new pikaUser($auth_row['user_id']);
	$is_authorized = true;
	if(strlen($newpass1) < 1)
	{
		$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Error: New password cannot be blank");
		$is_authorized = false;
	}
	elseif ((md5($oldpass) != $user->password) && !(password_verify($oldpass, $user->password)))
	{
		$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Error: Old Password incorrect");
		$is_authorized = false;
	}
	else 
	{
		if(isset($settings['pass_min_strength']) && $settings['pass_min_strength'] && pikaUser::passStrength($newpass1) < $settings['pass_min_strength'])
		{
			$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Error: New password does not meet strength requirement ({$pass_min_strength_label})");
			$is_authorized = false;
		}
		if(isset($settings['pass_min_length']) && $settings['pass_min_length'] && strlen($newpass1) < $settings['pass_min_length'])
		{
			$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Error: New password does not meet length requirement ({$pass_min_length})");
			$is_authorized = false;
		}
		if($newpass1 != $newpass2){
			$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Error: New password(s) entries don't match");
			$is_authorized = false;
		}
	}
	
	if($is_authorized)
	{
		$user->password = md5($newpass1);
		$user->save();	
		$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Password updated successfully");
	}
	else 
	{
		$html['flags'] .= pikaTempLib::plugin('red_flag','red_flag',"Errors detected - Password not updated");
	}
}


$template = new pikaTempLib('subtemplates/password.html',$html);
$template->addMenu('p_len',$menu_pass_length);
$template->addMenu('p_method',$menu_pass_method);
$main_html['content'] = $template->draw();
$main_html['page_title'] = $page_title = 'Change Password';
$main_html['nav'] = "<a href=\"{$base_url}\">Pika Home</a> 
					&gt; {$page_title}";


$default_template = new pikaTempLib('templates/default.html',$main_html);
$buffer = $default_template->draw();
pika_exit($buffer);
