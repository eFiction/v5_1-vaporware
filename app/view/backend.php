<?php
namespace View;

class Backend extends Template
{

	public function __construct()
	{
		parent::__construct();
		
		$this->f3->copy('BACKEND_UI','UI');
	}

	/*
		Base render function wrapper
	*/
    public function finish()
	{
        /** @var \Base $f3 */
		if($this->data)
            $this->f3->mset($this->data);

		$this->f3->set( 'JS_HEAD', @implode("\n", @$this->f3->JS['head']) );
		$this->f3->set( 'JS_BODY', @implode("\n", @$this->f3->JS['body']) );
		
		$this->f3->set('TITLE', $this->config['page_title'].$this->config['page_title_separator'].$this->config['page_slogan']);

		$this->f3->set('DEBUGLOG', $this->f3->get('DB')->log());
		$Iconset = Iconset::instance();
		
		return preg_replace_callback(
								'/\{ICON:([\w-]+)(:|\!)?(.*?)\}/s',	// for use with forced visibility
								function ($icon)
								{
									return Iconset::parse($icon);
								}
								, \Template::instance()->render('index.html')
							);

		return $body;
    }

	
}
