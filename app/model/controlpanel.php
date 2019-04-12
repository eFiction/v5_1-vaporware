<?php
namespace Model;

class Controlpanel extends Base {

	public function deleteStory($storyID)
	{
		// get a review mapper
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'feedback');
		$deleted['reviews'] = 0;
		// go through all reviews
		while( $mapper->load(array('reference=? AND type=?',$storyID, 'ST')) )
		{
			// Faster with a direct SQL call, delete all comments for this review
			$this->exec
			(
				"DELETE FROM `tbl_feedback` WHERE `reference` = :ref AND `type` = 'C';",
				[ ":ref" => $mapper->fid ]
			);
			$deleted['comments'] = $this->db->count();
			// delete the review itself
			$mapper->erase();
			$deleted['reviews']++;
		}
		
		// Load all series that housed this story
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'series_stories');
		$deleted['series'] = 0;
		while( $mapper->load(array('sid=?',$storyID)) )
		{
			// rebuild cache of affected series
			$this->rebuildSeriesCache($mapper->seriesid);
			// delete link to the series
			$mapper->erase();
			$deleted['series']++;
		}
		
		// Faster with a direct SQL call, delete all user-story relations for this story
		$this->exec
		(
			"DELETE FROM `tbl_stories_authors` WHERE `sid` = :sid;",
			[ ":sid" => $storyID ]
		);
		$deleted['authors'] = $this->db->count();
		
		// get a tag relation mapper
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');
		$deleted['tags'] = 0;
		$deleted['characters'] = 0;
		while( $mapper->load(array('sid=?',$storyID)) )
		{
			// take note of character to recount
			if ( $mapper->character == 1 )	$recountC[]= $mapper->tid;
			// take note of tag to recount
			else							$recountT[]= $mapper->tid;

			// remove the relation
			$mapper->erase();
		}
		// Tag recount
		if ( !empty($recountT) )
			$deleted['tags'] = $this->storyRecountTags($recountT);

		// Character recount
		if ( !empty($recountC) )
			$deleted['characters'] = $this->storyRecountTags($recountC,1);

		// get a categories relation mapper
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');
		$deleted['categories'] = 0;
		while( $mapper->load(array('sid=?',$storyID)) )
		{
			$category = $mapper->cid;
			// remove the relation
			$mapper->erase();
			$this->cacheCategories($category);
			$deleted['categories']++;
		}

		// Delete all tracker entries for the story
		$this->exec
		(
			"DELETE FROM `tbl_tracker` WHERE `sid` = :sid;",
			[ ":sid" => $storyID ]
		);
		$deleted['tracker'] = $this->db->count();
		
		// Delete chapters form local storag if required
		if ( $this->config['chapter_data_location'] == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$db->exec('DELETE FROM "chapters" WHERE "sid" = :sid', array(':sid' => $storyID ));
			$deleted['chapterlocal'] = $db->count();
		}
		// Delete all chapters
		$this->exec
		(
			"DELETE FROM `tbl_chapters` WHERE `sid` = :sid;",
			[ ":sid" => $storyID ]
		);
		$deleted['chapterdb'] = $this->db->count();

		// Remove the story from the db
		$this->exec
		(
			"DELETE FROM `tbl_stories` WHERE `sid` = :sid;",
			[ ":sid" => $storyID ]
		);
		$deleted['story'] = $this->db->count();
		
		return $deleted;
	}
	
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
		
		$authors = 		$this->exec ( "SELECT A.aid as id, A.name FROM `tbl_authors`A INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'M' );", [ ":sid" => $storyData['sid'] ]);
		$pre['mainauth'] = json_encode($authors);

		$supauthors = 	$this->exec ( "SELECT A.aid as id, A.name FROM `tbl_authors`A INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'S' );", [ ":sid" => $storyData['sid'] ]);
		$pre['supauth'] = json_encode($supauthors);

		return $pre;
	}

	public function storyChapterAdd($storyID, $userID=FALSE, $date=NULL)
	{
		// get the current chapter count, and with it, check if the story exists
		// if required, the user's permission to add a chapter to the story will also be checked, although the controller should have taken care of this
		if ( $userID )
		{
			$countSQL = "SELECT COUNT(chapid) as chapters, U.uid
							FROM `tbl_stories`S
								LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid )
								INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid AND rSA.type='M' )
									INNER JOIN `tbl_authors`A ON ( (rSA.aid = A.aid) AND (A.uid=:uidU OR A.curator=:uidC) )
						WHERE S.sid = :sid ";
			$countBind = [ ":sid" => $storyID, ":uidU" => $userID, ":uidC" => $userID ];
			
			$chapterCount = $this->exec($countSQL, $countBind);
			
			if ( empty($chapterCount) OR  $chapterCount[0]['uid']==NULL )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount = $chapterCount[0]['chapters'] + 1;

			// set the initial validation status
			// even with a trusted author, we don't want the chapter to be marked as finished right away
			$validated = 11;
		}
		else
		{
			// coming from adminCP, no need to check user permission
			$chapterCount = $this->exec("SELECT COUNT(Ch.chapid) as chapters
											FROM `tbl_chapters`Ch
										WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'];
			
			if ( empty($chapterCount) )
				return FALSE;

			// Get current chapter count and raise
			$chapterCount++;
			
			// coming from adminCP, we set the chapter to active assuming them people know what they are doing
			if ( $_SESSION['groups']&32 )	$validated = 32;	// added by mod
			if ( $_SESSION['groups']&128 )	$validated = 33;	// added by admin
			
			// date is NULL when adding additional chapters, in this context we also update the story entry
			if ( !$date )
			{
				$this->exec(
					"UPDATE `tbl_stories` SET `updated` = CURRENT_TIME() WHERE `sid` = :sid;",
					[ ":sid" =>  $storyID ]
				);
			}
		}
		
		$newChapter = new \DB\SQL\Mapper($this->db, $this->prefix."chapters");
		$newChapter->sid		= $storyID;
		$newChapter->title		= \Base::instance()->get('LN__Chapter')." #{$chapterCount}";
		$newChapter->inorder	= $chapterCount;
		$newChapter->validated	= $validated;
		$newChapter->created	= 'CURRENT_TIMESTAMP';
		$newChapter->save();
		
		$chapterID = $newChapter->_id;

		// if using local storage, create a chapter entry in SQLite
		if ( "local" == $this->config['chapter_data_location'] )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid","inorder") VALUES ( :chapid, :sid, :inorder )', 
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
									':inorder' 		=> $chapterCount
								]
			);
		}

		// rebuild the story cache
		$this->rebuildStoryCache($storyID);
		
		return $chapterID;
	}

	public function loadChapterList($sid)
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.title,Ch.validated,Ch.inorder
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid ORDER BY Ch.inorder ASC",
			[":sid" => $sid ]
		);
		if (sizeof($data)>0) return $data;
		return [];
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
			$chapterSave= $db->exec('UPDATE "chapters" SET "chaptertext" = :chaptertext WHERE "chapid" = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
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
					GROUP_CONCAT(DISTINCT aid,',',name,',',username ORDER BY name ASC SEPARATOR '||' ) as authorblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',ratingwarning,',',rating_image SEPARATOR '||' ) as rating,
					COUNT(DISTINCT fid) AS reviews,
					COUNT(DISTINCT chapid) AS chapters
					FROM
					(
						SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
								F.fid,
								S.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image, Ra.ratingwarning,
								A.aid, A.name, U.username,
								Cat.cid, Cat.category,
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Ch.charid, Ch.charname
							FROM `tbl_stories` S
								LEFT JOIN `tbl_ratings` Ra ON ( Ra.rid = S.ratingid )
								LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
									LEFT JOIN `tbl_authors`A ON ( rSA.aid = A.aid )
									LEFT JOIN `tbl_users`U ON ( A.uid = U.uid )
								LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
									LEFT JOIN `tbl_tags` T ON ( T.tid = rST.tid AND rST.character = 0 )
										LEFT JOIN `tbl_tag_groups` TG ON ( TG.tgid = T.tgid )
									LEFT JOIN `tbl_characters` Ch ON ( Ch.charid = rST.tid AND rST.character = 1 )
								LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
									LEFT JOIN `tbl_categories` Cat ON ( rSC.cid = Cat.cid )
								LEFT JOIN `tbl_chapters` C ON ( C.sid = S.sid AND C.validated >= 30 )
								LEFT JOIN `tbl_feedback` F ON ( F.reference = S.sid AND F.type='ST' )
							WHERE S.sid = :sid
					)AS SELECT_OUTER
				GROUP BY sid ORDER BY sid ASC";
		
		$item = $this->exec($sql, [':sid' => $sid] );
		
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
	
	public function rebuildStoryWordcount($sid)
	{
		$this->exec("UPDATE `tbl_stories`S
						INNER JOIN
						(
							SELECT sid, SUM(wordcount)'wordcount' FROM `tbl_chapters`
							WHERE sid = :sid AND validated >= 30
							GROUP BY sid
						) Ch ON ( S.sid = Ch.sid  )
					SET S.wordcount = Ch.wordcount;",
					[ ":sid" => $sid ]
					);
	}

	public function rebuildSeriesCache($seriesID)
	{
		$sql = "SELECT 
					SERIES.seriesid, 
					SERIES.tagblock, 
					SERIES.characterblock, 
					SERIES.authorblock, 
					SERIES.categoryblock, 
					CONCAT(rating,'||',max_rating_id) as max_rating
				FROM
					(
						SELECT 
							Ser.seriesid,
							MAX(Ra.rid) as max_rating_id,
							GROUP_CONCAT(DISTINCT A.aid,',',A.name,',',U.username ORDER BY username ASC SEPARATOR '||' ) as authorblock,
							GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
							GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
							GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description,',',TG.tgid ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock
						FROM `tbl_series`Ser
							LEFT JOIN `tbl_series_stories`TrS ON ( Ser.seriesid = TrS.seriesid )
								LEFT JOIN `tbl_stories`S ON ( TrS.sid = S.sid )
									LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.ratingid )
									LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
										LEFT JOIN `tbl_tags`T ON ( T.tid = rST.tid AND rST.character = 0 )
											LEFT JOIN `tbl_tag_groups`TG ON ( TG.tgid = T.tgid )
										LEFT JOIN `tbl_characters`Chara ON ( Chara.charid = rST.tid AND rST.character = 1 )
									LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
										LEFT JOIN `tbl_categories`C ON ( rSC.cid = C.cid )
									LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
										LEFT JOIN `tbl_authors`A ON ( rSA.aid = A.aid )
										LEFT JOIN `tbl_users`U ON ( A.uid = U.uid )
						WHERE Ser.seriesid = :series
						GROUP BY Ser.seriesid
					) AS SERIES
				LEFT JOIN `tbl_ratings`R ON (R.rid = max_rating_id);";
		$item = $this->exec($sql, [':series' => $seriesID] );
		
		if ( empty($item) ) return FALSE;
		
		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_series',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_authors'		=> json_encode($this->cleanResult($item['authorblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'max_rating'		=> json_encode(explode(",",$item['max_rating'])),
			],
			['seriesid=?',$seriesID]
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
		
		$item = $this->exec($sql, [':conid' => $conid] );
		
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

//	public function cacheCategories(int $catID=0)
	public function cacheCategories($catID=0)
	{
		// clear stats of affected categories
		$sql = "UPDATE `tbl_categories` SET `stats` = NULL";
		if ($catID>0) $sql .=  " WHERE `cid` = {$catID};";
		$this->exec($sql);
		
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories' );
		
		$sql = "SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, 
					GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories, 
					GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
			FROM `tbl_categories`C 
				INNER JOIN (SELECT leveldown FROM `tbl_categories` WHERE `stats` = '' ORDER BY leveldown DESC LIMIT 0,1) c2 ON ( C.leveldown = c2.leveldown )
				LEFT JOIN `tbl_stories_categories`SC ON ( C.cid = SC.cid )
				LEFT JOIN `tbl_stories`S ON ( S.sid = SC.sid )
				LEFT JOIN `tbl_categories`C1 ON ( C.cid = C1.parent_cid )
			GROUP BY C.cid";
			
		do {
			$items = $this->exec( $sql );
			$change = FALSE;
			foreach ( $items as $item)
			{
				if ( $item['sub_categories']==NULL ) $sub = NULL;
				else
				{
					$sub_categories = explode("||", $item['sub_categories']);
					$sub_stats = explode("||", $item['sub_stats']);
					$sub_stats = array_map("json_decode", $sub_stats);
					foreach( $sub_categories as $key => $value )
					{
						$item['counted'] += $sub_stats[$key]->count;
						$sub[$value] = $sub_stats[$key]->count;
					}
				}
				$stats = json_encode([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
				unset($sub);
				
				$categories->load(array('cid=?',$item['cid']));
				$categories->stats = $stats;
				$categories->save();
				
				$change = ($change) ? : $categories->changed();
			}
		} while ( $change != FALSE );
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
				// recache this category
				$this->cacheCategories($X['cid']);
			}
			else unset($data[$temp]);
		}
		
		// Insert any category IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp)
			{
				// Add relation to table
				$categories->reset();
				$categories->sid = $sid;
				$categories->cid = $temp;
				$categories->save();
				// recache this category
				$this->cacheCategories($temp);
			}
		}
		unset($categories);
	}
	
	public function storyRelationTag( $sid, $data, $character = 0 )
	{
		// Check tags:
		$data = array_filter(explode(",",$data));
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');

		foreach ( $relations->find(array('`sid` = ? AND `character` = ?',$sid,$character)) as $X )
		{
			$temp=array_search($X['tid'], $data);
			if ( $temp===FALSE )
			{
				$recounts[] = $X['tid'];
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag/character IDs not already present
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
				$recounts[] = $temp;
			}
		}
		unset($relations);
		
		// call recount function
		if ( isset( $recounts ) )
			$this->storyRecountTags( $recounts, $character );
	}
	
	public function storyRecountTags( $tags, $character = 0 )
	{
		// tags is either an array
		if ( is_array($tags) ) $tags = implode(",",$tags);
		// or a plain numeric
		elseif ( !is_numeric($tags) ) return FALSE;
		
		if ( $character == 1 )
		{
			$this->exec("UPDATE `tbl_characters`C 
							LEFT JOIN
							(
								SELECT C.charid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `tbl_characters`C
								LEFT JOIN `tbl_stories_tags`RT ON (RT.tid = C.charid AND RT.character = 1)
									WHERE C.charid IN ({$tags})
									GROUP BY C.charid
							) AS C2 ON C.charid = C2.charid
							SET C.count = C2.counter WHERE C.charid = C2.charid;");
		}
		else
		{
			$this->exec("UPDATE `tbl_tags`T1 
							LEFT JOIN
							(
								SELECT T.tid, COUNT( DISTINCT RT.sid ) AS counter 
								FROM `tbl_tags`T 
								LEFT JOIN `tbl_stories_tags`RT ON (RT.tid = T.tid AND RT.character = 0)
									WHERE T.tid IN ({$tags})
									GROUP BY T.tid
							) AS T2 ON T1.tid = T2.tid
							SET T1.count = T2.counter WHERE T1.tid = T2.tid;");
		}
		return $this->db->count();
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
