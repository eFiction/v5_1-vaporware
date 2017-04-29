<?php
namespace Model;

class Auth extends Base {

	public function userLoad($login, $password, $uid=-1)
	{
		// Load a compatibility wrapper for PHP versions prior to 5.5.0
		if ( !function_exists("password_hash") ) include ( "app/inc/password_compat.php" );

		$this->prepare("userQuery", "SELECT U.password, U.uid FROM `tbl_users` U where ( U.login = :login OR U.uid = :uid ) AND U.groups > 0");
		$this->bindValue("userQuery", ":login", $login, \PDO::PARAM_STR);
		$this->bindValue("userQuery", ":uid",	 $uid,	 \PDO::PARAM_INT);
		$user = $this->execute("userQuery");
		
		if(sizeof($user)==0) return FALSE;
		else $user = $user[0];

		if ( password_verify ( $password, $user['password'] ) )
		{
			// Check if the password requires improvement
			if ( password_needs_rehash($user['password'], PASSWORD_DEFAULT) )
					$this->userChangePW( $user['uid'], $password );
		}
		elseif( $user['password']==md5($password) )
		{
			$this->userChangePW( $user['uid'], $password );
		}
		else
		{
			return FALSE;
		}

		$this->userSession($user['uid']);
		return $user['uid'];
	}
	
	public function userRecovery($username, $email)
	{
		$data = $this->exec(
					"SELECT U.uid, U.nickname, U.email FROM `tbl_users`U WHERE ( U.email = :email AND U.email != '' ) OR ( U.login = :login );",
					[ ':email' => $email, ':login' => $username ]
				);
		if ( sizeof($data)>0 )
			return $data[0];
		return NULL;
	}
	
	public function setRecoveryToken($uid)
	{
		$token = md5(time());
		$dbtoken = $token."//".sprintf('%u',ip2long($_SERVER["REMOTE_ADDR"]))."//".time();
		// $uid and $token are safe
		$this->exec("INSERT INTO `tbl_user_info` (uid,field,info) VALUES ({$uid},-1,CONCAT_WS( '//', :token , INET_ATON('{$_SERVER['REMOTE_ADDR']}'), :time ) )
						ON DUPLICATE KEY UPDATE info=CONCAT_WS( '//', :token , INET_ATON('{$_SERVER['REMOTE_ADDR']}'), :time ) ",
						[":token" => $token, ":time" => time() ]
					);

		return $token;
	}
	
	public function dropRecoveryToken($uid)
	{
		// $uid and $token are safe
		$this->exec("DELETE FROM `tbl_user_info` WHERE `uid` = {$uid} AND `field` = -1;");
	}
	
	public function getRecoveryToken($token)
	{
		// $ip is safe
		$recovery = $this->exec(
			"SELECT U.uid, I.info as token
				FROM `tbl_users`U 
					INNER JOIN `tbl_user_info`I ON (U.uid=I.uid AND I.field=-1)
				WHERE I.info LIKE CONCAT_WS( '//', :token , INET_ATON('{$_SERVER['REMOTE_ADDR']}'), '%' );",
			[":token" => $token]
		);
		// no token found
		if ( sizeof($recovery)==0 ) return FALSE;
		
		// token expired
		$token = explode("//",$recovery[0]['token']);
		if((time()-$token[2])>3600)
			return FALSE;
		
		// return token
		return $recovery[0]['uid'];
	}
	
	
	public function userSession($uid)
	{
		$session = new \DB\SQL\Mapper($this->db, $this->prefix."sessions");
		$session->load(array('session=?',$_SESSION['session_id']));
		$session->user=$uid;
		$session->save();
	}

	public function createSession()
	{
        $f3 = \Base::instance();
		
		$session_id = md5(time());
		
		$f3->set('SESSION.session_id', $session_id );
//		echo "<br>new: ".$_SESSION['session_id'];
		$this->exec("INSERT INTO `tbl_sessions`(`session`, `user`, `lastvisited`, `ip`) VALUES
				('{$_SESSION['session_id']}', '{NULL}', NOW(), INET_ATON('{$_SERVER['REMOTE_ADDR']}') );");

	  setcookie("session_id", $session_id, time()+31536000, $f3->get('BASE') );
	  return $session_id;
	}

	public function validateSession($session_id)
	{
        $f3 = \Base::instance();

		$sql[] = "DELETE FROM `tbl_sessions` WHERE (user = 0 AND TIMESTAMPDIFF(MINUTE,`lastvisited`,NOW())>60 )
																						OR 
																						TIMESTAMPDIFF(MONTH,`lastvisited`,NOW())>1;";
		$sql[] = "SET @guests  := (SELECT COUNT(DISTINCT S.session) 
																	FROM `tbl_sessions`S WHERE S.user IS NULL AND NOT (S.session = '{$session_id}' AND S.ip = INET_ATON('{$_SERVER['REMOTE_ADDR']}') )
															);";
		$sql[] = "SET @members := (SELECT COUNT(DISTINCT user) FROM (SELECT * FROM `tbl_sessions` GROUP BY user ORDER BY `lastvisited` DESC) as S WHERE 
															S.user IS NOT NULL AND 
															TIMESTAMPDIFF(MINUTE,S.lastvisited,NOW())<60 AND
															NOT (S.session = '{$session_id}' AND S.ip = INET_ATON('{$_SERVER['REMOTE_ADDR']}') )
											);";
		$sql[] = "UPDATE `tbl_sessions`S SET lastvisited = CURRENT_TIMESTAMP WHERE S.session = '{$session_id}' AND S.ip = INET_ATON('{$_SERVER['REMOTE_ADDR']}');";

