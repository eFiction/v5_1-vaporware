<?php
namespace View;

class Story extends Base
{
	public static function viewList($data)
	{
		while ( list($key, $value) = each($data) )
			Story::dataProcess($data[$key], $key);
		
		\Base::instance()->set('stories', $data);
		
		return \Template::instance()->render( 'story/listing.html' );
	}
	
	public static function searchPage($terms=array(), $data=array())
	{
		$form = \View\Story::searchForm($terms);
		return $form;
	}
	
	protected static function searchForm($terms)
	{
		\Base::instance()->set('searchForm', $terms);
		
		return \Template::instance()->render('story/search.html');
	}
	
	protected static function dataProcess(&$item, $key=NULL)
	{
		if (isset($item['published']))	$item['published']	= date(\Base::instance()->get('CONFIG')['date_format_short'],$item['published']);
		if (isset($item['modified']))	$item['modified']	= date(\Base::instance()->get('CONFIG')['date_format_short'],$item['modified']);
										$item['number']		= isset($item['inorder']) ? "{$item['inorder']}&nbsp;" : "";
		if (isset($item['wordcount'])) 	$item['wordcount']	= number_format($item['wordcount'], 0, '','.');
		if (isset($item['count'])) 		$item['count']		= number_format($item['count'], 0, '','.');
										$item['authors'] 	= $item['authorblock'] = unserialize($item['authorblock']);

		array_walk($item['authors'], function (&$v, $k){ $v = $v[1];} );

		if (isset($item['categoryblock'])) 	$item['categoryblock']	= unserialize($item['categoryblock']);
		if (isset($item['tagblock'])) 		$item['tagblock']		= unserialize($item['tagblock']);
		if (isset($item['characterblock'])) $item['characterblock']	= unserialize($item['characterblock']);
	}

	public static function buildTOC($tocData, $storyData)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'jquery.columnizer.js' );
		\Registry::get('VIEW')->javascript('body', FALSE, "$(function(){ $('.columnize').columnize({ columns: 2 }); });" );
		
		$infoblock = \View\Story::buildInfoblock($storyData);

		\Base::instance()->set('tocData', $tocData);
		\Base::instance()->set('storyID', $storyData['sid']);
		
		return $infoblock.\Template::instance()->render('story/toc.html');
	}
	
	public static function buildInfoblock($storyData)
	{
		$storyData['categoryblock'] = unserialize($storyData['categoryblock']);
		$storyData['tagblock'] = unserialize($storyData['tagblock']);
		$storyData['characterblock'] = unserialize($storyData['characterblock']);

		\Base::instance()->set('storyData', $storyData);

		return \Template::instance()->render('story/information.html');
	}

	public static function buildReviews($reviewData)
	{
		\Base::instance()->set('story_reviews', $reviewData);

		return \Template::instance()->render('story/reviews.html');
	}

	public static function commentForm($storyID, $parentID)
	{
		return \Template::instance()->render('main/feedback_form.html');
	}

	public static function reviewForm($storyID, $parentID)
	{
		return \Template::instance()->render('main/feedback_form.html');
	}

	public static function buildStory($storyData,$content,$dropdown)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'chapter.js' );
		\Registry::get('VIEW')->javascript('body', FALSE, "var url='".\Base::instance()->get('BASE')."/story/read/{$storyData['sid']},'" );

		$storyData['authorblock'] = unserialize($storyData['authorblock']);
		$storyData['published'] = date( \Config::instance()->date_format_short, $storyData['published']);
		$storyData['modified'] = date( \Config::instance()->date_format_short, $storyData['modified']);
		
		\Base::instance()->set('render_data', [
												"story" => $storyData,
												"content" => $content,
												"dropdown" => $dropdown,
												"groups" => $_SESSION['groups'],
												"infoblock" => ( $storyData['chapters'] > 1 ) ? "" : \View\Story::buildInfoblock($storyData),
												]);
		
		return \Template::instance()->render('story/single.html');
	}

	public static function dropdown($data,$chapter)
	{
		$i=1;
		if(sizeof($data) > 1) $dropDown[] = array ( FALSE, "toc", FALSE, "__TOC" );
		foreach ( $data as $item )
		{
			$dropDown[] = array ( ($chapter==$item['chapter']), $item['chapter'], $i++, $item['title']);
		}
		$dropDown[] = array ( ($chapter==="reviews"), "reviews", FALSE, "__Reviews" );
		return $dropDown;
	}
	
	public static function categories($data)
	{
		\Base::instance()->set('categoriesData', $data);
		
		return \Template::instance()->render('story/categories.html');
	}
	
	public static function epubXMLtag()
	{
		return "<?xml version='1.0' encoding='utf-8'?>\n";
	}

	public static function epubContainer()
	{
		return \Template::instance()->render('container.xml');
	}

	public static function epubCSS()
	{
		return \Template::instance()->render('epub.css');
	}

	public static function epubPage($body, $title, $language)
	{
		return \Template::instance()->render('base.xhtml', 'text/html', [ 	"BODY" => $body, 
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
			return \Template::instance()->render('chapter_v3.xhtml',
																	'text/html',
																	[ 	"CONTENT" => $content, 
																		"CHAPTER_TITLE" => $title,
																		"LANGUAGE" => $ebook['language']
																	]
															);
			
		}
		elseif ( $ebook['version']==2 )
		{
			return \Template::instance()->render('chapter_v2.xhtml',
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
		return \Template::instance()->render($file, 'application/xhtml+xml', [ "STORY_TITLE"	=>	$ebook['title'],
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
			return \Template::instance()->render('root_v2.opf', 'application/xhtml+xml',
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
			return \Template::instance()->render('toc.xhtml', 'application/xhtml+xml',
																		[
																			"pages" => $chapterTOC,
																			"ebook" => $ebook,
																		]
																	);
		}
		else
		{
			return \Template::instance()->render('toc.ncx', 'application/xhtml+xml',
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
		return parent::render('story/block.stats.html');
	}
	
	public static function blockNewStories($stories)
	{
		while ( list($key, $value) = each($stories['data']) )
			Story::dataProcess($stories['data'][$key]);

		\Base::instance()->set('renderData', $stories);
		return parent::render('story/block.new.html');
	}
	
	public static function blockRandomStory($stories)
	{
		while ( list($key, ) = each($stories) )
			Story::dataProcess($stories[$key]);

		\Base::instance()->set('renderData', $stories);
		return parent::render('story/block.random.html');
	}
	
	public static function blockFeaturedStory($stories)
	{
		while ( list($key, ) = each($stories) )
			Story::dataProcess($stories[$key]);

		\Base::instance()->set('renderData', $stories);
		return parent::render('story/block.featured.html');
	}
	
	public static function blockTagcloud($taglist)
	{
		$max = current($taglist)['count'];
		$min = end($taglist)['count'];
		shuffle($taglist);
		foreach ( $taglist as &$tag )
		{
			$size_factor = ( \Config::instance()->tagcloud_spread - 1 ) / ( ($min==$max)?1:($max - $min) ) * ( $tag['count'] - $min ) + 1;
			$tag['z_index'] = $max-$tag['count'];
			$tag['percent'] = intval(\Config::instance()->tagcloud_basesize*$size_factor);
		}

		\Base::instance()->set('renderData', $taglist);
		return parent::render('story/block.tagcloud.html');
	}
	
}
