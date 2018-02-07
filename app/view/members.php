<?php
namespace View;

class Members extends Base
{
	public function profile(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('members/profile.html');
		//return "<pre>".print_r($data,TRUE)."</pre>";
	}

}
