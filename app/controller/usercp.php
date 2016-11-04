<?php
namespace Controller;

class UserCP extends Base
{
	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		$this->config = \Base::instance()->get('CONFIG');
		\Base::instance()->set('systempage', TRUE);
	}
	
	public function beforeroute()
	{
		parent::beforeroute();
		$this->response->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $f3, $params)
	{
		$modules =
		[
			"library"		=> "library",
			"messaging"		=> "messaging",
			"author"		=> "author",
			"feedback"		=> "feedback",
			"settings"		=> "settings",
		];
	
		$p = array_pad(@explode("/",$params[1]),2,NULL);
		$mod = array_shift($p);

		if(@array_key_exists($mod,$modules))
		{
			list($params, $returnpath) = array_pad(explode(";returnpath=",implode("/",$p)), 2, '');
			$params = $this->parametric($params);
			$params['returnpath'] = $returnpath;
			$this->{$modules[$mod]}($f3, $params);
		}
		// Just show default menu
		else
			$this->showMenu();
	}

	public function ajax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;
		
		$post = $f3->get('POST');
		
		if ( $params['module']=="messaging" )
		{
			$data = $this->model->ajax("messaging", $post);
		}
		echo json_encode($data);
		exit;
	}
	
	public function author(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );

		$buffer = NULL;
		
		if ( $_SESSION['groups']&5 OR TRUE === $this->config->author_self )
		{
			if ( array_key_exists("curator", $params) )
				$buffer = $this->authorCurator($f3, $params);
			
			elseif ( array_key_exists("uid", $params) AND isset ($params[1]) )
			{
				$buffer = print_r($params,1);
			}
		}
		
		$this->buffer ( ($buffer) ?: $this->authorHome( $f3, $params) );

		$this->showMenu("author", $params);
	}
	
	protected function authorCurator(\Base $f3, $params)
	{
		return "curator";
	}

	protected function authorHome(\Base $f3, $params)
	{
		return \View\Base::stub("home");
	}

	public function feedback(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Reviews') );

		$sub = [ "reviews", "comments", "shoutbox" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		// delete function get's accompanied by a pseudo-post, this doesn't count here. Sorry dude
		if( NULL != $post = $f3->get('POST') )
		{
			if ( array_key_exists("confirmed",$post) )
			{
				//$this->model->libraryBookFavDelete($params);
				//$f3->reroute($params['returnpath'], false);
				//exit;
			}
			elseif ( $params[0] == "shoutbox" )
			{
				//
				
			}
			else
			{
				if ( FALSE === $result = $this->model->saveFeedback($post, $params) )
				{
					$params['error'] = "saving";
					//$this->libraryBookFavEdit($f3, $params);
				}
				else
				{
					$f3->reroute($params['returnpath'], false);
					exit;
				}
			}
		}

		$this->counter = $this->model->getCount("feedback");
		$this->showMenu("feedback", [
								"RW" => $this->counter['rw']['sum'],
								"RR" => $this->counter['rr']['sum'],
								"CW" => $this->counter['cw']['sum'],
								"CR" => $this->counter['cr']['sum'],
								"SB" => $this->counter['sb']['sum'],
							]
						);

		switch ( $params[0] )
		{
			case "reviews":
				$this->buffer ( $this->feedbackReviews($f3, $params) );
				break;
			case "comments":
				$this->buffer ( \View\Base::stub("reviews") );
				break;
			case "shoutbox":
				$this->buffer ( \View\Base::stub("reviews") );
				break;
			default:
				$this->buffer ( $this->feedbackHome($f3, $params) );
		}

	}
	
	protected function feedbackReviews(\Base $f3, $params)
	{
		if ( empty($params[1]) OR !in_array($params[1], [ "written","received" ]) )
			$f3->reroute("/userCP/feedback/reviews/written", false);
		
		// Build upper micro-menu
		$select = $params[1];
		$counter = $this->counter['r'.$params[1][0]]['details'];

		if ( is_array($counter) )
		{
			$menu_upper =
			[
				[ "link" => "ST", "label" => "Stories" ],
				[ "link" => "SE", "label" => "Series" ],
				[ "link" => "RC", "label" => "Recomm" ],
			];
			$this->buffer ( \View\UserCP::upperMenu($menu_upper, $counter, "feedback/reviews/".$params[1], "comments") );
		}
		
		// End of menu
		
		if(array_key_exists("edit",$params))
			$this->feedbackReviewsEdit($f3, $params);

		if ( isset($params[2]) AND isset($counter[$params[2]]) )
		{
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			// search/browse
			$allow_order = array (
					"date"		=>	"date",
					"name"		=>	"name",
					"title"		=>	"title",
					"text"		=>	"text",
			);

			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "date";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

			$data = $this->model->listReviews($page, $sort, $params);

			$extra = [ "sub" => [ $params[0], $params[1] ], "type" => $params[2] ];
			
			$this->buffer ( \View\UserCP::feedbackListReviews($data, $sort, $extra) );
		}
	}
	
	protected function feedbackReviewsEdit(\Base $f3, $params)
	{
		if ( FALSE !== $data = $this->model->loadReview($params) )
		{
			//print_r($data);
			$this->buffer ( \View\UserCP::libraryFeedbackEdit($data, $params) );
		}
	}


	protected function feedbackHome(\Base $f3, $params)
	{
		$stats = $this->model->feedbackHomeStats($this->counter);
		$this->buffer ( \View\UserCP::feedbackHome($stats) );
		//return "Noch nix";
	}

	public function settings(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Settings') );
		$this->buffer ( \View\Base::stub("settings") );

		$this->showMenu("settings");
	}

	public function library(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );

		$sub = [ "bookmark", "favourite", "recommendation" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";
		
		// delete function get's accompanied by a pseudo-post, this doesn't count here. Sorry dude
		if( NULL != $post = $f3->get('POST') )
		{
			if ( array_key_exists("confirmed",$post) )
			{
				$this->model->libraryBookFavDelete($params);
				$f3->reroute($params['returnpath'], false);
				exit;
			}
			elseif ( $params[0] == "recommendation" )
			{
				//
				
			}
			else
			{
				if ( FALSE === $result = $this->model->saveBookFav($post, $params) )
				{
					$params['error'] = "saving";
					$this->libraryBookFavEdit($f3, $params);
				}
				else
				{
					$f3->reroute($params['returnpath'], false);
					exit;
				}
			}
		}

		$this->counter = $this->model->getCount("library");

		$this->showMenu("library", [
									"BMS"	=> $this->counter['bookmark']['sum'],
									"FAVS"	=> $this->counter['favourite']['sum'],
									"RECS"	=> is_numeric($this->counter['recommendation']['sum']) ? $this->counter['recommendation']['sum'] : FALSE,
								   ]
						);

		switch ( $params[0] )
		{
			case "bookmark":
			case "favourite":
				$this->libraryBookFav($f3, $params);
				break;
			case "recommendation":
				$this->libraryRecommendations($f3, $params);
				break;
			default:
				$this->buffer ( "Empty page");
		}

	}
	
	private function libraryBookFav(\Base $f3, $params)
	{
		// Build upper micro-menu
		$counter = $this->counter[$params[0]]['details'];

		if ( is_array($counter) )
		{
			$menu_upper =
			[
				[ "link" => "AU", "label" => "Author" ],
				[ "link" => "RC", "label" => "Recomm" ],
				[ "link" => "SE", "label" => "Series" ],
				[ "link" => "ST", "label" => "Stories" ],
			];
			$this->buffer ( \View\UserCP::upperMenu($menu_upper, $counter, "library/{$params[0]}", $params[0]) );
		}
		// End of menu

		if(array_key_exists("edit",$params))
			$this->libraryBookFavEdit($f3, $params);
		
		if ( isset($params[1]) AND isset($counter[$params[1]]) )
		{
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			// search/browse
			$allow_order = array (
					"id"		=>	"id",
					"visibility"=>	"visibility",
					"name"		=>	"name",
					"comments"	=>	"comments",
			);

			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "name";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
			
			$data = $this->model->listBookFav($page, $sort, $params);
			
			$extra = [ "sub" => $params[0], "type" => $params[1] ];
			
			$this->buffer ( \View\UserCP::libraryListBookFav($data, $sort, $extra) );
		}
	}
	
	private function libraryRecommendations(\Base $f3, $params)
	{
		
	}
	
	private function libraryBookFavEdit(\Base $f3, $params)
	{
		if ( FALSE !== $data = $this->model->loadBookFav($params) )
		{
			$this->buffer ( \View\UserCP::libraryBookFavEdit($data, $params) );
		}
	}
	
	public function messaging(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Message') );
		
		$sub = [ "outbox", "read", "write" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		switch ( $params[0] )
		{
			case "read":
				$this->msgRead($f3, $params);
				break;
			case "write":
				$this->msgWrite($f3, $params);
				break;
			case "outbox":
				$data = $this->model->msgOutbox();
				$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
				break;
			default:
				$data = $this->model->msgInbox();
				$this->buffer ( \View\UserCP::msgInOutbox($data, "inbox") );
		}
		$this->showMenu("messaging");
	}
	
	public function msgRead(\Base $f3, $params)
	{
		if ( $data = $this->model->msgRead($params['id']) )
		{
			$this->buffer ( \View\UserCP::msgRead($data) );
		}
		else $this->buffer( "*** No such message or access violation!");
	}
	
	// fix me!
	public function msgWrite(\Base $f3, $params)
	{
		if( isset($_POST['recipient']) )
			$this->msgSave($f3);

		if ( isset($params[0][1]) AND is_numeric($params[0][1]) )
			$data = $this->model->msgReply($params[0][1]);
		else $data = $this->model->msgReply();
		
		$this->buffer ( \View\UserCP::msgWrite($data) );
	}
	
	// fix me!
	protected function msgSave(\Base $f3)
	{
		$save = $f3->get('POST');
		if ( sizeof($save)>0 )
		{
			if ( $save['recipient']== "" )
			{
				$f3->set('msgWriteError', "__noRecipient");
				return FALSE;
			}
			$save['recipient'] = explode(",",$save['recipient']);
			if ( sizeof($save['recipient'])>1 )
			{
				// Build an array of recipients
			}
			
			$status = $this->model->msgSave($save);

			print_r($save);
		}
	}
	
	protected function showMenu($selected=FALSE, array $data=[])
	{
		$menu = $this->model->showMenu($selected, $data);

		$this->buffer
		( 
			\View\UserCP::showMenu($menu), 
			"LEFT"
		);
	}
}
