<?php
namespace Controller;

class Story extends Base
{

	public function __construct()
	{
		$this->model = \Model\Story::instance();
	}

	public function beforeroute()
	{
		parent::beforeroute();
		$this->view  = \Registry::get('VIEW');
		$this->view->addTitle( \Base::instance()->get('LN__Stories') );
	}

	public function index(\Base $f3, $params)
	{
		switch(@$params['action'])
		{
			case 'read':
				$data = $this->read($params['*']);
				break;
			case 'print':
				$this->printer($params['*']);
				break;
			case 'categories':
				$data = $this->categories($params);
				break;
			case 'updates':
				$data = $this->updates($params);
				break;
			case 'series':
				$data = $this->series($params);
				break;
			case 'archive':
			default:
				$data = $this->intro($params);
				//$data = "";
		}
		$this->buffer ($data);
	}
	
	public function save(\Base $f3, $params)
	{
		if ( $params['action']=="write" )
		{
			// write review or reply to a review
			if($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews') )
			{
				$data = $f3->get('POST.write');

				// Obviously, there should be some text ...
				if ( "" == $data['text'] = trim($data['text']) )
					$errors[]= 'MessageEmpty';
				
			}
			else
			{
				// Error reporting
				
			}
		}
		echo "<pre>".print_r($params,TRUE)."</pre>";
		print_r($_POST);
		exit;
	}
	
	public function ajax(\Base $f3, $params)
	{
		if ( isset($params['segment']) AND $params['segment']=="search" )
		{
			$query = $f3->get('POST');
			$item = NULL;

			if ( is_array($query) ) list ( $item, $bind ) = each ( $query );

			$data = $this->model->searchAjax($item, $bind);
			echo json_encode($data);

			exit;
		}

		elseif ( isset($params['segment']) AND $params['segment']=="review_comment_form" )
		{
			// This is a comment to an element
			$id = (int)$f3->get('POST.childof');

			$errors = [];
			$removeButton = "";
			
			if($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews') )
			{
				if ( isset($_POST['write']) )
				{
					$data = $f3->get('POST.write');

					// Obviously, there should be some text ...
					if ( "" == $data['text'] = trim($data['text']) )
						$errors[]= 'MessageEmpty';

					if ( $_SESSION['userID'] )
					{
						if ( empty($errors) AND $insert_id = $this->model->saveComment($id, $data, TRUE) )
						{
							$removeButton = $id;
							$view = \View\Story::buildReviewCell($data, (int)$_POST['level'], $insert_id);
							//$f3->reroute('news/id='.$params['id'], false);
						}
						else $errors[] = "CannotSave";
					}
					else
					{
						// Check if captcha is initialized and matches user entry
						if ( empty($_SESSION['captcha']) OR !password_verify(strtoupper($data['captcha']),$_SESSION['captcha']) )
							$errors[]= 'CaptchaMismatch';

						// Guest can't post with an empty name
						if ( "" == $data['name'] = trim($data['name']) )
							$errors[]= 'GuestNameEmpty';

						// guest can't post URL (reg ex is not perfect, but it's a start)
						if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$data['text']))
							$errors[]= 'GuestURL';

						if ( empty($errors) AND $insert_id = $this->model->saveComment($id, $data) )
						{
							// destroy this session captcha
							unset($_SESSION['captcha']);
							$removeButton = $id;
							$view = \View\Story::buildReviewCell($data, (int)$_POST['level']);
						}

						//$f3->set('formError.1', "CaptchaMismatch");
					}
					// If no data was saved, we end up here, so we show the page again and it will display the errors
					$f3->set('formError', $errors);
				}
			}
			
			if(empty($view)) $view = \View\Story::commentForm($id);
			$this->buffer( array ( "", $view, $removeButton, ($_SESSION['userID']==0) ) , "BODY", TRUE );
		}
	}

