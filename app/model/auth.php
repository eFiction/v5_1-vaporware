<?php
namespace Model;

class Auth extends Base {

	public function userLoad($login, $password, $uid=-1)
	{
		// Load a compatibility wrapper for PHP versions prior to 5.5.0
		if ( !function_exists("password_hash") ) include ( "app/inc/password_compat.php" );

		$this->prepare("userQuery", "SELECT U.password, U.uid FROM `tbl_users` U where ( U.login = :login OR U.uid = :uid )");
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
	
	public function userSession($uid)
	{
		$session = new \DB\SQL\Mapper($this->db, $this->prefix."sessions");
		$session->load(array('session=?',$_SESSION['session_id']));
		$session->user=$uid;
		$session->save();
	}

	public function createSession($ip_db)
	{
        $f3 = \Base::instance();
		
		$session_id = md5(time());
		
		$f3->set('SESSION.session_id', $session_id );
//		echo "<br>new: ".$_SESSION['session_id'];
		$this->exec("INSERT INTO `tbl_sessions`(`session`, `user`, `lastvisited`, `ip`) VALUES
				('{$_SESSION['session_id']}', '{NULL}', NOW(), '{$ip_db}');");

	  setcookie("session_id", $session_id, time()+31536000, $f3->get('BASE') );
	  return $session_id;
	}

	public function validateSession($session_id,$ip_db)
	{
        $f3 = \Base::instance();

		$sql[] = "DELETE FROM `tbl_sessions` WHERE (user = 0 AND TIMESTAMPDIFF(MINUTE,`lastvisited`,NOW())>60 )
																						OR 
																						TIMESTAMPDIFF(MONTH,`lastvisited`,NOW())>1;";
		$sql[] = "SET @guests  := (SELECT COUNT(DISTINCT S.session) 
																	FROM `tbl_sessions`S WHERE S.user IS NULL AND NOT (S.session = '{$session_id}' AND S.ip ='{$ip_db}')
															);";
		$sql[] = "SET @members := (SELECT COUNT(DISTINCT user) FROM (SELECT * FROM `tbl_sessions` GROUP BY user ORDER BY `lastvisited` DESC) as S WHERE 
															S.user IS NOT NULL AND 
															TIMESTAMPDIFF(MINUTE,S.lastvisited,NOW())<60 AND
															NOT (S.session = '{$session_id}' AND S.ip ='{$ip_db}')
											);";
		$sql[] = "UPDATE `tbl_sessions`S SET lastvisited = CURRENT_TIMESTAMP WHERE S.session = '{$session_id}' AND S.ip ='{$ip_db}';";
		$sql[] = "SELECT S.session,UNIX_TIMESTAMP(lastvisited) as time, ip, IF(user,user,0) as userID, U.nickname, U.groups, IF(admin,IF(TIMESTAMPDIFF(MINUTE,`admin`,NOW())<15,1,0),0) as admin_active, COUNT(P1.mid) as mail, COUNT(P2.mid) as unread, @guests, @members
							FROM `tbl_sessions`S 
							INNER JOIN `tbl_users` U ON ( IF(S.user,S.user = U.uid,U.uid=0) )
							LEFT JOIN `tbl_messaging` P1 ON ( U.uid = P1.recipient )
							LEFT JOIN `tbl_messaging` P2 ON ( U.uid = P2.recipient AND P2.date_read IS NULL )
						WHERE S.session = '{$session_id}' AND S.ip ='{$ip_db}';";

		$user = $this->exec($sql)[0];
//		print_r($user);
		if ( $user['session'] > '' && $user['userID'] > 0 )
		{
			$f3->set('usercount', 
					[
						"member"	=>	$user['@members']+1,
						"guest"		=>	$user['@guests']
					]
			);
			return $user;
		}
		else
		{
			$f3->set('usercount', 
					[
						"member"	=>	$user['@members'],
						"guest"		=>	$user['@guests']+1
					]
			);
			if ( $user['session'] == '' )	Auth::instance()->createSession($ip_db);
			return FALSE;
		}
	}

