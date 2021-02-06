<?php

namespace Controller;

//abstract class Base {
class Base extends \Prefab {

	protected $response;
	protected $data = array ( "BODY" => "", "RIGHT" => "", "LEFT" => "" );

	/**
	* init the View
	*/
	public function beforeroute(): void
	{
		$f3 = \Base::instance();

		list($params, $parsedParams['returnpath']) = array_pad(explode(";returnpath=",$f3->get('PARAMS.*')), 2, '');

		if ( $pArray = explode(";", str_replace(["/","&"],";",$params) ) )
		{
			foreach ( $pArray as $pKey => $pElement )
			{
				$x = explode ( "=", $pElement );
				if ( isset($x[1]) )
				{
					$parsedParams[$x[0]] = explode(",",$x[1]);
					if ( sizeof($parsedParams[$x[0]])==1 ) $parsedParams[$x[0]] = $x[1];
				}
				else
				{
					$parsedParams[$x[0]] = TRUE;
					$parsedParams[$pKey] = $x[0];
				}
			}
		}

		$parsedParams['action'] = $f3->get('PARAMS.action');
		$parsedParams['currentpath'] = $f3->get('PARAMS.0');
		$f3->set('paginate.page', max(1,$parsedParams['page']??0));
		$f3->set('PARAMS',$parsedParams);

		if($f3->get('AJAX')===TRUE)
			$this->response = new \View\JSON();

		else
			$this->response = new \View\Frontend();

		\Registry::set('VIEW',$this->response);

		$this->config = $f3->get('CONFIG');

		$this->config['maintenance'] = 0; /* debug */

		// check if a reroute to the privacy page is required
		/*
		if
		(
			// not in maintenance mode (user can still access privacy page)
			FALSE == $this->config['maintenance']
			AND
			(
				// no privacy consent cookie set at all
				empty($f3->get('COOKIE')['privacy_consent'])
				OR
				// from EU and no GDPR consent
				( 1 == \Web\Geo::instance()->location()['in_e_u'] AND empty($f3->get('COOKIE')['gdpr_consent']) )
			)
			AND
			// not already on privacy page
			!( strpos( ($f3->get('PARAMS')[0]), "/privacy" ) === 0 )
		)
		$f3->reroute("/privacy/consent;returnpath=".$f3->get('PARAMS')[0], false);
		*/
	}

	// Add data for use in regular output
	protected function buffer(string $content, string $section="BODY", bool $destroy = FALSE): void
	{
		if ( $destroy )
			$this->data[$section]  = $content;
		else
			$this->data[$section] .= $content;
	}

	// Add data for use in JSON output
	protected function jbuffer(array $content): void
	{
		$this->data["BODY"]  = $content;
	}

	public function mailman(string $subject, string $mailText, string $rcpt_mail, string $rcpt_name=NULL): bool
	{
		if ( $this->config['smtp_server']!="" )
		{
			$smtp = new \SMTP (
						$this->config['smtp_server'],
						$this->config['smtp_scheme'],
						$this->config['smtp_port']=="" ? ( $this->config['smtp_scheme']=="ssl" ? 465 : 587 ) : $this->config['smtp_port'],
						$this->config['smtp_username'],
						$this->config['smtp_password']
			);
			$smtp->set('From', '"'.$this->config['page_title'].'" <'.$this->config['page_mail'].'>');
			$smtp->set('To', '"'.$rcpt_name.'" <'.$rcpt_mail.'>');
			$smtp->set('Subject', $subject);
			$smtp->set('content_type', 'text/html; charset="utf-8"');

			return $smtp->send($mailText, TRUE);
		}
		else
		{
			$headers   = array();
			$headers[] = "MIME-Version: 1.0";
			$headers[] = "Content-Type: text/html; charset=utf-8";
			$headers[] = "From: {$this->config['page_title']} <{$this->config['page_mail']}>";
			$headers[] = "X-Mailer: PHP/".phpversion();

			return mail(
				"{$rcpt_name} <{$rcpt_mail}>",	// recipient
				$subject,						// subject
				$mailText,						// content
				implode("\r\n", $headers)		// headers
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
	public function afterroute(): void
	{
		if (!$this->response)
			trigger_error('No View has been set.');

		$this->response->data = $this->data;
		echo $this->response->finish();

		// drop lastAction from session, it's either handled right after being set or never
		unset($_SESSION['lastAction']);
	}
}
