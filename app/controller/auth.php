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
        $fw = \Base::instance();
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

		if ( $fw->exists('SESSION.session_id') )
				$session_id = $fw->get('SESSION.session_id');
		elseif ( isset ($_COOKIE['session_id']) )
		{
				$session_id = $_COOKIE['session_id'];
				$fw->set('SESSION.session_id', $session_id);
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
	
	public function login($fw,$params)
	{
		\Registry::get('VIEW')->addTitle('__Login');
		if( $fw->exists('POST.login') && $fw->exists('POST.password') )
		{
			if ( $userID = $this->model->userLoad($fw->get('POST.login'), $fw->get('POST.password') ) )
			{
				/*
				$this->buffer( \View\Auth::loginSuccess($fw) );
				*/
				$fw->reroute($fw->get('POST')['returnpath'], false);
				exit;
			}
		}

		else
			$this->buffer( \View\Auth::loginError($fw) );
	}
	
	public function logout($fw,$params)
	{
		$return = explode("returnpath=",$params[1]);
		$returnpath = ( isset($return[1]) AND $return[1]!="") ? $return[1] : "/";

		$this->model->userSession(NULL);
		unset($_SESSION['session_id']);
		unset($_COOKIE['session_id']);
		setcookie("session_id", "", time()-1, $fw->get('BASE') );
		//session_destroy();
		
		$fw->reroute($returnpath, false);
	}

}
