<?php
namespace View;
class Redirect extends Base
{
	public function inform($redirect)
	{
		$this->f3->set('redirect', $redirect);
		return $this->render('main/redirect.html');
	}
}
