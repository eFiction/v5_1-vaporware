<?php
namespace Controller;

class Auth extends Base {

	public function __construct()
	{
		$this->model = \Model\Auth::instance();
		//$this->view = new \View\Story;
	}

    protected $response;

    /**
     * check login state
     * @return bool
     */
    static public function isLoggedIn()
	{
        /** @var Base $f3 */
        $f3 = \Base::instance();
		$ip_db = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));

		/*
		Session mask (bit-wise)

		- admin						 128
		- super mod					64
		- story mod 				32
		- lector						16
		- author (trusted)	 8
		- author (regular)	 4
		- user (trusted)		 2
		- user (active)			 1
		- guest							 0
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
			$_SESSION['groups'] 			= $user['groups'];
			$_SESSION['admin_active'] = $user['admin_active'];
			$_SESSION['userID']			= $user['userID'];
			$_SESSION['username']			= $user['nickname'];
			$_SESSION['mail']					= array($user['mail'],$user['unread']);
			$_SESSION['tpl']		= [ "default", 1];
			
			return TRUE;
		}
		else
		{
			//getStats();
			//
			$_SESSION['groups'] 			= bindec('0');
			$_SESSION['admin_active'] = FALSE;
			$_SESSION['userID']			= FALSE;
			$_SESSION['username']			= "__Guest";
			$_SESSION['mail']					= FALSE;
			$_SESSION['tpl']		= [ "default", 1];
			
			return FALSE;
		}
	}
	
	public function login($f3,$params)
	{
		\Registry::get('VIEW')->addTitle('__Login');
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

		$this->model->userSession(NULL);
		unset($_SESSION['session_id']);
		unset($_COOKIE['session_id']);
		setcookie("session_id", "", time()-1, $f3->get('BASE') );
		//session_destroy();
		
		$f3->reroute($returnpath, false);
	}
	
	public function register(\Base $f3, $params)
	{
		$this->buffer( "stub *controller-auth-register*" );
	}

}
