<?php
namespace Controller;

class AdminCP extends Base
{
	var $moduleBase = "home";
	var $hasSub		= FALSE;

	public function __construct()
	{
		$this->model = \Model\AdminCP::instance();
		\Base::instance()->set('systempage', TRUE);
	}

	public function beforeroute()
	{
		$this->response = new \View\Backend();
		\Registry::set('VIEW',$this->response);
		
		$this->response->addTitle( \Base::instance()->get('LN__AdminCP') );
		$this->hasSub = $this->showMenu($this->moduleBase);
	}

	protected function showMenu($selected=FALSE)
	{
		$menu = $this->model->showMenu($selected);
		$this->buffer
		( 
			\View\AdminCP::showMenu($menu), 
			"LEFT"
		);
		
		if ( isset($menu[$this->moduleBase]['sub']) AND sizeof($menu[$this->moduleBase]['sub'])>0 )
			\Base::instance()->set('accessSub', TRUE);
	}

	protected function moduleInit( $submodule )
	{
		$submodule = in_array ( @$submodule, $this->submodules ) ? $submodule : NULL;
		if ( $submodule )
			$s = "/{$submodule}";
		else
			$submodule = "home";
		
		if ( TRUE === $this->model->checkAccess($this->moduleBase.@$s) )
			return $submodule;
	}

	protected function showMenuUpper($selected=FALSE)
	{
		$menu = $this->model->showMenuUpper($selected);
		\Base::instance()->set('menu_upper', $menu);
		foreach ( $menu as $m ) $link[] = $m['link'];
		return $link;
	}
	
	public function fallback(\Base $f3, $params)
	{
		$f3->reroute('/adminCP/home', false);
	}
}
