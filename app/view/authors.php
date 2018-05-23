<?php
namespace View;

class Authors extends Base
{

	public function page($header, $menu, $content)
	{
		$this->f3->set('header',  $header);
		$this->f3->set('letters', $menu);
		$this->f3->set('content', $content);
		
		return $this->render('authors/main.html');
	}
	
	public function list($list, $letter=NULL)
	{
		// common definitions
		$this->javascript('body', TRUE, 'jquery.columnizer.js' );
		$this->f3->set('listing',  $list);
		
		// List authors for a specific letter
		if ( $letter )
		{
			$columns = min ( 3, ceil (sizeof($list)/5) );
			$this->javascript('body', FALSE, "$(function(){ $('.author-grid-wrapper').addClass(\"dontsplit\"); $('.columnize').columnize({ columns: {$columns}, lastNeverTallest: true }); });" );

			$this->f3->set('letter',   $letter);
			$this->f3->set('viewtype', 'small');
		}
		// List all authors
		else
		{
			$this->javascript('body', FALSE, "$(function(){ $('.author-grid-wrapper').addClass(\"dontsplit\"); $('.columnize').columnize({ width: 200, lastNeverTallest: true }); });" );

			$this->f3->set('viewtype', 'full');
		}

		return $this->render('authors/listing.html');
	}

}