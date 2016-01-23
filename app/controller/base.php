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