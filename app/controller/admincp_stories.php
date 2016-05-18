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
				if ($params['chapter']=="new")
				{
					
				}
				else
				{
					$chapter = min(max(1,(int)$params['chapter']),$storyInfo['chapters']);
					$this->buffer ( $this->editChapter($chapter, $storyInfo) );
				}
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
	
//	protected function editChapter(int $chapter, array $storyInfo)
	protected function editChapter($chapter, array $storyInfo)
	{
		return "";
	}

	public function save(\Base $f3, $params)
	{
		
	}
}