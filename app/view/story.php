<?php
namespace View;

class Story extends Base
{
	public function viewList($data)
	{
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

	public function buildTOC($tocData, $storyData)
	{
		$this->javascript('body', 'jquery.columnizer.js', TRUE );
		$this->javascript('body', "$(function(){ $('.chapter-box').addClass(\"dontsplit\"); $('.columnize').columnize({ width: 400 }); });" );

		$this->f3->set('tocData', $tocData);
		$this->f3->set('storyID', $storyData['sid']);

		return $this->render('story/toc.html');
	}

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
					"feedback_form_label"	=> ($in_structure['level'] > 0) ? $this->f3->get("LN__Comment") : $this->f3->get("LN__Review"),
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
		return $this->commentFormBase($out_structure,$data);
	}

	public function readBody($storyData,$content,$dropdown,$view=1)
	{
		$this->javascript('body', 'chapter.js?', TRUE );
		$this->javascript('body', "var url='".\Base::instance()->get('BASE')."/story/read/{$storyData['sid']},'" );

		// fix for <b> not showing with bulma, might have to find a better one for this
		$content = str_replace(["<b>","</b>"], ["<strong>","</strong>"], $content);

		$this->f3->set('data', [
											"story" 	=> $storyData,
											"content" 	=> $content,
											"dropdown" 	=> $dropdown,
											"view"		=> $view,
										]);
		$this->f3->set('returnpath', \Base::instance()->get('PATH') );

		return $this->render('story/read.body.html');
	}

	public function buildReviews($storyData)
	{
		$this->javascript('body', 'jquery.comments.min.js', TRUE );
		$this->javascript('body', 'chapter.js?', TRUE );

		$this->f3->set('story', $storyData);
		$this->f3->set('returnpath', $this->f3->get('PATH') );

		return $this->render('story/reviews.html');
	}

	public function dropdown($data,$chapter)
	{
		$i=1;

		$dropDown[] = array ( FALSE, "toc", FALSE, \Base::instance()->get("LN__TOC") );
		foreach ( $data as $item )
		{
			$dropDown[] = array ( ($chapter==$item['chapter']), $item['chapter'], $i++, $item['title']);
		}
		return $dropDown;
	}

	public function categories($data)
	{
		$this->f3->set('categoriesData', $data);

		return $this->render('story/categories.html');
	}

	public function contestList(array $data)
	{
		$this->f3->set('contests', $data);

		return $this->render('story/contests.list.html');
	}

	public function contestShow(array $data, string $returnpath)
	{
		$this->f3->set('data', $data);
		$this->f3->set('returnpath', $returnpath=="" ? "/story/contests" : $returnpath );

		return $this->render('story/contest.show.html');
	}

	public function contestEntries(array $contest, array $entries)
	{
		$this->f3->set('contest', $contest);
		$this->f3->set('entries', $entries);

		return $this->render('story/contest.entries.html');
	}

	public function collectionsList(array $data)
	{
		$this->f3->set('type', "collections");
		$this->f3->set('data', $data);

		return $this->render('story/coll-ser.list.html');
	}

	public function collectionsShow(array $collection)
	{
		$this->f3->set('type', 		"collections");
		$this->f3->set('data', 		[$collection['data']]);
		$this->f3->set('stories',	$collection['stories']);

		return ($this->render('story/coll-ser.item.html').$this->render('story/listing.html'));
	}

	public function seriesList(array $data)
	{
		$this->f3->set('type', "series");
		$this->f3->set('data', $data);

		return $this->render('story/coll-ser.list.html');
	}

	public function seriesShow(array $series)
	{
		$this->f3->set('type', 		"stories");
		$this->f3->set('data', 		[$series['data']]);
		$this->f3->set('stories',	$series['stories']);

		return ($this->render('story/coll-ser.item.html').$this->render('story/listing.html'));
	}

	public function outreadSingle(array $data): string
	{
		$this->f3->set('data', $data);
		return $this->render('story/outread.single.html');
	}

	public function outreadList(array $data): string
	{
		$this->f3->set('data', $data);
		return $this->render('story/outread.list.html');
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
				"AUTHOR"			=>	$ebook['authors'],
		    "NOTES"				=>	$ebook['storynotes']
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

	public function archiveStats(array $stats): string
	{
		$this->f3->set('archiveStats', $stats);
		return $this->render('blocks/stats.html');
	}

	public function blockStory(string $type, array $stories=[], $extra=NULL): ?string
	{
		if ( in_array($type, [ "recommended", "featured", "random", "new" ]) )
		{
			$this->f3->set('renderData', $stories);
			$this->f3->set('extra', $extra);

			return $this->render("story/block.{$type}.html");
		}
		else return NULL;
	}

	public function blockTagcloud($taglist)
	{
		$max = current($taglist)['count'];
		$min = end($taglist)['count'];
		shuffle($taglist);
		foreach ( $taglist as &$tag )
		{
			$size_factor = ( $this->config['tagcloud_spread'] - 1 ) / ( ($min==$max)?1:($max - $min) ) * ( $tag['count'] - $min ) + 1;
			$tag['z_index'] = $max-$tag['count'];
			$tag['percent'] = intval($this->config['tagcloud_basesize']*$size_factor);
		}

		$this->f3->set('renderData', $taglist);
		return $this->render('story/block.tagcloud.html');
	}

	public function blockContests($contests)
	{

		$this->f3->set('contests', $contests);
		return $this->render('story/block.contest.html');
	}
}
