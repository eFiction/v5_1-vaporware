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
	
	protected function buffer($content, $section="BODY", $destroy = FALSE)
	{
		if ( $destroy )
			$this->data[$section]  = $content;
		else
			$this->data[$section] .= $content;
	}
	
	protected function parametric($params=NULL)
	{
		$r = [];
		if ( $pArray = explode(";", str_replace(["/","&"],";",$params) ) )
		{
			foreach ( $pArray as $pKey => $pElement )
			{
				$x = explode ( "=", $pElement );
				if ( isset($x[1]) )
				{
					$r[$x[0]] = explode(",",$x[1]);
					if ( sizeof($r[$x[0]])==1 ) $r[$x[0]] = $x[1];
				}
				else
				{
					$r[$x[0]] = TRUE;
					$r[$pKey] = $x[0];
				}
			}
		}

		if(isset($r['page'])) \Base::instance()->set('paginate.page', max(1,$r['page']));
		return $r;
	}

	public function mailman($subject, $mailText, $rcpt_mail, $rcpt_name=NULL)
	{
		if ( $this->config->smtp_server!="" )
		{
			$smtp = new \SMTP ( 
						$this->config->smtp_server, 
						$this->config->smtp_scheme, 
						$this->config->smtp_port=="" ? ( $this->config->smtp_scheme=="ssl" ? 465 : 587 ) : $this->config->smtp_port,
						$this->config->smtp_username, 
						$this->config->smtp_password
			);
			$smtp->set('From', '"'.$this->config->page_title.'" <'.$this->config->page_mail.'>');
			$smtp->set('To', '"'.$rcpt_name.'" <'.$rcpt_mail.'>');
			$smtp->set('Subject', $subject);
			$smtp->set('content_type', 'text/html; charset="utf-8"');
			
			$sent = $smtp->send($mailText, TRUE);
			//$mylog = $smtp->log();
			//echo '<pre>'.$smtp->log().'</pre>';
		}
		else
		{
			$headers   = array();
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-Type: text/html; charset=utf-8";
			$headers[] = "From: {$this->config->page_title} <{$this->config->page_mail}>";
			$headers[] = "X-Mailer: PHP/".phpversion();
			
			$sent = mail(
				"{$rcpt_name} <{$rcpt_mail}>",	// recipient
				$subject,							// subject
				$mailText,											// content
				implode("\r\n", $headers)							// headers
			);
		}
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
