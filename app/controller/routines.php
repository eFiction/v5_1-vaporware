<?php
namespace Controller;

/*
	This class offers routines that can be triggered based on certain events
*/

class Routines extends Base {

	public function __construct()
	{
		$this->model = \Model\Routines::instance();
		$this->config = \Base::instance()->get('CONFIG');
	}

	public function notification($type, $id)
	{
		if ( FALSE===$this->config['mail_notifications'] )
			return FALSE;

		if ( $type == "ST" )
		{
			// an author has received a review for a story
			// @ $id = story ID
			$authorData = $this->model->noteReview( $id );
			if ( sizeof($authorData)>0 )
			{
				foreach ( $authorData as $author )
				{
					$subject = $this->config['page_title']."Test Review";
					$mailText = "Test eMail, sinnlos";
					//$this->mailman($subject, $mailText, $author['email'], $author['mailname']);
				}
			}
			return sizeof($authorData);
		}
		elseif ( $type == "C" )
		{
			// an author has received a comment for a review
			// @ $id = feedback ID
			$authorData = $this->model->noteComment( $id );
			if ( sizeof($authorData)>0 )
			{
				$subject = $this->config['page_title']."Test Comment";
				$mailText = "Test eMail, sinnlos";
				//$this->mailman($subject, $mailText, $authorData[0]['email'], $authorData[0]['mailname']);
			}
			return sizeof($authorData);
		}
	}
}