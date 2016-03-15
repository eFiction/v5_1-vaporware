<?php

namespace Controller;

class Page extends Base {

	public function getMain(\Base $f3, $params)
	{
		$this->buffer ( \Template::instance()->render('main/welcome.html') );
	}
	
}
?>