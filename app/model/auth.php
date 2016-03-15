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

	public function userChangePW($uid, $password)
	{
		// Load a compatibility wrapper for PHP versions prior to 5.5.0
		if ( !function_exists("password_hash") ) include ( "app/inc/password_compat.php" );

		$hash = password_hash( $password, PASSWORD_DEFAULT );
		$this->prepare("updateUser", "UPDATE `tbl_users`U SET U.password = :password WHERE U.uid = :uid");
		$this->bindValue("updateUser", "password", $hash, \PDO::PARAM_STR);
		$this->bindValue("updateUser", "uid",	 $uid,	 \PDO::PARAM_INT);
		$user = $this->execute("updateUser");
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
}