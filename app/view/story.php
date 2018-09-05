<?php
namespace View;

class Story extends Base
{
	public function viewList($data)
	{
		while ( list($key, $value) = each($data) )
			$this->dataProcess($data[$key], $key);

		$this->f3->set('stories', $data);
		
		return $this->render( 'story/listing.html' );
	}
	
	public function searchHead($terms=array(), $return=NULL, $search=NULL)
	{
		$this->f3->set('searchForm', $terms);
		$this->f3->set('searchLink', $return);

		if ( $search )
			return $this->render('story/head.search.html');
		else
			return $this->render('story/head.browse.html');
	}

	public function vTest()
	{
		return "nichts";
	}

	public function buildTOC($tocData, $storyData)
	{
		$this->javascript('body', TRUE, 'jquery.columnizer.js' );
		$this->javascript('body', FALSE, "$(function(){ $('.columnize').columnize({ columns: 2 }); });" );
		
		$this->f3->set('tocData', $tocData);
		$this->f3->set('storyID', $storyData['sid']);
		
		return $this->buildInfoblock($storyData) . $this->render('story/toc.html');
	}
	
	public function buildInfoblock($storyData)
	{
		$storyData['cache_categories'] = json_decode($storyData['cache_categories'],TRUE);
		$storyData['cache_tags'] = json_decode($storyData['cache_tags'],TRUE);
		$storyData['cache_characters'] = json_decode($storyData['cache_characters'],TRUE);

		$this->f3->set('storyData', $storyData);

		return $this->render('story/information.html');
	}

/*	public static function buildReviews($reviewData,$selection)
	{
		\Base::instance()->set('returnpath', \Base::instance()->get('PATH') );
		\Base::instance()->set('story_reviews', $reviewData);
		\Base::instance()->set('selection', $selection);

		return parent::render('story/reviews.html');
	}		*/

	// for AJAX purposes	- requires reviews.inner
	// currently unused
	/*
	public static function buildReviewCell($data, $level = 1, $insert_id = NULL)
	{
		$item =
		[
			"name"		=> ($_SESSION['userID'] == 0) ? $data['name'] : $_SESSION['username'],
			"timestamp" => time(),
			"text"		=> $data['text'],
			"uid"		=> $_SESSION['userID'],
			"level"		=> $level,
			"id"		=> $insert_id,
		];
		\Base::instance()->set('item', $item);
		\Base::instance()->set('returnpath', \Base::instance()->get('PATH') );

		return parent::render('story/reviews.inner.html');
	}
	*/
	
	
	public function commentForm(array $in_structure)
	{
		// renaming fields for use in base comment form
		$out_structure = [ 
					"level"			=> $in_structure['level'],
					"element"		=> $in_structure['story'],
					"subelement"	=> $in_structure['chapter'],
					"childof"		=> $in_structure['childof'],
				];
		
		$data = [ 
					"cancel" 				=> TRUE,
					"feedback_form_label"	=> ($in_structure['level'] > 0) ? "__COMMENT" : "__REVIEW",
					"postText"				=> \Base::instance()->get('POST.write.text'),
					"postName"				=> \Base::instance()->get('POST.write.name'),
				];
		
		// Label for the submit button
		if ( $in_structure['level'] == 0 )
		{
			if ( $in_structure['chapter'] > 0 )
				$data['submit_button_label'] = $this->f3->get("LN__Button_reviewChapter");
			else
				$data['submit_button_label'] = $this->f3->get("LN__Button_reviewStory");
		}
		else $data['submit_button_label'] = $this->f3->get("LN__Button_writeComment");

		// defined in \View\Base
		return parent::commentFormBase($out_structure,$data);
	}

