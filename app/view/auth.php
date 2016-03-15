<?php
namespace View;

class Auth extends Base
{
	public static function loginError($f3)
	{
		$data =
		[
			"returnpath" 	=>	(""==$f3->get('POST.returnpath')) ? $f3->get('PATH') : $f3->get('POST.returnpath'),//['returnpath'],
			"BASE"			=>	$f3->get('BASE'),
			"allow_registration" => $f3->get('CONFIG')['allow_registration'],
		];

		if( sizeof($f3->get('POST'))>0 )
		{
			if(""==$f3->get('POST.login') || ""==$f3->get('POST.password')) 
			{
				$data['login']['error'] = "No data";
			}
			else
			{
				$data['login']['error'] = "No match";
			}
		}

		return \Template::instance()->render('main/login.html','text/html', $data);
	}

	public static function loginSuccess(\Base $f3)
	{
		$data =
		[
			"returnpath" 	=>	$f3->get('POST.returnpath'),//['returnpath'],
			"BASE"			=>	$f3->get('BASE'),
			"success"		=>	TRUE,
		];

		return \Template::instance()->render('main/login.html','text/html', $data);
	}
	
}
