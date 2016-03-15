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
		
		if ( $f3->get('AJAX')===TRUE )
			$this->ajax($f3, $params);
		
		if ( empty($params['action']) )
			$data = $this->intro($f3);
		
		else switch($params['action'])
		{
			case 'read':
				$data = $this->read($params['id']);
				break;
			case 'print':
				$this->printer($params['id']);
				break;
			case 'categories':
				$data = $this->categories($params);
				break;
			default:
				$data = "";
		}
		$this->buffer ($data);
	}
	
	public function ajax(\Base $f3, $params)
	{
		if ( isset($params['id']) && $params['id']=="search" )
		{
			$query = explode( "=", parse_url($params[0])['query'] );

			if( @$query[0]=="tag" )
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 5";
				$bind = [ "tag" =>  "%{$query[1]}%" ];
			}
			elseif( @$query[0]=="author" )
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND ( U.groups & 5 ) ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ "nickname" =>  "%{$query[1]}%" ];
			}
			elseif( @$query[0]=="category" )
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 5";
				$bind = [ "category" =>  "%{$query[1]}%" ];
			}

			if ( isset($ajax_sql) )
			{
				$data = $this->model->searchAjax($ajax_sql, $bind);
				echo json_encode($data);
			}
			exit;
		}
		elseif ( $id = @explode(",",$params['id']) )
		{
			if ( $id[1]=="commentform" )
			{
				if(empty($id[2])) exit;
				echo \View\Story::commentForm((int)$id[0], (int)$id[2]);
			}
			exit;
		}
	}
	
	protected function intro(\Base $f3)
	{
		$data = $this->model->intro();
		
		return \View\Story::viewList($data);
		/*
				$limit = 5;
		// now build the query for the listing
		$this->sql_replacement['limit'] = "LIMIT 0,{$eFI->config['story_intro_items']}";
		$this->sql_replacement['order'] = "ORDER BY {$eFI->config['story_intro_order']} DESC";

		return $this->buildScreenData($this->buildSQL(),FALSE);

		*/
		//return print_r($data,1);
	}
	
	public function author($id)
	{
		list($info, $data) = $this->model->author($id);
		//return print_r($info,1);
		$stories = \View\Story::viewList($data);
		return [ $info[0], $stories];
	}
	
	protected function categories($params)
	{
		$id = empty($params['id']) ? 0 : $params['id'];
		if(empty($params[3]))
		{
			$data = $this->model->categories( (int)$id );

			return \View\Story::categories($data);
		}
		else
		{
			
		}
	}
	
	protected function printer($id)
	{
		$id = explode(",",$id);
		$printer = ($id[1]=="") ? "paper" : $id[1];
		$id = $id[0];
		
		if ( $printer == "epub" ) $this->model->printEPub($id);

	}

	public function search(\Base $f3, $params)
	{
		$this->view->addTitle($f3->get('LN__Search'));

		if ( isset($params[1]) )
		{
			$termsTMP = explode(" ",$params[1]);
			foreach ( $termsTMP as $t )
			{
				list ($term, $param) = explode(":",$t);
				$terms[$term] = explode(",",$param);
			}
			$data = $this->model->search($terms);
		}
		else $terms = NULL;



		$this->buffer ( \View\Story::searchPage() );
//		$this->buffer ( print_r($terms,TRUE) );
	}

	protected function read($id)
	{
		$id = explode(",",$id);

		if($storyData = $this->model->getStory($id[0]))
		{
			$story = $id[0];
			if ( empty($id[1]) AND $storyData['chapters']>1 ) $id[1] = "toc";

			if ( isset($id[1]) AND $id[1] == "reviews" )
			{
				$content = "*No reviews found";
				$tocData = $this->model->getMiniTOC($story);
				//$offset = isset((int)@$id[2]) ? 1:2;
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
			}

			$dropdown = \View\Story::dropdown($tocData,$id[1]);
			$view = \View\Story::buildStory($storyData,$content,$dropdown);
			$this->buffer($view);
		}
		else $this->buffer("Error, not found");
		/*
		if( isset($id[1]) AND is_numeric($id[1]) )
		{
			$data = $this->model->getStory($id);
		}
		else
		{
			$data = $this->model->getTOC($id[0]);
		}
		return ( $data );
		*/
	}
	
	public function storyBlocks($select)
	{
		$select = explode(".",$select);
/*
		if ( empty($select) OR $select == ".home" )
		{
			return \View\Story::storyHome();
		}
*/
		if ( $select[1] == "stats" )
		{
			$statsCache = $this->model->blockStats();

			foreach($statsCache as $sC)
			{
				$data[$sC['field']] = $sC['value'];
			}
			if ( $data['newmember']!="" ) $data['newmember'] = explode(",",$data['newmember']);
			return \View\Story::archiveStats($data);
		}
		elseif ( $select[1] == "new" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 5;
			$data['data'] = $this->model->blockNewStories($items);
			$data['size'] = isset($select[3]) ? $select[3] : 'large';
			
			return \View\Story::blockNewStories($data);
		}
		elseif ( $select[1] == "random" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 1;
			$data = $this->model->blockRandomStory($items);
			
			return \View\Story::blockRandomStory($data);
		}
		elseif ( $select[1] == "featured" )
		{
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : 1;
			$order = isset($select[3]) ? $select[3] : FALSE;
			$data = $this->model->blockFeaturedStory($items,$order);
			
			return \View\Story::blockFeaturedStory($data);
		}
		elseif ( $select[1] == "recommend" )
		{
			// break if module not enabled
			if ( empty(\Config::instance()->modules_enabled['recommendation']) ) return NULL;
			
			return "**recommend**";
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
