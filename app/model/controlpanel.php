<?php
namespace Model;

class Controlpanel extends Base {

	public function storyEditPrePop(array $storyData)
	{
		$categories = json_decode($storyData['cache_categories']);
		if(sizeof($categories))
		{
			foreach ( $categories as $tmp ) $pre['cat'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['cat'] = json_encode($pre['cat']);
		}
		else $pre['cat'] = '""';

		$tags = json_decode($storyData['cache_tags'],TRUE)['simple'];
		if(sizeof($tags)>0)
		{
			foreach ( $tags as $tmp ) $pre['tag'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['tag'] = json_encode($pre['tag']);
		}
		else $pre['tag'] = '""';

		$characters = json_decode($storyData['cache_characters']);
		if(sizeof($characters))
		{
			foreach ( $characters as $tmp ) $pre['char'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['char'] = json_encode($pre['char']);
		}
		else $pre['char'] = '""';
		
		$authors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'M' );", [ ":sid" => $storyData['sid'] ]);
		$pre['mainauth'] = json_encode($authors);

		$supauthors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'S' );", [ ":sid" => $storyData['sid'] ]);
		$pre['supauth'] = json_encode($supauthors);

		return $pre;
	}

	public function storyChapterAdd($storyID, $userID=FALSE)
	{
		if ( $userID )
		{
			// coming from userCP, $userID is safe
			$countSQL = "SELECT COUNT(chapid) as chapters, U.uid
							FROM `tbl_stories`S
								LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid )
								INNER JOIN `tbl_stories_authors`SA ON ( SA.sid = S.sid AND SA.type='M' )
									INNER JOIN `tbl_users`U ON ( ( U.uid=SA.aid ) AND ( U.uid={$userID} OR U.curator={$userID} ) )
						WHERE S.sid = :sid ";
			$countBind = [ ":sid" => $storyID ];
			
			$chapterCount = $this->exec($countSQL, $countBind);

			if ( empty($chapterCount) OR  $chapterCount[0]['uid']==NULL )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount = $chapterCount[0]['chapters'] + 1;
		}
		else
		{
			if ( FALSE == $chapterCount = $this->exec("SELECT COUNT(chapid) as chapters FROM `tbl_chapters` WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'] )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount++;
		}

		$validated = 1;
		if ( $_SESSION['groups']&32 ) $validated = 2;
		if ( $_SESSION['groups']&128 ) $validated = 3;
		
		$kv = [
			'title'			=> \Base::instance()->get('LN__Chapter')." #{$chapterCount}",
			'inorder'		=> $chapterCount,
			//'notes'			=> '',
			//'workingtext'
			//'workingdate'
			//'endnotes'
			'validated'		=> "1".$validated,
			'wordcount'		=> 0,
			'rating'		=> "0", // allow rating later
			'sid'			=> $storyID,
		];

		$chapterID = $this->insertArray($this->prefix.'chapters', $kv );

		if ( "local" == $this->config['chapter_data_location'] )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid","inorder","chaptertext") VALUES ( :chapid, :sid, :inorder, :chaptertext )', 
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
									':inorder' 		=> $chapterCount,
									':chaptertext'	=> '',
								]
			);
		}

		$this->rebuildStoryCache($storyID);
		
