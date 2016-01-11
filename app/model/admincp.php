<?php
namespace Model;

class AdminCP extends Base
{
	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu(FALSE,TRUE);
	}

	public function showMenu($selected=FALSE)
	{
		if ( $selected )
		{
			$this->menu[$selected]["sub"] = $this->panelMenu($selected,TRUE);
		}
		return $this->menu;
	}

	
}