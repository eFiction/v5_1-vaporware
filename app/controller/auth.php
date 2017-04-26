<?php
namespace Controller;

class Auth extends Base {

	public function __construct()
	{
		$this->model = \Model\Auth::instance();
		$this->config = \Base::instance()->get('CONFIG');
		
		\Base::instance()->set('AUTHPAGE', TRUE);
	}

    protected $response;

    /**
     * check login state
     * @return bool
     */
    static public function isLoggedIn(\Base $f3)
	{
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
		else $session_id = \Model\Auth::instance()->createSession();

		if ( isset($session_id) && $user = \Model\Auth::instance()->validateSession($session_id) AND $user['userID']>0 )
		{
			$_SESSION['groups'] 		= $user['groups'];
			//$_SESSION['admin_active'] 	= $user['admin_active'];
			$_SESSION['userID']			= $user['userID'];
			$_SESSION['username']		= $user['nickname'];
			$_SESSION['mail']			= array($user['cache']['messaging']['inbox']['sum'],$user['cache']['messaging']['unread']['sum']);
			$_SESSION['allowed_authors']= explode(",",$user['allowed_authors']);
			$_SESSION['preferences']	= $user['preferences'];
			$_SESSION['tpl']			= [ "default", 1];

			return TRUE;
		}
		else
		{
			//getStats();
			//
			$_SESSION['groups'] 		= bindec('0');
			//$_SESSION['admin_active'] 	= FALSE;
			$_SESSION['userID']			= FALSE;
			$_SESSION['username']		= "__Guest";
			$_SESSION['mail']			= FALSE;
			$_SESSION['allowed_authors']= [];
			$_SESSION['preferences']	= ["language" => $f3->get('CONFIG.language_default')];
			$_SESSION['tpl']			= [ "default", 1];
			
			return FALSE;
		}
	}
	
	public function login($f3,$params)
	{
		if ( isset($params['*']) ) $params = ($this->parametric($params['*']));  // 3.6
		\Registry::get('VIEW')->addTitle( $f3->get('LN__Login') );
		
		if( $f3->exists('POST.login') && $f3->exists('POST.password') )
		{
			if ( $userID = $this->model->userLoad($f3->get('POST.login'), $f3->get('POST.password') ) )
			{
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
		elseif( isset($params['activate']) )
		{
			if( empty($params['activate'][1]) )
			{
				// bad
				$this->buffer( "Bad token" );
				return FALSE;
			}
			if ( TRUE === $this->model->newuserEmailLink($params['activate']) )
				$this->buffer( "Activating" );
			else
				$this->buffer( "Failing" );
		}

		else
			$this->buffer( \View\Auth::loginError($f3) );
	}
	
	protected function recoveryMail(\Base $f3)
	{
		$recovery = $this->model->userRecovery($f3->get('POST.username'), $f3->get('POST.email'));
		if ( $recovery )
		{
			$token = $this->model->setRecoveryToken($recovery['uid']);
			
			$this->buffer( \View\Auth::loginMulti($f3, "lostpw") );
			
			$mailText = \View\Auth::lostPWMail($f3, $recovery, $token);
			
			return $this->mailman($f3->get('LN__PWRecovery'), $mailText, $recovery['email'], $recovery['nickname']);
		}
		return FALSE;
	}
	
	protected function recoveryForm(\Base $f3, $token)
	{
		if ( TRUE === $token OR $user = $this->model->getRecoveryToken($token) )
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
				if ( $f3->exists('POST.token') AND ""!=$f3->get('POST.token') )
					$f3->set('resettoken',$f3->get('POST.token'));
				elseif (TRUE===$token)
					$f3->set('resettoken','');
				else
					$f3->set('resettoken',$token);

				$this->buffer( \View\Auth::loginMulti($f3, "tokenform") );
			}
		}
		else
		{
			// some error message
			$this->buffer( "__tokenInvalid" );
		}
	
	}
	
	public function logout($f3,$params)
	{
		$return = explode("returnpath=",@$params['*']);
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
		if( FALSE == \Config::getPublic('allow_registration') )
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

				$check = $this->model->registerCheckInput($formData);
				if ( $check['count']==0 )
				{
					if ( $check['status']==2 )
					{
						// kicked by SFS check
						// might want to give different replies based on $check['reason']
						$_SESSION['lastAction'] = [ "registered" => "failed" ];
						
						// log this attempt
						\Logging::addEntry("RF", json_encode([ 'name'=>$formData['login'], 'email'=>$formData['email'], 'reason'=>$check['reason'] ]),0);
					}
					else
					{
						$token = md5(time());
						$formData['groups'] = 0;

						if ( $check['status']<1 AND FALSE == \Config::getPublic('reg_require_mod') )
						{
							// either passed SFS check, or SFS is disabled
							if( TRUE == \Config::getPublic('reg_require_email') )
							{
								$status = -2;
								$check['next'] = "mail";
							}
							else
							{
								$status = FALSE;
								$formData['groups'] = 1;
								$check['next'] = "done";
							}
						}
						else
						{
							// put on moderation by SFS check or by admin setting
							$status = -3;
							$check['next'] = "moderation";
						}
						
						$userID = $this->model->addUser($formData);

						if ( $status == -2 )
						{
							$token =  md5(time()+mt_rand());
							$mailText = \View\Auth::registerMail($formData, $userID.",".$token);

							if ( TRUE == $this->mailman($f3->get('LN__Registration'), $mailText, $formData['email'], $formData['login']) )
							{
								$mod = $token;
							}
							else
							{
								$check['reason'] = "mailfail";
								$mod = json_encode($moderation);
								$status = -3;
								$check['next'] = "mailfail";
							}
						}
						else $mod = json_encode($moderation);

						if ( $status!==FALSE )
							$this->model->newuserSetStatus($userID, $status, $mod);
						
						// Set password with the pre-built function
						$this->model->userChangePW($userID, $formData['password1']);

						$_SESSION['lastAction'] = [ "registered" => $check['next'] ];
						
						\Logging::addEntry("RG", json_encode([ 'name'=>$formData['login'], 'uid'=>$userID, 'email'=>$formData['email'], 'reason'=>$check['reason'], 'admin'=>FALSE ]),$userID);
					}
					$formData = [];
					
				}

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