	public function buildStory($storyData,$content,$dropdown,$view=1)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'chapter.js?' );
		\Registry::get('VIEW')->javascript('body', FALSE, "var url='".\Base::instance()->get('BASE')."/story/read/{$storyData['sid']},'" );

		$storyData['cache_authors'] = json_decode($storyData['cache_authors'],TRUE);
		$storyData['published'] = date( \Config::getPublic('date_format_short'), $storyData['published']);
		$storyData['modified'] = date( \Config::getPublic('date_format_short'), $storyData['modified']);

		\Base::instance()->set('data', [
											"story" 	=> $storyData,
											"content" 	=> $content,
											"dropdown" 	=> $dropdown,
											"feedback_form_label" => "__Review",
											"view"		=> $view,
											"postName"	=> '',
											"postText"	=> '',
										]);
		\Base::instance()->set('returnpath', \Base::instance()->get('PATH') );
		
		return parent::render('story/single.html');
	}
	
	public function buildReviews($storyData, $reviewData, $chapter, $selected)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'chapter.js?' );

		parent::dataProcess($storyData);
		\Base::instance()->set('story', $storyData);
		\Base::instance()->set('data', [
											"reviews" 	=> $reviewData,
											"selected"	=> $selected,
											"chapter"	=> $chapter,
										]);
		\Base::instance()->set('returnpath', \Base::instance()->get('PATH') );
		
		return parent::render('story/reviews.html');
	}

	public function dropdown($data,$chapter)
	{
		$i=1;
		//if(sizeof($data) > 1) 
		$dropDown[] = array ( FALSE, "toc", FALSE, \Base::instance()->get("LN__TOC") );
		foreach ( $data as $item )
		{
			$dropDown[] = array ( ($chapter==$item['chapter']), $item['chapter'], $i++, $item['title']);
		}
		//$dropDown[] = array ( ($chapter==="reviews"), "reviews", FALSE, \Base::instance()->get("LN__Reviews") );
		return $dropDown;
	}
	
	public function categories($data)
	{
		$this->f3->set('categoriesData', $data);
		
		return $this->render('story/categories.html');
	}
	
	public function epubXMLtag()
	{
		return "<?xml version='1.0' encoding='utf-8'?>\n";
	}

	public function epubContainer()
	{
		return $this->TPL->render('container.xml');
	}

	public function epubCSS()
	{
		return $this->TPL->render('epub.css');
	}

	public function epubPage($body, $title, $language)
	{
		return $this->TPL->render(
			'base.xhtml',
			'text/html',
			[
				"BODY"		=> $body, 
				"TITLE"		=> $title,
				"LANGUAGE"	=> $language
			]
		);
	}

	public function epubChapter($title, $content)
	{
		$ebook = $this->f3->get('EPUB');
		if ( $ebook['version']==3 )
		{
			return $this->TPL->render(
				'chapter_v3.xhtml',
				'text/html',
				[
					"CONTENT" 		=> $content, 
					"CHAPTER_TITLE" => $title,
					"LANGUAGE" 		=> $ebook['language']
				]
			);
		}
		elseif ( $ebook['version']==2 )
		{
			return $this->TPL->render(
				'chapter_v2.xhtml',
				'text/html',
				[
					"CONTENT" 		=> $content, 
					"CHAPTER_TITLE" => $title,
				]
			);
		}
	}

	public function epubTitle()
	{
		$ebook = $this->f3->get('EPUB');

		return $this->TPL->render(
			"title".($ebook['version']==3 ?"_v3":"_v2").".xhtml",
			'application/xhtml+xml',
			[
				"STORY_TITLE"	=>	$ebook['title'],
				"AUTHOR"		=>	$ebook['authors'],
		    	"NOTES"			=>	$ebook['storynotes']
			]
		);
	}
	
	public function epubRoot( $chapterTOC )
	{
		$ebook = $this->f3->get('EPUB');
		if ( $ebook['version']==3 )
		{
			
		}
		elseif ( $ebook['version']==2 )
		{
			return $this->TPL->render(
				'root_v2.opf',
				'application/xhtml+xml',
				[
					"pages" => $chapterTOC,
					"ebook" => $ebook,
				]
			);
		}
	}

	public function epubTOC( $chapterTOC, $version = 2 )
	{
		$ebook = \Base::instance()->get('EPUB');
		if ( $version==3 )
		{
			return $this->TPL->render(
				'toc.xhtml',
				'application/xhtml+xml',
				[
					"pages" => $chapterTOC,
					"ebook" => $ebook,
				]
			);
		}
		else
		{
			return $this->TPL->render(
				'toc.ncx',
				'application/xhtml+xml',
				[
					"pages" => $chapterTOC,
					"ebook" => $ebook,
				]
			);
		}
	}

	public static function archiveStats($stats)
	{
		\Base::instance()->set('archiveStats', $stats);
		return parent::render('blocks/stats.html');
	}
	
	public function blockStory($type, $stories=[], $extra=NULL)
	{
		$blocks = [ "recommended", "featured", "random", "new" ];

		if ( in_array($type, $blocks) )
		{
			while ( list($key, ) = each($stories) )
				$this->dataProcess($stories[$key]);

			$this->f3->set('renderData', $stories);
			$this->f3->set('extra', $extra);

			return $this->render("story/block.{$type}.html");
		}
		else return NULL;
	}
	
	public static function blockTagcloud($taglist)
	{
		$max = current($taglist)['count'];
		$min = end($taglist)['count'];
		shuffle($taglist);
		foreach ( $taglist as &$tag )
		{
			$size_factor = ( \Config::getPublic('tagcloud_spread') - 1 ) / ( ($min==$max)?1:($max - $min) ) * ( $tag['count'] - $min ) + 1;
			$tag['z_index'] = $max-$tag['count'];
			$tag['percent'] = intval(\Config::getPublic('tagcloud_basesize')*$size_factor);
		}

		\Base::instance()->set('renderData', $taglist);
		return parent::render('story/block.tagcloud.html');
	}
	
}
