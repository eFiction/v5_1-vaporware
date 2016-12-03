<?php
namespace Controller;

class Auth extends Base {

	public function __construct()
	{
		$this->model = \Model\Auth::instance();
		$this->config = \Config::instance();
	}

    protected $response;

    /**
     * check login state
     * @return bool
     */
    static public function isLoggedIn(\Base $f3)
	{
		$ip_db = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));

		/*
		Session mask (bit-wise)

		- admin			   128
		- super mod			64
		- story mod 		32
		- lector			16
		- author (trusted)	 8
		- author (regular)	 4
		- user (trusted)	 2
		- user (active)		 1
		- guest				 0
		*/

		if ( $f3->exists('SESSION.session_id') )
				$session_id = $f3->get('SESSION.session_id');
		elseif ( isset ($_COOKIE['session_id']) )
		{
				$session_id = $_COOKIE['session_id'];
				$f3->set('SESSION.session_id', $session_id);
		}
		else $session_id = \Model\Auth::instance()->createSession($ip_db);
//		echo "<br>old: ".$_SESSION['session_id'];

		if ( isset($session_id) && $user = \Model\Auth::instance()->validateSession($session_id,$ip_db) AND $user['userID']>0 )
		{
			$_SESSION['groups'] 		= $user['groups'];
			$_SESSION['admin_active'] 	= $user['admin_active'];
			$_SESSION['userID']			= $user['userID'];
			$_SESSION['username']		= $user['nickname'];
			$_SESSION['mail']			= array($user['mail'],$user['unread']);
			$_SESSION['tpl']			= [ "default", 1];
			
			return TRUE;
		}
		else
		{
			//getStats();
			//
			$_SESSION['groups'] 		= bindec('0');
			$_SESSION['admin_active'] 	= FALSE;
			$_SESSION['userID']			= FALSE;
			$_SESSION['username']		= "__Guest";
			$_SESSION['mail']			= FALSE;
			$_SESSION['tpl']			= [ "default", 1];
			
			return FALSE;
		}
	}
	
	public function login($f3,$params)
	{
		if ( isset($params[1]) ) $params = ($this->parametric($params[1]));
		\Registry::get('VIEW')->addTitle( $f3->get('LN__Login') );
		
		if( $f3->exists('POST.login') && $f3->exists('POST.password') )
		{
			if ( $userID = $this->model->userLoad($f3->get('POST.login'), $f3->get('POST.password') ) )
			{
				/*
				$this->buffer( \View\Auth::loginMulti($f3, "success") );
				*/
				$f3->reroute($f3->get('POST')['returnpath'], false);
				exit;
			}
			$this->buffer( \View\Auth::loginError($f3) );
		}
		elseif( ($f3->exists('POST.username') OR $f3->exists('POST.email')) AND $f3->get('POST.username').$f3->get('POST.email')>"" )
		{
			$this->recoveryMail($f3);
		}
		elseif( isset($params['token']) )
		{
			if ( $f3->exists('POST.token') AND ""!=$f3->get('POST.token') ) $params['token'] = $f3->get('POST.token');
			$this->recoveryForm($f3, $params['token']);
		}

		else
			$this->buffer( \View\Auth::loginError($f3) );
	}
	
	protected function recoveryMail(\Base $f3)
	{
		$recovery = $this->model->userRecovery($f3->get('POST.username'), $f3->get('POST.email'));
		if ( $recovery )
		{
			$token = md5(time());
			$dbtoken = $token."//".ip2long($_SERVER["REMOTE_ADDR"])."//".time();
			$this->model->setRecoveryToken($recovery['uid'], $dbtoken);
			
			$this->buffer( \View\Auth::loginMulti($f3, "lostpw") );
			
			$mailText = \View\Auth::lostPWMail($f3, $recovery, $token);
			
			return $this->mailman($f3->get('LN__PWRecovery'), $mailText, $recovery['email'], $recovery['nickname']);
		}
		return FALSE;
	}
	
	protected function recoveryForm(\Base $f3, $token)
	{
		if ( TRUE === $token OR $user = $this->model->getRecoveryToken($token, ip2long($_SERVER["REMOTE_ADDR"])) )
		{
			// valid token, proceed
			if ( TRUE !== $token AND $f3->exists('POST.newpassword1') AND ( TRUE === $pw_check = $this->model->newPasswordQuality( $f3->get('POST.newpassword1'), $f3->get('POST.newpassword2')) ) )
			{
				$this->model->userChangePW($user, $f3->get('POST.newpassword1'));
				$this->model->dropRecoveryToken($user);
				$this->buffer( \View\Auth::loginMulti($f3, "changed") );
			}
			else
			{
				//print_r($pw_check);
				if ( $f3->exists('POST.token') AND ""!=$f3->get('POST.token') )
					$f3->set('resettoken',$f3->get('POST.token'));
				elseif (TRUE===$token)
					$f3->set('resettoken','');
				else
					$f3->set('resettoken',$token);

				$this->buffer( \View\Auth::loginMulti($f3, "tokenform") );
				//echo $pw_check;
			}
			//$this->buffer( print_r($user,1) );
			//echo $params['token'];
		}
		else
		{
			// some error message
			$this->buffer( "__tokenInvalid" );
		}
	
	}
	
	public function logout($f3,$params)
	{
		$return = explode("returnpath=",$params[1]);
		$returnpath = ( isset($return[1]) AND $return[1]!="") ? $return[1] : "/";

		$this->model->userSession(0);
		//unset($_SESSION['session_id']);
		//unset($_COOKIE['session_id']);
		//setcookie("session_id", "", time()-1, $f3->get('BASE') );
		//session_destroy();
		
		$f3->reroute($returnpath, false);
	}
	
	public function register(\Base $f3, $params)
	{
		// check if configuration is disabled
		if( $this->config->allow_registration == FALSE )
			$this->buffer( "stub *controller-auth-register* denied" );
		
		// check if user is already logged in
		elseif ( empty($_SESSION['session_id']) )
			$this->buffer( "stub *controller-auth-register* registered" );
		
		// start registration process
		else
		{
			// Data sent ?
			if(empty($_POST['form']))
			{
				// No data yet, just create an empty form
				$this->buffer( \View\Auth::register() ); //. "stub *controller-auth-register* proceed" );
			}
			else
			{
				// We have received a form, let's work through it
				$formData = $f3->get('POST')['form'];
				if ( TRUE === $check = $this->model->registerCheckInput($formData) )
				{
					$_SESSION['lastAction'] = [ "registered" => "done" ];
					$return = $this->model->addUser($formData);
					$formData = [];
				}
				elseif ( isset($check['sfs'])==2 )
					$_SESSION['lastAction'] = [ "registered" => "failed" ];

				$this->buffer( \View\Auth::register($formData, $check) );
			}
		}
	}

	public function captcha(\Base $f3)
	{
		unset($_SESSION['captcha']);

		//\View\Auth::captchaEfiction();
		\View\Auth::captchaF3();

		exit;
	}
}
