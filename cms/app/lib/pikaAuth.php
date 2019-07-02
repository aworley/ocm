<?php

require_once('pikaUserSession.php');
require_once('pikaSettings.php');

class pikaAuth 
{
	private static $instance;
	
	private $session_id;
	private $user;
	private $pass;
	
	private $messages = array();
	private $is_authorized = false;
	private $auth_row = array(	'user_id' => '',
								'username' => '',
								'group_id' => ''										
	);
	
	public $user_agent;
	public $ip_address;
	
	public $delete_session_on_timeout = true;
	public $regenerate_sid = false;  // disabled - browser can't update cookie fast enough.
	
	
	
	private function __construct()
	{
		if(session_id() == '')
		{
			session_start();
		}
		
		$this->session_id = session_id();
		if(isset($_SESSION['SID']) && $_SESSION['SID'])
		{
			$this->session_id = $_SESSION['SID'];
		}
		$this->ip_address = $_SERVER['REMOTE_ADDR'];
		$this->user_agent = $_SERVER['HTTP_USER_AGENT'];
		
		// Prevent back from re-submitting login
		if(!isset($_SESSION['auth_id']))
		{
			$_SESSION['auth_id'] = 1;
		}
	}
	
	public static function getInstance()
	{
		if(empty(self::$instance)) 
		{
			self::$instance = new self();
		} 
		return self::$instance;
	}
	
	public function authenticate($user,$pass,$authAdapterObj) {
		$this->is_authorized = false;
		// Clear messages
		$this->messages = array();
		
		$settings = pikaSettings::getInstance();
		
		// First check to see if existing non-expired session
		$result = pikaUserSession::getSessions(array('session_id' => $this->session_id));
		if(DBResult::numRows($result) == 1)
		{
			$session_timeout = $settings['session_timeout'];
			$row = DBResult::fetchRow($result);
			
			if($row['logout'] == 1)
			{// User has logged out or has been logged out by admin. Need to change SID at this point
				session_regenerate_id();
				$_SESSION['SID'] = session_id();
				$msgstr = "You have Logged Out of {$settings['owner_name']} successfully";
				$this->setMessage('0900',$msgstr,__FILE__,__LINE__);
			}
			elseif($session_timeout > 0 && $row['seconds_elapsed'] > $session_timeout)
			{// User session has timed out - decide whether to delete row and set message
				if($this->delete_session_on_timeout)
				{
					$expired_session = new pikaUserSession($row['user_session_id']);
					$expired_session->delete();
				}
				$msgstr = 'Your Session has timed out.  Please log back in to continue.';
				$this->setMessage('0102',$msgstr,__FILE__,__LINE__);
				
			}
			elseif($row['enabled'] && ($row['ip_address'] == $this->ip_address || $row['user_agent'] == $this->user_agent))
			{
				// Verify IP or User Agent is the same (measure against spoofing)
				// Needs to be one or other as many mobile phones operate through cache proxies (meaning multiple ips)
				$this->is_authorized = true;
				
				$session = new pikaUserSession($row['user_session_id']);
				$session->session_id = $_SESSION['SID'] = session_id();
				$session->save();
				$this->auth_row = $row;
			} 	
		}
		
		// If no pre-existing session exists - run supplied authorization adapter
		// If no authorization adapter is supplied assume users
		if(!$this->is_authorized)
		{
			if(!is_object($authAdapterObj) || !method_exists($authAdapterObj,'authenticate'))
			{
				$authAdapterObj = new pikaAuthDb('users','username','password');
			}
			if($authAdapterObj->authenticate($user,$pass))
			{
				// Check to see that the login is not a back/refresh of submission form
				$auth_id = $_SESSION['auth_id'];
				if(isset($_REQUEST['auth_id']))
				{
					$auth_id = $_REQUEST['auth_id'];
				}
				if($auth_id == $_SESSION['auth_id'])
				{
					$_SESSION['auth_id']++;
	
					$this->is_authorized = true;
					$this->auth_row = $authAdapterObj->getAuthRow();
				
					$new_session = new pikaUserSession();
					session_regenerate_id(true);
					$new_session->session_id = $_SESSION['SID'] = session_id();
					$new_session->logout = 0;
					$new_session->user_id = $this->auth_row['user_id'];
					$new_session->save();
				}
				else 
				{
					$msgstr = 'This session has been logged out. Please log in again to continue.';
					$this->setMessage('0103',$msgstr,__FILE__,__LINE__);
				}
			}
			else 
			{
				$messages = $authAdapterObj->getMessages();
				foreach ($messages as $message)
				{
					$this->messages[] = $message;
				}
			}
		}
		
		
		$this->processAuthRow();
		
		if($this->is_authorized && !$settings['enable_system'] && $this->auth_row['group_id'] != 'system')
		{	
			$this->is_authorized = false;
			
			if(isset($this->auth_row['user_session_id']) && is_numeric($this->auth_row['user_session_id']))
			{
				$active_session = new pikaUserSession($this->auth_row['user_session_id']);
				$active_session->logout = 1;
				$active_session->save();
			}
			
			session_regenerate_id();
			$_SESSION['SID'] = session_id();
			
			$msgstr = 'This system has been taken offline for maintenance.';
			$this->setMessage('0104',$msgstr,__FILE__,__LINE__);
		}
		
		if ($this->is_authorized)
		{
			apache_note('cms_user', $this->auth_row['username']);
		}
		
		return $this->is_authorized;
	}
	

	
	public function logout()
	{
		$result = pikaUserSession::getSessions(array('session_id' => $this->session_id));
		if(DBResult::numRows($result) == 1)
		{
			$row = DBResult::fetchRow($result);
			$user_session = new pikaUserSession($row['user_session_id']);
			$user_session->logout = 1;
			$user_session->save();
		}
		
		$this->is_authorized = false;
		
		return true;
	}
	
	private function processAuthRow()
	{
		$temp = $this->auth_row;
	
		if(isset($temp['read_office']) && strlen($temp['read_office']))
		{
			$temp['read_office'] = explode(',',$temp['read_office']);
		}
		if(isset($temp['edit_office']) && strlen($temp['edit_office']))
		{
			$temp['edit_office'] = explode(',', $temp['edit_office']); 		
		}

		$this->auth_row = $temp;
	}
	
	public function getAuthRow()
	{
		return $this->auth_row;
	}

	public function isAuthorized()
	{
		return $this->is_authorized;
	}
	
	public function setMessage($msgno = null, $msgstr = null, $msgfile = null, $msgline = null)
	{
		$this->messages[] = array($msgno,$msgstr,$msgfile,$msgline);
	}
	
	public function getMessages()
	{
		return $this->messages;
	}
}