	public function registerCheckInput(&$register )
	{
		$this->configExt = $this->extendConfig();
		/*
		 	$register: registration form data

			returns TRUE if all checks pass
		*/
		$error = [ "count" => 0 ];

		// Check data entered
		if(empty($register['login']) OR trim($register['login'])=="" )
		{
			$error['count']++;
			$error['nologin'] = 1;
		}
		if(empty($register['email']) OR trim($register['email'])=="" )
		{
			$error['count']++;
			$error['noemail'] = 1;
		}
		
		if ( $error['count']==0 )
		{
			// Check name and email against DB
			$sql = "SELECT U.login,U.email FROM `tbl_users`U WHERE U.login LIKE :login OR U.email LIKE :email ;";
			$data = $this->exec( $sql, [ ":login" => $register['login'], ":email" => $register['email'] ] );
			/*
			$this->DB->bindValue($lookup, "login", $register['login'], PDO::PARAM_STR);
			$this->DB->bindValue($lookup, "email", $register['email'], PDO::PARAM_STR);
			$this->DB->execute();*/
			if ( sizeof($data) > 0 )
			{
				$error['count']++;
				$oldUser = $data[0];
				if ( $oldUser['email'] == $register['email'] )
				{
					$error['email'] = "__RegEmailTaken";
					$register['email'] = "";
				}
				if ( $oldUser['login'] == $register['login'] )
				{
					$error['login'] = "__RegNameTaken";
					$register['login'] = "";
				}
			}

			// Check with SFS database
			if ( $this->configExt['reg_sfs_usage'] == TRUE )
			{
				$register['ip'] = $_SERVER['REMOTE_ADDR'];
				$check = $this->checkInputSFS($register);
				if ( is_array($check) AND $check['status'] == 2 )
				{
					// Registration refused
					$error['sfs'] = 2;
				}
				elseif ( is_array($check) AND $check['status'] == 1 )
				{
					// Queued for moderation
					$this->regModeration = $check['reason'];
				}
				$error['sfsreason'] = $check['reason'];
//				print_r($check);
			}
		}
		
		$this->password_regex = '/^(?=^.{'.$this->configExt['reg_min_password'].',}$)(?:.*?(?>((?(1)(?!))[a-z]+)|((?(2)(?!))[A-Z]+)|((?(3)(?!))[0-9]+)|((?(4)(?!))[^a-zA-Z0-9\s]+))){'.$this->configExt['reg_password_complexity'].'}.*$/s';
		
		// Passwords match?
		if ( $register['password1'] != $register['password2'] )
		{
			$error['count']++;
			$error['password'] = "__RegPasswordMismatch";
		}
		elseif( $register['password1'] == "" )
		{
			$error['count']++;
			$error['password'] = "__RegPasswordMissing";
		}
		// Passwords meets the criteria required?
		elseif ( preg_match( $this->password_regex, $register['password1'], $matches) != 1 )
		{
			$error['count']++;
			$error['password'] = "__RegPasswordCriteria";
		}

		// Check if fields have been filled
//		print_r($register);
//		print_r($error);
		if
			( $error['count']==0 AND empty( $error['sfs'] ) ) return TRUE;
		else
			return $error;
	}

