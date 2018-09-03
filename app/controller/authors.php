<?php

namespace Controller;

class Authors extends Base {
	
	public function __construct()
	{
		$this->model = \Model\Authors::instance();
		$this->template = new \View\Authors();
	}

	public function index(\Base $f3, $params)
	{
		if ( isset($params['*']) ) $this->parametric($params['*']);
		
		// set header
		$header[] = $f3->get('LN__Authors');

		if ( empty($params['id']) )
		{
			// Build menu letters
			$letters = $this->model->letters();

			// Load list of authors
			if ( FALSE !== $data = $this->model->getAuthors() )
			{
				// build view
				$content = $this->template->listing($data);
				// switch off right sidebar
				\Base::instance()->set('bigscreen', TRUE);
			}
			else $content = NULL;
		}
		elseif ( preg_match("/[a-zA-Z#].*/", $params['id']) )
		{
			// Build menu letters
			$letters = $this->model->letters();

			// load list of authors starting with letter
			$letter = $params['id'][0];
			$data = $this->model->getAuthors($letter);
			// build view
			$content = $this->template->listing($data, $letter);
		}
		elseif ( is_numeric($params['id']) )
		{
			$this->buffer ( "{BLOCK:profile.{$params['id']}}", "RIGHT" );
			list($authorInfo, $content) = \Controller\Story::instance()->author($params['id']);//$this->profile();

			$header[] = $authorInfo;
		}

		// build letter list only if there are authors
		if(!empty($letters)) $menu = $this->model->menuLetters($letters);
		else $menu = NULL;
		
		// output
		$this->buffer ( $this->template->page($header , $menu, $content) );
	}

}
