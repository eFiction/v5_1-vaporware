<?php

namespace Controller;

class Authors extends Base {
	
	public function __construct()
	{
		$this->model = \Model\Authors::instance();
	}

	public function index(\Base $f3, $params)
	{
		if ( isset($params[2]) ) $this->parametric($params[2]);
		
		// Build menu letters
		$letters = $this->model->letters();
		$menu = $this->model->menuLetters($letters);

		// set header
		$header[] = "__authors";

		if ( empty($params['id']) )
		{
			// Load list of authors
			$data = $this->model->getAuthors();
			// build view
			$content = \View\Authors::fullList($data);
			// switch off right sidebar
			\Base::instance()->set('bigscreen', TRUE);
		}
		elseif ( preg_match("/[a-zA-Z#].*/", $params['id']) )
		{
			// load list of authors starting with letter
			$letter = $params['id'][0];
			$data = $this->model->getAuthors($letter);
			// build view
			$content = \View\Authors::letterList($letter, $data);
		}
		elseif ( is_numeric($params['id']) )
		{
			$this->buffer ( "{BLOCK:profile.{$params['id']}}", "RIGHT" );
			list($authorInfo, $content) = \Controller\Story::instance()->author($params['id']);//$this->profile();

			$header[] = $authorInfo;

			//$this->buffer ( $content );
			//return TRUE;
		}

		// output
		$this->buffer ( \View\Authors::page($header , $menu, $content) );
	}
	/*
	protected function letterList($letter)
	{
		$data = $this->model->getAuthors($letter);
		return  "letter";
	}
	*/
	protected function profile()
	{
		return "profile";
	}
	
}