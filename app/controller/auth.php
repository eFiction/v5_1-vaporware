<?php
namespace Controller;

class Auth extends Base {

	public function __construct()
	{
		$this->model = \Model\Auth::instance();
		$this->cfg = \Config::instance();
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
		\Registry::get('VIEW')->addTitle( $f3->get('LN__Login') );
		if( $f3->exists('POST.login') && $f3->exists('POST.password') )
		{
			if ( $userID = $this->model->userLoad($f3->get('POST.login'), $f3->get('POST.password') ) )
			{
				/*
				$this->buffer( \View\Auth::loginSuccess($f3) );
				*/
				$f3->reroute($f3->get('POST')['returnpath'], false);
				exit;
			}
			$this->buffer( \View\Auth::loginError($f3) );
		}

		else
			$this->buffer( \View\Auth::loginError($f3) );
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
		if (isset($params['status']))
		{
			$this->registerStatus($params['status']);
			return TRUE; //  leave function
		}

		// check if configuration is disabled
		if( $this->cfg['allow_registration'] == FALSE )
			$this->buffer( "stub *controller-auth-register* denied" );
		
		// check if user is already logged in
		elseif ( empty($_SESSION['session_id']) )
			$this->buffer( "stub *controller-auth-register* registered" );
		
		// start registration process
		else
		{
			$f3->set('register.step', 'form');
				// Data sent ?
			if(empty($_POST['form']))
			{
				// No data yet, just create an empty form
				$this->buffer( \View\Auth::register(). "stub *controller-auth-register* proceed" );
			}
			else
			{
				// We have received a form, let's work through it
				$register = $f3->get('POST')['form'];
				if ( TRUE === $check = $this->model->registerCheckInput($register) )
				{
					$f3->set('register.step', 'done');
					$return = $this->model->addUser($register);
				}
				else
				{
					// check if already agreed to the TOS
					if ( isset($check['sfs'])==2 )
					{
						$f3->set('register.step', 'failed');
					}
					$this->buffer( \View\Auth::register($register, $check) );
				}
			}
		}
	}

	protected function registerStatus($p)
	{
		// test: http://dev.efiction.org/mvc/register/status=moderation,91bc4ea33e07bf9a05d60b7c9d968115
		$parameter = $this->parametric($p);
		list($reason, $hash) = $parameter['status'];

		$check = $this->model->exec("SELECT U.uid, U.login, U.groups, U.resettoken, U.about as reason FROM `tbl_users`U WHERE MD5(CONCAT(U.uid,U.registered)) = '{$hash}';")[0];
		if ( $reason == "done" AND $check['groups'] > 0 )
		{
			$this->buffer( "** Reg done **");
		}
		elseif ( $reason == "email" AND $check['resettoken'] > "" )
		{
			$this->buffer( "** Reg email **");
		}
		elseif ( $reason == "moderation" AND $check['reason'] > "" )
		{
			$this->buffer( "** Reg moderation **");
		}
		else
		{
			$this->buffer( "** Reg unknown **");
			// Trigger an admin note
		}
		
		\Logging::addEntry("RG", "Reg user", $check['uid']);
		/*$log->reset();
		$log->log_type='AM';
		$log->save();*/
		//$this->buffer(print_r($check,1));
	}
	
	public function captcha(\Base $f3)
	{
		unset($_SESSION['captcha']);

		\View\Auth::captchaEfiction();
		//\View\Auth::captchaF3();

		exit;
	}
}
