<?php
namespace Controller;

class Privacy extends Base
{
	var $eu_gdpr = FALSE;
	
	public function __construct()
	{
		//$this->model = \Model\Privacy::instance();
		$this->config = \Config::instance();
		$this->template = new \View\Privacy();
	}
	
	public function beforeroute()//: void
	{
		parent::beforeroute();
		\Registry::get('VIEW')->addTitle( \Base::instance()->get('LN__Privacy') );
	}

	public function index(\Base $f3, array $params)
	{
		$params = $this->parametric($params['*']);
		// Privacy page header
		if ( isset($params['consent']) )
		{
			// show 
			
		}
		if ( FALSE == $this->config['maintenance'] )
		{}
		$in_EU = \Web\Geo::instance()->location()['in_e_u'];
		//var_dump($in_EU);
		//if ( $this->config['require_eu_gdpr'] == 1 ) echo "EU";
		
		
	}
}

?>
