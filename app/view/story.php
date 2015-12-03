<?php
namespace View;

class Story extends Base
{
	public static function buildTOC($tocData, $storyData)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'jquery.columnizer.js' );
		\Registry::get('VIEW')->javascript('body', FALSE, "$(function(){ $('.columnize').columnize({ columns: 2 }); });" );
		
		$infoblock = \View\Story::buildInfoblock($storyData);
		
		return $infoblock.\Template::instance()->render('story/toc.html','text/html', [ "tocs" => $tocData, "story" => $storyData['sid'], "BASE" => \Base::instance()->get('BASE') ]);
	}
	
	public static function buildInfoblock($storyData)
	{
		$storyData['categoryblock'] = unserialize($storyData['categoryblock']);
		$storyData['tagblock'] = unserialize($storyData['tagblock']);
		return \Template::instance()->render('story/information.html','text/html', [ "story" => $storyData, "BASE" => \Base::instance()->get('BASE') ]);
	}

	public static function buildStory($storyData,$content,$dropdown)
	{
		\Registry::get('VIEW')->javascript('body', TRUE, 'chapter.js' );
		\Registry::get('VIEW')->javascript('body', FALSE, "var url='".\Base::instance()->get('BASE')."/story/read/{$storyData['sid']},'" );


		$storyData['authorblock'] = unserialize($storyData['authorblock']);
		$storyData['published'] = date( \Config::instance()->date_format_short, $storyData['published']);
		$storyData['modified'] = date( \Config::instance()->date_format_short, $storyData['modified']);
		return \Template::instance()->render('story/single.html','text/html',
															[ 
																"story" => $storyData,
																"content" => $content,
																"dropdown" => $dropdown,
																"groups" => $_SESSION['groups'],
																"BASE" => \Base::instance()->get('BASE'),
															]
		);
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
		//print_r($ebook);exit;
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
		//print_r($ebook);exit;
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

}
