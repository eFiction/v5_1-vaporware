<?php
namespace Model;

class UserCP extends Base
{
	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu();
	}

	public function showMenu($selected=FALSE)
	{
		if ( $selected )
		{
			$this->menu[$selected]["sub"] = $this->panelMenu($selected);
		}
		return $this->menu;
	}
}