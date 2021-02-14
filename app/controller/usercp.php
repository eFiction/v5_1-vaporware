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

	public function beforeroute() : void
	{
		parent::beforeroute();
		$this->response->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $f3, array $params): void
	{
		$modules = [ "library", "messaging", "author", "feedback", "friends", "settings" ];

		if ( TRUE == @$this->config['optional_modules']['shoutbox'] )
			$modules[] = "shoutbox";

		// run the module and let it show the module
		if ( isset($params['module']) AND in_array($params['module'], $modules) )
			$this->buffer( $this->{$params['module']}($f3, $f3->get('PARAMS')) ?? "" );
		// Just show default menu
		else
			$this->buffer( $this->start($f3, $f3->get('PARAMS')) );
	}

	public function ajax(\Base $f3, array $params): void
	{
		$data = [];
		if ( empty($params['module']) ) return;

		$post = $f3->get('POST');

		switch ( $params['module'] )
		{
			case "messaging":
				$data = $this->model->ajax("messaging", $post);
				break;
			//case "curator":
			case "stories":
				$data = $this->model->ajax("stories", $post, $f3->get('PARAMS'));
				break;
			case "library":
				$data = $this->model->ajax("library", $post);
				break;
		}

		echo json_encode($data);
		exit;
	}

	public function start(\Base $f3, array $params): string
	{
		// no additional work required here
		$this->showMenu();

		// get some user stats
		$stats = $this->model->startGetStats();

		return $this->template->start($stats);
	}

	public function author(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );
		// Menu must be built at first because it also generates the list of allowed authors
		// This way, we save one SQL query *thumbs up*
		$this->showMenu("author", $params);

		$allowed_authors = $f3->get('allowed_authors');

		if ( $_SESSION['groups']&5 OR TRUE === $this->config['author_self'] )
		{
			if ( array_key_exists("curator", $params) )
				return $this->authorCurator($f3, $params);

			elseif ( array_key_exists("uid", $params) AND isset($allowed_authors[$params['uid']]) AND isset ($params[1]) )
			{
				switch ( $params[1] )
				{
					case "add":
						return $this->authorStoryAdd($f3, $params['uid']);
						break;
					case "finished":
					case "unfinished":
					case "drafts":
					case "deleted":
						return $this->authorStorySelect($params);
						break;
					case "edit":
						return $this->authorStoryEdit($f3, $params);
						break;
				}
			}
		}

		return $this->authorHome( $f3, $params );
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
				if ( $newID = $this->model->storyAdd($data) )
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
		if ( isset($params['sid']) AND  ( [] !== $storyData = $this->model->storyLoadInfo($params['sid'], $params['uid']) ) )
		{
			if (isset($_POST) and sizeof($_POST)>0 )
			{
				// so we want to delete something
				if( isset($params['delete']) )
				{
					// let's assume this will work
					$reroute['base']  = "/userCP/author/uid={$params['uid']}/edit";
					$reroute['story'] = "/sid={$params['sid']}";
					if ( isset($params['chapter']) ) $reroute['chapter'] = "/chapter=".$params['chapter'];
					$reroute['editor'] = "/editor=".$params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
					if ( isset($params['returnpath']) ) $reroute['returnpath'] = ";returnpath=".$params['returnpath'];

					if ( ""!=$f3->get('POST.confirm_delete') )
					{
						// delete a chapter
						if ( isset($params['chapter']) )
						{
							// when deleting a chapter, always return to chapter overview
							if ( 0 == $i = $this->model->chapterDelete( $params['sid'], $params['chapter'], $_SESSION['userID'] ) )
								$_SESSION['lastAction']['delete_error'] = TRUE;
							else
							{
								$_SESSION['lastAction']['delete_success'] = TRUE;
								unset($reroute['chapter']);
							}
						}
						// delete the whole story
						else
						{
							if ( 0 == $i = $this->model->storyDelete( $params['sid'], $_SESSION['userID'] ) )
								$_SESSION['lastAction']['delete_error'] = TRUE;
							// since the story is now gone, we must fall back to the story listing
							else
							{
								$_SESSION['lastAction']['delete_success'] = TRUE;
								if (isset($params['returnpath']))
									$reroute = [$params['returnpath']];
								else
									$reroute = $reroute['base'];
							}
						}
					}
					else
					{
						$_SESSION['lastAction']['delete_confirm'] = TRUE;
					}
					$f3->reroute(implode("",$reroute),FALSE);
				}
				else
				{
					if ( isset($params['chapter']) )
					{
						if ( 0 < $i = $this->model->chapterSave($params['chapter'], $f3->get('POST.form'), 'U') )
							$f3->set('save_success', $i);
					}
					else
					{
						if ( 0 < $i = $this->model->storySaveChanges($params['sid'], $f3->get('POST.form'), $_SESSION['userID']) )
							$_SESSION['lastAction']['save_success'] = $i;
						$f3->reroute("/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']};returnpath=".$params['returnpath'], false);
						exit;
					}
				}
			}

			// Chapter list is always needed, load after POST to catch chapter name changes
			$chapterList = $this->model->chapterLoadList($storyData['sid']);

			if ( isset($params['chapter']) )
			{
				if ( $params['chapter']=="new" )
				{
					if ( 0 == $newChapterID = $this->model->chapterAdd($params['sid'], $params['uid'] ) )
					{
						// could not create chapter, return with the bad news *todo*
					}
					else
					{
						$reroute = "/userCP/author/uid={$params['uid']}/edit/sid={$params['sid']}/chapter={$newChapterID};returnpath=".$params['returnpath'];
						$f3->reroute($reroute, false);
						exit;
					}
				}
				$chapterData = $this->model->chapterLoad($storyData['sid'],(int)$params['chapter']);
				// abusing $chapterData to carry a few more details
				$chapterData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'], "storytitle" => $storyData['title'] ];
				// figure out if we want a visual editor
				$chapterData['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");

				return $this->template->authorStoryChapterEdit($chapterData,$chapterList);
			}
			else
			{
				// abusing $storyData to carry a few more details
				$storyData['form'] = [ "uid" => $params['uid'], "returnpath" => $params['returnpath'] ];
				$storyData['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$prePopulate = $this->model->storyEditPrePop($storyData);
				return $this->template->authorStoryHeaderEdit($storyData,$chapterList,$prePopulate);
			}
		}
		elseif ( isset($params['sid']) )
			$_SESSION['lastAction']['load_error'] = TRUE;
		$return = !empty($params['returnpath']) ? $params['returnpath'] : "userCP/author/uid={$params['uid']}";
		$f3->reroute($return, false);
	}

	public function feedback(\Base $f3, array $params): string
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Reviews') );

		$sub = [ "reviews", "comments" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		// delete function get's accompanied by a pseudo-post, this doesn't count here. Sorry dude
		if( NULL != $post = $f3->get('POST') )
		{
			if ( array_key_exists("confirm_delete",$post) )
			{
				$this->model->deleteFeedback($params);
				$f3->reroute($params['returnpath'], false);
				exit;
			}
			else
			{
				if ( FALSE === $result = $this->model->saveFeedback($post, $params) )
				{
					$_SESSION['lastAction'] = [ "modified" => $result ];
					$f3->reroute($params['returnpath'], false);
					exit;
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
				return $this->feedbackReviews($f3, $params);
				break;
			case "comments":
				return \View\Base::stub("reviews");
				break;
			default:
				return $this->feedbackHome($f3, $params);
		}

	}

	protected function feedbackReviews(\Base $f3, array $params): string
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
			return $this->feedbackReviewsEdit($f3, $params);
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

			if ( [] !== $data = $this->model->listReviews($page, $sort, $params) )
			{
				$extra = [ "sub" => [ $params[0], $params[1] ], "type" => $params[2] ];

				return $this->template->feedbackListReviews($data, $sort, $extra);
			}
		}
		// nothing yet ...
		return "";
	}

	protected function feedbackReviewsEdit(\Base $f3, array $params): string
	{
		if ( [] === $data = $this->model->loadReview($params) )
		{
			// test
			$f3->reroute("/userCP/feedback/reviews", false);
			exit;
		}
		return $this->template->libraryFeedbackEdit($data, $params);
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

	protected function feedbackHome(\Base $f3, array $params): string
	{
		$stats = $this->model->feedbackHomeStats($this->counter);
		return $this->template->feedbackHome($stats);
	}

	public function friends(\Base $f3, array $params): void
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

	public function friendsList(\Base $f3, array $params): void
	{

	}

	protected function friendsUpdate(\Base $f3, array $params): void
	{
		if ( isset($params['add']) )
			$this->model->friendsAdd($params['add']);
		if ( isset($params['remove']) )
			$this->model->friendsRemove($params['remove']);

		// return to member page on empty data
		$f3->reroute
		(
			isset($params['returnpath']) ? $params['returnpath'] : "/userCP/friends",
			false
		);
		exit;
	}


	public function settings(\Base $f3, array $params): void
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

	protected function settingsProfile(\Base $f3, array $params): void
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

	protected function settingsPreferences(\Base $f3, array $params): void
	{
		if( NULL != $post = $f3->get('POST') )
		{
			if ( 0 < $i = $this->model->settingsSavePreferences($post['form']) )
				$_SESSION['lastAction']['save_success'] = $i;
			// At this point, the view is already set up.
			// We need to reload the page or the user may think that changes did not apply
			$f3->reroute("/userCP/settings/preferences", false);
		}
		$preferences = $this->model->settingsLoadPreferences();

		$this->buffer ( $this->template->settingsPreferences($preferences) );
	}

	protected function settingsChangePW(\Base $f3, array $params): void
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

	protected function settingsUser(\Base $f3, array $params): void
	{
		$this->buffer ( \View\Base::stub("user") );
	}

	public function library(\Base $f3, array $params): void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );

		$sub = [ "bookmark", "favourite", "recommendations", "series", "collections" ];
		if ( !in_array(@$params[0], $sub) ) $params[0] = "";

		$this->showMenu("library");

		switch ( $params[0] )
		{
			case "bookmark":
			case "favourite":
				$this->libraryBookFav($f3, $params);
				break;
			case "recommendations":
				$this->libraryRecommendations($f3, $params);
				break;
			case "series":
			case "collections":
				$this->libraryCollections($f3, $params);
				break;
			default:
				$this->buffer ( "Empty page");
		}
	}

	private function libraryBookFav(\Base $f3, array $params) : void
	{
		if( NULL != $post = $f3->get('POST') )
		{
			if ( array_key_exists("confirm_delete",$post) )
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

		// Build upper micro-menu
		$counter = $this->counter[$params[0]]['details'];
		$menu_upper = [];

		if ( is_array($counter) )
		{
			$menu_upper =
			[
				[ "link" => "AU", "label" => "Author" ],
				[ "link" => "RC", "label" => "Recomm" ],
				[ "link" => "CO", "label" => "Collections" ],
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

			if ( [] !== $data = $this->model->listBookFav($page, $sort, $params) )
			{
				$extra = [ "sub" => $params[0], "type" => $params[1] ];

				$this->buffer ( $this->template->libraryBookFavList($data, $sort, $extra) );
			}
		}
	}

	private function libraryBookFavEdit(\Base $f3, array $params) : void
	{
		if ( [] !== $data = $this->model->loadBookFav($params) )
		{
			$this->buffer ( $this->template->libraryBookFavEdit($data, $params) );
		}
	}

	private function libraryCollections(\Base $f3, array $params) : void
	{
		if ( $params[0]=="collections" )
		{
			$this->response->addTitle( $f3->get('LN__AdminMenu_Collections') );
			$f3->set('title_h3', $f3->get('LN__AdminMenu_Collections') );
			$module = "collections";
		}
		else
		{
			$this->response->addTitle( $f3->get('LN__AdminMenu_Series') );
			$f3->set('title_h3', $f3->get('LN__AdminMenu_Series') );
			$module = "series";
		}

		if (isset($_POST['form_data']))
		{
			if( isset($params['delete']) )
			{
				if ( ""!=$f3->get('POST.confirm_delete') )
				{
					if ( 0 == $i = $this->model->collectionDelete($params['id'], $_SESSION['userID'] ) )
					{
						// failed to delete this item
						$reroute  = "/userCP/library/{$module}/id={$params['id']}/editor=";
						$reroute .= $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
						if ( isset($params['returnpath']) ) $reroute .= ";returnpath=".$params['returnpath'];
						$_SESSION['lastAction']['delete_error'] = TRUE;
					}
					else
					{
						// successfully deleted the item
						$_SESSION['lastAction']['delete_success'] = TRUE;
						$reroute = isset($params['returnpath']) ? $params['returnpath'] : "/userCP/library/".$module;
					}
				}
				else
				{
					$reroute  = "/userCP/library/{$module}/id={$params['id']}/editor=";
					$reroute .= $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
					if ( isset($params['returnpath']) ) $reroute .= ";returnpath=".$params['returnpath'];
					$_SESSION['lastAction']['delete_confirm'] = TRUE;
				}
				$f3->reroute($reroute,FALSE);
				exit;
			}

			// save data and report success to the user
			if ( 0 < $i = $this->model->collectionSave($params['id'], $f3->get('POST.form_data'), $_SESSION['userID'] ) )
				$f3->set('save_success', $i);

			if ( isset($_POST['form_data']['changetype']) )
			{
				$reroute = "/userCP/library/".$module;
				foreach($params as $key => $param)
				{
					if ($key!="returnpath")
						$reroute .= "/{$key}={$param}";
				}
				$f3->reroute($reroute,FALSE);
				exit;
			}
		}
		elseif (isset($_POST['new_data']))
		{
			$params['id'] = $this->model->collectionAdd($f3->get('POST.new_data') );
			if ( is_numeric($params['id']) AND $params['id']>0 )
			{
				$reroute = "/userCP/library/".(($f3->get('POST.new_data.ordered')==0)?"collections":"series");
				$reroute .= "/id={$params['id']};returnpath=".$params['returnpath'];
				$f3->reroute($reroute,FALSE);
			}
		}
		elseif (isset($_POST['story-add']))
		{
			$this->model->collectionItemsAdd($params['id'], $f3->get('POST.story-add'), $_SESSION['userID'] );
		}

		if( isset ($params['id']) )
		{
			// delete an element from the collection/series
			if ( isset ($params['delete']) )
			{
				$delete = $this->model->collectionItemDelete($params['id'], $params['delete'], $_SESSION['userID']);
				// session to report deleted item *todo*
				$params['items'] = 1;
			}

			// edit the elements of the collection/series
			if ( isset ($params['items']) AND NULL !== $data = $this->model->collectionLoadItems($params['id']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->collectionItems($data, $module, @$params['returnpath']) );
				return;
			}
			// edit the collection/series
			elseif ( NULL !== $data = $this->model->collectionLoad($params['id'], $_SESSION['userID']) )
			{
				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer( $this->template->collectionEdit($data, $this->model->storyEditPrePop($data), $module, @$params['returnpath']) );
				return;
			}
			else $f3->set('load_error', TRUE);
		}

		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"		=>	"Coll.collid",
				"date"		=>	"date",
				"title"		=>	"title",
				"author"	=>	"author",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$this->buffer
		(
			$this->template->libraryCollectionsList
			(
				$this->model->collectionsList($page, $sort, $module, $_SESSION['userID']),
				$sort,
				$module
			)
		);
	}

	/**
	* Manage recommendations from the UCP
	* 2020-09
	*
	* @param	\Base		$f3
	* @param	array		$params
	*/
	private function libraryRecommendations(\Base $f3, array $params) : void
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Recommendations') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Recommendations') );

		if( isset($params['delete']) )
		{
			// default return point
			$reroute = empty($params['returnpath']) ? "/userCP/library/recommendations" : $params['returnpath'];

			if ( ""!=$f3->get('POST.confirm_delete') )
			{
				if ( 0 == $i = $this->model->recommendationDelete( $params['id'], $_SESSION['userID'] ) )
				{
					$_SESSION['lastAction']['delete_error'] = TRUE;
				}
				else
				{
					$_SESSION['lastAction']['delete_success'] = $i;
				}
			}
			// but it seems we are not really sure ...
			else
			{
				$reroute = "/userCP/library/recommendations/id={$params['id']}";
				if ( isset($params['returnpath']) ) $reroute .= ";returnpath=".$params['returnpath'];
				$_SESSION['lastAction']['delete_confirm'] = TRUE;
			}
			$f3->reroute($reroute,FALSE);
			exit;
		}

		if (isset($_POST['form_data']))
		{
			if ( 0 < $i = $this->model->recommendationSave($params['id'], $f3->get('POST.form_data'), $_SESSION['userID'] ) )
				$f3->set('save_success', $i);
		}
		elseif (isset($_POST['new_data']))
		{
			$params['id'] = $this->model->recommendationAdd($f3->get('POST.new_data') );
		}

		if( isset ($params['id']) )
		{
			if ( [] !== $data = $this->model->recommendationLoad($params['id'], $_SESSION['userID']) )
			{
				// despite 0 not being an official code, AO3 will reply this way when a story is no longer found.
				if ( @$data['lookup']['http_code']===0 )
				{
					$f3->set('lookup_error', 0);
				}

				// server has found something
				elseif ( @$data['lookup']['http_code']==200 )
					$f3->set('lookup_success', 1);
				// server has found something but it's not the plain 'OK' code
				elseif ( @$data['lookup']['http_code']>200 AND @$data['lookup']['http_code']<300 )
					$f3->set('lookup_success', 0);

				// Server replies with a permanent moved status
				elseif ( @$data['lookup']['http_code']==301 OR @$data['lookup']['http_code']==308 )
				{
					// if the only difference is a change from http to https, silently alter the value and inform the user.
					if ( str_replace("http:", "https:", $data['url']) == @$data['lookup']['redirect_url'] OR
							"https://".$data['url'] == $data['lookup']['redirect_url'] )
					{
						$data['url'] = $data['lookup']['redirect_url'];
						$f3->set('lookup_moved', 1);
					}
					else
					{
						$f3->set('lookup_moved', 0);
					}
				}

				elseif ( @$data['lookup']['http_code']>=400 )
				{
					$f3->set('lookup_error', 0);
				}

				$data['editor'] = $params['editor'] ?? ((empty($_SESSION['preferences']['useEditor']) OR $_SESSION['preferences']['useEditor']==0) ? "plain" : "visual");
				$this->buffer ( $this->template->recommendationEdit($data, $this->model->storyEditPrePop($data), @$params['returnpath']) );
				return;
			}
			else
			{
				$reroute = isset($params['returnpath']) ? $params['returnpath'] : "/userCP/library/recommendations";
				$f3->reroute($reroute,FALSE);
				exit;
			}
		}

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// search/browse
		$allow_order = array (
				"id"		=>	"Rec.recid",
				"title"		=>	"title",
				"author"	=>	"maintainer",
				"date"		=>	"date",
				"rating"	=>	"R.inorder",
		);

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$this->buffer
		(
			$this->template->recommendationList
			(
				$this->model->recommendationList($page, $sort, $_SESSION['userID']),
				$sort
			)
		);

	}

	public function messaging(\Base $f3, array $params) : void
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

	public function msgRead(\Base $f3, array $params): void
	{
		if ( $data = $this->model->msgRead($params['id']) )
		{
			$this->buffer ( $this->template->msgRead($data) );
		}
		// todo: make a better error page
		else $this->buffer( "*** No such message or access violation!");
	}

	public function msgDelete(\Base $f3, array $params): void
	{
		$result = $this->model->msgDelete($params['message']);

		$_SESSION['lastAction'] = [ "deleted" => $result===TRUE ? "success" : $result ];
		$f3->reroute($params['returnpath'], false);
		exit;
	}

	public function msgWrite(\Base $f3, array $params): void
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

	/* todo */
	protected function shoutbox(\Base $f3, array $params): void
	{
		$this->response->addTitle( $f3->get('LN__UserMenu_Shoutbox',0) );

		$this->showMenu("shoutbox");

		// Check for form data
		if( NULL != $post = $f3->get('POST') )
		{
			// check if the delete confirmation was triggered
			if ( array_key_exists("confirm_delete",$post) )
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

	protected function showMenu($selected=FALSE, array $data=[]): void
	{
		$menu = $this->model->showMenu($selected, $data);

		$this->buffer ( $this->template->showMenu($menu), "LEFT" );

		if($selected) $this->counter = $this->model->getCounter($selected);
	}
}
