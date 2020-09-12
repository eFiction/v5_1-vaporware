<?php
namespace Model;

class Controlpanel extends Base {

	// rewrite 2020-09
	public function storyAdd( array $data ) : int
	{
		$newStory = new \DB\SQL\Mapper($this->db, $this->prefix."stories");
		$newStory->title		= $data['new_title'];
		// completed 1 means it's a draft
		$newStory->completed	= 1;
		$newStory->date			= date('Y-m-d H:i:s');
		$newStory->updated		= $newStory->date;

		// get the highest level according to the groups bit-mask and assign a validation level accordingly
		switch ( floor(log($_SESSION['groups'], 2)) )
		{
			// admin
			case 7:
				$newStory->validated = 33;
				break;			
			// mods and supermods
			case 6:
			case 5:
				$newStory->validated = 32;
				break;
			// lector and trusted author
			case 4:
			case 3:
				$newStory->validated = 31;
				break;
			// regular author
			default:
				$newStory->validated = 11;
				break;
		}

		// save data
		$newStory->save();
		
		// get the new story's ID
		$newID = $newStory->_id;
		
		// new authors - either from form data or the UCP selection
		$new_authors = ( empty($data['uid']) ) ? explode(",",$data['new_author']) : [ $data['uid'] ];
		foreach ( $new_authors as $new_author )
		{
			// add the story-author relation
			$newRelation = new \DB\SQL\Mapper($this->db, $this->prefix."stories_authors");
			$newRelation->sid	= $newID;
			$newRelation->aid	= $new_author;
			$newRelation->type	= 'M';
			$newRelation->save();
			
			// already counting as author? mainly for stats, but would allow a user to post stories when authoring is restricted.
			$editUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
			$editUser->load(array("uid=?",$new_author));
			if ( !($editUser->groups & 4) )
				$editUser->groups += 4;
			$editUser->save();
		}
		
		// This story is created as draft without a chapter text and tags, so there is no need to do any recount or cache reset
		return $newID;
	}

	// rewrite 2020-09
	public function storyLoadInfo( int $sid, int $uid=0 ) : array
	{
		// common SQL builder
		$sql = 	"SELECT S.*, COUNT(DISTINCT Ch.chapid) as chapters, COUNT(DISTINCT Ch2.chapid) as validchapters
					FROM `tbl_stories`S
						@INNER@
						LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
						LEFT JOIN `tbl_chapters`Ch2 ON ( S.sid = Ch2.sid AND Ch2.validated >= 30)
					WHERE S.sid = :sid";
					
		if ( $uid )
		{
			// inside UCP
			$sql = str_replace("@INNER@", "INNER JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' AND A.aid = :aid )", $sql);
			$bind = [":sid" => $sid, ":aid" => $uid ];
		}
		else
		{
			// inside ACP
			$sql = str_replace("@INNER@", "", $sql);
			$bind = [":sid" => $sid ];
		}

		$data = $this->exec ( $sql, $bind );

		if (sizeof($data)==1 AND $data[0]['sid']!="")
		{
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return [];
	}

	public function storyDelete( int $storyID, int $userID = 0 ) : int
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
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
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
		if ( NULL != $categories = json_decode(@$storyData['cache_categories']) )
		{
			foreach ( $categories as $tmp ) $pre['cat'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['cat'] = json_encode($pre['cat']);
		}
		else $pre['cat'] = '""';

		if ( NULL != $tags = json_decode(@$storyData['cache_tags'],TRUE) )
		{
			foreach ( $tags['simple']??$tags as $tmp ) $pre['tag'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['tag'] = json_encode($pre['tag']);
		}
		else $pre['tag'] = '""';

		if ( NULL != $characters = json_decode(@$storyData['cache_characters']) )
		{
			foreach ( $characters as $tmp ) $pre['char'][] = [ "id" => $tmp[0], "name" => $tmp[1] ];
			$pre['char'] = json_encode($pre['char']);
		}
		else $pre['char'] = '""';

		if (isset($storyData['sid']))
		{
			$authors = 		$this->exec ( "SELECT U.uid as id, U.username as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'M' );", [ ":sid" => $storyData['sid'] ]);
			$pre['mainauth'] = json_encode($authors);

			$supauthors = 	$this->exec ( "SELECT U.uid as id, U.username as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'S' );", [ ":sid" => $storyData['sid'] ]);
			$pre['supauth'] = json_encode($supauthors);
		}
		elseif (isset($storyData['collid']))
		{
			$maintainer = 	$this->exec ( "SELECT U.uid as id, U.username as name FROM `tbl_users`U INNER JOIN `tbl_collections`Coll ON ( U.uid = Coll.uid AND Coll.collid = :collid );", [ ":collid" => $storyData['collid'] ]);
			$pre['maintainer'] = json_encode($maintainer);
		}

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
									INNER JOIN `tbl_users`U ON ( (rSA.aid = U.uid) AND (U.uid=:uidU OR U.curator=:uidC) )
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

			$validated = "1".($_SESSION['groups']&128)?"3":"2";

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
		//$this->rebuildStoryCache($storyID);

		return $chapterID;
	}

	public function chapterLoadList($sid): array
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

	public function chapterLoad( $story, $chapter )
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.inorder,Ch.title,Ch.notes,Ch.endnotes,Ch.validated,Ch.rating
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid AND Ch.chapid = :chapter",
			[":sid" => $story, ":chapter" => $chapter ]
		);
		if (empty($data)) return FALSE;
		$data[0]['chaptertext'] = parent::getChapterText( $story, $data[0]['inorder'], FALSE );

		return $data[0];
	}

