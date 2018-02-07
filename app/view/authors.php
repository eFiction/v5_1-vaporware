<?php
namespace View;

class Authors extends Base
{

	public static function page($header, $menu, $content)
	{
		return \Template::instance()->render
														('authors/main.html','text/html', 
															[
																"header"	=> $header,
																"letters"	=> $menu,
																"content"	=> $content,
																"BASE"		=> \Base::instance()->get('BASE')
															]
														);
	}
	
	public static function fullList($authors)
	{
		foreach ( $authors as $author )
		{
			if ( preg_match("/[A-Z]/is", $author['letter']) )
				$construct[$author['letter']][] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
			else
				$construct["#"][] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
		}
		
		if ( isset($construct) )
		{
			\Registry::get('VIEW')->javascript('body', TRUE, 'jquery.columnizer.js' );
			\Registry::get('VIEW')->javascript('body', FALSE, "$(function(){ $('.author-grid-wrapper').addClass(\"dontsplit\"); $('.columnize').columnize({ width: 200, lastNeverTallest: true }); });" );

			return \Template::instance()->render
														('authors/listing.html','text/html', 
															[
																"construct"	=> $construct,
																"BASE"		=> \Base::instance()->get('BASE')
															]
														);
		}
		else return \Base::instance()->get('LN__noAuthors');
	}

	public static function letterList($letter, $authors)
	{
		foreach ( $authors as $author )
		{
			$list[] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
		}
		
		$columns = min ( 3, ceil (sizeof($list)/5) );
		\Registry::get('VIEW')->javascript('body', TRUE, 'jquery.columnizer.js' );
		\Registry::get('VIEW')->javascript('body', FALSE, "$(function(){ $('.author-grid-wrapper').addClass(\"dontsplit\"); $('.columnize').columnize({ columns: {$columns}, lastNeverTallest: true }); });" );

		return \Template::instance()->render
														('authors/listing.html','text/html', 
															[
																"authors"	=> $list,
																"letter"	=> $letter,
																"BASE"		=> \Base::instance()->get('BASE')
															]
														);
	}

}