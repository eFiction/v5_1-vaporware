<?php
namespace View;

class Auth extends Base
{
	public static function loginError($fw)
	{
		$data =
		[
			"returnpath" 	=>	$fw->get('POST.returnpath'),//['returnpath'],
			"BASE"			=>	$fw->get('BASE'),
			"allow_registration" => $fw->get('CONFIG')['allow_registration'],
		];

		if( ""==$fw->get('POST.login') || ""==$fw->get('POST.password') )
		{
			$data['login']['error'] = "No data";
		}
		else
		{
			$data['login']['error'] = "No match";
		}
		return \Template::instance()->render('main/login.html','text/html', $data);
	}
}
