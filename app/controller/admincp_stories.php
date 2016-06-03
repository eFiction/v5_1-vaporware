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
				$this->buffer( \View\Base::stub() );
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
			if ( isset($params[2]) ) $params = $this->parametric($params[2]);
			$data = $this->model->ajax("chaptersort", $post);
		}
		
		echo json_encode($data);
		exit;
	}

	protected function home(\Base $f3)
	{
		$this->buffer( \View\Base::stub() );
	}
	
	protected function edit(\Base $f3, $params)
	{
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);
		
		if ( empty($params['story']) )
		{
			// Select story form
			$this->buffer( \View\AdminCP::searchStoryForm() );
			return TRUE;
		}
		else
		{
			$storyInfo = $this->model->loadStoryInfo((int)$params['story']);
			if ( $storyInfo AND isset($params['chapter']) )
			{
				$this->buffer ( $this->editChapter($params, $storyInfo) );
			}
			elseif ( $storyInfo )
			{
				$chapterList = $this->model->loadChapterList($storyInfo['sid']);
				$prePopulate = $this->model->storyEditPrePop($storyInfo);
				$this->buffer( \View\AdminCP::storyMetaEdit($storyInfo,$chapterList,$prePopulate) );
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
				"validated"	=> 0,
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
		if ( isset($params[2]) ) $params = $this->parametric($params[2]);
		
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
