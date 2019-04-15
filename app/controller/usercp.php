<?php
namespace Controller;

class UserCP extends Base
{
	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		$this->config = \Config::instance();
		$this->template = new \View\UserCP();
		\Base::instance()->set('systempage', TRUE);
	}
	
	public function beforeroute()//: void
	{
		parent::beforeroute();
		$this->response->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $f3, array $params)//: void
	{
		$modules = [ "library", "messaging", "author", "feedback", "friends", "settings" ];

		if ( TRUE == @$this->config->optional_modules['shoutbox'] )
			$modules[] = "shoutbox";

		// grab the first parameter
		$p = array_pad(@explode("/",$params['*']),2,NULL);
		$mod = array_shift($p);

		$params = $this->parametric(implode("/",$p));

		// run the module and let it show the module
		if ( in_array($mod, $modules) )
			$this->{$mod}($f3, $params);
		// Just show default menu
		else
			$this->start($f3, $params);
	}

	public function ajax(\Base $f3, array $params)//: void
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

		echo json_encode($data);
		exit;
	}
	
	public function start(\Base $f3, array $params)//: void
	{
		// no additional work required here
		$this->showMenu();
		
		$this->buffer ( $this->template->start() );
	}
	
	public function author(\Base $f3, array $params)//: void
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
					case "deleted":
						$buffer = $this->authorStorySelect($params);
						break;
					case "edit":
						$buffer = $this->authorStoryEdit($f3, $params);
						break;
				}
			}
		}
		
		$this->buffer ( ($buffer) ?: $this->authorHome( $f3, $params) );

	}
	
	protected function authorCurator(\Base $f3, array $params): string
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
		
		return $this->template->authorCurator($data);
	}

	protected function authorHome(\Base $f3, array $params): string
	{
		// Future: load stuff, like to-do, open ends ...
		if ( $_SESSION['groups']&5 )
		{
			$data = [];
		}
		else $data = FALSE;
		
		return $this->template->authorHome($data);
	}
	
	protected function authorStoryAdd(\Base $f3, int $uid): string
	{
		// check for an attempt to impersonate a different author
		if(FALSE===in_array($uid, $_SESSION['allowed_authors']))
			$f3->reroute("/userCP/author", false);
		
		$data = [ "uid" => $uid ];
		// Check data
		if ( sizeof($_POST) )
		{
			if( "" != $data['new_title'] = $f3->get('POST.new_title') )
			{
				if ( $newID = $this->model->authorStoryAdd($data) )
				{
					$f3->reroute("/userCP/author/uid={$uid}/edit/sid={$newID};returnpath=/userCP/author/uid={$uid}/drafts", false);
					exit;
				}
			}
		}
		
		return $this->template->authorStoryAdd($data);
	}
	
	protected function authorStorySelect(array $params): string
	{
		if ( empty($params['uid']) ) return FALSE;
		
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"sid"			=>	"sid",
				"title"			=>	"title",
				"svalidated"	=>	"story_validated",
				"chvalidated"	=>	"chapter_validated",
				"updated"		=>  "updated",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "title";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";

		if ( FALSE === $data = $this->model->authorStoryList($params[1],$params['uid'],$sort,$page) )
			return FALSE;
		
		return $this->template->authorStoryList($data, $sort, $params);
	}

	protected function authorStoryEdit(\Base $f3, array $params): string
	{
		if(empty($params['sid'])) return "__Error";
		//$uid = isset($params['uid']) ? $params['uid'] : $_SESSION['userID'];
		if ( FALSE !== $storyData = $this->model->authorStoryLoadInfo($params['sid'], $params['uid']) )
		{
			if (isset($_POST) and sizeof($_POST)>0 )
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
					if  ( "" != $f3->get('POST.delete') )
					{
						// look for the confirmation checkboxes
						if ( $storyData['completed']>0 OR ("" != $f3->get('POST.deleteComfirm1') AND "" != $f3->get('POST.deleteComfirm2')) )
						{
							// attempt to delete
							if ( FALSE !== $deleted = $this->model->authorStoryDelete($params['sid'], $params['uid']) )
							{
								// an array indicates the story was deleted, array contains the deleted elements
								if ( is_array($deleted) )
									$_SESSION['lastAction'] = [ "deleted" =>  "success", "results" =>  $deleted ];
								else
									$_SESSION['lastAction'] = [ "deleted" =>  "moved" ];

								$f3->reroute($params['returnpath'], false);
								exit;
							}
							// model failed to delete the story, let the user know
							else $_SESSION['lastAction'] = [ "deleted" => "failed" ];
						}
						// delete confirmations not checked
						else $_SESSION['lastAction'] = [ "deleted" => "confirm" ];
					}
					else
					{
						$this->model->authorStoryHeaderSave($params['sid'], $f3->get('POST.form'));
						$reroute = "/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']};returnpath=".$params['returnpath'];
						$f3->reroute($reroute, false);
						exit;
					}
				}
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
				$chapterData = $this->model->loadChapter($storyData['sid'],(int)$params['chapter']);
				// abusing $chapterData to carry a few more details
				$chapterData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'], "storytitle" => $storyData['title'] ];

				if ( isset($params['plain']) ) $editor = "plain";
				elseif ( isset($params['visual']) ) $editor = "visual";
				else
				{
					if (empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0)
						$editor = "plain";
					else
						$editor = "visual";
				}

				return $this->template->authorStoryChapterEdit($chapterData,$chapterList,$editor);
			}
			else
			{
				// abusing $storyData to carry a few more details
				$storyData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'] ];
				$prePopulate = $this->model->storyEditPrePop($storyData);
				return $this->template->authorStoryMetaEdit($storyData,$chapterList,$prePopulate);
			}
		}
		else return "__ErrorFileEdit";
	}
	
	public function feedback(\Base $f3, array $params)//: void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Reviews') );

		$sub = [ "reviews", "comments", "shoutbox" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		// delete function get's accompanied by a pseudo-post, this doesn't count here. Sorry dude
		if( NULL != $post = $f3->get('POST') )
		{
			if ( array_key_exists("delete",$post) )
			{
				$this->model->deleteFeedback($post, $params);
				$f3->reroute($params['returnpath'], false);
				exit;
			}
			else
			{
				if ( FALSE === $result = $this->model->saveFeedback($post, $params) )
				{
					$_SESSION['lastAction'] = [ "modified" => "unknown" ];
					//$this->libraryBookFavEdit($f3, $params);
				}
				else
				{
				}
			}
			$f3->reroute($params['returnpath'], false);
			exit;
		}

		$this->showMenu("feedback");

		switch ( $params[0] )
		{
			case "reviews":
				$this->feedbackReviews($f3, $params);
				break;
			case "comments":
				$this->buffer ( \View\Base::stub("reviews") );
				break;
			default:
				$this->feedbackHome($f3, $params);
		}

	}
	
	protected function feedbackReviews(\Base $f3, array $params)//: void
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
			$this->buffer ( $this->template->upperMenu($menu_upper, $counter, "feedback/reviews/".$params[1], "comments") );
		}
		
		// End of menu
		
		if(array_key_exists("edit",$params))
			$this->feedbackReviewsEdit($f3, $params);
		if(array_key_exists("save",$params))
			$this->feedbackReviewsSave($f3, $params);
		elseif(array_key_exists("delete",$params))
		{
			$this->feedbackReviewsDelete($f3, $params);
			$f3->reroute($params['returnpath'], false);
			exit;
		}

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
			
			$this->buffer ( $this->template->feedbackListReviews($data, $sort, $extra) );
		}
	}
	
	protected function feedbackReviewsEdit(\Base $f3, array $params)//: void
	{
		if ( FALSE === $data = $this->model->loadReview($params) )
		{
			// test
			$f3->reroute("/userCP/feedback/reviews", false);
			exit;
		}
		$this->buffer ( $this->template->libraryFeedbackEdit($data, $params) );
	}

	protected function feedbackReviewsSave(\Base $f3, array $params)//: void
	{
		if ( FALSE === $data = $this->model->loadReview($params) )
		{
			// test
			$f3->reroute("/userCP/feedback/reviews", false);
			exit;
		}
		$this->buffer ( $this->template->libraryFeedbackEdit($data, $params) );
	}

	protected function feedbackReviewsDelete(\Base $f3, array $params): bool
	{
		// check if user is allowed to delete the review
		if ( FALSE )
			return FALSE;

		// check if review has child elements
		if ( $this->model->reviewHasChildren($params['id'][1]) )
		{
			// can we delete a branch ?
			if ( TRUE )
			{
				// do
				if ( FALSE === $this->model->reviewDeleteTree($params['id'][1]) )
				{
					// Set a session note to show after reroute
					
					// drop out with an error
					return FALSE;
				}
			}
				// don't
			else return FALSE;
		}
		// no child elements
		elseif ( FALSE === $this->model->reviewDelete($params['id'][1]) )
		{
			// drop out with an error
			return FALSE;
		}
		return TRUE;
	}
	
	protected function feedbackHome(\Base $f3, array $params)//: void
	{
		$stats = $this->model->feedbackHomeStats($this->counter);
		$this->buffer ( $this->template->feedbackHome($stats) );
		//return "Noch nix";
	}
	
	public function friends(\Base $f3, array $params)//: void
	{
		//$this->response->addTitle( $f3->get('LN__UserMenu_Settings') );

		switch ( $params[0] )
		{
			case "update":
				$this->friendsUpdate($f3, $params);
				break;
			default:
				$this->friendsList($f3, $params);
		}

		$this->showMenu("friends");
	}
	
	public function friendsList(\Base $f3, array $params)//: void
	{
		
	}
	
	protected function friendsUpdate(\Base $f3, array $params)//: void
	{
		if ( isset($params['add']) )
			$this->model->friendsAdd($params['add']);
		if ( isset($params['remove']) )
			$this->model->friendsRemove($params['remove']);
		//print_r($params);exit;
		
		
		// return to member page on empty data
		$f3->reroute
		(
			isset($params['returnpath']) ? $params['returnpath'] : "/userCP/friends",
			false
		);
		exit;
	}
		

	public function settings(\Base $f3, array $params)//: void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Settings') );
		$sub = [ "profile", "changepw" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		switch ( $params[0] )
		{
			case "profile":
				$this->settingsProfile($f3, $params);
				break;
			case "changepw":
				$this->settingsChangePW($f3, $params);
				break;
			default:
				$this->settingsPreferences($f3, $params);
		}

		$this->showMenu("settings");
	}
	
	protected function settingsProfile(\Base $f3, array $params)//: void
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
		
		$this->buffer ( $this->template->settingsProfile($profile) );
	}

	protected function settingsPreferences(\Base $f3, array $params)//: void
	{
		if( NULL != $post = $f3->get('POST') )
		{
			$this->model->settingsSavePreferences($post['form']);
			// At this point, the view is already set up.
			// We need to reload the page or the user may think that changes did not apply
			if ( $_SESSION['preferences']['layout'] != $post['form']['p']['layout'] OR $_SESSION['preferences']['language'] != $post['form']['p']['language'] )
				$f3->reroute("/userCP/settings/preferences", false);
		}
		$preferences = $this->model->settingsLoadPreferences();
		
		$this->buffer ( $this->template->settingsPreferences($preferences) );
	}

	protected function settingsChangePW(\Base $f3, array $params)//: void
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
		
		$this->buffer ( $this->template->settingsChangePW($feedback) );
	}

	protected function settingsUser(\Base $f3, array $params)//: void
	{
		$this->buffer ( \View\Base::stub("user") );
	}

	public function library(\Base $f3, array $params)//: void
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

		$this->showMenu("library");

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
	
	private function libraryBookFav(\Base $f3, array $params)//: void
	{
		// Build upper micro-menu
		$counter = $this->counter[$params[0]]['details'];
		$menu_upper = [];
		
		if ( is_array($counter) )
		{
			$menu_upper =
			[
				[ "link" => "AU", "label" => "Author" ],
				[ "link" => "RC", "label" => "Recomm" ],
				[ "link" => "SE", "label" => "Series" ],
				[ "link" => "ST", "label" => "Stories" ],
			];
		}
		$this->buffer ( $this->template->upperMenu($menu_upper, $counter, "library/{$params[0]}", $params[0]) );
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
			
			$this->buffer ( $this->template->libraryListBookFav($data, $sort, $extra) );
		}
	}
	
	private function libraryRecommendations(\Base $f3, $params)
	{
		
	}
	
	private function libraryBookFavEdit(\Base $f3, array $params)//: void
	{
		if ( FALSE !== $data = $this->model->loadBookFav($params) )
		{
			$this->buffer ( $this->template->libraryBookFavEdit($data, $params) );
		}
	}
	
	public function messaging(\Base $f3, array $params)//: void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Message') );
		
		$sub = [ "outbox", "read", "write", "delete" ];
		
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		$this->showMenu("messaging");

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
				$this->buffer
				(
					$this->template->msgInOutbox(
						$this->model->msgOutbox(), "outbox"
					)
				);
				break;
			default:
				$this->buffer
				(
					$this->template->msgInOutbox(
						$this->model->msgInbox(), "inbox"
					)
				);
		}
	}
	
	public function msgRead(\Base $f3, array $params)//: void
	{
		if ( $data = $this->model->msgRead($params['id']) )
		{
			$this->buffer ( $this->template->msgRead($data) );
		}
		// todo: make a better error page
		else $this->buffer( "*** No such message or access violation!");
	}
	
	public function msgDelete(\Base $f3, array $params)//: void
	{
		$result = $this->model->msgDelete($params['message']);

		$_SESSION['lastAction'] = [ "deleted" => $result===TRUE ? "success" : $result ];
		$f3->reroute($params['returnpath'], false);
		exit;
	}
	
	public function msgWrite(\Base $f3, array $params)//: void
	{
		if( isset($_POST['recipient']) )
			$this->msgSave($f3);

		$data = $this->model->msgReply(@$params['reply']);
		
		$this->buffer
		(
			$this->template->msgWrite
			(
				$this->model->msgReply(@$params['reply'])
			)
		);
	}
	
	// todo: add reroute based on the outcome of the model
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

			$status = $this->model->msgSave($save); // return TRUE
		}
	}
	
	protected function shoutbox(\Base $f3, array $params)//: void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Shoutbox',0) );
		
		$this->showMenu("shoutbox");

		// Check for form data
		if( NULL != $post = $f3->get('POST') )
		{
			// check if the delete confirmation was triggered
			if ( array_key_exists("confirmed",$post) )
			{
				// delete message
				$result = $this->model->shoutboxDelete(@$params['message']);
				// remember last Action, show via template
				$_SESSION['lastAction'] = [ "deleted" => $result ];
				// reroute
				if ( $params['returnpath']=="" ) $params['returnpath'] = "/userCP/messaging/shoutbox";
				$f3->reroute($params['returnpath'], false);
				exit;
			}
			
			// save changes - to come
			
			
			// reroute
			if ( $params['returnpath']=="" ) $params['returnpath'] = "/userCP/messaging/shoutbox";
			$f3->reroute($params['returnpath'], false);
			exit;
		}
		elseif( isset($params['edit']) )
		{
			// load message

		}
		
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];
		$data = $this->model->shoutboxList($page);
		
		$this->buffer( $this->template->shoutboxList($data) );
	}
	
	protected function showMenu($selected=FALSE, array $data=[])//: void
	{
		$menu = $this->model->showMenu($selected, $data);

		$this->buffer ( $this->template->showMenu($menu), "LEFT" );
		
		if($selected) $this->counter = $this->model->getCounter($selected);
	}
}
