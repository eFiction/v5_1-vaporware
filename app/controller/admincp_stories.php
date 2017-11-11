<?php
namespace Controller;

class AdminCP_Stories extends AdminCP
{
	var $moduleBase = "stories";
	var $submodules = [ "pending", "edit", "add" ];

	public function index(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Stories') );

		switch( $this->moduleInit(@$params['module']) )
		{
			case "pending":
				$this->pending($f3, $params);
				break;
			case "edit":
				$this->edit($f3, $params);
				break;
			case "add":
				$this->buffer( \View\Base::stub() );
				break;
			case "home":
				$this->home($f3);
				break;
			default:
				$this->buffer(\Template::instance()->render('access.html'));
		}
	}

	public function ajax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;

		$post = $f3->get('POST');
		
		if ( $params['module']=="search" )
			$data = $this->model->ajax("storySearch", $post);

		elseif ( $params['module']=="editMeta" )
			$data = $this->model->ajax("editMeta", $post);
		
		elseif ( $params['module']=="chaptersort" )
		{
			//if ( isset($params[2]) ) $params = $this->parametric($params[2]); // 3.6
			$data = $this->model->ajax("chaptersort", $post);
		}
		
		echo json_encode($data);
		exit;
	}

	protected function home(\Base $f3)
	{
		$this->buffer( \View\Base::stub() );
	}

	protected function pending(\Base $f3, $params)
	{
		$this->response->addTitle( $f3->get('LN__AdminMenu_Pending') );
		$f3->set('title_h3', $f3->get('LN__AdminMenu_Pending') );

		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		// search/browse
		$allow_order = array (
				"id"		=>	"sid",
				"date"		=>	"timestamp",
				"title"		=>	"title",
		);

		// page will always be an integer > 0
		$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

		// sort order
		$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "id";
		$sort["order"]		= $allow_order[$sort["link"]];
		$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="asc") ?	"asc" : "desc";

		$data = $this->model->getPendingStories($page, $sort);
		$this->buffer( $this->template->listPendingStories($data, $sort) );
	}
	
	protected function edit(\Base $f3, $params)
	{
		if ( isset($params['*']) )
		{
			list($params, $returnpath) = array_pad(explode(";returnpath=",$params['*']), 2, '');
			$params = $this->parametric($params);
			$params['returnpath'] = $returnpath;
		}
		
		if ( empty($params['story']) )
		{
			// Select story form
			$this->buffer( $this->template->searchStoryForm() );
			return TRUE;
		}
		else
		{
			$storyInfo = $this->model->loadStoryInfo((int)$params['story']);
			if ( $storyInfo AND isset($params['chapter']) )
			{
				$storyInfo['returnpath'] = $returnpath;
				$this->buffer ( $this->editChapter($params, $storyInfo) );
			}
			elseif ( $storyInfo )
			{
				$storyInfo['returnpath'] = $returnpath;
				$chapterList = $this->model->loadChapterList($storyInfo['sid']);
				$prePopulate = $this->model->storyEditPrePop($storyInfo);
				$this->buffer( $this->template->storyMetaEdit($storyInfo,$chapterList,$prePopulate) );
			}
			else
			{
				$this->buffer ( "__Error" );
			}
		}
	}
	
	protected function editChapter(array $params, array $storyInfo)
	{
		$chapterList = $this->model->loadChapterList($storyInfo['sid']);

		if ($params['chapter']=="new")
		{
			$chapterInfo =
			[
				"sid" 		=> $storyInfo['sid'],
				"chapid" 	=> "new",
				"title"		=> "",
				"notes"		=> "",
				"validated"	=> '03',
				"rating"	=> 0,
				"chaptertext" => "",
			];
		}
		else
		{
			$chapterInfo = $this->model->getChapter($storyInfo['sid'],(int)$params['chapter']);
		}
		$chapterInfo['storytitle'] = $storyInfo['title'];
		
		$plain = @$params['style']=="plain";

		$this->buffer( \View\AdminCP::storyChapterEdit($chapterInfo,$chapterList,$plain) );
	}

	public function save(\Base $f3, $params)
	{
		if ( isset($params['*']) )
		{
			list($params, $returnpath) = array_pad(explode(";returnpath=",$params['*']), 2, '');
			$params = $this->parametric($params);
			$params['returnpath'] = $returnpath;
		}

		$current = $this->model->loadStoryMapper($params['story']);
		
		if ( $current['sid'] != NULL )
		{
			$post = $f3->get('POST');
			if ( isset($params['chapter']) AND $params['chapter']=="new" )
			{
				$chapter = $this->model->addChapter($params['story'], $post['form']);
				$f3->reroute("/adminCP/stories/edit/story={$current['sid']};chapter={$chapter}", false);
				exit;
			}
			elseif ( isset($params['chapter']) )
			{
				$this->model->saveChapterChanges($params['chapter'], $post['form']);
				$f3->reroute("/adminCP/stories/edit/story={$current['sid']};chapter={$params['chapter']}", false);
				exit;
			}
			else
			{
				$this->model->saveStoryChanges($current, $post['form']);
				$f3->reroute('/adminCP/stories/edit/story='.$current['sid'], false);
				exit;
			}
		}
		
		var_dump ( $current['sid'] );
		
		print_r($params);
		
		print_r($post);
	}
}
