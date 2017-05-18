<?php
namespace Controller;

class Story extends Base
{

	public function __construct()
	{
		$this->model = \Model\Story::instance();
		$this->template = new \View\Story();
	}

	public function beforeroute()
	{
		parent::beforeroute();
		//$this->view  = \Registry::get('VIEW');
		//$this->view->addTitle( \Base::instance()->get('LN__Stories') );
		$this->template->addTitle( \Base::instance()->get('LN__Stories') );
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
		list($requestpath, $returnpath) = array_pad(explode(";returnpath=",$params['*']), 2, '');
		$params['returnpath'] = $returnpath;

		if ( $params['action']=="read" )
		{
			if ( isset($_POST['s_data']) )
			{
				parse_str($f3->get('POST.s_data'), $data);
				//print_r($data);
			}
			elseif ( isset($_POST['write']) )
				$data = $f3->get('POST');
			
			// write review or reply to a review
			if( isset($data) AND ($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews')) )
			{
				$errors = $this->validateReview($data['write']);
				
				if ( sizeof($errors)==0 )
				{
					$insert_id = $this->model->saveComment($data['childof'], $data['write'], ($_SESSION['userID']!=0));
					$return = (empty($params['returnpath']) ? $requestpath."#r".$insert_id : $returnpath);
					$f3->reroute($params['returnpath'], false);
					exit;
				}
				else
				{
					echo "<pre>".print_r($params,TRUE).print_r(@$data,TRUE).print_r(@$errors,TRUE)."</pre>";
				}
			}
			else
			{
				// Error reporting
				
			}
		}
		
		//echo "<pre>".print_r($params,TRUE).print_r(@$data,TRUE).print_r(@$errors,TRUE)."</pre>";

		// If nothing else has worked so far, return to where we came from and pretend this was intentional
		//$f3->reroute($requestpath, false);
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
			$saveData = "";
			
			if($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews') )
			{
				if ( isset($_POST['write']) )
				{
					$data = $f3->get('POST.write');
					
					$errors = $this->validateReview($data);

					$f3->set('formError', $errors);
					if ( empty($errors) ) $saveData = 1;
				}
			}
			
			if(empty($view)) $view = \View\Story::commentForm($id);
			$this->buffer( [ "", $view, $saveData, ($_SESSION['userID']==0) ], "BODY", TRUE );
			//$this->buffer( array ( "", $view, (sizeof($errors)>0?"":1), ($_SESSION['userID']==0) ) , "BODY", TRUE );
			//$this->buffer( array ( "", $view, (int)empty($errors), ($_SESSION['userID']==0) ) , "BODY", TRUE );
		}
	}
	
	protected function validateReview($data)
	{
		$errors = [];

		// Obviously, there should be some text ...
		if ( "" == $data['text'] = trim($data['text']) )
			$errors[]= 'MessageEmpty';

		if ( !$_SESSION['userID'] )
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
		}
		
		return $errors;
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
		
		if ( $printer == "epub" )
		{
			$epubData = $this->model->printEPub($id);
			
			if($file = realpath("tmp/epub/s{$epubData['sid']}.zip"))
			{
				$filesize = filesize($file);
				$ebook = @fopen($file,"rb");
			}
			else
			{
				list($ebook, $filesize) = $this->model->createEPub($epubData['sid']);
			}
			
			if ( $ebook )
			{
				// http://stackoverflow.com/questions/93551/how-to-encode-the-filename-parameter-of-content-disposition-header-in-http
				$filename = rawurlencode ( $epubData['title']." by ".$epubData['authors'].".epub" );
				
				header("Content-type: application/epub+zip; charset=utf-8");
				header("Content-Disposition: attachment; filename=\"{$filename}\"; filename*=utf-8''".$filename);
				header("Content-length: ".$filesize);
				header("Cache-control: private");

				while(!feof($ebook))
				{
					$buffer = fread($ebook, 8*1024);
					echo $buffer;
				}
				fclose ($ebook);

				exit;
			}
		}
		
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
		
		//\Registry::get('VIEW')->addTitle($f3->get('LN__Search'));
		$this->template->addTitle($f3->get('LN__Search'));
		
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
		@list($story, $view, $selected) = explode(",",$id);

		if($storyData = $this->model->getStory($story,empty($view)?1:$view))
		{
			if ( empty($view) AND $storyData['chapters']>1 )
				$view = (TRUE===\Config::getPublic('story_toc_default')) ? "toc" : 1;

/* 			if ( isset($view) AND $view == "reviews" )
			{
				$content = "*No reviews found";
				$tocData = $this->model->getMiniTOC($story);
				if ( $reviewData = $this->model->loadReviews($story,$selected) )
					$content = \View\Story::buildReviews($reviewData,$view);
			}
			elseif ( isset($view) AND $view == "toc" AND $storyData['chapters']>1 )
			{
				$tocData = $this->model->getTOC($story);
				$content = \View\Story::buildTOC($tocData,$storyData);
			}
			else
			{
				if( empty($view) OR !is_numeric($view) ) $view = 1;
				$chapter = $view = max ( 1, min ( $view, $storyData['chapters']) );
				$tocData = $this->model->getMiniTOC($story);
				\Base::instance()->set('bigscreen',TRUE);
				$content = ($content = $this->model->getChapter( $story, $chapter )) ? : "Error";

				if ( $reviewData = $this->model->loadReviews($story,$selected,$storyData['chapid']) )
					$content .= \View\Story::buildReviews($reviewData,$view);
			}
 */			
			if ( isset($view) AND $view == "toc" AND $storyData['chapters']>1 )
			{
				$tocData = $this->model->getTOC($story);
				$content = $this->template->buildTOC($tocData,$storyData);
			}
			else
			{
				$tocData = $this->model->getMiniTOC($story);

				if ( isset($view) AND $view == "reviews" )
				{
					$content = NULL;
					$storyData['reviewData'] = $this->model->loadReviews($story,$selected);
				}
				else
				{
					if( empty($view) OR !is_numeric($view) ) $view = 1;
					$chapter = $view = max ( 1, min ( $view, $storyData['chapters']) );
					\Base::instance()->set('bigscreen',TRUE);
					$content = ($content = $this->model->getChapter( $story, $chapter )) ? : "Error";

					$storyData['reviewData'] = $this->model->loadReviews($story,$selected,$storyData['chapid']);
				}
			}

			$dropdown = \View\Story::dropdown($tocData,$view);
			$view = \View\Story::buildStory($storyData,$content,$dropdown,$view);
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
