<?php

namespace Controller;

class Page extends Base {

	public function getMain(\Base $fw, $params)
	{
		$this->buffer ( \Template::instance()->render('main/welcome.html') );
	}
	
}
?>