	protected function checkInputSFS($data)
	{
		$url = "http://api.stopforumspam.org/api?f=serial";
		
		if ( $this->configExt['reg_sfs_check_mail'] 		== TRUE ) $url .= "&email=".$data['email'];
		if ( $this->configExt['reg_sfs_check_ip'] 			== TRUE ) $url .= "&ip=".$data['ip'];
		if ( $this->configExt['reg_sfs_check_username'] == TRUE ) $url .= "&username=".$data['login'];

		if ($handle = fopen($url, "r"))
		{
			$sfs = unserialize(stream_get_contents($handle));
		}
		// Needs better error handling
		else $sfs['success'] = 0;

		if ( $sfs['success'] == 1 )
		{
			$bad = "";
			// Successful query
			if ( $this->configExt['reg_sfs_check_mail'] == 		 "TRUE" && $sfs['email']['appears'] == 1 )
			{
				$bad .= "E";
			}
			if ( $this->configExt['reg_sfs_check_ip'] == 			 "TRUE" && $sfs['ip']['appears'] == 1 )
			{
				$bad .= "I";
			}
			if ( $this->configExt['reg_sfs_check_username'] == "TRUE" && $sfs['username']['appears'] == 1 )
			{
				$bad .= "U";
			}
			// No occurence in spam database, pass
			if( strlen($bad)==0 ) $save = "green";
			// Too many hits, straight kick
			elseif( strlen($bad)>2 ) $deny = "red";
			else $save = "suspend";
		}
		else
		{
			// Failed to query SFS API Server
			if ( $this->configExt['reg_sfs_failsafe'] == 0 )
			{
				// Accept registration, but put member on hold
				$save = "yellow";
			}
			elseif ( $this->configExt['reg_sfs_failsafe'] == 1 )
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
				return TRUE;
			}
			elseif ( $save == "yellow" )
			{
				// Failed to query server, according to config settings, will save the registration, but put on hold
				$return = [ "status" => 1, "reason" => "S" ];
			}
			else
			{
				// At least one of the checked items was found in the SFS database, gonna put the member on hold
				$return = [ "status" => 1, "reason" => $bad ];
			}
		}
		else
		{
			if ( $deny == "connect" )
			{
				// SFS server yould not be queried, registration was not completed
				$return = [ "status" => 2, "reason" => "technical" ];
			}
			elseif ( $deny == "red" )
			{
				// Too many items were found in the spam database, by directive, registration was refused
				$return = [ "status" => 2, "reason" => "refused" ];
			}
		}
		return $return;
	}

	public function addUser($register)
	{
		$extra="done,";
		// Gather data for SQL insert
		$token = md5(time());
		$data = [
							"login"			=> $register['login'],
							"nickname"		=> $register['login'],
							"email"			=> $register['email'],
							"registered"	=> [ "NOW", "" ],
							"groups"		=> ($this->configExt['reg_require_email']) ? 0 : 1,
							"resettoken"	=> $token."//".time(),
						];
		$userID = $this->insertArray("tbl_users", $data);
		
		//$userID = $this->DB->lastStats['insertID'];
		

		// Set password with the pre-built function
		$this->userChangePW($userID, $register['password1']);

		// If the account is set for moderation
		if( isset($this->regModeration) )
		{
			//echo $this->regModeration;
			
			$this->update
			(
				"tbl_users",
				[
					"groups"		=> 0,						// Revoke login permission (if set)
					"resettoken"	=> NULL,				// Revoke the token
					"about"			=> $_SERVER['REMOTE_ADDR'].'#'.$this->regModeration	// Remember why this user is on moderation
				],
				[ "uid=?", $userID ]
			);
			$extra = "moderation,";			// and tell the user (s)he is
		}
		elseif( $this->configExt['reg_require_email'] )
		{
			if 
			(
				!sendmail
				(
					array($this->request['post']['login'], $this->request['post']['email']), 
					"__AccountActivation", 
					$this->TPL->buildBlock
					(
						"controlpanel",
						"mailActivateAccount",
						[
							"USERNAME"	=>	$this->request['post']['login'],
							"TOKEN"			=>	$token,
							"UID"				=>	$userID,
							"BASEURL"		=>	__baseURL__
						]
					)
				)
			) $extra = "nomail,";
		}
		$hash = $this->exec("SELECT MD5(CONCAT(U.uid,U.registered)) as md5 FROM `tbl_users`U WHERE U.uid = ".$userID)[0]['md5'];
		
		// add a log entry
		
		return $extra.$hash;
	}
}