		return $chapterID;
	}

	public function loadChapter( $story, $chapter )
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.inorder,Ch.title,Ch.notes,Ch.validated,Ch.rating
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid AND Ch.chapid = :chapter",
			[":sid" => $story, ":chapter" => $chapter ]
		);
		if (empty($data)) return FALSE;
		$data[0]['chaptertext'] = parent::getChapterText( $story, $data[0]['inorder'], FALSE );
		
		return $data[0];
	}

	public function saveChapter( $chapterID, $chapterText )
	{
		if ( $this->config['chapter_data_location'] == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterSave= @$db->exec('UPDATE "chapters" SET "chaptertext" = :chaptertext WHERE "chapid" = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
		}
		else
		{
			$chapterSave = $this->exec('UPDATE `tbl_chapters` SET `chaptertext` = :chaptertext WHERE `chapid` = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
		}

		return $chapterSave;
	}

	public function rebuildStoryCache($sid)
	{
		$sql = "SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT uid,',',nickname ORDER BY nickname ASC SEPARATOR '||' ) as authorblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating,
					COUNT(DISTINCT fid) AS reviews,
					COUNT(DISTINCT chapid) AS chapters
					FROM
					(
						SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
								F.fid,
								S.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
								U.uid, U.nickname,
								Cat.cid, Cat.category,
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Ch.charid, Ch.charname
							FROM `tbl_stories` S
								LEFT JOIN `tbl_ratings` Ra ON ( Ra.rid = S.ratingid )
								LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
									LEFT JOIN `tbl_users` U ON ( rSA.aid = U.uid )
								LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `tbl_tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
										LEFT JOIN `tbl_tag_groups` TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `tbl_characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
								LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
									LEFT JOIN `tbl_categories` Cat ON ( rSC.cid = Cat.cid )
								LEFT JOIN `tbl_chapters` C ON ( C.sid = S.sid )
								LEFT JOIN `tbl_feedback` F ON ( F.reference = S.sid AND F.type='ST' )
							WHERE S.sid = :sid
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC";
		
		$item = $this->exec($sql, ['sid' => $sid] );
		
		if ( empty($item) ) return FALSE;
		
		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_stories',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_authors'		=> json_encode($this->cleanResult($item['authorblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'cache_rating'		=> json_encode(explode(",",$item['rating'])),
				'reviews'			=> $item['reviews'],
				'chapters'			=> $item['chapters'],
			],
			['sid=?',$sid]
		);
	}
	
	public function rebuildContestCache($conid)
	{
		$sql = "SELECT SELECT_OUTER.conid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT sid,',',title ORDER BY title ASC SEPARATOR '||' ) as storyblock
					FROM
					(
						SELECT C.conid, 
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Cat.cid, Cat.category,
								S.sid, S.title,
								Ch.charid, Ch.charname
							FROM `tbl_contests`C
								LEFT JOIN `tbl_contest_relations`rC ON ( rC.conid = C.conid )
									LEFT JOIN `tbl_tags`T ON ( T.tid = rC.relid AND rC.type = 'T' )
										LEFT JOIN `tbl_tag_groups`TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `tbl_characters`Ch ON ( Ch.charid = rC.relid AND rC.type = 'CH' )
									LEFT JOIN `tbl_categories`Cat ON ( rC.relid = Cat.cid AND rC.type = 'CA' )
									LEFT JOIN `tbl_stories`S ON ( rC.relid = S.sid AND rC.type = 'ST' )
							WHERE C.conid = :conid
					)AS SELECT_OUTER
				GROUP BY conid ORDER BY conid ASC";
		
		$item = $this->exec($sql, ['conid' => $conid] );
		
		if ( empty($item) ) return FALSE;
		
		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_contests',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'cache_stories'		=> json_encode($this->cleanResult($item['storyblock'])),
			],
			['conid=?',$conid]
		);
	}

	public function storyRelationCategories( $sid, $data )
	{
		$data = array_filter(explode(",",$data));
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');

		foreach ( $categories->find(array('`sid` = ?',$sid)) as $X )
		{
			$temp=array_search($X['cid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$categories->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any character IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp)
			{
				// Add relation to table
				$categories->reset();
				$categories->sid = $sid;
				$categories->cid = $temp;
				$categories->save();
			}
		}
		unset($categories);
	}
	
	public function storyRelationTag( $sid, $data, $character = 0 )
	{
		print_r($data);
		// Check tags:
		$data = array_filter(explode(",",$data));
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');

		foreach ( $relations->find(array('`sid` = ? AND `character` = ?',$sid,$character)) as $X )
		{
			$temp=array_search($X['tid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp)
			{
				// Add relation to table
				$relations->reset();
				$relations->sid = $sid;
				$relations->tid = $temp;
				$relations->character = $character;
				$relations->save();
			}
		}
		unset($relations);
	}
	
	public function storyRelationAuthor ( $storyID, $mainauthor, $supauthor, $allowEmptyAuthor = FALSE )
	{
		// Author and co-Author preparation:
		$mainauthor = array_filter(explode(",",$mainauthor));
		$supauthor = array_filter(explode(",",$supauthor));
		// remove co-authors, that are already in the author field
		$supauthor = array_diff($supauthor, $mainauthor);

		// Check co-Authors:
		$supDB = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');

		foreach ( $supDB->find(array('`sid` = ? AND `type` = ?',$storyID,'S')) as $X )
		{
			// delete entry if is no longer a supporting author or
			$isSup=array_search($X['aid'], $supauthor);
			// delete if is now a main author
			$isMain=array_search($X['aid'], $mainauthor);
			
			if ( $isSup===FALSE OR $isMain===TRUE )
			{
				// Excess relation, drop from table
				$supDB->erase(['lid=?',$X['lid']]);
			}
			else unset($supauthor[$isSup]);
		}

		// Insert any supporting author IDs not already present
		if ( sizeof($supauthor)>0 )
		{
			foreach ( $supauthor as $temp)
			{
				// Add relation to table
				$supDB->reset();
				$supDB->sid = $storyID;
				$supDB->aid = $temp;
				$supDB->type = 'S';
				$supDB->save();
			}
		}
		unset($supDB);

		// Check Authors:
		$mainDB = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');

		// refuse to leave an empty author list behind
		if(sizeof($mainauthor))
		{
			foreach ( $mainDB->find(array('`sid` = ? AND `type` = ?',$storyID,'M')) as $X )
			{
				$isMain=array_search($X['aid'], $mainauthor);
				if ( $isMain===FALSE )
				{
					// Excess relation, drop from table
					$mainDB->erase(['lid=?',$X['lid']]);
				}
				else unset($mainauthor[$isMain]);
			}
		}
		else
		{
			$_SESSION['lastAction'] = [ "deleteWarning" => \Base::instance()->get('LN__MainAuthorNotEmpty') ];
		}

		// Insert any author IDs not already present
		if ( sizeof($mainauthor)>0 )
		{
			foreach ( $mainauthor as $temp)
			{
				// Add relation to table
				$mainDB->reset();
				$mainDB->sid = $storyID;
				$mainDB->aid = $temp;
				$mainDB->type = 'M';
				$mainDB->save();
			}
		}
		unset($mainDB);
	}
}

?>
