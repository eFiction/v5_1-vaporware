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

	public function stories(array $userdata, array $extradata)
	{
		$this->f3->set('userdata', $userdata);
		
		while ( list($key, $value) = each($extradata['stories']) )
			$this->dataProcess($extradata['stories'][$key], $key);

		$this->f3->set('extradata', $extradata);
		return $this->render('members/stories.html');
	}
	
	public function listBookFav(array $userdata, array $extradata)
	{
		$this->f3->set('userdata', $userdata);

		$this->f3->set('extradata', $extradata);
		return $this->render('members/bookfav.html');
	}

}
