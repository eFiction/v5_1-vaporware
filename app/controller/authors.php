<?php

namespace Controller;

class Authors extends Base {
	
	public function __construct()
	{
		$this->model = \Model\Authors::instance();
	}

	public function index(\Base $fw, $params)
	{
		// Build menu letters
		$letters = $this->model->letters();
		$menu = $this->model->menuLetters($letters);

		// set header
		$header[] = "__authors";

		if ( empty($params[1]) )
		{
			// Load list of authors
			$data = $this->model->getAuthors();
			// build view
			$content = \View\Authors::fullList($data);
			// switch off right sidebar
			\Base::instance()->set('bigscreen', TRUE);
		}
		elseif ( preg_match("/[a-zA-Z#].*/", $params[1]) )
		{
			// load list of authors starting with letter
			$letter = $params[1][0];
			$data = $this->model->getAuthors($letter);
			// build view
			$content = \View\Authors::letterList($letter, $data);
		}
		elseif ( is_numeric($params[1]) )
		{
			$this->buffer ( "{BLOCK:profile.{$params[1]}}", "RIGHT" );
			list($authorInfo, $content) = \Controller\Story::instance()->author($params[1]);//$this->profile();

			$header[] = $authorInfo;

			//$this->buffer ( $content );
			//return TRUE;
		}

		// output
		$this->buffer ( \View\Authors::page($header , $menu, $content) );
	}
	
	protected function letterList($letter)
	{
		$data = $this->model->getAuthors($letter);
		return  "letter";
	}
	
	protected function profile()
	{
		return "profile";
	}
	
}