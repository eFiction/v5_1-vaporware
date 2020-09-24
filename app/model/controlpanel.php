<?php
namespace Model;

class Controlpanel extends Base {

	/**
	* List chapters for a given story ID
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	* @param	int		$userID		Optional, only used in UCP
	*
	* @return	int					New chapter's ID (0 in case of error)
	*/
	public function chapterAdd( int $storyID, int $userID=0 ) : int
	{
		// get the current chapter count, and with it, check if the story exists
		// if required, the user's permission to add a chapter to the story will also be checked, although the controller should have taken care of this
		if ( $userID )
		{
			$countSQL = "SELECT COUNT(chapid) as chapters, U.uid, MAX(Ch.inorder) as lastorder
							FROM `tbl_stories`S
								LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid )
								INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid AND rSA.type='M' )
									INNER JOIN `tbl_users`U ON ( (rSA.aid = U.uid) AND (U.uid=:uidU OR U.curator=:uidC) )
						WHERE S.sid = :sid ";
			$countBind = [ ":sid" => $storyID, ":uidU" => $userID, ":uidC" => $userID ];

			$chapterCount = $this->exec($countSQL, $countBind)[0];

			if ( empty($chapterCount) OR $chapterCount['uid']==NULL )
				return 0;

			// set the initial validation status
			// even with a trusted author, we don't want the chapter to be marked as finished right away
			$validated = 11;
		}
		else
		{
			// coming from adminCP, no need to check user permission
			$chapterCount = $this->exec("SELECT COUNT(Ch.chapid) as chapters, MAX(Ch.inorder) as lastorder
											FROM `tbl_chapters`Ch
										WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0];

			if ( empty($chapterCount) )
				return 0;

			// set the initial validation status
			$validated = "1".($_SESSION['groups']&128)?"3":"2";
		}

		// use this to check if the inorder has been properly set, i.e. no missing orders
		if ( $chapterCount['chapters'] != $chapterCount['lastorder'] )
			$this->rebuildStoryChapterOrder ( $storyID );

		// Get current chapter count and raise
		$chapterCount = $chapterCount['chapters'] + 1;

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
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid") VALUES ( :chapid, :sid )',
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
								]
			);
		}

		return $chapterID;
	}

	/**
	* List chapters for a given story ID
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	*
	* @return	array			Result or empty
	*/
	public function chapterLoadList( int $storyID ) : array
	{
		return $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.title,Ch.validated,Ch.inorder
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid ORDER BY Ch.inorder ASC",
			[":sid" => $storyID ]
		);
	}

	/**
	* Load chapter
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	* @param	int		$chapterID	Chapter ID
	*
	* @return	array			Result or empty
	*/
	public function chapterLoad( int $storyID, int $chapterID ) : array
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.inorder,Ch.title,Ch.notes,Ch.endnotes,Ch.validated,Ch.rating,Ch.chaptertext
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid AND Ch.chapid = :chapter",
			[":sid" => $storyID, ":chapter" => $chapterID ]
		);
		// an empty result means there is no such chapter
		if (empty($data)) return [];

		// if the chapter text is stored locally, we must go end get it
		if ( $this->config['chapter_data_location'] == "local" )
			$data[0]['chaptertext'] = $this->getChapterText( $storyID, $chapterID, FALSE );

		return $data[0];
	}

	/**
	* Save chapter info/description
	* rewrite 2020-09
	*
	* @param	int		$chapterID	Chapter ID ID
	* @param	array	$post		Data sent from the form
	* @param	string	$power		Are we an 'A'dmin or a 'U'ser
	*
	* @return	int					Counted changes
	*/
	public function chapterSave( int $chapterID, array $post, string $power = 'U' ) : int
	{
		// plain and visual return different newline representations, this will bring things to standard.
		$chaptertext = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $post['chapter_text']);

		$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		$chapter->load(array('chapid=?',$chapterID));
		
		$chapter->title 	= $post['chapter_title'];
		$chapter->notes 	= $post['chapter_notes'];
		$chapter->endnotes 	= $post['chapter_endnotes'];
		$chapter->wordcount	= max(count(preg_split("/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]{0,}/u",$chaptertext))-1, 0);

		// Treat validation input based on module
		// 'A'dmin has more power
		if ( $power == 'A' )
		{
			// remember old validation status
			$oldValidated 		= $chapter->validated;
			$chapter->validated = $post['validated'].$post['valreason'];

			if ( $chapter->changed("validated") )
			{
				if ( $post['validated'] == 3 AND substr($oldValidated,0,1)!=3 )
				// story got validated
				\Logging::addEntry(['VS','c'], [ $chapter->sid, $chapter->inorder] );

				elseif ( $post['validated'] < 3 AND substr($oldValidated,0,1)==3 )
				// story got invalidated
				// need better logging here
				\Logging::addEntry(['VS','c'], [ $chapter->sid, $chapter->inorder] );
			}
		}
		// 'U'ser may onle be able to request validation
		else
		{
			// Toggle validation request, keeping the reason part untouched
			if ( isset($post['request_validation']) AND $chapter->validated < 20 )
			{
				$chapter->validated 	= 	$chapter->validated + 10;
				// Insert time of creation (internal data)
				$chapter->created		=	date('Y-m-d H:i:s');
			}
			elseif ( empty($post['request_validation']) AND $chapter->validated >= 20 AND $chapter->validated < 30 )
				$chapter->validated 	= 	$chapter->validated - 10;
				
			// Allow trusted authors to set validation
			if ( isset($post['mark_validated']) AND $_SESSION['groups']&8 AND $chapter->validated < 20 AND $chapter->wordcount > 0 )
			{
				$chapter->validated 	= 	$chapter->validated + 20;
				$chapter->created		=	date('Y-m-d H:i:s');
				
				// Update the story entry, set updated field to now
				$this->exec("UPDATE `tbl_stories`S 
								INNER JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid AND Ch.chapid = :chapid ) 
							SET S.updated = :updated;",
							[
								":chapid" => $chapterID,
								":updated" => $chapter->created
							]);
				// Log validation
				\Logging::addEntry(['VS','c'], [$chapter->sid,$chapterID]);
			}
		}

		// Decide if we need to run a recount
		if ( 
			// validation status changed
			$chapter->changed("validated") 
			// chapter text changed
			OR $this->chapterContentSave($chapterID, $chaptertext, $chapter)
			)
		{
			$recount = TRUE;
		}

		$i = $chapter->changed();
		// save chapter information
		$chapter->save();

		if ( isset($recount) )
		// perform recount, this has to take place after save();
		{
			// recount this story
			$this->recountStory($chapter->sid);
			
			// recount all collections that feature this story
			$collection=new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
			$inSeries = $collection->find(array('sid=?',$chapter->sid));
			foreach ( $inSeries as $in )
			{
				// Rebuild collection/series cache based on new data
				$this->cacheCollections($in->seriesid);
			}
			$i++;
		}

		return $i;
	}

	/**
	* Save chapter text in the assigned database
	* rewrite 2020-09
	*
	* @param	int				$chapterID
	* @param	string			$chapterText
	* @param	\DB\SQL\Mapper	$mapper			mapper to the SQL table
	*
	* @return	int								Was anything changed?
	*/
	public function chapterContentSave( int $chapterID, string $chapterText, \DB\SQL\Mapper $mapper ) : int 
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

	/**
	* Delete the given chapter
	* rewrite 2020-09
	*
	* @param	int				$storyID
	* @param	int				$chapterID
	* @param	int				$userID			Optional, only used in UCP
	*
	* @return	int								Success count
	*/
	public function chapterDelete( int $storyID, int $chapterID, int $userID = 0 ) : int
	{
		if ( $userID == 0 OR 1==(new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors'))->count( ["sid = ? AND aid = ? and type='M'", $storyID, $userID] ) )
		{	
			$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
			$bind = ['sid=? AND chapid=?', $storyID, $chapterID];
			
			if ( $this->config['chapter_data_location'] == "local" )
			{
				$localdb = new \DB\SQL\Mapper(\storage::instance()->localChapterDB(), 'chapters');
				$i = $localdb->erase($bind);
			}
			else $i=1;
			
			// delete the chapter
			$j = $chapter->erase($bind);
			
			// recount story stats based on new data
			$this->recountStory($storyID);
			
			// rebuild the inorder fields
			$this->rebuildStoryChapterOrder($storyID);
			
			// this should equate to 1*1 if there was no error
			return ( $i * $j );
		}
		return 0;
	}

	/**
	* Add a new story
	* rewrite 2020-09
	*
	* @param	array	$data	Story title, and in case of ACP also a list of authors
	*
	* @return	int				SQL ID of the newly created story
	*/
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

	/**
	* Load story info
	* rewrite 2020-09
	*
	* @param	int		$sid	Story ID
	* @param	int		$uid	User ID, optional, only sent from the UCP
	*
	* @return	array			Result or empty
	*/
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

	/**
	* Delete the entire story
	* This will also delete all associated reviews, tracker entries, and relations
	* rewrite 2020-09
	*
	* @param	int		$sid	Story ID
	* @param	int		$uid	User ID, optional, only sent from the UCP
	*
	* @return	array			Result or empty
	*/	
	public function storyDelete( int $storyID, int $userID = 1 ) : int
	{
		// drop out with an error if the story exists, but the user has no access
		if ( $userID > 0 AND 0 == ( new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors') )->count(["sid = ? AND aid = ? AND type = 'M'",$storyID,$userID]) )
			return -1;

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

		/**
		*  rewrite: entries get deleted by foreign key, so in this step
		*  we take note of all items that need to be recounted or recached
		*/
		// Collections
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
		$mapper->load(["sid = ?",$storyID]);
		while ( !$mapper->dry() )
		{
			$recacheCollections[] = $mapper->collid;
			$mapper->next();
		}

		// Tags
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');
		$mapper->load(["sid = ?",$storyID]);
		while ( !$mapper->dry() )
		{
			if ( $mapper->character == 0 )
				$recountTags[] = $mapper->tid;

			else
				$recountCharacters[] = $mapper->tid;

			$mapper->next();
		}
		
		// Categories
		$mapper = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');
		$mapper->load(['sid = ?',$storyID]);
		while ( !$mapper->dry() )
		{
			$recacheCategories[] = $mapper->cid;
			$mapper->next();
		}

		// Remove the story from the db
		$this->exec
		(
			"DELETE FROM `tbl_stories` WHERE `sid` = :sid;",
			[ ":sid" => $storyID ]
		);
		// Let the user know that the story couldn't be deleted
		if ( 0 == $deleted['story'] = $this->db->count() )
			return 0;

		/**
		*  If we are here, it means we have deleted a story.
		*  Now it's time to clean up
		*/

		// Delete chapters from local storage if required
		if ( $this->config['chapter_data_location'] == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$db->exec('DELETE FROM "chapters" WHERE "sid" = :sid', array(':sid' => $storyID ));
			$deleted['chapterlocal'] = $db->count();
		}
		
		// Tag recount
		if ( !empty($recountTags) )
			$deleted['tags'] = $this->recountTags($recountTags);

		// Character recount
		if ( !empty($recountCharacters) )
			$deleted['characters'] = $this->recountTags($recountCharacters,1);

		// re-create Collections cache
		if ( !empty($recacheCollections) )
			$this->cacheCollections($mapper->seriesid);

		// re-create Categories cache
		if ( !empty($recacheCategories) )
			$this->cacheCategories($category);

		return 1;
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


	/**
	* Rebuild the inorder data for chapters within a story
	* This will be called whenever a chapter is being deleted and if there is a mismatch detected when adding a chapter.
	* rewrite 2020-09
	*
	* @param	int		$sid	Story ID
	* @param	int		$uid	User ID, optional, only sent from the UCP
	*
	* @return	array			Result or empty
	*/
	public function rebuildStoryChapterOrder( int $storyID )
	{
		$chapterList = new \DB\SQL\Mapper($this->db, $this->prefix."chapters");
		$chapterList->load(['sid = ?', $storyID], ['order' => 'inorder ASC, chapid ASC']);
		
		$i = 1;
		while ( !$chapterList->dry() )
		{
			$chapterList->inorder = $i++;
			$chapterList->save();
			$chapterList->next();
		}
	}

	public function rebuildStoryCache( int $sid ) : bool
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
		return TRUE;
	}

	/**
	* rebuild the stats cache for all collections that were added to or removed from a story
	* ot that hat a story added to or removed from
	* rewrite 2020-09
	*
	* @param	array		$collID		ID of collection to be re-cached
	*
	* @return	bool
	*/
	public function cacheCollections( int $collID ) : bool
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
		return TRUE;
	}

	/**
	* rebuild the stats cache for all collections that were added to or removed from a story
	* ot that hat a story added to or removed from
	* rewrite 2020-09
	*
	* @param	array		$recID	ID of recommendation to be re-cached
	*
	* @return	bool
	*/
	public function cacheRecommendations( int $recID ) : bool
	{
		$sql = "SELECT SELECT_OUTER.recid,
					GROUP_CONCAT(DISTINCT tid,',',tag,',',description,',',tgid ORDER BY `order`,tgid,tag ASC SEPARATOR '||') AS tagblock,
					GROUP_CONCAT(DISTINCT charid,',',charname ORDER BY charname ASC SEPARATOR '||') AS characterblock,
					GROUP_CONCAT(DISTINCT cid,',',category ORDER BY category ASC SEPARATOR '||' ) as categoryblock,
					GROUP_CONCAT(DISTINCT ratingid,',',rating_name,',',rating_image SEPARATOR '||' ) as rating
					FROM
					(
						SELECT R.recid,
							R.ratingid, Ra.rating as rating_name, IF(Ra.rating_image,Ra.rating_image,'') as rating_image,
							Cat.cid, Cat.category,
							TG.description,TG.order,TG.tgid,T.label as tag,T.tid,
							Ch.charid, Ch.charname
						FROM `tbl_recommendations` R
							LEFT JOIN `tbl_ratings` Ra ON ( Ra.rid = R.ratingid )
							LEFT JOIN `tbl_recommendation_relations`rRT ON ( rRT.recid = R.recid )
								LEFT JOIN `tbl_tags` T ON ( T.tid = rRT.relid AND rRT.type='T' )
									LEFT JOIN `tbl_tag_groups` TG ON ( TG.tgid = T.tgid )
								LEFT JOIN `tbl_characters` Ch ON ( Ch.charid = rRT.relid AND rRT.type = 'CH' )
								LEFT JOIN `tbl_categories` Cat ON ( Cat.cid = rRT.relid AND rRT.type = 'CA' )
						WHERE R.recid = :recid
					)AS SELECT_OUTER
					GROUP BY recid ORDER BY recid ASC;;";
		$item = $this->exec($sql, [':recid' => $recID] );

		if ( empty($item) ) return FALSE;

		$item = $item[0];

		$tagblock['simple'] = $this->cleanResult($item['tagblock']);
		if($tagblock['simple']!==NULL) foreach($tagblock['simple'] as $t)
			$tagblock['structured'][$t[2]][] = [ $t[0], $t[1], $t[2], $t[3] ];

		$this->update
		(
			'tbl_recommendations',
			[
				'cache_tags'		=> json_encode($tagblock),
				'cache_characters'	=> json_encode($this->cleanResult($item['characterblock'])),
				'cache_categories'	=> json_encode($this->cleanResult($item['categoryblock'])),
				'cache_rating'		=> json_encode(explode(",",$item['rating']))
			],
			['recid=?',$recID]
		);
		return TRUE;
	}

	public function rebuildContestCache( int $conid ) : bool
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
		return TRUE;
	}


	/**
	* rebuild the stats cache for all categories that were added to or removed from a story
	* rewrite 2020-09
	*
	* @param	array		$catList	ID or array of category/categories to be re-cached
	*
	* @return	bool
	*/
	public function cacheCategories( $catList ) : bool
	{
		// mapper for categories
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories' );

		/**
			if we received a single ID, convert to array
			no need to sanitize the array, this will be done
			when building the job list
		*/
		if ( is_numeric($catList) )
			$catList[] = (int)$catList;
		
		if ( [] == $jobs = $this->cacheCategoriesGetParent( $categories, $catList ) )
			return FALSE;
		// $jobs is an array containing all categories that need to be redone, starting with lowest level

		$sql = "SELECT C.cid, C.category, COUNT(DISTINCT S.sid) as counted, C.parent_cid as parent,
					GROUP_CONCAT(DISTINCT C1.category SEPARATOR '||' ) as sub_categories,
					GROUP_CONCAT(DISTINCT C1.stats SEPARATOR '||' ) as sub_stats
			FROM `tbl_categories`C
				LEFT JOIN `tbl_stories_categories`SC ON ( C.cid = SC.cid )
				LEFT JOIN `tbl_stories`S ON ( S.sid = SC.sid )
				LEFT JOIN `tbl_categories`C1 ON ( C.cid = C1.parent_cid )
			WHERE C.cid = :cid
			GROUP BY C.cid";

		foreach ( $jobs as $catID )
		{
			// set the cursor to the current category
			$categories->load(['cid = ?', $catID]);
			
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
			$categories->counter = (int)$item['counted'];
			$categories->stats = json_encode([ "count" => (int)$item['counted'], "cid" => $item['cid'], "sub" => $sub ]);
			$categories->save();
		}
		return TRUE;
	}

	/**
	* Get a list of all categories to be recached,
	* recursively done from bottom to top
	* rewrite 2020-09
	*
	* @param	\DB\SQL\Mapper	$categories	DB mapper
	* @param	array			$collList	List of all collections that need to be checked for their parent
	* @param	array			$jobList	List of already known category IDs
	*
	* @return	array			Updated job list
	*/
	public function cacheCategoriesGetParent( \DB\SQL\Mapper $categories, array $collList, array $jobList = [] ) : array
	{
		$parents = [];
		foreach ( $collList as $coll )
		{
			if ( !in_array( $coll, $jobList ) )
			{
				$categories->load(['cid = ?', $coll]);
				if ( $categories->cid == $coll )
					$jobList[] = $categories->cid;
				if ( $categories->parent_cid > 0 )
					$parents[] = $categories->parent_cid;
			}
		}
		if ( !empty($parents) )
			$jobList = $this->cacheCategoriesGetParent( $categories, array_unique($parents), $jobList );
		
		return $jobList;
	}

	/**
	* Given the list of new authors and a current status,
	* either insert, drop or skip entries in the story-authors table.
	* Make sure that nobody is main and supporting author and, if required, 
	* make sure the main author isn't empty
	* rewrite 2020-09
	*
	* @param	int			$storyID
	* @param	string		$mainauthor			List of main authors to be
	* @param	string		$supauthor			List of supporting authors to be
	* @param	bool		$allowEmptyAuthor	Check if the author list can be empty (ACP only)
	*
	* @return	int								Changes made
	*/
	public function relationStoryAuthor ( int $storyID, string $mainauthor, string $supauthor, bool $allowEmptyAuthor = FALSE ) : int
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

	/**
	* Given the list of new relations and a current status,
	* either insert, drop or skip entries in the story-categories table.
	* Call for a recache when changes did occur
	* rewrite 2020-09
	*
	* @param	int			$storyID
	* @param	string		$data		List of relations to be
	*
	* @return	int						Changes made
	*/
	public function relationStoryCategories( int $storyID, string $data ) : int
	{
		$data = array_filter(explode(",",$data));
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');
		$i = 0;

		foreach ( $categories->find(array('`sid` = ?',$storyID)) as $X )
		{
			$temp=array_search($X['cid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$categories->erase(['lid=?',$X['lid']]);
				// recache this category
				$recache[] = $X['cid'];
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
				$categories->sid = $storyID;
				$categories->cid = $temp;
				$categories->save();
				// recache this category
				$recache[] = $temp;
				$i++;
			}
		}
		unset($categories);
		
		if ( !empty($recache) )
			$this->cacheCategories( $recache );
		
		return $i;
	}

	/**
	* Update the 'story - character' relation table
	* Wrapper for the relationStoryTag function
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	* @param	string	$data		New character list
	*
	* @return	int					Report changes made
	*/	
	public function relationStoryCharacter( int $storyID, string $data )
	{
		return $this->relationStoryTag( $storyID, $data, 1 );
	}

	/**
	* Given the list of new relations and a current status,
	* either insert, drop or skip entries in the story-tag table.
	* Call for a recache when changes did occur
	* rewrite 2020-09
	*
	* @param	int		$storyID	Story ID
	* @param	string	$data		New tag list
	* @param	int		$character	Is it a character(person) (default=no)
	*
	* @return	int					Report changes made
	*/
	public function relationStoryTag( int $storyID, string $data, $character = 0 )
	{
		// Check tags:
		$data = array_filter(explode(",",$data));
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');
		$i = 0;

		foreach ( $relations->find(array('`sid` = ? AND `character` = ?',$storyID,$character)) as $X )
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
				$relations->sid = $storyID;
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
			$this->recountTags( $recounts, $character );
		return $i;
	}

	/**
	* Recount story chapters and words
	* rewrite 2020-09
	*
	* @param	mixed	$storyID	Story ID
	*/
	public function recountStory( int $storyID ) : void
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
					[ ":sid" => $storyID ]
					);
		// drop stats cache to make changes visible
		\Cache::instance()->clear('stats');
	}

	/**
	* Recount tag usage
	* rewrite 2020-09
	*
	* @param	mixed	$tags		Tag ID or list of Tag IDs
	* @param	int		$character	Is it a character(person) (default=no)
	*
	* @return	int					Report recounted tags
	*/
	public function recountTags( $tags, $character = 0 ) : int
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

		$this->cacheCollections($collection->collid);
		
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

	private function collectionProperties( int $collid, array $data, string $type )
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
	
	public function collectionItemDelete(int $collid, int $itemid, int $userID=0)
	{
		// make sure the user actuall owns this collection
		if ( ($userID==0) OR  (1 == (new \DB\SQL\Mapper($this->db, $this->prefix."collections"))->count(['collid=? AND uid=?', $collid, $userID])) )
		{
			return (new \DB\SQL\Mapper($this->db, $this->prefix."collection_stories"))->erase(['collid=? AND sid=?', $collid, $itemid]);
		}
		return 0;
	}
	
	public function recommendationList( int $page, array $sort, int $userID = 0 ) : array
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = 	"SELECT SQL_CALC_FOUND_ROWS
					Rec.recid, Rec.title, Rec.url, Rec.uid, 
					IF(Rec.guestname IS NULL,U.username,Rec.guestname) as maintainer, 
					R.rating
				FROM `tbl_recommendations`Rec
					LEFT JOIN `tbl_users`U ON ( Rec.uid = U.uid )
					LEFT JOIN `tbl_ratings`R ON ( Rec.ratingid = R.rid )
				".(($userID>0)?" WHERE Rec.uid = {$userID}":"")."
				GROUP BY Rec.recid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
		
		$data = $this->exec( $sql );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/stories/recommendations/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}
	
	public function recommendationLoad( int $recID, int $userID = 0 ) : array
	{
		$sql = "SELECT Rec.recid, Rec.uid, Rec.url, Rec.title, Rec.author, Rec.summary, Rec.comment, Rec.ratingid, Rec.public, Rec.completed,
					Rec.cache_tags, Rec.cache_characters, Rec.cache_categories,
					U.uid, U.username
				FROM `tbl_recommendations`Rec
					LEFT JOIN `tbl_users`U ON ( Rec.uid = U.uid )
				".(($userID>0)?" WHERE Rec.uid = {$userID}":"")."
				WHERE Rec.recid = :recid
				GROUP BY Rec.recid";

		if ( ( FALSE !== $data = current ( $this->exec( $sql, [ ":recid" => $recID ] ) ) ) AND $data['recid']!="" )
		{
			$data['maintainerblock'] = json_encode( [[ "id" => $data['uid'], "name" => $data['username'] ]] );
			/**
				Use cURL to get an idea of how good or bad the URL might be.
				In the end, a closer look may be required, but it might give a hint
			*/
			/*
			$handle = curl_init();
			curl_setopt($handle, CURLOPT_URL, $data['url']);
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_FAILONERROR, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, 1);
			curl_exec($handle);
			// embed the status into the data array
			$data['lookup'] = curl_getinfo ($handle);
			*/
			$data['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");

			return $data;
		}
		return [];
	}

	public function recommendationSave( int $recid, array $data, int $userID=0 ) : int
	{
		$recommendation=new \DB\SQL\Mapper($this->db, $this->prefix.'recommendations');
		
		if($userID)
			$recommendation->load(array('recid=? AND uid=?', $recid, $userID));
		else
			$recommendation->load(array('recid=?', $recid));

		// copy form data, also used to create a new feature
		$recommendation->copyfrom( 
			[ 
				"title"		=> $data['title'],
				"url"		=> $data['url'],
				"uid"		=> $data['maintainer'],
				"author"	=> $data['author'],
				"summary"	=> $data['summary'],
				"comment"	=> $data['comment'],
			]
		);

		$i  = $recommendation->changed("title");
		$i += $recommendation->changed("url");
		$i += $recommendation->changed("author");
		$i += $recommendation->changed("summary");
		$i += $recommendation->changed("comment");

		// save date
		$recommendation->save();

		// update relation table
		$i += $this->recommendationProperties( $recid, $data['maintainer'], "A" );
		$i += $this->recommendationProperties( $recid, $data['tag'], "T" );
		//$this->recommendationProperties( $recid, $data['character'], "CH" );
		$i += $this->recommendationProperties( $recid, $data['category'], "CA" );

		$this->cacheRecommendations($recommendation->recid);

		return $i;
	}

	/**
	* Re-set the recommendation properties based on a data field
	* rewrite 2020-09
	*
	* @param	int			$recid	
	* @param	string 		$data		Comma-separated list of new relations
	* @param	string 		$type		Type of relations
	*
	* @return	int						Amount of changes made
	*/
	private function recommendationProperties( int $recid, string $data, string $type ) : int
	{
		// load given relations:
		$data = explode(",",$data);
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'recommendation_relations');
		
		$i = 0;

		foreach ( $relations->find(array('`recid` = ? AND `type` = ?',$recid,$type)) as $X )
		{
			if ( FALSE === $temp = array_search($X['relid'], $data) )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
				$i++;
			}
			else unset($data[$temp]);
		}
		
		// Insert any relation IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp )
			{
				if ( !empty($temp) )		// Fix adding empty entries
				{
					// Add relation to table
					$relations->reset();
					$relations->recid = $recid;
					$relations->relid = $temp;
					$relations->type = $type;
					$relations->save();
					$i++;
				}
			}
		}
		unset($relations);
		return $i;
	}

	public function featuredDelete(int $sid)
	{
		$feature=new \DB\SQL\Mapper($this->db, $this->prefix.'featured');
		$feature->load(array('id=? AND type="ST"',$sid));
		
		$_SESSION['lastAction'] = [ "deleteResult" => $feature->erase() ];
	}

	/**
	* AJAX sort collection items
	* rewrite 2020-09
	*
	* @param	array		$date	Lists the new item order
	*/	
	public function ajaxCollectionItemsort( array $data, int $userID = 0 ) : void
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
	
	/**
	* AJAX sort chapters
	* rewrite 2020-09
	*
	* @param	array		$date	Lists the new chapter order
	*/	
	public function ajaxStoryChaptersort( array $data ) : void
	{
		$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		foreach ( $data["neworder"] as $order => $id )
		{
			if ( is_numeric($order) && is_numeric($id) && is_numeric($data["chaptersort"]) )
			{
				$chapters->load(array('chapid = ? AND sid = ?',$id, $data['chaptersort']));
				$chapters->inorder = $order+1;
				$chapters->save();
			}
		}
	}

}
?>