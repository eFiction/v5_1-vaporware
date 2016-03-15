<?php

namespace Controller;

//abstract class Base {
class Base extends \Prefab {

	protected $response;
	protected $data = array ( "BODY" => "", "RIGHT" => "", "LEFT" => "" );

	/**
	 * set a new view
	 * @param \View\Base $view
	 
	public function setView(\View\Base $view) {
		$this->response = $view;
	}
*/

	/**
	 * init the View
	 */
	public function beforeroute()
	{
		if(\Base::instance()->get('AJAX')===TRUE)
			$this->response = new \View\JSON();

		else
			$this->response = new \View\Frontend();

		\Registry::set('VIEW',$this->response);
	}
	
	protected function buffer($content, $section="BODY")
	{
		$this->data[$section] .= $content;
	}
	
	protected function parametric($params=NULL)
	{
		if ( $pArray = explode(";", str_replace("/",";",$params) ) )
		{
			foreach ( $pArray as $pElement )
			{
				$x = explode ( "=", $pElement );
				if ( isset($x[1]) )
				{
					$r[$x[0]] = explode(",",$x[1]);
					if ( empty($r[$x[0]][1]) ) $r[$x[0]] = $x[1];
				}
				else $r[$x[0]] = TRUE;
			}
		}
		if(isset($r['page'])) \Base::instance()->set('paginate.page', max(1,$r['page']));
	}

	/**
	 * kick start the View, which creates the response
	 * based on our previously set content data.
	 * finally echo the response or overwrite this method
	 * and do something else with it.
	 * @return string
	 */
	public function afterroute() {
		if (!$this->response)
			trigger_error('No View has been set.');
		
		$this->response->data = $this->data;
		echo $this->response->finish();
	}
}