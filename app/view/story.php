<?php
namespace View;

class Story extends Base
{
	public static function viewList($data)
	{
		while ( list($key, $value) = each($data) )
			Story::dataProcess($data[$key], $key);

		\Base::instance()->set('stories', $data);
		
		return parent::render( 'story/listing.html' );
	}
	
	public static function searchPage($terms=array(), $data=array())
	{
		$form = \View\Story::searchForm($terms);
		return $form;
	}
	
	protected static function searchForm($terms)
	{
		\Base::instance()->set('searchForm', $terms);
		
		return parent::render('story/search.html');
	}
	
	protected static function dataProcess(&$item, $key=NULL)
	{
		if (isset($item['modified']))	$item['modified']	= ($item['modified'] > ($item['published'] + (24*60*60) ) ) ?
																	date(\Config::getPublic('date_format_short'),$item['modified']) :
																	NULL;
		if (isset($item['published']))	$item['published']	= date(\Config::getPublic('date_format_short'),$item['published']);
										$item['number']		= isset($item['inorder']) ? "{$item['inorder']}&nbsp;" : "";
		if (isset($item['wordcount'])) 	$item['wordcount']	= number_format($item['wordcount'], 0, '','.');
		if (isset($item['count'])) 		$item['count']		= number_format($item['count'], 0, '','.');

		if (isset($item['cache_authors']))
		{
												$item['authors'] 	= $item['cache_authors'] = json_decode($item['cache_authors'],TRUE);
												array_walk($item['authors'], function (&$v, $k){ $v = $v[1];} );
		}

		if (isset($item['cache_categories'])) 	$item['cache_categories']	= json_decode($item['cache_categories'],TRUE);
		if (isset($item['cache_rating'])) 		$item['cache_rating']		= json_decode($item['cache_rating'],TRUE);
		if (isset($item['cache_tags'])) 		$item['cache_tags']			= json_decode($item['cache_tags'],TRUE);
		if (isset($item['cache_characters'])) 	$item['cache_characters']	= json_decode($item['cache_characters'],TRUE);
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

		Story::dataProcess($storyData);
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
	
	public static function categories($data)
	{
		\Base::instance()->set('categoriesData', $data);
		
		return parent::render('story/categories.html');
	}
	
	public static function epubXMLtag()
	{
		return "<?xml version='1.0' encoding='utf-8'?>\n";
	}

	public static function epubContainer()
	{
		return parent::render('container.xml');
	}

	public static function epubCSS()
	{
		return parent::render('epub.css');
	}

	public static function epubPage($body, $title, $language)
	{
		return parent::render('base.xhtml', 'text/html', [ 	"BODY" => $body, 
																										"TITLE" => $title,
																										"LANGUAGE" => $language
																									]
																);
	}

	public static function epubChapter($title, $content)
	{
		$ebook = \Base::instance()->get('EPUB');
		if ( $ebook['version']==3 )
		{
			return parent::render('chapter_v3.xhtml',
																	'text/html',
																	[ 	"CONTENT" => $content, 
																		"CHAPTER_TITLE" => $title,
																		"LANGUAGE" => $ebook['language']
																	]
															);
			
		}
		elseif ( $ebook['version']==2 )
		{
			return parent::render('chapter_v2.xhtml',
																	'text/html',
																	[ 	"CONTENT" => $content, 
																		"CHAPTER_TITLE" => $title,
																	]
															);
		}
	}

	public static function epubTitle()
	{
		$ebook = \Base::instance()->get('EPUB');
		$file = "title".($ebook['version']==3 ?"_v3":"_v2").".xhtml";
		return parent::render($file, 'application/xhtml+xml', [ "STORY_TITLE"	=>	$ebook['title'],
																							"AUTHOR"			=>	$ebook['authors'],
		    																				"NOTES"			=>	$ebook['storynotes']
																							]
																);
	}
	
	public static function epubRoot( $chapterTOC )
	{
		$ebook = \Base::instance()->get('EPUB');
		if ( $ebook['version']==3 )
		{
			
		}
		elseif ( $ebook['version']==2 )
		{
			return parent::render('root_v2.opf', 'application/xhtml+xml',
																		[
																			"pages" => $chapterTOC,
																			"ebook" => $ebook,
																		]
																	);
		}
	}

	public static function epubTOC( $chapterTOC, $version = 2 )
	{
		$ebook = \Base::instance()->get('EPUB');
		if ( $version==3 )
		{
			return parent::render('toc.xhtml', 'application/xhtml+xml',
																		[
																			"pages" => $chapterTOC,
																			"ebook" => $ebook,
																		]
																	);
		}
		else
		{
			return parent::render('toc.ncx', 'application/xhtml+xml',
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
	
	public static function blockStory($type, $stories=[], $extra=NULL)
	{
		$blocks = [ "recommended", "featured", "random", "new" ];

		if ( in_array($type, $blocks) )
		{
			while ( list($key, ) = each($stories) )
				Story::dataProcess($stories[$key]);

			\Base::instance()->set('renderData', $stories);
			\Base::instance()->set('extra', $extra);

			return parent::render("story/block.{$type}.html");
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
