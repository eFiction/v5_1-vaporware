<?php

namespace Model;

class Story extends Base
{
	public function intro()
	{
		$replacements =
		[
			"ORDER" => "ORDER BY ". \Base::instance()->get('CONFIG')['story_intro_order']." DESC" ,
			"LIMIT" => "LIMIT 0,".\Base::instance()->get('CONFIG')['story_intro_items']
		];
		$data = $this->exec($this->storySQL($replacements));

		return $data;
	}
	
	public function author($id)
	{
		$author = "SELECT U.uid, U.nickname as name, COUNT(rSA.sid) as counter FROM `tbl_stories_authors`rSA INNER JOIN `tbl_users`U ON ( rSA.aid = U.uid AND rSA.aid = :aid ) GROUP BY rSA.aid";
		$info = $this->exec( $author, ["aid" => $id] );
		$replacements =
		[
			"ORDER" => "ORDER BY S.updated DESC" ,
			"LIMIT" => "LIMIT 0,".\Base::instance()->get('CONFIG')['story_intro_items'],
			"JOIN" => "INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid AND rSA.aid = :aid )"
		];
		$data = $this->exec($this->storySQL($replacements),["aid" => $id]);

		return [$info, $data];
	}
	
	public function getStory($story)
	{
		$replacements =
		[
			"WHERE" => "AND S.sid = :sid"
		];
		$data = $this->exec($this->storySQL($replacements), [ ":sid" => $story ]);

		if ( sizeof($data)==1 )
			return $data[0];
		else return FALSE;
	}
	
	public function getChapter( $story, $chapter, $counting = TRUE )
	{
		$location = \Config::instance()->chapter_data_location;

		if ( $location == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterLoad= $db->exec('SELECT "chaptertext" FROM "chapters" WHERE "sid" = :sid AND "inorder" = :inorder', array(':sid' => $story, ':inorder' => $chapter ))[0];
		}
		else
		{
			$chapterLoad = $this->exec("SELECT C.chaptertext FROM `tbl_chapters`C WHERE C.sid=:sid AND C.inorder=:inorder", array(':sid' => $story, ':inorder' => $chapter ))[0];
		}
		if ( sizeof($chapterLoad)>0 ) $chapterText = $chapterLoad['chaptertext'];
		else return FALSE;
		
		if ( $counting AND \Base::instance()->get('SESSION')['userID'] > 0 )
		{
			$sql_tracker = "INSERT INTO `tbl_tracker` (sid,uid,last_chapter) VALUES (".(int)$story.", ".\Base::instance()->get('SESSION')['userID'].",".(int)$chapter.") 
											ON DUPLICATE KEY
											UPDATE last_read=NOW(),last_chapter=".(int)$chapter.";";
			$this->exec($sql_tracker);
		}
		return nl2br($chapterText);
	}
	
