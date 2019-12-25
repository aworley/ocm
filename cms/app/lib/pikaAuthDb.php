<?php

/**********************************/
/* Pika CMS (C) 2011              */
/* Pika Software, LLC             */
/* http://pikasoftware.com        */
/**********************************/


/**
* pikaAuthDb class - class for pikaAuth
* implementing user/password verification against a database table
*
* @author Matthew Friedlander <matt@pikasoftware.com>;
* @version 1.0
* @package Danio
*/
class pikaAuthDb 
{
	private $table_name = 'users';
	private $identity_column = 'username';
	private $credential_column = 'password';
	
	protected $is_authorized = false;
	protected $auth_row = array();
	protected $messages = array();
	
	
	
	/**
	 * public function __construct
	 * 
	 * Initializes key properties of the pikaAuthDb class to prepare to verify
	 * provided credentials for authorization
	 *
	 * @param string $table_name = name of the database table to check
	 * @param string $identity_column = name of the column in $table_name containing user identity (e.g. username)
	 * @param string $credential_column = name of the column in $table_name containing user credential (e.g. password)
	 * @param string $deprecated = DEPRECATED; was name of DB supported function for hashing credential (i.e. MD5, PASSWORD)
	 */
	public function __construct($table_name = null,$identity_column = null,$credential_column = null,$deprecated = null)
	{
		$this->setTableName($table_name);
		if(!is_null($identity_column) && strlen($identity_column) > 0)
		{
			$this->identity_column = $identity_column;			
		}
		if(!is_null($credential_column) && strlen($credential_column) > 0)
		{
			$this->credential_column = $credential_column;		
		}
	}
	
	/**
	 * public function setTableName(
	 *
	 * @param string $table_name - sets the name of the table to query for identities.
	 */
	public function setTableName($table_name = null)
	{
		if(!is_null($table_name) && strlen($table_name))
		{
			$this->table_name = $table_name;
		}
	}
	
	public function authenticate($identity = null,$credential = null)
	{
		$this->is_authorized = false;
		
		if(!is_null($identity) && strlen($identity) > 0 && strlen($this->table_name) > 0)
		{
			$safe_identity = DB::escapeString($identity);
			
			$sql  = "SELECT user_id, username, enabled, password_expire, 
					users.group_id AS group_name, `groups`.*, password
					FROM {$this->table_name}
					LEFT JOIN `groups` ON users.group_id=groups.group_id
					WHERE enabled = '1'
					AND username='{$safe_identity}'
					AND LENGTH(password) > 0";
			$result = DB::query($sql) or trigger_error("SQL: " . $sql . " Error: " . DB::error());
			
			if (DBResult::numRows($result) == 1)
			{
				if (PHP_VERSION_ID >= 50303)
				{
					require_once('password_hash_compat.php');
				}
				
				$row = DBResult::fetchRow($result);
				// one user record matched the username and password
				if (PHP_VERSION_ID >= 50303 && password_verify($credential, $row['password']))
				{  // Identity & Credential match existing records - allow login
					$this->is_authorized = true;
					$this->auth_row = $row;
					
					if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) 
					{
						require_once('pikaUser.php');
						$u = new pikaUser($row['user_id']);
						$u->setValue('password', password_hash($credential, PASSWORD_DEFAULT));
						$u->save();
    				}
				}
				
				else if (md5($credential) == $row['password'])
				{
					$this->is_authorized = true;
					$this->auth_row = $row;
					
					if (PHP_VERSION_ID >= 50303)
					{
						/*	While we have the password in memory, replace the 
							stored md5 value with a password_hash value.
							*/
						require_once('pikaUser.php');
						$u = new pikaUser($row['user_id']);
						$u->setValue('password', password_hash($credential, PASSWORD_DEFAULT));
						$u->save();
					}
				}
				
				else 
				{  // No matching user credentials found - pass login error			
					$msgstr = 'The Login Credentials you supplied are invalid.  Please re-check your Username and Password and try again.';
					$this->setMessage('0100',$msgstr,__FILE__,__LINE__);
				}
			}
			
			else
			{
				$msgstr = 'The Login Credentials you supplied are invalid.  Please re-check your Username and Password and try again.';
				$this->setMessage('0100',$msgstr,__FILE__,__LINE__);
			}
			
		}
		elseif(!is_null($identity))
		{ 
				// No matching session - no username provided - pass no user login error
				$msgstr = 'Username provided is blank.  Please re-enter your Username and Password and try again';
				$this->setMessage('0101',$msgstr,__FILE__,__LINE__);	
		}
		
		return $this->is_authorized;
	}
	
	public function getAuthRow()
	{
		return $this->auth_row;
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

?>
