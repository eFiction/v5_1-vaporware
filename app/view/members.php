<?php
namespace View;

class Members extends Base
{
	public function __construct()
	{
		// invoke parent constructor
		parent::__construct();

		if( isset($_SESSION['lastAction']) )
		{
			foreach( $_SESSION['lastAction'] as $key => $value )
				$this->f3->set($key,$value);
			unset($_SESSION['lastAction']);
		}
		
		$this->javascript( 'body', FALSE, 'document.addEventListener(\'DOMContentLoaded\', () => {
								  (document.querySelectorAll(\'.notification .delete\') || []).forEach(($delete) => {
									$notification = $delete.parentNode;
									$delete.addEventListener(\'click\', () => {
									  $notification.parentNode.removeChild($notification);
									});
								  });
								});'
		);
	}
	
	public function profile(array $data)
	{
		$this->f3->set('data', $data);
		return $this->render('members/profile.html');
		//return "<pre>".print_r($data,TRUE)."</pre>";
	}

	public function stories(array $userdata, array $extradata)
	{
		$this->f3->set('userdata', $userdata);
		
		if ( sizeof(@$extradata['stories']) )
			while ( list($key, $value) = each($extradata['stories']) )
				$this->dataProcess($extradata['stories'][$key], $key);

		$this->f3->set('extradata', $extradata);
		return $this->render('members/stories.html');
	}
	
	public function collections(array $userdata, string $type, array $collections)
	{
		$this->f3->set('userdata', $userdata);
		
		if ( sizeof($collections) )
			while ( list($key, $value) = each($collections) )
				$this->dataProcess($collections[$key], $key);

		$this->f3->set('type', $type);
		$this->f3->set('collections', $collections);
		return $this->render('members/collections.html');
	}
	
	public function listBookFav(array $userdata, array $extradata)
	{
		$this->f3->set('userdata', $userdata);

		$this->f3->set('extradata', $extradata);
		return $this->render('members/bookfav.html');
	}

}
