<?php
namespace View;

class Backend extends Base
{

	public function __construct()
	{
		$fw = \Base::instance();

		$fw->copy('BACKEND_UI','UI');
		
		$fw->set('SELF', rawurlencode($_SERVER["QUERY_STRING"]));

		//\View\Base::javascript('body', TRUE, 'global.js' );
		//$this->iconset = Iconset::instance();
	}

	/*
		Base render function wrapper
	*/
    public function finish()
	{
        /** @var \Base $f3 */
        $fw = \Base::instance();
		$cfg = $fw->get('CONFIG');

		if($this->data)
            $fw->mset($this->data);

		if ( isset($this->JS['head']) ) $fw->set( 'JS_HEAD', implode("\n", $this->JS['head']) );
		if ( isset($this->JS['body']) ) $fw->set( 'JS_BODY', implode("\n", $this->JS['body']) );
		
		$fw->set('TITLE', implode($cfg['page_title_separator'], array_merge([$cfg['page_title']],$this->title) ) );

		$fw->set('DEBUGLOG', $fw->get('DB')->log());

		return preg_replace_callback(
								'/\{ICON:([\w-]+)\}/s',
								function ($icon)
								{
									return Iconset::instance()->{$icon[1]};
									//return "#";
								}
								, \Template::instance()->render('layout.html')
							);

		return $body;
    }

	
}