	public function chapterSave( int $chapterID, string $chapterText, \DB\SQL\Mapper $mapper ) : int 
	{
		if ( $this->config['chapter_data_location'] == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterSave= $db->exec('UPDATE "chapters" SET "chaptertext" = :chaptertext WHERE "chapid" = :chapid', array(':chapid' => $chapterID, ':chaptertext' => $chapterText ));
		}
		else
		{
			$mapper->chaptertext = $chapterText;
			$chapterSave = $mapper->changed();
			$mapper->save();
		}

		return $chapterSave;
	}

	public function chapterDelete( int $storyID, int $chapterID, int $userID = 0 ) : int
	{
		if ( $userID == 0 OR 1==(new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors'))->count( ["sid = ? AND aid = ? and type='M'", $storyID, $userID] ) )
		{	
			$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
			
			if ( $this->config['chapter_data_location'] == "local" )
			{
				$chapter->load(['sid=? AND chapid=?', $storyID, $chapterID]);

				$localdb = new \DB\SQL\Mapper(\storage::instance()->localChapterDB(), 'chapters');
				$i = $localdb->erase(['sid=? AND inorder=?', $storyID, $chapter->inorder]);
			}
			else $i=1;
			
			$this->recountStory($storyID);
			
			// this should equate to 1*1 if there was no error
			return ( $i * $chapter->erase() );
		}
		return 0;
	}

	public function rebuildStoryCache($sid)
	{
		$sql = "SELECT SELECT_OUTER.sid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT uid,',',username ORDER BY username ASC SEPARATOR '||' ) as authorblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',ratingwarning,',',rating_image SEPARATOR '||' ) as rating,
					COUNT(DISTINCT fid) AS reviews,
					COUNT(DISTINCT chapid) AS chapters
					FROM
					(
						SELECT S.sid,C.chapid,UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified,
								F.fid,
								S.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image, Ra.ratingwarning,
								U.uid, U.username,
								Cat.cid, Cat.category,
								TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
								Ch.charid, Ch.charname
							FROM `tbl_stories` S
								LEFT JOIN `tbl_ratings` Ra ON ( Ra.rid = S.ratingid )
								LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
									LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
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

		// only build tag tree when there is a tag set
		if($item['tagblock']!==NULL)
		{
			$tagblock['simple'] = $this->cleanResult($item['tagblock']);
			foreach($tagblock['simple'] as $t)
				$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];
		}
		else $tagblock = NULL;

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

	public function recountStory(int $sid)
	{
		$this->exec("UPDATE `tbl_stories`S
						INNER JOIN
						(
							SELECT
								sid,
								COUNT(DISTINCT chapid)'chapters',
								SUM(wordcount)'wordcount'
							FROM `tbl_chapters`
							WHERE sid = :sid AND validated >= 30
							GROUP BY sid
						) Ch ON ( S.sid = Ch.sid  )
					SET S.wordcount = Ch.wordcount, S.chapters = Ch.chapters;",
					[ ":sid" => $sid ]
					);
		// drop stats cache to make changes visible
		\Cache::instance()->clear('stats');
	}

	public function rebuildSeriesCache($collID)
	{
		$sql = "SELECT
					SERIES.collid,
					SERIES.tagblock,
					SERIES.characterblock,
					SERIES.authorblock,
					SERIES.categoryblock,
					CONCAT(rating,'||',max_rating_id) as max_rating
				FROM
					(
						SELECT
							Coll.collid,
							MAX(Ra.rid) as max_rating_id,
							GROUP_CONCAT(DISTINCT U.uid,',',U.username ORDER BY username ASC SEPARATOR '||' ) as authorblock,
							GROUP_CONCAT(DISTINCT Chara.charid,',',Chara.charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
							GROUP_CONCAT(DISTINCT C.cid,',',C.category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
							GROUP_CONCAT(DISTINCT T.tid,',',T.label,',',TG.description,',',TG.tgid ORDER BY TG.order,TG.tgid,T.label ASC SEPARATOR '||') AS tagblock
						FROM `tbl_collections`Coll
							LEFT JOIN `tbl_collection_stories`rCS ON ( Coll.collid = rCS.collid )
								LEFT JOIN `tbl_stories`S ON ( rCS.sid = S.sid )
									LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.ratingid )
									LEFT JOIN `tbl_stories_tags`rST ON ( rST.sid = S.sid )
										LEFT JOIN `tbl_tags`T ON ( T.tid = rST.tid AND rST.character = 0 )
											LEFT JOIN `tbl_tag_groups`TG ON ( TG.tgid = T.tgid )
										LEFT JOIN `tbl_characters`Chara ON ( Chara.charid = rST.tid AND rST.character = 1 )
									LEFT JOIN `tbl_stories_categories`rSC ON ( rSC.sid = S.sid )
										LEFT JOIN `tbl_categories`C ON ( rSC.cid = C.cid )
									LEFT JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid )
										LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
						WHERE Coll.collid = :collection
						GROUP BY Coll.collid
					) AS SERIES
				LEFT JOIN `tbl_ratings`R ON (R.rid = max_rating_id);";
		$item = $this->exec($sql, [':collection' => $collID] );

		if ( empty($item) ) return FALSE;

		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_collections',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_authors'		=> json_encode($this->cleanResult($item['authorblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'max_rating'		=> json_encode(explode(",",$item['max_rating'])),
			],
			['collid=?',$collID]
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

	public function rebuildCollectionCache($collid)
	{

	}

	public function cacheCategories(int $catID)
	{
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories' );
		$categories->load(array('cid=?',$catID));

		// recover from bad category ID
		if( empty($categories->cid) ) return FALSE;

		$sql = "SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, C.parent_cid as parent,
					GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories,
					GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
			FROM `tbl_categories`C
				LEFT JOIN `tbl_stories_categories`SC ON ( C.cid = SC.cid )
				LEFT JOIN `tbl_stories`S ON ( S.sid = SC.sid )
				LEFT JOIN `tbl_categories`C1 ON ( C.cid = C1.parent_cid )
			WHERE C.cid = :cid
			GROUP BY C.cid";
		$item = $this->exec($sql, [":cid" => $catID])[0];

		if ( $item['sub_categories']==NULL ) $sub = NULL;
		else
		{
			$sub_categories = explode("||", $item['sub_categories']);
			$sub_stats = explode("||", $item['sub_stats']);
			$sub_stats = array_map("json_decode", $sub_stats);

			foreach( $sub_categories as $key => $value )
			{
				if ($sub_stats[$key]!=NULL)
				{
					$item['counted'] += $sub_stats[$key]->count;
					$sub[] =
					[
						'id' 	=> $sub_stats[$key]->cid,
						'count' => $sub_stats[$key]->count,
						'name'	=> $value,
					];
				}
			}
		}
		$categories->stats = json_encode([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
		$categories->save();

		if ( $categories->parent_cid > 0 )
			$this->cacheCategories( $categories->parent_cid );

		return TRUE;
	}

	public function storyRelationCategories( $sid, $data )
	{
		$data = array_filter(explode(",",$data));
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');
		$i = 0;

		foreach ( $categories->find(array('`sid` = ?',$sid)) as $X )
		{
			$temp=array_search($X['cid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$categories->erase(['lid=?',$X['lid']]);
				// recache this category
				$this->cacheCategories($X['cid']);
				$i++;
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
				$i++;
			}
		}
		unset($categories);
		
		return $i;
	}

	// wrapper for storyRelationTag
	public function storyRelationCharacter( $sid, $data )
	{
		return $this->storyRelationTag( $sid, $data, 1 );
	}

	public function storyRelationTag( $sid, $data, $character = 0 )
	{
		// Check tags:
		$data = array_filter(explode(",",$data));
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');
		$i = 0;

		foreach ( $relations->find(array('`sid` = ? AND `character` = ?',$sid,$character)) as $X )
		{
			$temp=array_search($X['tid'], $data);
			if ( $temp===FALSE )
			{
				$recounts[] = $X['tid'];
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
				$i++;
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
				$i++;
			}
		}
		unset($relations);

		// call recount function
		if ( isset( $recounts ) )
			$this->storyRecountTags( $recounts, $character );
		return $i;
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
		// Check Authors:
		$mainDB = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');
		$i = 0;


		// refuse to leave an empty author list behind (unless stated otherwise)
		if(sizeof($mainauthor) or ($allowEmptyAuthor))
		{

			// clean up supporting authors
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
					$i++;
				}
				else unset($supauthor[$isSup]);
			}

			// clean up main authors,
			foreach ( $mainDB->find(array('`sid` = ? AND `type` = ?',$storyID,'M')) as $X )
			{
				$isMain=array_search($X['aid'], $mainauthor);
				if ( $isMain===FALSE )
				{
					// Excess relation, drop from table
					$mainDB->erase(['lid=?',$X['lid']]);
					$i++;
				}
				else unset($mainauthor[$isMain]);
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
					$i++;
				}
			}
			unset($supDB);

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
					$i++;
				}
			}
			unset($mainDB);
		}
		else
		{
			$_SESSION['lastAction'] = [ "deleteWarning" => \Base::instance()->get('LN__MainAuthorNotEmpty') ];
		}
		return $i;
	}

	public function collectionAdd(array $data) : int
	{
		$newCollection = new \DB\SQL\Mapper($this->db, $this->prefix."collections");
		$newCollection->title	= $data['title'];
		$newCollection->uid		= $_SESSION['userID'];
		$newCollection->open	= 0;
		$newCollection->ordered	= $data['ordered'];
		$newCollection->status	= "H";
		$newCollection->save();
		
		$newID = $newCollection->_id;
		\Cache::instance()->reset("menuUCPCountLib.{$_SESSION['userID']}");

		return $newID;
	}
	
	public function collectionsList(int $page, array $sort, string $module, int $userid=0) : array
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = 	"SELECT SQL_CALC_FOUND_ROWS
					Coll.collid, Coll.title, U.username,
					COUNT(DISTINCT rCS.sid) as stories
				FROM `tbl_collections`Coll
					LEFT JOIN `tbl_collection_stories`rCS ON ( Coll.collid = rCS.collid )
					LEFT JOIN `tbl_users`U ON ( Coll.uid = U.uid )
				WHERE Coll.ordered = ".(int)($module!="collections").(($userid>0)?" AND Coll.uid = {$userid}":"")."
				GROUP BY Coll.collid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
		
		$data = $this->exec( $sql );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/stories/".(($module=="collections")?"collections":"series")."/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}

	public function collectionLoad(int $collid, int $userID=0)
	{
		// if not coming from the admin panel, restrict to self
		$where = ($userID) ? "AND Coll.uid = {$userID}" : "";

		$sql = "SELECT Coll.collid, Coll.title, Coll.summary, Coll.ordered, Coll.status, Coll.uid,
					U1.username,
				--	Coll.cache_tags, Coll.cache_characters, Coll.cache_categories,
					GROUP_CONCAT(DISTINCT Ch.charid,',',Ch.charname ORDER BY Ch.charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT U.uid,',',U.username ORDER BY U.username ASC SEPARATOR '||' ) as authorblock,
					GROUP_CONCAT(DISTINCT T.tid,',',T.label ORDER BY TG.order,T.label ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT Cat.cid,',',Cat.category ORDER BY Cat.category ASC SEPARATOR '||') as categoryblock
					FROM `tbl_collections`Coll
						LEFT JOIN `tbl_users`U1 ON ( U1.uid = Coll.uid )
						LEFT JOIN `tbl_collection_properties`pColl ON ( pColl.collid = Coll.collid )
							LEFT JOIN `tbl_users`U ON ( U.uid = pColl.relid AND pColl.type = 'A' )
							LEFT JOIN `tbl_characters`Ch ON ( Ch.charid = pColl.relid AND pColl.type = 'CH' )
							LEFT JOIN `tbl_tags`T ON ( T.tid = pColl.relid AND pColl.type = 'T' )
								LEFT JOIN `tbl_tag_groups`TG ON ( TG.tgid = T.tgid )
							LEFT JOIN `tbl_categories`Cat ON ( Cat.cid = pColl.relid AND pColl.type = 'CA' )
					WHERE Coll.collid = :collid @WHERE@
					GROUP BY Coll.collid";

		$tmp = $this->exec(str_replace("@WHERE@", $where, $sql), [":collid" => $collid ])[0] ?? [];

		if (sizeof($tmp)==0)
			return NULL;

		$data =
		[
			"collid"			=> $tmp['collid'],
			"title"				=> $tmp['title'],
			"summary"			=> $tmp['summary'],
			"ordered"			=> $tmp['ordered'],
			"status"			=> $tmp['status'],
			"authorblock"		=> parent::cleanResult($tmp['authorblock']),
			"characterblock"	=> parent::cleanResult($tmp['characterblock']),
			"tagblock"			=> parent::cleanResult($tmp['tagblock']),
			"categoryblock"		=> parent::cleanResult($tmp['categoryblock']),
			"maintainerblock"	=> json_encode( [[ "id" => $tmp['uid'], "name" => $tmp['username'] ]] ),
			// inject possible collection states
			"states"			=> ['H','F','P','A']
		];

		// Compile currently used tags and characters from stories in this collection
		$data['unused'] =
		[
			"tags"			=> $this->collectionCountTags($collid,$data['tagblock']),
			"characters"	=> $this->collectionCountCharacters($collid,$data['characterblock']),
			"categories"	=> $this->collectionCountCategories($collid,$data['categoryblock']),
			"authors"		=> $this->collectionCountAuthors($collid,$data['authorblock']),
		];

		return $data;
	}

	public function collectionLoadItems(int $collid, int $userID=0)
	{
		// if not coming from the admin panel, restrict to self
		$where = ($userID) ? "AND Coll.uid = {$userID}" : "";

		$sql = "SELECT Coll.collid, Coll.title, Coll.ordered,
					S.sid,S.title,
					GROUP_CONCAT(DISTINCT A.uid,',',A.username ORDER BY A.username ASC SEPARATOR '||') as authorblock
					FROM `tbl_collections`Coll
						LEFT JOIN `tbl_collection_stories`rCS ON ( Coll.collid = rCS.collid )
							LEFT JOIN `tbl_stories`S ON ( rCS.sid = S.sid )
							LEFT JOIN `tbl_stories_authors`rSA ON ( rCS.sid = rSA.sid )
								LEFT JOIN `tbl_users`A ON ( rSA.aid = A.uid )
					WHERE Coll.collid = :collid @WHERE@
					GROUP BY rCS.sid
					ORDER BY
						CASE ordered WHEN 1 THEN rCS.inorder END ASC, 
						CASE ordered WHEN 0 THEN S.updated END DESC";

		$items = $this->exec(str_replace("@WHERE@", $where, $sql), [":collid" => $collid ]);

		if (sizeof($items)==0)
			return NULL;

		foreach ( $items as &$item )
			$item["authorblock"] = parent::cleanResult($item['authorblock']);

		$data =
		[
			"collid"		=> $items[0]['collid'],
			"title"			=> $items[0]['title'],
			"ordered"		=> $items[0]['ordered'],
			"items"			=> ($items[0]['sid'])?$items:NULL,
		];

		return $data;
	}

	protected function collectionCountCharacters(int $collid, &$used)
	{

		$sql = "SELECT Ch.charid as id, Ch.charname as name, COUNT(rST.sid) AS counted
					FROM `tbl_collection_stories`rCS
						LEFT JOIN `tbl_stories`S ON ( S.sid = rCS.sid )
							LEFT JOIN `tbl_stories_tags`rST ON ( S.sid = rST.sid AND rST.character = 1 )
								LEFT JOIN `tbl_characters`Ch ON ( rST.tid = Ch.charid )
				WHERE collid = :collid AND Ch.charname IS NOT NULL
				GROUP BY Ch.charid
				ORDER BY counted DESC;";

		$tmp = $this->exec( $sql, [ ":collid" => $collid ] );

		if (is_array($used) AND sizeof($used))
		{
			foreach ( $used as $U )
			{
				$key = array_search($U[0], array_column($tmp, 'id'));
				$newU[] = [ 'id' => $U[0], 'name' => $U[1].(($key===FALSE)?"":" ({$tmp[$key]['counted']}x)") ];
				if ( $key!==FALSE ) array_splice($tmp, $key, 1);
			}
			$used = json_encode($newU);
		}
		else $used = '""';

		/*
		// for use as a token pre-pop
		foreach ( $tmp as $T )
		{
			$J[] = [ "id" => $T['id'], "name" => $T['name'] ];
		}
		return json_encode($J);
		*/
		return $tmp;
}

	protected function collectionCountTags(int $collid, &$used)
	{
		$sql = "SELECT T.tid as id, T.label as name, COUNT(rST.sid) AS counted
					FROM `tbl_collection_stories`rCS
						LEFT JOIN `tbl_stories`S ON ( S.sid = rCS.sid )
							LEFT JOIN `tbl_stories_tags`rST ON ( S.sid = rST.sid AND rST.character = 0 )
								LEFT JOIN `tbl_tags`T ON ( rST.tid = T.tid )
				WHERE collid = :collid AND T.label IS NOT NULL
				GROUP BY T.tid
				ORDER BY counted DESC;";

		$tmp = $this->exec( $sql, [ ":collid" => $collid ] );

		if (is_array($used) AND sizeof($used))
		{
			foreach ( $used as $U )
			{
				$key = array_search($U[0], array_column($tmp, 'id'));
				$newU[] = [ 'id' => $U[0], 'name' => $U[1].(($key===FALSE)?"":" ({$tmp[$key]['counted']}x)") ];
				if ( $key!==FALSE ) array_splice($tmp, $key, 1);
			}
			$used = json_encode($newU);
		}
		else $used = '""';

		return $tmp;
	}

	protected function collectionCountCategories(int $collid, &$used)
	{
		$sql = "SELECT Cat.cid as id, Cat.category as name, COUNT(rSC.sid) AS counted
					FROM `tbl_collection_stories`rCS
						LEFT JOIN `tbl_stories`S ON ( S.sid = rCS.sid )
							LEFT JOIN `tbl_stories_categories`rSC ON ( S.sid = rSC.sid )
								LEFT JOIN `tbl_categories`Cat ON ( rSC.cid = Cat.cid )
				WHERE collid = :collid AND Cat.description IS NOT NULL
				GROUP BY Cat.cid
				ORDER BY counted DESC;";

		$tmp = $this->exec( $sql, [ ":collid" => $collid ] );

		if (is_array($used) AND sizeof($used))
		{
			foreach ( $used as $U )
			{
				$key = array_search($U[0], array_column($tmp, 'id'));
				$newU[] = [ 'id' => $U[0], 'name' => $U[1].(($key===FALSE)?"":" ({$tmp[$key]['counted']}x)") ];
				if ( $key!==FALSE ) array_splice($tmp, $key, 1);
			}
			$used = json_encode($newU);
		}
		else $used = '""';

		return $tmp;
	}

	protected function collectionCountAuthors(int $collid, &$used)
	{
		$sql = "SELECT U.uid as id, U.username as name, COUNT(rSA.sid) AS counted
					FROM `tbl_collection_stories`rCS
						LEFT JOIN `tbl_stories`S ON ( S.sid = rCS.sid )
							LEFT JOIN `tbl_stories_authors`rSA ON ( S.sid = rSA.sid )
								LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
				WHERE collid = :collid AND U.username IS NOT NULL
				GROUP BY U.uid
				ORDER BY counted DESC;";

		$tmp = $this->exec( $sql, [ ":collid" => $collid ] );

		if (is_array($used) AND sizeof($used))
		{
			foreach ( $used as $U )
			{
				$key = array_search($U[0], array_column($tmp, 'id'));
				$newU[] = [ 'id' => $U[0], 'name' => $U[1].(($key===FALSE)?"":" ({$tmp[$key]['counted']}x)") ];
				if ( $key!==FALSE ) array_splice($tmp, $key, 1);
			}
			$used = json_encode($newU);
		}
		else $used = '""';

		return $tmp;
	}

	public function collectionSave(int $collid, array $data, int $userID=0)
	{
		$uid = ($userID==0)?$data['maintainer']:$_SESSION['userID'];
		$collection=new \DB\SQL\Mapper($this->db, $this->prefix.'collections');
		if($userID)
			$collection->load(array('collid=? AND uid=?', $collid, $userID));
		else
			$collection->load(array('collid=?', $collid));

		$collection->copyfrom( 
			[
				"title"		=> $data['title'],
				"ordered"	=> (isset($data['changetype'])) ? !$collection->ordered : $collection->ordered,
				"summary"	=> $data['summary'],
				"uid"		=> ($userID==0)?$data['maintainer']:$_SESSION['userID'],
			]
		);

		$i  = $collection->changed("title");
		$i += $collection->changed("summary");
		
		$collection->save();
		
		// update relation table
		$this->collectionProperties( $collid, $data['author'], "A" );
		$this->collectionProperties( $collid, $data['tag'], "T" );
		$this->collectionProperties( $collid, $data['character'], "CH" );
		$this->collectionProperties( $collid, $data['category'], "CA" );

		$this->rebuildCollectionCache($collection->collid);
		
		// drop menu cache if type changed:
		if(isset($data['changetype'])) \Cache::instance()->reset("menuUCPCountLib.{$uid}");

		return $i;
	}
	
	public function collectionDelete(int $collid, array $data, int $userID=0)
	{
		$collection=new \DB\SQL\Mapper($this->db, $this->prefix.'collections');
		
		// attempt to delete based upon ACP or UCP access
		if($userID)
			$i = $collection->erase(array('collid=? AND uid=?', $collid, $userID));
		else
			$i = $collection->erase(array('collid=?', $collid));
		
		// if one item was deleted, clean up the relation tables
		if ( $i )
		{
			(new \DB\SQL\Mapper($this->db, $this->prefix.'collection_properties'))->erase(array('collid=?', $collid));
			(new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories'))->erase(array('collid=?', $collid));

			// decide whose menu cache to delete
			$uid = ($userID==0)?$data['maintainer']:$_SESSION['userID'];
			\Cache::instance()->reset("menuUCPCountLib.{$uid}");
		}
		// report the result
		return $i;
	}

	private function collectionProperties( $collid, $data, $type )
	{
		// Check tags:
		$data = explode(",",$data);
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'collection_properties');

		foreach ( $relations->find(array('`collid` = ? AND `type` = ?',$collid,$type)) as $X )
		{
			if ( FALSE === $temp = array_search($X['relid'], $data) )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp )
			{
				if ( !empty($temp) )		// Fix adding empty entries
				{
					// Add relation to table
					$relations->reset();
					$relations->collid = $collid;
					$relations->relid = $temp;
					$relations->type = $type;
					$relations->save();
				}
			}
		}
		unset($relations);
	}
	
	public function collectionItemsAdd(int $collid, string $data, int $userID=0)
	{
		// make sure the user actuall owns this collection
		if ( ($userID==0) OR  (1 == (new \DB\SQL\Mapper($this->db, $this->prefix."collections"))->count(['collid=? AND uid=?', $collid, $userID])) )
		{
			$items = explode(",",$data);
			$newItem = new \DB\SQL\Mapper($this->db, $this->prefix."collection_stories");
			$count = $newItem->count(array('collid=?',$collid));
	  
			foreach ( $items as $item )
			{
				if(is_numeric($item))
				{
					$newItem->reset();
					$newItem->collid	= $collid;
					$newItem->sid 		= $item;
					$newItem->confirmed	= $item;
					$newItem->inorder	= ++$count;
					$newItem->save();
				}
			}
		}
		else
		{
			// Access violation *todo*
			
		}
	}
	
	public function libraryCollectionItemDelete(int $collid, int $itemid, int $userID=0)
	{
		// make sure the user actuall owns this collection
		if ( ($userID==0) OR  (1 == (new \DB\SQL\Mapper($this->db, $this->prefix."collections"))->count(['collid=? AND uid=?', $collid, $userID])) )
		{
			return (new \DB\SQL\Mapper($this->db, $this->prefix."collection_stories"))->erase(['collid=? AND sid=?', $collid, $itemid]);
		}
		return 0;
	}
	
	public function collectionAjaxItemsort(array $data, int $userID = 0)
	{
		// quietly drop out on user mismatch
		if ( ($userID==0) OR  (1 == (new \DB\SQL\Mapper($this->db, $this->prefix."collections"))->count(['collid=? AND uid=?', $data['collectionsort'], $userID])) )
		{
			$stories = new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
			foreach ( $data["neworder"] as $order => $id )
			{
				if ( is_numeric($order) && is_numeric($id) && is_numeric($data["collectionsort"]) )
				{
					$stories->load(array('collid = ? AND sid = ?', $data['collectionsort'], $id));
					$stories->inorder = $order+1;
					$stories->save();
				}
			}
		}
		exit;
	}

}
?>