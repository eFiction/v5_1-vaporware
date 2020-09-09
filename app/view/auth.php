<?php
namespace View;

class Auth extends Base
{
	public function loginForm($f3)
	{
		\Base::instance()->set('loginform', TRUE );
		if( sizeof($f3->get('POST'))>0 )
		{
			if(""==$f3->get('POST.login') && ""==$f3->get('POST.password')) 
			{}
			
			elseif(""==$f3->get('POST.login') || ""==$f3->get('POST.password')) 
				\Base::instance()->set('error', 'Login_NoData' );

			else
				\Base::instance()->set('error', 'Login_NoMatch' );

			}
		$f3->set('returnpath', (""==$f3->get('POST.returnpath')) ? $f3->get('PATH') : $f3->get('POST.returnpath') );

		return $this->render('main/login.html','text/html');
	}

	public static function loginMulti(\Base $f3, $switch)
	{
		switch ($switch)
		{
			case "success":
				\Base::instance()->set('success', TRUE );
				break;
			case "lostpw":
				\Base::instance()->set('lostpw', TRUE );
				break;
			case "changed":
				\Base::instance()->set('changed', TRUE );
				break;
			case "tokenform":
				\Base::instance()->set('tokenform', TRUE );
				break;
		}

		\Base::instance()->set('returnpath', (""==$f3->get('POST.returnpath')) ? $f3->get('PATH') : $f3->get('POST.returnpath') );

		return \Template::instance()->render('main/login.html','text/html');
	}

	public static function lostPWMail(\Base $f3, $user, $token)
	{
		\Base::instance()->set('username', $user['username']);
		\Base::instance()->set('token', $token);
		
		return \Template::instance()->render('email/lostpw.html','text/html');
	}

	public function register($data = [], $error = [])
	{
		if( isset($_SESSION['lastAction']) )
		{
			$this->f3->set(key($_SESSION['lastAction']),current($_SESSION['lastAction']));
			unset($_SESSION['lastAction']);
		}

		$this->f3->set('data', $data);
		$this->f3->set('error', $error);

		return $this->render('main/register.html');
	}
	
	public function registerMail($user, $token)
	{
		$this->f3->set('username', $user['login']);
		$this->f3->set('token', $token);
		
		return $this->render('email/activation.html','text/html');
	}

	public static function captchaF3()
	{
		ob_start();
			$img = new \Image();
			$img->captcha('template/captchaFonts/Browning.ttf',16,5,'SESSION.captcha');
			$_SESSION['captcha'] = password_hash($_SESSION['captcha'], PASSWORD_DEFAULT);

			$img->render();
			$image_data = ob_get_contents();
		ob_end_clean();

		echo base64_encode($image_data);
	}
	
	public static function captchaEfiction()
	{
		$image = imagecreate(150, 40);

		$white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
		$gray    = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);
		$darkgray = imagecolorallocate($image, 0x50, 0x50, 0x50);
		$black = imagecolorallocate($image, 0x00, 0x00, 0x00);

		srand((double)microtime()*1000000);
		
		// Reduced to all characters that are visually distinct
		$cnum = explode(" ", "A B C D E F G H J K M N Q R T U V W X Y 2 3 4 5 7 8");
		shuffle ( $cnum );
		$cnum = array_slice( $cnum, 0, 5);
		
		$_SESSION['captcha'] = password_hash(implode("", $cnum), PASSWORD_DEFAULT);

		//The directory where your fonts reside
		$folder=dir("template/captchaFonts/");
		while($font=$folder->read())
		{
			if(stristr($font,'.ttf')) $fontList[] = "template/captchaFonts/".$font;
		}
		$folder->close();

		/* generate random dots in background */
		for( $i=0; $i<(150*40)/3; $i++ ) {
			imagefilledellipse($image, mt_rand(0,150), mt_rand(0,40), 1, 1, $gray);
		}

		foreach ( $cnum as $i => $char)
		{
			$rColors[$i] = imagecolorallocate($image, mt_rand(0, 175), mt_rand(0, 175), mt_rand(0, 175) );

			for ($x = 0; $x < 2; $x++)
			{
				$x1 = rand(0,150);
				$y1 = rand(0,40);
				$x2 = rand(0,150);
				$y2 = rand(0,40);
				imageline($image, $x1, $y1, $x2, $y2 , $rColors[$i]);  
			}

			$x = ($i*28) + mt_rand(5,15);
			$y = mt_rand(26, 32); 
			$angle = mt_rand(-15, 15);
			$c = $rColors[$i];
			$fnt = mt_rand(0, sizeof($fontList) - 1);
			$colori = $rColors[$i];
			imagettftext($image, mt_rand(20, 24), $angle,  $x, $y, $colori, $fontList[$fnt], $char);
		}

		/*
		$ts = gmdate("D, d M Y H:i:s") . " GMT";
		header("Expires: $ts");
		header("Last-Modified: $ts");
		header("Pragma: no-cache");
		header("Cache-Control: no-store, no-cache, must-revalidate");  
		header("Pragma: no-cache");
		header('Content-type: image/png');
*/
		ob_start();
		imagepng($image);
		$image_data = ob_get_contents();
		ob_end_clean();

		imagedestroy($image);
		
		echo base64_encode($image_data);
	}

	
}