	public function storySQL($replacements=[])
	{
		// $replacements = [ "EXTRA" => , "JOIN" => , "WHERE" => , "ORDER" => , "LIMIT" =>
		$sql_StoryConstruct = "SELECT SQL_CALC_FOUND_ROWS
				Cache.*,
				S.title, S.summary, S.storynotes, S.completed, S.wordcount, UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified, 
				S.count,GROUP_CONCAT(rSC.chalid) as contests,GROUP_CONCAT(Ser.seriesid,',',rSS.inorder,',',Ser.title ORDER BY Ser.title DESC SEPARATOR '||') as in_series @EXTRA@,
				Ra.rating as rating_name
			FROM `tbl_stories`S
				@JOIN@
			LEFT JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid )
			LEFT JOIN `tbl_contest_relation`rSC ON ( rSC.relid = S.sid AND rSC.type = 'story' )
			LEFT JOIN `tbl_series_stories`rSS ON ( rSS.sid = S.sid )
				LEFT JOIN `tbl_series`Ser ON ( Ser.seriesid=rSS.seriesid )
			LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.rid )
			WHERE S.completed @COMPLETED@ 0 AND S.validated > 0 @WHERE@
			GROUP BY S.sid
			@ORDER@
			@LIMIT@";
			
		$replace =
		[
			"@EXTRA@"			=> "",
			"@JOIN@"				=> "",
			"@COMPLETED@"	=> ">=",
			"@WHERE@"			=> "",
			"@ORDER@"			=> "",
			"@LIMIT@"			=> ""
		];
		
		foreach ( $replacements as $key => $value )
		{
			$replace["@{$key}@"] = $value;
		}
		return str_replace(array_keys($replace), array_values($replace), $sql_StoryConstruct);
	}
	
	public function loadReviews($storyID)
	{
		$limit=5;
		$sql = "SELECT 
						F1.*, 
						F2.fid as comment_id, 
						F2.text as comment_text, 
						F2.reference_sub as parent_item, 
						IF(F2.writer_uid>0,U2.nickname,F2.writer_name) as comment_writer_name, 
						F2.writer_uid as comment_writer_uid 
					FROM 
					(
						SELECT 
							F.fid as review_id, 
							F.text as review_text, 
							F.reference as review_storyid, 
							F.reference_sub as review_chapterid, 
							IF(F.writer_uid>0,U.nickname,F.writer_name) as review_writer_name, 
							F.writer_uid as review_writer_uid 
						FROM `tbl_feedback`F 
							JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.reference = :storyid AND F.type='ST' 
						ORDER BY F.datetime 
						DESC LIMIT 0,".$limit."
					) F1
				JOIN `tbl_feedback`F2  ON (F1.review_id = F2.reference AND F2.type='C')
					JOIN `tbl_users`U2 ON ( F2.writer_uid = U2.uid )
				ORDER BY F2.datetime ASC";
		$flat = $this->exec( $sql, [':storyid' => $storyID] );
		
		if ( sizeof($flat) == 0 ) return FALSE;
		
		$current_id = 0;
		foreach ( $flat as $item )
		{
			if ( $item['review_id']!=$current_id )
			{
				// remember current review ID
				$current_id = $item['review_id'];
				$data[] =
				[
					"level"	=> 1,
					"sid"	=>	$item['review_storyid'],
					"id"	=> $item['review_id'],
					"text" => $item['review_text'],
					"name" => $item['review_writer_name'],
					"uid" =>	$item['review_writer_uid']
				];
			}
			// Check parent level and remember this node's level
			if ( isset($depth[$item['parent_item']]) )
				$depth[$item['comment_id']] = $depth[$item['parent_item']] + 1;
			else
				$depth[$item['comment_id']] = 2;
			
			$data[] =
			[
				"level"	=> min ($depth[$item['comment_id']], 3),
				"sid"	=>	$item['review_storyid'],
				"id"	=> $item['comment_id'],
				"text" => $item['comment_text'],
				"name" => $item['comment_writer_name'],
				"uid" =>	$item['comment_writer_uid']
			];
		}
		return $data;
	}
	
	public function getTOC($story)
	{
		return $this->exec( "SELECT UNIX_TIMESTAMP(T.last_read) as tracker_last_read, T.last_chapter, IF(T.last_chapter=Ch.inorder,1,0) as last,
		Ch.title, Ch.notes, Ch.wordcount, Ch.inorder as chapter,
		COUNT(DISTINCT F.fid) as reviews
		FROM `tbl_chapters`Ch 
		INNER JOIN `tbl_stories`S ON ( S.sid = Ch.sid )
		LEFT JOIN `tbl_tracker`T ON ( T.sid = Ch.sid AND T.uid = :user )
		LEFT JOIN `tbl_feedback`F ON ( F.reference_sub = Ch.chapid AND F.type='ST' ) 
		WHERE Ch.sid = :story GROUP BY Ch.inorder ORDER BY Ch.inorder ASC", [ ":story" => $story, ":user" => \Base::instance()->get('SESSION.userID') ]);
	}

	public function getMiniTOC($story)
	{
		return $this->exec( "SELECT Ch.title, Ch.inorder as chapter
		FROM `tbl_chapters`Ch 
		WHERE Ch.sid = :story ORDER BY Ch.inorder ASC", [ ":story" => $story ]);
	}

	public function blockStats()
	{
		return $this->exec('SELECT SC.field, IF(SC.name IS NULL,SC.value,SC.name) as value FROM `tbl_stats_cache`SC;');
	}
	
	public function blockNewStories($items)
	{
		return $this->exec('SELECT S.sid, S.title, S.summary, 
											Cache.*
										FROM `tbl_stories`S
											INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid )
										WHERE (datediff(S.updated,S.date) = 0)
										ORDER BY S.updated DESC
										LIMIT 0,'.(int)$items);
	}
	
	public function blockRandomStory($items=1)
	{
		return $this->exec('SELECT S.title, S.sid, S.summary, Cache.authorblock, Cache.rating
				FROM `tbl_stories`S
					INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid  )
			ORDER BY RAND() 
			LIMIT '.(int)$items);
	}
	
	public function blockTagcloud($items)
	{
		return $this->exec('SELECT T.tid, T.label, T.count
				FROM `tbl_tags`T
			WHERE T.tgid = 1 AND T.count > 0
			ORDER BY T.count DESC
			LIMIT 0, '.(int)$items);
	}
	
	public function blockFeaturedStory($items=1, $order=FALSE)
	{
		if ( $order == "random" ) $sort = 'RAND()';
		else $sort = 'S.featured DESC';
		return $this->exec("SELECT S.title, S.sid, S.summary, Cache.authorblock, Cache.rating
				FROM `tbl_stories`S
					INNER JOIN `tbl_stories_blockcache`Cache ON ( S.sid = Cache.sid  )
			WHERE ( S.featured = 1 OR (S.featured > 2 AND S.featured < UNIX_TIMESTAMP(NOW())) )
			ORDER BY {$sort} 
			LIMIT 0,".$items);
		// 1 = aktuell, 2, ehemals
		//return $this
	}
	
	public function printEPub($id)
	{
		$epubSQL =	"SELECT 
			S.print_cache, S.sid, S.title,
			GROUP_CONCAT(DISTINCT U.nickname ORDER BY U.nickname ASC SEPARATOR ', ') as authors,
			'1' AS allow_ebook
			FROM `tbl_stories`S
				INNER JOIN `tbl_stories_authors`rSA ON ( S.sid=rSA.sid ) 
					INNER JOIN `tbl_users`U ON (rSA.aid=U.uid)
			WHERE S.sid=:sid AND S.completed >= 0 AND validated > 0
			GROUP BY S.sid";

		$epubData = $this->exec( $epubSQL, array(':sid' => $id) )[0];
		
		if($file = realpath("tmp/epub/s{$epubData['sid']}.zip"))
		{
			$filesize = filesize($file);
			$ebook = @fopen($file,"rb");
		}
		else
		{
			list($ebook, $filesize) = $this->createEPub($epubData['sid']);
		}
		
		if ( $ebook )
		{
			header("Content-type: application/epub+zip; charset=utf-8");
			header("Content-Disposition: filename='".$epubData['title']." - ".$epubData['authors'].".epub'");
			header("Content-length: ".$filesize);
			header("Cache-control: private");

			while(!feof($ebook))
			{
				$buffer = fread($ebook, 8*1024);
				echo $buffer;
			}
			fclose ($ebook);

			exit;
		}
	}
	
	protected function createEPub($id)
	{
		$epubSQL =	"SELECT 
			S.sid, S.title, S.storynotes, S.summary, UNIX_TIMESTAMP(S.date) as written, UNIX_TIMESTAMP(S.date) as updated, 
			GROUP_CONCAT(DISTINCT U.nickname ORDER BY U.nickname ASC SEPARATOR ', ') as authors,
			GROUP_CONCAT(DISTINCT T.label ORDER BY T.tgid,T.label ASC SEPARATOR ', ') as tags
			FROM `tbl_stories`S
				INNER JOIN `tbl_stories_authors`rSA ON ( S.sid=rSA.sid ) 
					INNER JOIN `tbl_users`U ON (rSA.aid=U.uid)
				INNER JOIN `tbl_stories_tags`rST ON (S.sid=rST.sid) 
					INNER JOIN `tbl_tags`T ON (rST.tid=T.tid)
			WHERE S.sid=:sid 
			GROUP BY S.sid";

		$epubData = $this->exec( $epubSQL, array(':sid' => $id) )[0];

		\Base::instance()->set('UI', "template/epub/");
		$filename = realpath("tmp/epub")."/s{$epubData['sid']}.zip";

		// Load or create the namespace
		if ( "" == @\Config::instance()->epub_namespace )
		{
			$cfg = \Config::instance();
			$cfg['epub_namespace'] = uuid_v5("6ba7b810-9dad-11d1-80b4-00c04fd430c8", \Base::instance()->get('HOST').\Base::instance()->get('BASE') );
			$cfg->save();
		}

		/*
		This must be coming from admin panel at some point, right now we will fake it
		*/
		$epubData['version'] = 2;		// supported by most readers, v3 is still quite new
		$epubData['language'] = "de";
		$epubData['uuid']  = uuid_v5(\Config::instance()->epub_namespace, $epubData['title']);
		
		\Base::instance()->set('EPUB', $epubData);

		$body = "";
		$re_element = array (
			"old"	=>	array ("<center>", "</center>"),
			"new"	=>	array ("<span style=\"text-align: center;\">", "</span>"),
		);
		$elements_allowed = "<a><abbr><acronym><applet><b><bdo><big><br><cite><code><del><dfn><em><i><img><ins><kbd><map><ns:svg><q><samp><small><span><strong><sub><sup><tt><var>";

		// The folder *should* exist, but creating it and ignoring the outcome is the quickest way of making sure it really is there
		@mkdir("tmp/epub",0777,TRUE);

		/*
		Create the Archive
		Since the mimetype file has to be at the beginning of the archive and uncompressed, we have to create the zip file from binary
		*/
		file_put_contents($filename, base64_decode("UEsDBAoAAAAAAOmRAT1vYassFAAAABQAAAAIAAAAbWltZXR5cGVhcHBsaWNhdGlvbi9lcHViK3ppcFBLAQIUAAoAAAAAAOmRAT1vYassFAAAABQAAAAIAAAAAAAAAAAAIAAAAAAAAABtaW1ldHlwZVBLBQYAAAAAAQABADYAAAA6AAAAAAA="));

	  	$zip = new \ZipArchive;
		$res = $zip->open($filename);
		if ($res === TRUE)
		{
			// memorize the XML opening tag
			$xml = \View\Story::epubXMLtag();

			// add folder for container file & META-INF/container.xml
			$zip->addEmptyDir('META-INF');
			$zip->addFromString('META-INF/container.xml', $xml.\View\Story::epubContainer() );

			// styles.css
			$zip->addEmptyDir('Styles');
	    	$zip->addFromString('Styles/styles.css', \View\Story::epubCSS() );

			// add folder for content
			$zip->addEmptyDir('content');

		    // title.xhtml
	    	$zip->addFromString('content/title.xhtml', 
											$xml.\View\Story::epubPage(
															\View\Story::epubTitle(),
															$epubData['title'],
															$epubData['language']
														)
											);

			// page[n].xhtml							| epub_page
			$chapters = $this->exec("SELECT C.title, C.inorder
														FROM `tbl_chapters`C
														WHERE C.validated = '1' AND C.sid = :sid
														ORDER BY C.inorder ASC ",
												[ ":sid" => $epubData['sid'] ] );
			if(sizeof($chapters)>0)
			{
				$n = 1;
				foreach($chapters as $chapter)
				{
					$chapterText = $this->getChapter( $epubData['sid'], $chapter['inorder'], FALSE );
					$chapterTOC[] = array ( "number" => $n, "title" => "{$chapter['title']}" );
					
					$body = \View\Story::epubChapter(
															$chapter['title'],
															strip_tags(
	    														str_replace(
	    															$re_element['old'],
	    															$re_element['new'],
	    															$chapterText
	    														),
	    														$elements_allowed
		    												)
													);
					
					$zip->addFromString('content/chapter'.($n++).'.xhtml', 
											$xml.\View\Story::epubPage(
															$body,
															$chapter['title'],
															$epubData['language']
														)
											);

				}
			}
			else return "__StoryError";

			// root.opf
		    $zip->addFromString('root.opf', $xml.\View\Story::epubRoot( $chapterTOC ) );
			
			// TOC
			$zip->addFromString('toc.ncx', $xml.\View\Story::epubTOC( $chapterTOC ) );

			if( $epubData['version']==3 )
				$zip->addFromString('toc.xhtml', $xml.\View\Story::epubTOC( $chapterTOC, 3 ) );

			$zip->close();
		}
		return [ @fopen($filename,"rb"), filesize($filename) ];
	}
}

function uuid_v5($namespace, $name) {
  if(!uuid_validate($namespace)) return false;

  // Get hexadecimal components of namespace
  $nhex = str_replace(array('-','{','}'), '', $namespace);

  // Binary Value
  $nstr = '';

  // Convert Namespace UUID to bits
  for($i = 0; $i < strlen($nhex); $i+=2) {
    $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
  }

  // Calculate hash value
  $hash = sha1($nstr . $name);

  return sprintf('%08s-%04s-%04x-%04x-%12s',

    // 32 bits for "time_low"
    substr($hash, 0, 8),

    // 16 bits for "time_mid"
    substr($hash, 8, 4),

    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 5
    (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

    // 48 bits for "node"
    substr($hash, 20, 12)
  );
}

function uuid_validate($uuid) {
  return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
                    '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
}
