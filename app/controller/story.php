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
		\Registry::get('VIEW')->addTitle( \Base::instance()->get('LN__Stories') );
	}

	public function index(\Base $f3, $params)
	{
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
				$data = $this->categories(isset($params['id'])?$params['id']:FALSE);
				break;
				
		}
		$this->buffer ($data);
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
		return print_r($data,1);
	}
	
	public function author($id)
	{
		list($info, $data) = $this->model->author($id);
		//return print_r($info,1);
		$stories = \View\Story::viewList($data);
		return [ $info[0], $stories];
	}
	
	protected function categories($id)
	{
		return 1;
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
		\Registry::get('VIEW')->addTitle($f3->get('LN__Search'));

		if ( isset($params[1]) )
		{
			$termsTMP = explode("/",$params[1]);
			foreach ( $termsTMP as $t )
			{
				list ($term, $param) = explode(":",$t);
				$terms[$term] = explode(",",$param);
			}
		}
		else $terms = NULL;



		$this->buffer ( print_r($terms,TRUE) );
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
				$tocData = $this->model->getMiniTOC($story);
				$content = "";
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