		$sql[] = "SELECT S.session, UNIX_TIMESTAMP(S.lastvisited) as time, S.ip, IF(S.user,S.user,0) as userID, 
						U.nickname, U.groups, U.preferences, U.cache_messaging, 
						GROUP_CONCAT(DISTINCT U2.uid) as allowed_authors, 
						@guests, @members
							FROM `tbl_sessions`S 
							INNER JOIN `tbl_users` U ON ( IF(S.user,S.user = U.uid,U.uid=0) )
								LEFT JOIN `tbl_users`U2 ON ( (U.uid = U2.uid OR U.uid = U2.curator) AND U.groups&5 )
						WHERE S.session = '{$session_id}' AND S.ip = INET_ATON('{$_SERVER['REMOTE_ADDR']}');";
						
/*
	To do: create a cache field, move message status, curator to that field to reduce DB usage

*/
// IF(admin,IF(TIMESTAMPDIFF(MINUTE,`admin`,NOW())<15,1,0),0) as admin_active, 

		$user = $this->exec($sql)[0];

		if ( $user['session'] > '' && $user['userID'] > 0 )
		{
			$_SESSION['userID']	= $user['userID'];
			
			$f3->set('usercount', 
					[
						"member"	=>	$user['@members']+1,
						"guest"		=>	$user['@guests']
					]
			);

			//$user['cache_messaging'] = json_decode($user['cache_messaging'],TRUE);
			if ( NULL == $user['cache_messaging'] = json_decode($user['cache_messaging'],TRUE) )
			{
				$user['cache_messaging'] = $this->userCacheRecount("messaging");
				
				$userInstance = \User::instance();
				$userInstance->cache_messaging = json_encode($user['cache_messaging']);
				$userInstance->save();
			}

			$user['preferences'] = json_decode($user['preferences'],TRUE);
			// Check if language is available
			$user['preferences']['language'] = ( FALSE===$f3->get('CONFIG.language_forced') AND array_key_exists($user['preferences']['language'], $f3->get('CONFIG.language_available' )) )
									? $user['preferences']['language']
									// Fallback to page default
									: $f3->get('CONFIG.language_default');
			return $user;
		}
		else
		{
			$_SESSION['userID']	= FALSE;
			
			$f3->set('usercount', 
					[
						"member"	=>	$user['@members'],
						"guest"		=>	$user['@guests']+1
					]
			);
			if ( $user['session'] == '' )	Auth::instance()->createSession();
			return FALSE;
		}
	}

	public function registerCheckInput(&$register )
	{
		// check if registrar has agreed to the TOS
		if ( !isset($register['accept']) )
		{
			// no further checks happening if not accepted
			return [ 'accept' => 1 ];
		}
		
		/*
		 	$register: registration form data

			returns TRUE if all checks pass
		*/
		$error = [ "count" => 0 ];

		// Check data entered
		if(empty($register['login']) OR trim($register['login'])=="" )
		{
			$error['count']++;
			$error['login'] = "missing";
		}
		else
		{
			$sql = "SELECT U.uid,U.login,U.email FROM `tbl_users`U WHERE U.login LIKE :login";
			$data = $this->exec( $sql, [ ":login" => $register['login'] ] );
			if ( sizeof($data) == 1 )
			{
				$data = $data[0];
				$error['count']++;
				$error['login'] = "taken";
			}
		}
		
		if(empty($register['email']) OR trim($register['email'])=="" )
		{
			$error['count']++;
			$error['email'] = "missing";
		}
		else
		{
			if ( isset($data['email']) AND $register['email'] == $data['email'] )
			{
				// email matches user lookup from above
				$error['count']++;
				$error['login'] = "member";
			}
			else
			{
				$sql = "SELECT U.login,U.email FROM `tbl_users`U WHERE U.email LIKE :email";
				$data = $this->exec( $sql, [ ":email" => $register['email'] ] );

				if ( sizeof($data) == 1 )
				{
					$error['count']++;
					$error['email'] = "taken";
				}
			}
		}
		
		/*
			Password check
		*/
		if ( TRUE !== $pw_error = $this->newPasswordQuality( $register['password1'], $register['password2']) )
		{
			$error['count']++;
			$error['password'] = $pw_error;
		}

		if ( $error['count']==0 )
		{
			// Check with SFS database?
			if ( $this->config['reg_sfs_usage'] == TRUE )
			{
				$register['ip'] = $_SERVER['REMOTE_ADDR'];
				$check = $this->checkInputSFS($register);
				
				$error = array_merge ( $error, $this->checkInputSFS($register) );
			}
			else
			{
				$error['status'] = -1;
			}
		}

		return $error;
	}

	protected function checkInputSFS($data)
	{
		// return: TRUE - everything fine
		// return [int @status (1 or 2),str @reason] - something wrong (or everything)
		$url = "http://api.stopforumspam.org/api?f=json";
		
		if ( $this->config['reg_sfs_check_mail'] 		== TRUE ) $url .= "&email=".$data['email'];
		if ( $this->config['reg_sfs_check_ip'] 			== TRUE ) $url .= "&ip=".$data['ip'];
		if ( $this->config['reg_sfs_check_username']	== TRUE ) $url .= "&username=".$data['login'];
		
		$handle=FALSE; $i=0;

		/*
			Sometimes the api server may not respond on the first attempt (depends on webserver configuration)
			So the timeout was set to one second and we will try up to 5 times
		*/

		if ( 0 == ini_get('allow_url_fopen') )
		{
			$sfsCheck = @curl_init($url);
			@curl_setopt($sfsCheck, CURLOPT_RETURNTRANSFER, true);
			
			while( !$handle AND $i++ < 5 )
			{
				$handle = @curl_exec($sfsCheck);

				if ( !$handle ) {
				  $sfs['success'] = 0;
				  echo "Null";
				}
				else {
				  $sfs = json_decode($handle,TRUE);
				}
			}
			@curl_close($sfsCheck);			
		}
		else
		{
			$context = stream_context_create( array(
				'http'=>array(
				'timeout' => 1.0
				)
			));
			
			while( !$handle AND $i++ < 5 )
			{
				$handle = @fopen($url, 'r', false, $context);
				if ( !$handle ) {
				  $sfs['success'] = 0;
				}
				else {
				  $sfs = json_decode(stream_get_contents($handle));
				}
			}
		}

		$reason = "";
		if ( $sfs['success'] == 1 )
		{
			// Successful query
			if ( $this->config['reg_sfs_check_mail'] 	== TRUE && $sfs['email']['appears'] == 1 )
			{
				$reason .= "E";
			}
			if ( $this->config['reg_sfs_check_ip'] 		== TRUE && $sfs['ip']['appears'] == 1 )
			{
				$reason .= "I";
			}
			if ( $this->config['reg_sfs_check_username']== TRUE && $sfs['username']['appears'] == 1 )
			{
				$reason .= "U";
			}
			// No occurence in spam database, pass
			if( strlen($reason)==0 ) $save = "green";
			// Too many hits, straight kick
			elseif( strlen($reason)>2 ) $deny = "red";
			else $save = "yellow";
		}
		else
		{
			// Failed to query SFS API Server
			if ( $this->config['reg_sfs_failsafe'] == 0 )
			{
				// Accept registration, but put member on hold
				$save = "yellow";
				$reason = "S";
			}
			elseif ( $this->config['reg_sfs_failsafe'] == 1 )
			{
				// Accept registration
				$save = "green";
			}
			else
			{
				// Deny registration
				$deny = "connect";
			}
		}
		
		if ( isset($save) )
		{
			if ( $save == "green" )
			{
				// Everything is fine, gonna save member and continue to next step
				return [ "status" => 0, "reason" => NULL ];
			}
			else
			{
				// Failed to query server, according to config settings, will save the registration, but put on hold
				// or
				// At least one of the checked items was found in the SFS database, gonna put the member on hold
				return [ "status" => 1, "reason" => $reason ];
			}
		}
		else
		{
			if ( $deny == "connect" )
			{
				// SFS server yould not be queried, registration was not completed
				return [ "status" => 2, "reason" => "technical" ];
			}
			elseif ( $deny == "red" )
			{
				// Too many items were found in the spam database, by directive, registration was refused
				return [ "status" => 2, "reason" => "refused" ];
			}
		}
		return FALSE;
	}

	public function addUser(array $register, array $moderation=[])
	{
		$newUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
		$newUser->login			= $register['login'];
		$newUser->nickname		= $register['login'];
		$newUser->email			= $register['email'];
		$newUser->registered	= date("Y-m-d H:i:s",time());
		$newUser->groups		= $register['groups'];
		$newUser->save();
		
		return $newUser->_id;
	}
	
	public function newuserSetStatus($userID, $status, $mod)
	{
		// $userID, $status and $mod are safe
		$this->exec("INSERT INTO `tbl_user_info` (uid,field,info) VALUES ({$userID},'{$status}','{$mod}')
						ON DUPLICATE KEY UPDATE info='{$mod}' ");
	}
	
	public function newuserEmailLink(array $token)
	{
		$newInfo = new \DB\SQL\Mapper($this->db, $this->prefix."user_info");
		$newInfo->load(array('uid=? AND field=? AND info=?',$token[0], '-2', $token[1]));
		
		if ( $newInfo->uid != $token[0]) return FALSE;
		
		$newUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
		$newUser->load(array('uid=?',$newInfo->uid));
		$newUser->groups = 1;
		$newUser->save();
		
		$newInfo->erase();
		
		return TRUE;
	}
}
