<?php
namespace View;

class Backend extends Base
{

	public function __construct()
	{
		$f3 = \Base::instance();

		$f3->copy('BACKEND_UI','UI');
		
		$f3->set('SELF', rawurlencode($_SERVER["QUERY_STRING"]));

		//\View\Base::javascript('body', TRUE, 'global.js' );
		//$this->iconset = Iconset::instance();
	}

	/*
		Base render function wrapper
	*/
    public function finish()
	{
        /** @var \Base $f3 */
        $f3 = \Base::instance();
		$cfg = $f3->get('CONFIG');

		if($this->data)
            $f3->mset($this->data);

		if ( isset($this->JS['head']) ) $f3->set( 'JS_HEAD', implode("\n", $this->JS['head']) );
		if ( isset($this->JS['body']) ) $f3->set( 'JS_BODY', implode("\n", $this->JS['body']) );
		
		$f3->set('TITLE', implode($cfg->page_title_separator, array_merge([$cfg->page_title],$this->title) ) );

		$f3->set('DEBUGLOG', $f3->get('DB')->log());
		
		$Iconset = Iconset::instance();

		return preg_replace_callback(
								'/\{ICON:([\w-]+)\}/s',
								function ($icon) use($Iconset)
								{
									if ( $Iconset->exists($icon[1]) )
										return $Iconset->get($icon[1]);
									return "";
									//return "#";
								}
								, \Template::instance()->render('layout.html')
							);

		return $body;
    }

	
}
