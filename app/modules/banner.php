<?php
/*
	This is a simple demo class
	It can be called anywhere in the template with
	{{ \Modules\Banner::rotate()}}
	And will show something
	It can make use of all class functions
*/

// Named wrapper for static calls
namespace Modules;

class Banner extends \Controller\Base
{
	public static function rotate()
	{
		return \Controller\Banner::instance()->rotate();
	}

}

// MVC definitions
namespace Controller;

class Banner extends Base
{
	public function __construct()
	{
		$this->model = \Model\Banner::instance();
	}
	
	public function rotate()
	{
		return \View\Banner::rotate($this->model->load());
	}
	
	
}

namespace Model;

class Banner extends Base
{
	public function load()
	{
		return 3;
	}

}


namespace View;

class Banner extends Base
{
	public static function rotate($data)
	{
		return "Banner".$data;
	}

}

?>