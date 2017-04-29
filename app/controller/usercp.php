<?php
namespace Controller;

class UserCP extends Base
{
	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		$this->config = \Config::instance();
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

		$p = array_pad(@explode("/",$params['*']),2,NULL); // 3.6

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

		switch ( $params['module'] )
		{
			case "messaging":
			case "curator":
				$data = $this->model->ajax("messaging", $post);
				break;
			case "stories":
				$data = $this->model->ajax("stories", $post, @$params['sub']);
				break;
			case "chaptersort":
				$data = $this->model->ajax("chaptersort", $post);
				break;
		}
		/*
		if ( $params['module']=="messaging" )
		{
			$data = $this->model->ajax("messaging", $post);
		}
		elseif ( $params['module']=="curator" )
		{
			$data = $this->model->ajax("curator", $post);
		}*/
		echo json_encode($data);
		exit;
	}
	
	public function author(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );
		// Menu must be built at first because it also generates the list of allowed authors
		// This way, we save one SQL query *thumbs up*
		$this->showMenu("author", $params);

		$allowed_authors = $f3->get('allowed_authors');

		$buffer = NULL;
		
		if ( $_SESSION['groups']&5 OR TRUE === $this->config['author_self'] )
		{
			if ( array_key_exists("curator", $params) )
				$buffer = $this->authorCurator($f3, $params);
			
			elseif ( array_key_exists("uid", $params) AND isset($allowed_authors[$params['uid']]) AND isset ($params[1]) )
			{
				switch ( $params[1] )
				{
					case "add":
						$buffer = $this->authorStoryAdd($f3, $params['uid']);
						break;
					case "finished":
					case "unfinished":
					case "drafts":
						$buffer = $this->authorStorySelect($params);
						break;
					case "edit":
						$buffer = $this->authorStoryEdit($f3, $params);
						break;
				}
				//$buffer .= print_r($params,1);
			}
		}
		
		$this->buffer ( ($buffer) ?: $this->authorHome( $f3, $params) );

	}
	
	protected function authorCurator(\Base $f3, $params)
	{
		// Get the current curator and list of people being curated
		$data = $this->model->authorCuratorGet();
		$change = FALSE;
		
		// extract the curator id from the form
		$curator_id = $f3->get('POST.curator_id');

		if ( is_numeric($curator_id) )
			$change = $this->model->authorCuratorSet($curator_id);

		elseif ( $curator_id!==NULL OR isset($params['remove']) )
			$change = $this->model->authorCuratorRemove(@$params['id']);
			
		// Strip all requests and reload data
		if ($change) $f3->reroute("/userCP/author/curator", false);
		
		return \View\UserCP::authorCurator($data);
	}

	protected function authorHome(\Base $f3, $params)
	{
		// Future: load stuff, like to-do, open ends ...
		if ( $_SESSION['groups']&5 )
		{
			$data = [];
		}
		else $data = FALSE;
		
		return \View\UserCP::authorHome($data);
	}
	
	protected function authorStoryAdd(\Base $f3, $uid)
	{
		$data = [ "uid" => $uid ];
		// Check data
		if ( sizeof($_POST) )
		{
			if( "" != $data['new_title'] = $f3->get('POST.new_title') )
			{
				if ( $newID = $this->model->authorStoryAdd($data) )
					$f3->reroute("/userCP/author/uid={$uid}/edit/sid={$newID};returnpath=/userCP/author/uid={$uid}/drafts", false);
			}
		}
		
		return \View\UserCP::authorStoryAdd($data);
	}
	
	protected function authorStorySelect($params)
	{
		if ( empty($params['uid']) ) return FALSE;
		
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"sid"			=>	"sid",
				"title"			=>	"title",
				"svalidated"	=>	"story_validated",
				"chvalidated"	=>	"chapter_validated",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "title";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";

		if ( FALSE === $data = $this->model->authorStoryList($params[1],$params['uid'],$sort,$page) )
			return FALSE;
		
		//print_r($data);
		return \View\UserCP::authorStoryList($data, $sort, $params);
	}

	protected function authorStoryEdit(\Base $f3, array $params)
	{
		if(empty($params['sid'])) return "__Error";
		//$uid = isset($params['uid']) ? $params['uid'] : $_SESSION['userID'];
		if ( FALSE !== $storyData = $this->model->authorStoryLoadInfo((int)$params['sid'], (int)$params['uid']) )
		{
			if (isset($_POST) and sizeof($_POST) )
			{
				if ( isset($params['chapter']) )
				{
					$this->model->authorStoryChapterSave($params['chapter'], $f3->get('POST.form'));
					$reroute = "/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']}/chapter={$params['chapter']};returnpath=".$params['returnpath'];
					$f3->reroute($reroute, false);
					exit;
				}
				else
				{
					$this->model->authorStoryHeaderSave($params['sid'], $f3->get('POST.form'));
					$reroute = "/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']};returnpath=".$params['returnpath'];
					$f3->reroute($reroute, false);
					exit;
				}
				//
				
			}

			// Chapter list is always needed, load after POST to catch chapter name changes
			$chapterList = $this->model->loadChapterList($storyData['sid']);

			if ( isset($params['chapter']) )
			{
				if ( $params['chapter']=="new" )
				{
					$newChapterID = $this->model->authorStoryChapterAdd($params['sid'], $params['uid'] );
					$reroute = "/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']}/chapter={$newChapterID};returnpath=".$params['returnpath'];
					$f3->reroute($reroute, false);
					exit;
				}
				$chapterData = $this->model->authorStoryChapterLoad($storyData['sid'],(int)$params['chapter']);
				// abusing $chapterData to carry a few more details
				$chapterData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'], "storytitle" => $storyData['title'] ];

				if ( isset($params['plain']) ) $editor = "plain";
				elseif ( isset($params['visual']) ) $editor = "visual";
				else $editor = ($_SESSION['preferences']['useEditor']==0) ? "plain" : "visual";

				return \View\UserCP::authorStoryChapterEdit($chapterData,$chapterList,$editor);
			}
			else
			{
				// abusing $storyData to carry a few more details
				$storyData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'] ];
				$prePopulate = $this->model->storyEditPrePop($storyData);
				return \View\UserCP::authorStoryMetaEdit($storyData,$chapterList,$prePopulate);
			}
		}
		else return "__Error";
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
		$sub = [ "profile", "preferences", "changepw" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		switch ( $params[0] )
		{
			case "profile":
				$this->settingsProfile($f3, $params);
				break;
			case "preferences":
				$this->settingsPreferences($f3, $params);
				break;
			case "changepw":
				$this->settingsChangePW($f3, $params);
				break;
			default:
				$this->settingsUser($f3, $params);
		}

		$this->showMenu("settings");
	}
	
	protected function settingsProfile(\Base $f3, $params)
	{
		/*
			1 = URL
			2 = Options
			3 = yes/no
			4 = URL with ID
			5 = code -> now tpl field
			6 = text
		*/
		if( NULL != $post = $f3->get('POST') )
		{
			$this->model->settingsSaveProfile($post['form']);
		}
		$profile = $this->model->settingsLoadProfile();
		
		$this->buffer ( \View\UserCP::settingsProfile($profile) );
	}

	protected function settingsPreferences(\Base $f3, $params)
	{
		if( NULL != $post = $f3->get('POST') )
		{
			$this->model->settingsSavePreferences($post['form']);
		}
		$preferences = $this->model->settingsLoadPreferences();
		
		$this->buffer ( \View\UserCP::settingsPreferences($preferences) );
	}

	protected function settingsChangePW(\Base $f3, $params)
	{
		$feedback ="";
		if( NULL != $post = $f3->get('POST') )
		{
			if ( TRUE === $check = $this->model->settingsCheckPW($f3->get('POST.change.old')) )
			{
				if ( TRUE === $new = $this->model->newPasswordQuality($f3->get('POST.change.new1'),$f3->get('POST.change.new2')) )
				{
					$this->model->userChangePW( $_SESSION['userID'], $f3->get('POST.change.new1') );
					$feedback = "success";
				}
				else $feedback = "error";
			}
			else $feedback = "error";
		}
		
		$this->buffer ( \View\UserCP::settingsChangePW($feedback) );
	}

	protected function settingsUser(\Base $f3, $params)
	{
		$this->buffer ( \View\Base::stub("user") );
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
		else
		{
			$this->buffer ( "__empty" );
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
		
		if ( TRUE == $this->config->optional_modules['shoutbox'] )
			$sub = [ "outbox", "read", "write", "delete", "shoutbox" ];
		else
			$sub = [ "outbox", "read", "write", "delete" ];
		
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		switch ( $params[0] )
		{
			case "read":
				$this->msgRead($f3, $params);
				break;
			case "write":
				$this->msgWrite($f3, $params);
				break;
			case "delete":
				$this->msgDelete($f3, $params);
				break;
			case "outbox":
				$data = $this->model->msgOutbox();
				$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
				break;
			case "shoutbox":
				$this->msgShoutbox($f3, $params);
				//$this->buffer ( \View\Base::stub("shoutbox") );
				break;
			default:
				$data = $this->model->msgInbox();
				$this->buffer ( \View\UserCP::msgInOutbox($data, "inbox") );
		}

		$this->counter = $this->model->getCount("messaging");
		$this->showMenu("messaging",
							[
								"UN" => $this->counter['unread']['sum'],
								"SB" => $this->counter['shoutbox']['sum'],
							]
						);

	}
	
	public function msgRead(\Base $f3, $params)
	{
		if ( $data = $this->model->msgRead($params['id']) )
		{
			$this->buffer ( \View\UserCP::msgRead($data) );
		}
		else $this->buffer( "*** No such message or access violation!");
	}
	
	public function msgDelete(\Base $f3, $params)
	{
		$result = $this->model->msgDelete($params['message']);

		$_SESSION['lastAction'] = [ "deleted" => $result===TRUE ? "success" : $result ];
		$f3->reroute($params['returnpath'], false);
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
			/*
			if ( sizeof($save['recipient'])>1 )
			{
				// Build an array of recipients
			}
			*/
			$status = $this->model->msgSave($save); // return TRUE
		}
	}
	
	protected function msgShoutbox(\Base $f3, $params)
	{
		// Check for form data
		if( NULL != $post = $f3->get('POST') )
		{
			// check if the delete confirmation was triggered
			if ( array_key_exists("confirmed",$post) )
			{
				// delete message
				$result = $this->model->msgShoutboxDelete(@$params['message']);
				// remember last Action, show via template
				$_SESSION['lastAction'] = [ "deleted" => $result ];
				// reroute
				if ( $params['returnpath']=="" ) $params['returnpath'] = "/userCP/messaging/shoutbox";
				$f3->reroute($params['returnpath'], false);
			}
			
			// save changes - to come
			
			
			// reroute
			if ( $params['returnpath']=="" ) $params['returnpath'] = "/userCP/messaging/shoutbox";
			$f3->reroute($params['returnpath'], false);
		}
		elseif( isset($params['edit']) )
		{
			// load message

		}
		
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];
		$data = $this->model->msgShoutboxList($page);
		
		$this->buffer( \View\UserCP::msgShoutboxList($data) );
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