	protected function intro($params)
	{
		if ( isset($params['id']) ) $this->parametric($params['id']); // 3.6

		$data = $this->model->intro();
		
		return \View\Story::viewList($data);
	}
	
	public function author($id)
	{
		list($info, $data) = $this->model->author($id);

		$stories = \View\Story::viewList($data);
		return [ $info[0], $stories];
	}
	
	protected function updates($params)
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if ( isset($params['date']) AND $selection = explode("-",$params['date']) )
		{
			$year = $selection[0];
			$month = isset($selection[1]) ? min($selection[1],12) : FALSE;
			$day = isset($selection[2]) ? min($selection[2],date("t", mktime(0, 0, 0, $month, 1, $year))) : FALSE;
			
			$data = $this->model->updates($year, $month, $day);
			return \View\Story::viewList($data);
		}
		else return $this->intro($params);
	}
	
	protected function categories($params)
	{
		$id = empty($params['*']) ? 0 : $params['*'];
		if(empty($params[3]))
		{
			$data = $this->model->categories( (int)$id );

			return \View\Story::categories($data);
		}
		else
		{
			// What was I doing here? Need more comments :/
			return "stub *controller-story-categories*";
		}
	}
	
	protected function printer($id)
	{
		$id = explode(",",$id);
		$printer = ($id[1]=="") ? "paper" : $id[1];
		$id = $id[0];
		
		if ( $printer == "epub" ) $this->model->printEPub($id);

	}
	
	public function series($params)
	{
		
		$this->buffer ( \View\Base::stub("Series") );
	}

	public function search(\Base $f3, $params)
	{
		$searchForm = strpos($params[0],"search");
		$get = [];
		if ( isset($params['*']) ) $get = $this->parametric($params['*']); // 3.6
		unset($get['page']);

		$searchData = ($f3->get('POST'));
		$searchData = array_filter(array_merge($get, $searchData));
		$ratings = $this->model->ratings();
		$f3->set('searchRatings', $ratings);

		$ratingMaxID = end($ratings)['rid'];
		// Add personal search preferences at some point
		$searchData['rating'][0] = min( (@$searchData['rating'][0] ?: 0), $ratingMaxID);

		// Add personal search preferences at some point
		$searchData['rating'][1] = min (
									max ( (@$searchData['rating'][1] ?: end($ratings)['rid']), $searchData['rating'][0] ),
									$ratingMaxID
									);
		
		$this->view->addTitle($f3->get('LN__Search'));
		
		// Author
		if ( empty($searchData['author']) )
			$f3->set('prepopulateData.author',"[]");
		else
			$f3->set('prepopulateData.author', $this->model->searchPrepopulate( "author", implode(",",$this->searchCleanInput($searchData['author']) ) ) );

		// Category
		if ( empty($searchData['category']) )
			$f3->set('prepopulateData.category',"[]");
		else
			$f3->set('prepopulateData.category', $this->model->searchPrepopulate( "category", implode(",",$this->searchCleanInput($searchData['category']) ) ) );

		// Tag
		if ( empty($searchData['tagIn']) )
			$f3->set('prepopulateData.tagIn',"[]");
		else
			$f3->set('prepopulateData.tagIn', $this->model->searchPrepopulate( "tag", implode(",",$this->searchCleanInput($searchData['tagIn']) ) ) );

		// excluded Tag
		if ( empty($searchData['tagOut']) )
			$f3->set('prepopulateData.tagOut',"[]");
		else
			$f3->set('prepopulateData.tagOut', $this->model->searchPrepopulate( "tag", implode(",",$this->searchCleanInput($searchData['tagOut']) ) ) );

		// return string
		if ( sizeof($searchData)>0 )
		{
			foreach ( $searchData as $k => $v )
			{
				if ( is_array($v) )
					$return[] = "{$k}=".implode(",",$v);
				elseif ( $v > "" )
					$return[] = "{$k}={$v}";
			}
			$return = implode(";",$return);
			$data = $this->model->search( $searchData, $return, $searchForm );
			if($searchForm) $this->buffer ( \View\Story::searchPage($searchData) );

			$this->buffer ( \View\Story::viewList($data) );
		}

		else
			$this->buffer ( \View\Story::searchPage() );
	}
	
	protected function searchCleanInput(&$arr=array())
	{
		$arr = is_array($arr) ? $arr : explode(",",$arr);
		foreach( $arr as &$a ) $a = (int)$a;
		$arr = array_diff($arr, array(0));
		return $arr;
	}

	protected function read($id)
	{
		$id = explode(",",$id);
		if($storyData = $this->model->getStory($id[0],empty($id[1])?1:$id[1]))
		{
			$story = $id[0];
			if ( empty($id[1]) AND $storyData['chapters']>1 )
				$id[1] = (TRUE===\Config::getPublic('story_toc_default')) ? "toc" : 1;

			if ( isset($id[1]) AND $id[1] == "reviews" )
			{
				$content = "*No reviews found";
				$tocData = $this->model->getMiniTOC($story);
				if ( $reviewData = $this->model->loadReviews($story) )
					$content = \View\Story::buildReviews($reviewData);
			}
			elseif ( isset($id[1]) AND $id[1] == "toc" AND $storyData['chapters']>1 )
			{
				$tocData = $this->model->getTOC($story);
				$content = \View\Story::buildTOC($tocData,$storyData);
			}
			else
			{
				if( empty($id[1]) OR !is_numeric($id[1]) ) $id[1] = 1;
				$chapter = $id[1] = max ( 1, min ( $id[1], $storyData['chapters']) );
				$tocData = $this->model->getMiniTOC($story);
				\Base::instance()->set('bigscreen',TRUE);
				$content = ($content = $this->model->getChapter( $story, $chapter )) ? : "Error";

				if ( $reviewData = $this->model->loadReviews($story,$storyData['chapid']) )
					$content .= \View\Story::buildReviews($reviewData);
			}

			$dropdown = \View\Story::dropdown($tocData,$id[1]);
			$view = \View\Story::buildStory($storyData,$content,$dropdown);
			$this->buffer($view);
		}
		else $this->buffer("Error, not found");
	}
	
	public function storyBlocks($select)
	{
		$select = explode(".",$select);

		if ( $select[1] == "stats" )
		{
			if ( FALSE === $data = \Cache::instance()->get('stats') )
			{
				$data = $this->model->blockStats();
				\Cache::instance()->set('stats', $data, 3600);
			}

			return \View\Story::archiveStats($data);
		}
		elseif ( $select[1] == "new" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 5;
			$data = $this->model->blockNewStories($items);
			$size = isset($select[3]) ? $select[3] : 'large';
			
			return \View\Story::blockStory("new", $data, $size);
		}
		elseif ( $select[1] == "random" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 1;
			$data = $this->model->blockRandomStory($items);
			
			return \View\Story::blockStory("random", $data);
		}
		elseif ( $select[1] == "featured" )
		{
			/*
				$items: 0 = all featured stories
				$order: "random" or NULL
			*/
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 1;
			$order = isset($select[3]) ? $select[3] : FALSE;
			$data = $this->model->blockFeaturedStory($items,$order);
			
			return \View\Story::blockStory("featured", $data);
		}
		elseif ( $select[1] == "recommend" )
		{
			// break if module not enabled
			if ( empty(\Config::getPublic('optional_modules')['recommendations']) ) return NULL;
			/*
				$items: 0 = all featured stories
				$order: "random" or NULL
			*/
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 1;
			$order = isset($select[3]) ? $select[3] : FALSE;
			
			$data = $this->model->blockRecommendedStory($items,$order);
			
			return \View\Story::blockStory("recommended", $data);
		}
		elseif ( $select[1] == "tagcloud" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 15;
			$data = $this->model->blockTagcloud($items);

			return \View\Story::blockTagcloud($data);
		}
		return "";
	}

	
}
