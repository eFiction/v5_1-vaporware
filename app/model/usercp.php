<?php
namespace Model;

class UserCP extends Controlpanel
{
	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu();
	}

	public function showMenu($selected=FALSE, array $data=[])
	{
		if ( $selected )
		{
			if ( $selected == "author")
			{
				$allowed=[];
				// get associated author and curator data
				$authorData = $this->exec("SELECT U.uid, CONCAT(U.username, ' (',COUNT(DISTINCT SA.lid), ')') as label, IF(U.uid={$_SESSION['userID']},1,0) as curator
												FROM `tbl_users`U 
													LEFT JOIN `tbl_stories_authors`SA ON ( U.uid = SA.aid AND SA.type='M' ) 
												WHERE U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} 
												GROUP BY U.uid
												ORDER BY curator DESC, label ASC");
				foreach ( $authorData as $aD )
				{
					$authors["AUTHORS"][$aD["uid"]] = $aD["label"];
					$allowed[$aD["uid"]] = TRUE;
				}
				\Base::instance()->set('allowed_authors', $allowed);
				$authors["ID"] = @$data['uid'];

				$sub = $this->panelMenu($selected, $authors);

				if ( isset($data['uid']) AND isset($authors["AUTHORS"][$data['uid']]) )
				{
					// create an empty array with zero-count to start with
					$status = [ 'id' => $data['uid'], 0 => 0, 1 => 0, 6 => 0, 9 => 0 ];

					// get story count by completion status
					$authorData = $this->exec("SELECT S.completed, COUNT(DISTINCT S.sid) as count 
													FROM `tbl_stories`S
														INNER JOIN `tbl_stories_authors`SA ON (S.sid = SA.sid AND SA.aid = :aid AND SA.type='M') 
													GROUP BY S.completed", [ ":aid" => $data['uid'] ]);
					foreach ( $authorData as $aD )
					{
						switch ( $aD["completed"] )
						{
							case 0:
								$status[0] += $aD["count"];
								break;
							case 1:
								$status[1] += $aD["count"];
								break;
							case 2:
							case 3:
							case 4:
							case 5:
								$status[6] += $aD["count"];
								break;
							case 6:
								$status[6] += $aD["count"];
								break;
							case 9:
								$status[9] += $aD["count"];
								break;
						}
						//$status[$aD["completed"]] = $aD["count"];
					}

					// get second sub menu segment and place under selected author
					$sub2 = $this->panelMenu('author_sub', $status);
					
					foreach ( $sub as $sKey => $sData )
					{
						if ( $sKey == "sub" ) $menu = array_merge($menu, $sub2);
						else $menu[$sKey] = $sData;
					}
				}
				else
				{
					unset ($sub["sub"]);
					$menu = $sub;
				}

				$this->menu[$selected]["sub"] = $menu;
			}
			// Add sub menu
			elseif ( [] != $sub = $this->panelMenu($selected, $data) )
				$this->menu[$selected]["sub"] = $sub;
			
			// will not work for sub menus yet
			$this->menu[$selected]['selected'] = 1;
		}
		return $this->menu;
	}
	
	protected function panelMenu($selected=FALSE, array $data=[])
	{
		$f3 = \Base::instance();
		$sql = "SELECT M.label, M.link, M.icon, M.evaluate FROM `tbl_menu_userpanel`M WHERE M.child_of @WHERE@ ORDER BY M.order ASC";
		$menu = [];

		$menuItems = $this->exec(str_replace("@WHERE@", ($selected?"= :selected;":"IS NULL;"), $sql) , [":selected"=> $selected]);
		
		foreach ( $menuItems as $item )
		{
			if ( $item['evaluate']=="" OR isset($this->config['optional_modules'][$item['evaluate']]) )
			{
				$item["label"] = explode("%%",$item["label"]);
				
				// Get count for an item if not already available
				if( isset($item['label'][2]) AND empty($this->menuCount[$item['label'][1]]) )
					$this->getMenuCount($item['label'][1]);
				
				if ( $item["label"][0] == "" )
				{
					// Authoring sub-menu
					foreach ( $data[$item["label"][1]] as $id => $label )
					{
						$link = str_replace("%ID%", $id, $item["link"]);
						$menu[$link] = [ "label" => $label, "icon" => $item["icon"] ];
						if ( $data['ID'] == $id ) $menu['sub'] = FALSE;
					}	
				}
				else
				{
					// Item with count
					if ( isset($item["label"][1]) AND isset($item["label"][2]) )
						$label = $f3->get('LN__'.$item["label"][0],$this->menuCount[$item["label"][1]][$item["label"][2]]);
					
					// Authoring sub-menu
					elseif ( isset($item["label"][1]) AND isset($data[$item["label"][1]]) )
						$label = $f3->get('LN__'.$item["label"][0],$data[$item["label"][1]]);
					
					// Simple menu entry
					else $label = $f3->get('LN__'.$item["label"][0]);
					
					if ( isset ( $data['id']) ) $item["link"] = str_replace('%ID%', $data['id'], $item["link"]);
					$menu[$item["link"]] = [ "label" => $label, "icon" => $item["icon"] ];
				}
			}
		}
		return $menu;
	}
	
	public function getMenuCount($module="")
	{
		if ( $module == "LIB" )
		{
			// look for cached data
			if ( FALSE === \Cache::instance()->exists("menuUCPCountLib.{$_SESSION['userID']}", $counter) )
			{
				// prepare query
				$sql[]= "SET @bms  := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=1 GROUP BY F.type) AS F1);";
				$sql[]= "SET @favs := (SELECT CONCAT_WS('//', IF(SUM(counter)>0,SUM(counter),0), GROUP_CONCAT(type,',',counter SEPARATOR '||')) FROM (SELECT SUM(1) as counter, F.type FROM `tbl_user_favourites`F WHERE F.uid={$_SESSION['userID']} AND F.bookmark=0 GROUP BY F.type) AS F1);";
				if(array_key_exists("recommendations", $this->config['optional_modules']))
				{
					$sql[]= "SET @recs := (SELECT COUNT(1) FROM `tbl_recommendations` WHERE `uid` = {$_SESSION['userID']});";
				}
				else $sql[]= "SET @recs := NULL";
				$sql[]= "SET @ser  := (SELECT COUNT(1) FROM `tbl_collections` WHERE `ordered` = 1 and `uid` = {$_SESSION['userID']});";
				$sql[]= "SET @coll := (SELECT COUNT(1) FROM `tbl_collections` WHERE `ordered` = 0 and `uid` = {$_SESSION['userID']});";
				
				$sql[]= "SELECT @bms as bookmark,@favs as favourite,@recs as recommendation,@ser as series,@coll as collection;";

				$data = $this->exec($sql)[0];

				foreach( $data as $key => $count )
					list($counter[$key]['sum'], $counter[$key]['details']) = array_pad(explode("//",$count), 2, '');

				foreach ( $counter as &$count )
				{
					$cc = $this->cleanResult($count['details']);
					if(is_array($cc))
					{
						$count['details'] = [];
						foreach ( $cc as $c ) $count['details'][$c[0]] = $c[1];
					}
					else $count['details'] = "";
				}
				// cache the result for max 10 minutes or changes occur
				\Cache::instance()->set("menuUCPCountLib.{$_SESSION['userID']}", $counter, 600);
			}

			$this->menuCount['data']['library'] = $counter;
			
			$this->menuCount['LIB']  = 	[
											"BMS"	=> $counter['bookmark']['sum'],
											"FAVS"	=> $counter['favourite']['sum'],
											"RECS"	=> is_numeric($counter['recommendation']['sum']) ? $counter['recommendation']['sum'] : FALSE,
											"SER"	=> $counter['series']['sum'],
											"COLL"	=> $counter['collection']['sum'],
										];
		}
		elseif ( $module == "MSG" OR $module == "SB" )
		{
			$user = \User::instance();
			
			if ( NULL == $data = json_decode(@$user->cache_messaging,TRUE) )
			{
				$data = $this->userCacheRecount("messaging");
				$user->cache_messaging = json_encode($data);
				$user->save();
			}
			$this->menuCount['data']['messaging'] = $data;
			
			$this->menuCount['SB']  = [ "SB" => $data['shoutbox']['sum'] ];
			$this->menuCount['MSG'] = [ "UN" => $data['unread']['sum'] ];
		}
		elseif ( $module == "FB" )
		{
			$user = \User::instance();
			
			if ( NULL == $data = json_decode(@$user->cache_feedback,TRUE) )
			{
				$data = $this->userCacheRecount("feedback");
				$user->cache_feedback = json_encode($data);
				$user->save();
			}
			$this->menuCount['data']['feedback'] = $data;

			$this->menuCount['FB']	=	[
											"RW" => (int)$data['rw']['sum'],
											"RR" => (int)$data['rr']['sum'],
											"CW" => (int)$data['cw']['sum'],
											"CR" => (int)$data['cr']['sum'],
										];
		}
		elseif ( $module == "PL" )
		{
			if ( "" == $data = \Cache::instance()->get('openPolls') )
			{
				$sql = "SELECT UNIX_TIMESTAMP(`end_date`) as end FROM `tbl_poll` WHERE `start_date`IS NOT NULL AND (`end_date` IS NULL OR `end_date` > NOW()) ORDER BY `end_date` DESC;";
				$probe = $this->exec($sql);
				if ( 0 < $data = sizeof($probe) AND $probe[0]['end']>0 )
				{
					$seconds = $probe[0]['end']-time();
				}
				\Cache::instance()->set('openPolls', $data, $seconds??0);
			}
			$this->menuCount['PL'] = [ "PL" => $data ];
		}
	}
	
	public function getCounter($module)
	{
		return @$this->menuCount['data'][$module];
	}
	
	public function authorStoryAdd($data)
	{
		$newStory = new \DB\SQL\Mapper($this->db, $this->prefix."stories");
		$newStory->title		= $data['new_title'];
		$newStory->completed	= 1;
		$newStory->validated	= ($_SESSION['groups']&8) ? 31 : 11;
		$newStory->date			= date('Y-m-d H:i:s');
		$newStory->updated		= $newStory->date;
		$newStory->save();
		
		$newID = $newStory->_id;
		
		// add the story-author relation
		$newRelation = new \DB\SQL\Mapper($this->db, $this->prefix."stories_authors");
		$newRelation->sid	= $newID;
		$newRelation->aid	= $data['uid'];
		$newRelation->type	= 'M';
		$newRelation->save();
		
		// already counting as author? mainly for stats ...
		$editUser = new \DB\SQL\Mapper($this->db, $this->prefix."users");
		$editUser->load(array("uid=?",$data['uid']));
		if ( $editUser->groups < 4 )
			$editUser->groups += 4;
		$editUser->save();
		
		// add initial chapter to the story
		// must occur after the story-author relation to satisfy the check
		if ( FALSE === $this->storyChapterAdd($newID, $data['uid'], $newStory->date) )
		{

		}
		
		$this->rebuildStoryCache($newID);

		return $newID;
	}
	
	public function authorStoryLoadInfo($sid, $uid)
	{
		
		$data = $this->exec
		(
			"SELECT S.*, COUNT(DISTINCT Ch.chapid) as chapters, COUNT(DISTINCT Ch2.chapid) as validchapters
				FROM `tbl_stories`S
					INNER JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' AND A.aid = :aid )
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
					LEFT JOIN `tbl_chapters`Ch2 ON ( S.sid = Ch2.sid AND Ch2.validated >= 30)
				WHERE S.sid = :sid",
			[":sid" => $sid, ":aid" => $uid ]
		);
		if (sizeof($data)==1 AND $data[0]['sid']!="")
		{
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return FALSE;
	}
	
/*	
	public function chapterLoadList($sid)
	moved to parent
*/

	public function authorStoryStatus($sid)
	{
		$sql = "SELECT S.title, S.validated, S.completed
					FROM `tbl_stories`S 
						LEFT JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' )
						INNER JOIN `tbl_users`U ON ( A.aid = U.uid AND ( U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} ) )
					WHERE S.sid=:sid";
		
		$data = $this->exec($sql, [":sid" => $sid] );
		return ( empty($data) ) ? NULL : $data[0];
	}
	
//	public function authorStoryDelete(int $sid,int $uid)
	public function authorStoryDelete( $storyID, $authorID )
	{
		$sql = "SELECT S.completed, A2.aid
					FROM `tbl_stories`S
						INNER JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' AND A.aid = :aid )
						LEFT JOIN  `tbl_stories_authors`A2 ON ( S.sid = A2.sid AND A2.type='M')
					WHERE S.sid=:sid;";
		$data = $this->exec($sql, [":sid" => $storyID, ":aid" => $authorID] );
		
		if ( sizeof($data)==0 )
			return FALSE;
		
		// If the story previosly wasn't, it will now be moved to the deleted folder.
		if ( $data[0]['completed']>0 )
		{
			$this->exec
			(
				"UPDATE `tbl_stories` SET `completed` = '0' WHERE `sid` = :sid;",
				[ ":sid" => $storyID ]
			);
			return "moved";
		}
		else
		{
			return $this->deleteStory($storyID);
		}
	}
	
	public function authorStoryList($select,$author,$sort,$page)
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.sid,S.title, S.validated as story_validated, S.completed, UNIX_TIMESTAMP(S.updated) as updated, Ch.validated as chapter_validated
					FROM `tbl_stories`S 
						LEFT JOIN `tbl_stories_authors`A ON ( S.sid = A.sid AND A.type='M' )
							INNER JOIN `tbl_users`U ON ( A.aid = U.uid AND ( U.uid = {$_SESSION['userID']} OR U.curator = {$_SESSION['userID']} ) )
						LEFT JOIN `tbl_chapters`Ch ON ( Ch.sid = S.sid )
					WHERE A.aid=:aid AND ";

		switch ($select)
		{
			case "finished":
				$sql .= "S.completed = 9";
				break;
			case "unfinished":
				$sql .= "S.completed >= 2 AND S.completed <= 6";
				break;
			case "drafts":
				$sql .= "S.completed = 1";
				break;
			case "deleted":
				$sql .= "S.completed = 0";
				break;
			default:
				return FALSE;
		}
		
		$sql .= " GROUP BY S.sid
				 ORDER BY chapter_validated ASC, {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		
		$data = $this->exec($sql, [":aid" => $author] );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/author/uid={$author}/{$select}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}
	
	public function authorStoryHeaderSave( $storyID, array $post )
	{
		$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');
		$story->load(array('sid=?',$storyID));
		
		// Step one: save the plain data
		$story->title		= $post['story_title'];
		$story->summary		= str_replace("\n","<br />",$post['story_summary']);
		$story->storynotes	= str_replace("\n","<br />",$post['story_notes']);
		$story->ratingid	= @$post['ratingid'];	// Quick fix for when no rating is created
		$story->completed	= $post['completed'];
		
		// Toggle validation request, keeping the reason part untouched
		if ( isset($post['request_validation']) AND $story->validated < 20 )
			$story->validated 	= 	$story->validated + 10;
		elseif ( empty($post['request_validation']) AND $story->validated >= 20 AND $story->validated < 30 )
			$story->validated 	= 	$story->validated - 10;

		// Allow trusted authors to set validation
		if ( isset($post['mark_validated']) AND $_SESSION['groups']&8 AND $story->validated < 20 )
		{
			$story->validated 	= 	$story->validated + 20;
			// Log validation
			\Logging::addEntry('VS', $storyID);
		}

		$story->save();
		
		// Step two: check for changes in relation tables

		// Check tags:
		$this->storyRelationTag( $story->sid, $post['tags'] );
		// Check Characters:
		$this->storyRelationCharacter( $story->sid, $post['characters'] );
		// Check Categories:
		$this->storyRelationCategories( $story->sid, $post['category'] );
		// Check Authors:
		$this->storyRelationAuthor( $story->sid, $post['mainauthor'], $post['supauthor'] );
		
		$collection=new \DB\SQL\Mapper($this->db, $this->prefix.'collection_stories');
		$inSeries = $collection->find(array('sid=?',$storyID));
		foreach ( $inSeries as $in )
		{
			// Rebuild collection/series cache based on new data
			$this->rebuildSeriesCache($in->seriesid);
		}

		// Rebuild story cache based on new data
		$this->rebuildStoryCache($story->sid);
	}

	public function authorStoryChapterAdd($sid, $uid)
	{
		return parent::storyChapterAdd($sid, $uid);
	}

	public function authorStoryChapterSave( int $chapterID, array $post )
	{
		// plain and visual return different newline representations, this will bring things to standard.
		$chaptertext = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $post['chapter_text']);

		$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		$chapter->load(array('chapid=?',$chapterID));
		
		$chapter->title 	= $post['chapter_title'];
		$chapter->notes 	= $post['chapter_notes'];
		$chapter->endnotes 	= $post['chapter_endnotes'];
		$chapter->wordcount	= max(count(preg_split("/\p{L}[\p{L}\p{Mn}\p{Pd}'\x{2019}]{0,}/u",$chaptertext))-1, 0);
		
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

		if ( $chapter->changed("validated") OR $chapter->changed("wordcount") )
			// mark recount chapters and words for entire story
			$recount = 1;

		// save chapter information
		$chapter->save();
		// save the chapter text
		$this->chapterSave($chapterID, $chaptertext, $chapter);

		if ( isset($recount) )
		// perform recount, this has to take place after save();
			$this->recountStory($chapter->sid);
	}

	public function authorCuratorRemove($uid=NULL)
	{
		$this->exec("UPDATE `tbl_users`U set U.curator = NULL WHERE U.uid = :uid;", [ ":uid" => ($uid ?: $_SESSION['userID']) ]);
		return TRUE;
	}
	
	public function authorCuratorGet()
	{
		$data = $this->exec("SELECT C.username, C.uid
								FROM `tbl_users`U 
									INNER JOIN `tbl_users`C ON ( U.curator = C.uid AND U.uid = {$_SESSION['userID']} )
							");

		$return['self'] = ( sizeof($data) ) ? json_encode ( array ( "name"	=> $data[0]['username'], "id" => $data[0]['uid'] ) ) : "";
		
		$return['others'] = $this->exec("SELECT U.username, U.uid
											FROM `tbl_users`U
												INNER JOIN `tbl_users`C ON ( U.curator = C.uid AND C.uid = {$_SESSION['userID']} )
											");
		return $return;
	}
	
	public function authorCuratorSet($uid)
	{
		$data = $this->exec("SELECT U.uid FROM `tbl_users`U WHERE U.uid = :uid AND U.groups > 0;",[":uid" => $uid]);
		if(empty($data)) return FALSE;
		$this->exec("UPDATE `tbl_users`U set U.curator = {$data[0]['uid']} WHERE U.uid = {$_SESSION['userID']};");
		return TRUE;
	}
	
	public function feedbackHomeStats(array $data)
	{
		$chapters = $this->exec("SELECT COUNT(1) as count FROM `tbl_chapters`C INNER JOIN `tbl_stories_authors`SA ON ( C.sid = SA.sid ) WHERE SA.aid = {$_SESSION['userID']};")[0]['count'];

		$stats =
		[
			"stories"				=> $data['st']['sum'],
			"storiesReviewedQ"		=> $data['st']['sum'] > 0 ? round(($data['rq']['sum']/$data['st']['sum']*100),1) : NULL,
			"storiesReviewedPie"	=> $data['st']['sum'] > 0 ? (int)($data['rq']['sum']/$data['st']['sum']*360) :NULL,
			"reviewsPerStory"		=> ( isset($data['rr']['details']['ST']) AND $data['rq']['sum'] > 0)
										? round(($data['rr']['details']['ST']/$data['rq']['sum']),1)
										: NULL,
			"reviewsPerStoryTotal"	=> ( isset($data['rr']['details']['ST']) AND $data['st']['sum'] > 0 )
										? round(($data['rr']['details']['ST']/$data['st']['sum']),1)
										: NULL,
			"reviewsPerChapter"		=> ( isset($data['rr']['details']['ST']) AND $chapters > 0 )
										? round(($data['rr']['details']['ST']/$chapters),1)
										: NULL,
		];

		return $stats;
	}
	
	public function saveFeedback(array $post, array $params)
	{
		$sql = "UPDATE `tbl_feedback`
				SET `text` = :text
				WHERE `fid` = :fid AND `type` = :type;";
		return $this->exec( $sql,
					[
						":text"	=> $post['comment'],
						":fid"	=> $params['id'][1],
						":type"	=> $params['id'][0],
					]
		);
		// no recount required, editing a feedback does not change stats
	}
	
	public function deleteFeedback(array $post, array $params)
	{
		$sql = "DELETE FROM `tbl_feedback`
				WHERE `fid` = :fid AND `type` = :type;";
		return $this->exec( $sql,
					[
						":fid"	=> $params['id'][1],
						":type"	=> $params['id'][0],
					]
		);
		// drop user feedback stat cache
		\Model\Routines::dropUserCache("feedback");
		// purge stats cache, forcing re-cache
		\Cache::instance()->clear('stats');
	}
	
	public function friendsAdd($username)
	{
		$sql = "INSERT INTO `tbl_user_friends` (`user_id`, `friend_id`)
					SELECT '{$_SESSION['userID']}', U.uid 
				FROM `tbl_users`U WHERE U.username = :friend;
				ON DUPLICATE KEY UPDATE `active`= 1";

		$_SESSION['lastAction']['friend']
		=
		( 1 === $this->exec( $sql, [":friend" => $username ] ) )
		?
		$_SESSION['lastAction']['added'] = "success"
		:
		$_SESSION['lastAction']['added'] = "failed";
	}

	public function friendsRemove($username, $permanent = TRUE)
	{
		if ( $permanent )
		{
			$sql = "DELETE Fr
						FROM `tbl_user_friends`Fr
							INNER JOIN `tbl_users`U ON ( Fr.friend_id = U.uid )
					WHERE U.username = :friend AND Fr.user_id = {$_SESSION['userID']};";

		}

		$_SESSION['lastAction']['friend']
		=
		( 1 === $this->exec( $sql, [":friend" => $username ] ) )
		?
		$_SESSION['lastAction']['removed'] = "success"
		:
		$_SESSION['lastAction']['removed'] = "failed";
	}

	public function reviewHasChildren($reference)
	{
		$children = $this->exec("SELECT COUNT(1) FROM `tbl_feedback` WHERE `reference` = :reference AND `type` = 'C' ", [ ":reference" => $reference ] )[0]["COUNT(1)"];
		return (bool)$children;
	}
	
	public function reviewDelete($fid)
	{
		$bind = [ ":fid" => $fid ];
		if ( $storyID = $this->exec("SELECT F.reference FROM `tbl_feedback`F WHERE F.fid = :fid AND type='ST';", $bind ) )
			$storyID = $storyID[0]['reference'];
		else
		{
			// Set a session note to show after reroute
			$_SESSION['lastAction'] = [ "error" => "badID" ];
			return FALSE;
		}
		
		if ( $result = $this->exec("DELETE FROM `tbl_feedback` WHERE `fid` = :fid", $bind) )
		{
			// if something was deleted, decrement review counter and trigger recount
			$this->exec( "UPDATE `tbl_stories` SET reviews=reviews-1 WHERE sid = :sid", [ ":sid" => $storyID ] );
			\Model\Routines::dropUserCache("feedback");
			\Cache::instance()->clear('stats');
			return TRUE;
		}

		// Set a session note to show after reroute
		$_SESSION['lastAction'] = [ "error" => "unknown" ];
		return FALSE;
	}
	
	public function ajax($key, $data, $limitation=NULL)
	{
		$bind = NULL;

		if ( $key == "messaging" )
		{
			if(isset($data['namestring']))
			{
				$ajax_sql = "SELECT U.username as name, U.uid as id from `tbl_users`U WHERE U.username LIKE :username AND U.groups > 0 AND U.uid != {$_SESSION['userID']} ORDER BY U.username ASC LIMIT 10";
				$bind = [ ":username" =>  "%{$data['namestring']}%" ];
			}
		}
		elseif ( $key == "stories" )
		{
			if(isset($data['author']))
			{
				$ajax_sql = "SELECT U.username as name, U.uid as id from `tbl_users`U WHERE U.username LIKE :username AND (U.groups&5) ORDER BY U.username ASC LIMIT 10";
				$bind = [ ":username" =>  "%{$data['author']}%" ];
			}
			elseif(isset($data['storyID']))
			{
				$ajax_sql = "SELECT S.title as name,S.sid as id from `tbl_stories`S WHERE S.title LIKE :story OR S.sid = :sid ORDER BY S.title ASC LIMIT 5";
				$bind = [ ":story" =>  "%{$data['storyID']}%", ":sid" =>  $data['storyID'] ];
			}
			elseif(isset($data['category']))
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category AND C.locked = 0 ORDER BY C.category ASC LIMIT 5";
				$bind = [ ":category" =>  "%{$data['category']}%" ];
			}
			elseif(isset($data['tag']))
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 5";
				$bind = [ ":tag" =>  "%{$data['tag']}%" ];
			}
			elseif(isset($data['character']))
			{
				if ( isset($params['categories']) )
				{
					$where = ( is_array($params['categories']) )
						? "FIND_IN_SET(C.cid, :categories)"
						: "C.cid = :categories";
					$bind = ( is_array($params['categories']) )
						? implode(",",$params['categories'])
						: $params['categories'];
					$c = [];
					$this->getCategories
					(
						$c,
						$this->exec("SELECT C.cid,C.parent_cid 
										FROM `tbl_categories`C
									WHERE {$where};", [ ":categories" => $bind ] )
					);
					
					if ( sizeof($c) ) $categories = " OR rCC.catid IN (".implode(",",$c).")";
				}
				//$where[] = " Ch.catid=-1 ";
				/*if ( $limitation )
				{
					$limitation = explode(",",$limitation);
					foreach ( $limitation as $limit )
						$where[] = " FIND_IN_SET({$limit}, Ch.catid)";
				}
				$where = " AND ( ".implode(" OR", $where) .") ";
				*/
				
				//$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id from `tbl_characters`Ch WHERE Ch.charname LIKE :charname {$where} ORDER BY Ch.charname ASC LIMIT 5";
				$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id
								FROM `tbl_characters`Ch 
								LEFT JOIN `tbl_character_categories`rCC ON ( Ch.charid = rCC.charid )
							WHERE Ch.charname LIKE :charname AND ( rCC.catid IS NULL ".($categories??"")." )
							GROUP BY id
							ORDER BY Ch.charname ASC LIMIT 5";
				$bind = [ ":charname" =>  "%{$data['character']}%" ];
			}
			elseif(isset($data['chaptersort']))
			{
				if(sizeof($_SESSION['allowed_authors']))
				{
					$authors = $this->exec("SELECT rel.aid FROM `tbl_stories_authors`rel WHERE rel.sid = :sid AND rel.type='M' AND rel.aid IN (".implode(",",$_SESSION['allowed_authors']).")", [ ":sid" => $data['chaptersort']] );
					if(empty($authors)) return NULL;

					$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
					if ( $this->config['chapter_data_location'] == "local" )
						$chaptersLocal = new \DB\SQL\Mapper(\storage::instance()->localChapterDB(), "chapters");

					foreach ( $data["neworder"] as $order => $id )
					{
						if ( is_numeric($order) && is_numeric($id) && is_numeric($data["chaptersort"]) )
						{
							$chapters->load(array('chapid = ? AND sid = ?',$id, $data['chaptersort']));
							$chapters->inorder = $order+1;
							$chapters->save();
							if ( $this->config['chapter_data_location'] == "local" )
							{
								$chaptersLocal->load(array('chapid = ? AND sid = ?',$id, $data['chaptersort']));
								$chaptersLocal->inorder = $order+1;
								$chaptersLocal->save();
							}
						}
					}
				}
			}
		}
		elseif ( $key == "library" )
		{
			if(isset($data['collectionsort']))
			{
				$this->collectionAjaxItemsort($data, $_SESSION['userID']);
			}
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	public function msgInbox($offset=0)
	{
		$sql = "SELECT M.mid,UNIX_TIMESTAMP(M.date_sent) as date_sent, UNIX_TIMESTAMP(M.date_read) as date_read,M.subject,M.sender as name_id,U.username as name, FALSE as can_revoke
						FROM `tbl_messaging`M
							INNER JOIN `tbl_users`U ON ( M.sender = U.uid ) 
						WHERE M.recipient = ".$_SESSION['userID']." AND M.sent IS NULL
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}

	public function msgOutbox($offset=0)
	{
		$sql = "SELECT M.mid,UNIX_TIMESTAMP(M.date_sent) as date_sent, M2.mid as mid_read, UNIX_TIMESTAMP(M2.date_read) as date_read,M.subject,M.recipient as name_id,U.username as name, IF((M2.date_read IS NULL AND M2.mid IS NOT NULL),TRUE,FALSE) as can_revoke
						FROM `tbl_messaging`M 
							LEFT JOIN `tbl_messaging`M2 ON ( M.sent = M2.mid )
							INNER JOIN `tbl_users`U ON ( M.recipient = U.uid ) 
						WHERE M.sender = ".$_SESSION['userID']." AND M.sent IS NOT NULL
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}
	
	public function msgRead($msgID)
	{
		$sql = "SELECT M.mid,UNIX_TIMESTAMP(M.date_sent) as date_sent,UNIX_TIMESTAMP(M.date_read) as date_read,M.subject,M.message,
								M.sender as sender_id, u1.username as sender,
								M.recipient as recipient_id, u2.username as recipient 
						FROM `tbl_messaging`M 
							INNER JOIN `tbl_users`u1 ON ( M.sender = u1.uid ) 
							INNER JOIN `tbl_users`u2 ON ( M.recipient = u2.uid ) 
						WHERE M.mid = :mid AND ( M.sender = {$_SESSION['userID']} AND M.sent IS NOT NULL ) OR ( M.recipient = {$_SESSION['userID']} AND M.sent IS NULL ) ORDER BY date_sent DESC";
		$data = $this->exec($sql, [":mid" => $msgID]);
		if ( empty($data) ) return FALSE;

		/* if unread, set read date and change unread counter in SESSION */
		if($data[0]['date_read']==NULL AND $data[0]['recipient_id']==$_SESSION['userID'])
		{
			$this->exec("UPDATE `tbl_messaging`M SET M.date_read = CURRENT_TIMESTAMP WHERE M.mid = {$data[0]['mid']};");
			$data[0]['date_read'] = time();
			$_SESSION['mail'][1]--;
			// Drop user messaging cache
			\Model\Routines::dropUserCache("messaging");
		}
		return $data[0];
	}
	
	public function msgReply($msgID=NULL)
	{
		if ( is_numeric($msgID) )
		{
			$sql = "SELECT M.mid,UNIX_TIMESTAMP(M.date_sent) as date_sent,UNIX_TIMESTAMP(M.date_read) as date_read,M.subject,M.message,
									IF(M.recipient = {$_SESSION['userID']},M.sender,NULL) as recipient_id,
									IF(M.recipient = {$_SESSION['userID']},u1.username,NULL) as recipient,
									u1.username as sender
									FROM `tbl_messaging`M
									INNER JOIN `tbl_users`u1 ON ( M.sender = u1.uid ) 
									INNER JOIN `tbl_users`u2 ON ( M.recipient = u2.uid ) 
									WHERE M.mid = :mid AND ( M.sender = {$_SESSION['userID']} OR M.recipient = {$_SESSION['userID']} ) ORDER BY date_sent DESC";
			$data = $this->exec($sql, [":mid" => $msgID]);
		}
		
		if ( empty($data) )
		{
			return
			[
				'recipient' => "",
				'subject' => "",
				'message' => ""
			];
		}

		$data = $data[0];
		$data['recipient'] = ($data['recipient']==NULL) ? "" : json_encode ( array ( "name"	=> $data['recipient'], "id" => $data['recipient_id'] ) );
		$data['subject'] = \Base::instance()->get('LN__PM_ReplySubject')." ".$data['subject'];

		return $data;
	}
	
	public function msgSave($save)
	{
		// initialize a mapper
		$message = new \DB\SQL\Mapper($this->db, $this->prefix.'messaging');
		
		foreach ( $save['recipient'] as $recipient )
		{
			// basic data
			$message->sender	= $_SESSION['userID'];
			$message->subject	= $save['subject'];
			$message->message	= $save['message'];

			// set recipient
			$message->recipient	= $recipient;
			// save message
			$message->insert();
			// take note of created ID to generate the outbox copy
			$outboxID = $message->_id;
			// Drop the messaging cache of the recipient
			\Model\Routines::dropUserCache("messaging", (int)$recipient);

			$message->reset();
			// basic data - again
			$message->sender	= $_SESSION['userID'];
			$message->subject	= $save['subject'];
			$message->message	= $save['message'];
			// set recipient
			$message->recipient	= $recipient;
			// set outbox ID
			$message->sent	= $outboxID;
			// save outbox copy
			$message->insert();
		}
		
		// if we have no recorded ID, something went wrong
		if ( empty($outboxID) ) return FALSE;
		
		return TRUE;
	}
	
	public function msgDelete($message)
	{
		@list ( $messageID, $action ) = $message;
		
		// initialize a mapper
		$message = new \DB\SQL\Mapper($this->db, $this->prefix.'messaging');

		if ( $action == "r" )
		{
			// attempting to revoke a message. therefore, the message must still exist and be unread by the recipient
			$message->load(['mid = ? AND sender = ? AND sent IS NOT NULL', $messageID, $_SESSION['userID'] ]);
			// if there is no such message belonging to the current user, drop an error
			if ( $message->dry() )
				return "notfound";

			// load the message that corresponds to the sent_id
			$inbox = $message->sent;
			$message->load(['mid = ?', $inbox ]);

			// no such message, might have been deleted
			if ( $message->dry() )
				return "alreadydeleted";
			
			elseif ( $message->date_read == NULL )
			{
				// message exists and is unread, delete it on the recipient side
				$message->erase();
				//load the outbox message and delete as well
				$message->load(['mid = ? AND sender = ?', $messageID, $_SESSION['userID'] ]);
				$message->erase();
				return "revoked";
			}
			else
				return "msgread";
		}
		else
		{
			// attempting to delete a message
			$message->load(['mid = ? AND (sender = ? AND sent IS NOT NULL) OR ( recipient = ? AND sent IS NULL)', $messageID, $_SESSION['userID'], $_SESSION['userID'] ]);
			// if the message can't be accessed, it's either gone or beyond reach
			if ( $message->dry() )
				return "notfound";
			
			// message found, delete and return result
			$message->erase();
			return TRUE;
		}
		return NULL;
	}
	
	public function pollsList(int $page, array $sort):array
	{
		$limit = 10;
		$pos = $page - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS
					P.poll_id, P.question, P.options, UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date, P.open_voting,
					V.option,
					U.username
					FROM `tbl_poll`P
						LEFT JOIN `tbl_poll_votes`V ON ( P.poll_id = V.poll_id AND V.uid=:uid )
						LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
					WHERE P.start_date IS NOT NULL AND (P.end_date IS NULL OR NOW()<P.end_date OR V.option IS NOT NULL)
					ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql, [":uid" => $_SESSION['userID'] ]);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/polls",
			$limit
		);
		return $data;
	}
	
	public function shoutboxList($page,$user=FALSE)
	{
		$limit = 25;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS S.id, S.uid, U.username, S.message, UNIX_TIMESTAMP(S.date) as timestamp
					FROM `tbl_shoutbox`S 
						LEFT JOIN `tbl_users`U ON ( S.uid = U.uid )
					".
					(($user)?"WHERE S.uid = {$_SESSION['userID']} ":"")
					."ORDER BY S.date DESC
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/shoutbox",
			$limit
		);
		return $data;
	}
	
	public function shoutboxDelete($message)
	{
		$sql = "DELETE FROM `tbl_shoutbox` WHERE id = :message AND uid = {$_SESSION['userID']};";
		if ( 1 === $this->exec($sql, [ ":message" => $message ]) )
		{
			// drop user cache for messages
			\Model\Routines::dropUserCache("messaging");
			return "success";
		}
		
		$trouble = $this->exec("SELECT S.uid FROM `tbl_shoutbox`S WHERE id=:message;", [ ":message" => $message ]);

		// message doesn't exist
		if ( sizeof($trouble)==0 )
			return "notfound";
		else $trouble = $trouble[0];

		// <jedi>This is not your message</jedi>
		if ( $trouble['uid'] != $_SESSION['userID'] )
			return "noaccess";
		// might not happen, but let's cover this
		return "unknown";
	}
	
	public function libraryBookFavDelete($params)
	{
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;
		if ( in_array($params["id"][0],["AU","RC","CO","SE","ST"]) )
		{
			$mapper=new \DB\SQL\Mapper($this->db, $this->prefix.'user_favourites');
			$mapper->load(array("uid=? AND item=? AND type=? AND bookmark=?",$_SESSION['userID'], $params["id"][1], $params["id"][0], (array_key_exists("bookmark",$params))?1:0 ));
			if ( NULL !== $fid = $mapper->get('fid') )
			{
				$mapper->erase();
				return TRUE;
			}
			unset($mapper);
			return FALSE;
		}
	}
	
	public function listBookFav($page, $sort, $params)
	{
		$limit = 10;
		$pos = $page - 1;
		
		if ( in_array($params[1],["AU","RC","CO","SE","ST"]) )
		{
			$sql = $this->sqlMaker("bookfav", $params[1]) . 
					"ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		}
		else return FALSE;

		$data = $this->exec($sql,[":bookmark" => (($params[0]=="bookmark")?1:0) ] );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/library/{$params[0]}/{$params[1]}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}
	
	public function loadBookFav($params)
	{
		// ?
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;

		if ( $params['id'][0]=="AU" )
		{
			$sql = $this->sqlMaker("bookfav", "AU", FALSE) . 
				"WHERE U.uid = :id ";
		}
		elseif ( $params['id'][0]=="CO" )
		{
			$sql = $this->sqlMaker("bookfav", "CO", FALSE) . 
				"WHERE Coll.collid = :id ";
		}
		elseif ( $params['id'][0]=="SE" )
		{
			$sql = $this->sqlMaker("bookfav", "SE", FALSE) . 
				"WHERE Coll.collid = :id ";
		}
		elseif ( $params['id'][0]=="ST" )
		{
			$sql = $this->sqlMaker("bookfav", "ST", FALSE) . 
				"WHERE S.sid = :id ";
		}
		else return FALSE;

		$data = $this->exec($sql,[":bookmark" => (($params[0]=="bookmark")?1:0), ":id"=>$params['id'][1]]);

		if ( sizeof($data)==1 )
		{
			if ( isset($data[0]['authorblock']) )
				$data[0]['authorblock'] = json_decode($data[0]['authorblock'],TRUE);

			return $data[0];
		}
		return FALSE;
	}
	
	public function listReviews($page, $sort, $params)
	{
		$limit = 10;
		$pos = $page - 1;

		if ( in_array($params[2],["RC","SE","ST"]) )
		{
			$sql = $this->sqlMaker("feedback".$params[1], $params[2]) . 
					( $params[1]=="written" ? "GROUP BY F.reference_sub " : "") .
					"ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit;
		}
		else return FALSE;

		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/userCP/feedback/{$params[0]}/{$params[1]}/{$params[2]}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}

	public function loadReview($params)
	{
		// ?
		if ( empty($params['id'][0]) OR empty($params['id'][1]) ) return FALSE;

		if ( $params[1] == "written")
		{
			if ( $params['id'][0] == "ST" )
			{
				$sql = $this->sqlMaker("feedback".$params[1], "ST", FALSE) . 
					" AND F.fid = :id ";
			}
			elseif ( $params['id'][0] == "C" )
			{
				$sql = $this->sqlMaker("feedback".$params[1], "C", FALSE) . 
					" AND F.fid = :id ";
			}
		}
		else return FALSE;
		
		$data = $this->exec($sql,[ ":id"=>$params['id'][1] ]);

		if ( sizeof($data)==1 AND $data[0]['type']!="" )
			return $data[0];
		return FALSE;
	}
	
	protected function sqlMaker($module, $type, $inner = TRUE)
	{
		$join = $inner ? "INNER" : "LEFT";

		$select_bookfav =
		[
			"AU"	=>
			[
				"fields"	=> "'AU' as type, U.uid as id, U.username as name",
				"from"		=> "`tbl_users`U",
				"join"		=> "U.uid = Fav.item AND Fav.type='AU'"
			],
			"ST"	=>
			[
				"fields"	=> "'ST' as type, S.sid as id, S.title as name, S.cache_authors as authorblock",
				"from"		=> "`tbl_stories`S",
				"join"		=> "S.sid = Fav.item AND Fav.type='ST'"
			],
			"CO"	=>
			[
				"fields"	=> "'CO' as type, Coll.collid as id, Coll.title as name, Coll.cache_authors",
				"from"		=> "`tbl_collections`Coll",
				"join"		=> "Coll.collid = Fav.item AND Fav.type='CO'"
			],
			"SE"	=>
			[
				"fields"	=> "'SE' as type, Coll.collid as id, Coll.title as name, Coll.cache_authors",
				"from"		=> "`tbl_collections`Coll",
				"join"		=> "Coll.collid = Fav.item AND Fav.type='SE'"
			],
			"RC"	=>
			[
				"fields"	=> "'RC' as type, Rec.recid as id, Rec.title as name, Rec.author as cache_authors",
				"from"		=> "`tbl_recommendations`Rec",
				"join"		=> "Rec.recid = Fav.item AND Fav.type='RC'"
			]
		];
		$sql['bookfav'][$type] = "SELECT SQL_CALC_FOUND_ROWS {$select_bookfav[$type]['fields']}, 
						Fav.comments, Fav.visibility, Fav.notify, Fav.fid, Fav.bookmark
						FROM {$select_bookfav[$type]['from']} 
						{$join} JOIN `tbl_user_favourites`Fav ON
					( {$select_bookfav[$type]['join']} AND Fav.uid = {$_SESSION['userID']} AND Fav.bookmark = :bookmark ) ";		

		$sql['feedbackwritten'] =
		[
			"ST"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, SA.sid as id, Ch.inorder as chapter, GROUP_CONCAT(DISTINCT U.username SEPARATOR ', ') as name, U.uid, F.text, S.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
						INNER JOIN `tbl_stories`S ON ( F.reference = S.sid )
							{$join} JOIN `tbl_stories_authors`SA ON ( S.sid = SA.sid )
							INNER JOIN `tbl_users`U ON ( U.uid = SA.aid )
						LEFT JOIN `tbl_chapters`Ch ON ( F.reference_sub = Ch.chapid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='ST' ",
			"C"
				=>	"SELECT SQL_CALC_FOUND_ROWS
							F.type as type, F.fid, F.text, UNIX_TIMESTAMP(F.datetime) as date,
							F2.text as reviewtext, 
							SA.sid as id, 
							Ch.inorder as chapter, 
							GROUP_CONCAT(DISTINCT U.username SEPARATOR ', ') as name, U.uid, S.title
						FROM `tbl_feedback`F 
							INNER JOIN `tbl_feedback`F2 ON ( F.reference = F2.fid )
							INNER JOIN `tbl_stories`S ON ( F2.reference = S.sid ) 
								{$join} JOIN `tbl_stories_authors`SA ON ( S.sid = SA.sid ) 
									INNER JOIN `tbl_users`U ON ( U.uid = SA.aid ) 
							LEFT JOIN `tbl_chapters`Ch ON ( F2.reference_sub = Ch.chapid ) 
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='C' ",
			"CO"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Coll.collid as id, GROUP_CONCAT(DISTINCT U.username SEPARATOR ', ') as name, U.uid, F.text, Coll.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
						INNER JOIN `tbl_collections`Coll ON ( F.reference = Coll.collid )
							INNER JOIN `tbl_users`U ON ( U.uid = Coll.uid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='CO' ",
			"SE"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Coll.collid as id, GROUP_CONCAT(DISTINCT U.username SEPARATOR ', ') as name, U.uid, F.text, Coll.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
						INNER JOIN `tbl_collections`Coll ON ( F.reference = Coll.collid )
							INNER JOIN `tbl_users`U ON ( U.uid = Coll.uid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='SE' ",
			"RC"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Rec.recid as id, Rec.author as name, F.text, Rec.title, Rec.url, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid )
						WHERE F.writer_uid = {$_SESSION['userID']} AND F.type='ST' ",
		];

		$sql['feedbackreceived'] =
		[
			"ST"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, SA.sid as id, Ch.inorder as chapter, IF(F.writer_uid>0,U.username,F.writer_name) as name, U.uid, F.text, S.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
							INNER JOIN `tbl_stories_authors`SA ON ( F.reference = SA.sid AND SA.aid = {$_SESSION['userID']} )
							INNER JOIN `tbl_stories`S ON ( F.reference = S.sid )
								LEFT JOIN `tbl_chapters`Ch ON ( F.reference_sub = Ch.chapid )
						WHERE F.type='ST' ",
			"CO"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Coll.collid as id, IF(F.writer_uid>0,U.username,F.writer_name) as name, U.uid, F.text, Coll.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_collections`Coll ON ( F.reference = Coll.collid AND Coll.uid = {$_SESSION['userID']} )
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.type='CO' ",
			"SE"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Coll.collid as id, IF(F.writer_uid>0,U.username,F.writer_name) as name, U.uid, F.text, Coll.title, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_collections`Coll ON ( F.reference = Coll.collid AND Coll.uid = {$_SESSION['userID']} )
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.type='SE' ",
			"RC"
				=>	"SELECT SQL_CALC_FOUND_ROWS F.type as type, F.fid, Rec.recid as id, IF(F.writer_uid>0,U.username,F.writer_name) as name, U.uid, F.text, Rec.title, Rec.url, UNIX_TIMESTAMP(F.datetime) as date 
						FROM `tbl_feedback`F
							INNER JOIN `tbl_recommendations`Rec ON ( F.reference = Rec.recid AND Rec.uid = {$_SESSION['userID']} )
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.type='RC' ",
		];
		if ( isset($sql[$module][$type]) )
			return $sql[$module][$type];
		else return NULL;
	}
	
	public function saveBookFav($post, $params)
	{
		if ( isset($post['delete'] ) )
		{
			$this->libraryBookFavDelete($params);
			return TRUE;
		}

		if ( in_array($params['id'][0],["AU","RC","CO","SE","ST"]) )
		{
			$insert = 
			[
				"uid"			=>	$_SESSION['userID'],
				"item"			=>	$params['id'][1],
				"type"			=>	$params['id'][0],
				"bookmark"		=>	(int)($params[0]=="bookmark" XOR isset($post['change'])),	// prepare for bm-fav change
				"notify"		=>	(int)@isset($post['notify']),
				"visibility"	=>	(isset($post['visibility'])) ? $post['visibility'] : '',
				"comments"		=>	$post['comments'],
			];

			return $this->insertArray('tbl_user_favourites', $insert, TRUE);
		}
		return FALSE;
	}
	
	public function settingsCheckPW($oldPW)
	{
		$password = $this->exec("SELECT U.password FROM `tbl_users` U where ( U.uid = {$_SESSION['userID']} )")[0]['password'];
		
		if ( password_verify ( $oldPW, $password ) )
			return TRUE;
		
		if( $password==md5($oldPW) )
		{
			$this->userChangePW( $_SESSION['userID'], $oldPW );
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function settingsLoadProfile()
	{
		$sql = "SELECT F.field_type as type, F.field_name as name, F.field_title as title, F.field_options as options, I.info 
					FROM `tbl_user_fields`F 
						LEFT OUTER JOIN `tbl_user_info`I ON ( F.field_id = I.field AND I.uid={$_SESSION['userID']} )
				WHERE F.enabled=1";
		if([]==$data=$this->exec($sql)) return NULL;
		foreach($data as &$dat)
		{
			if ($dat['type']==2) $dat['options'] = json_decode($dat['options'],TRUE);
		}
		return $data;
	}
	
	public function settingsSaveProfile($data)
	{
		$fields = $this->exec("SELECT F.field_id as id, F.field_type as type, F.field_name as label
								FROM `tbl_user_fields`F
								WHERE F.enabled=1;");
		
		$mapper = new \DB\SQL\Mapper( $this->db, $this->prefix."user_info" );
		foreach($fields as $field)
		{
			$mapper->load(['uid = ? AND field = ?', $_SESSION['userID'], $field['id'] ]);
			
			// Delete empty field
			if ( $data[$field['label']]==="" )
			{
				// ... but only if it already exists
				if($mapper->uid>0)$mapper->erase();
			}
			else
			{
				// New or newly populated field
				if ( NULL == $mapper->uid )
				{
					$mapper->uid = $_SESSION['userID'];
					$mapper->field = $field['id'];
				}
				$mapper->info = $data[$field['label']];
				$mapper->save();
			}
			$mapper->reset();
		}
	}
	
	public function settingsLoadPreferences()
	{
		$data = $this->exec("SELECT `alert_feedback`, `alert_comment`, `alert_favourite`, `preferences` FROM `tbl_users`U WHERE U.uid = ".$_SESSION['userID']);
		if(empty($data)) return FALSE;
		
		$data = $data[0];
		$data['p'] = json_decode($data['preferences'],TRUE);
		return $data;
	}
	
	public function settingsSavePreferences($data)
	{
		$mapper = new \DB\SQL\Mapper( $this->db, $this->prefix."users" );
		$mapper->load(['uid = ?', $_SESSION['userID'] ]);
		
		$mapper->alert_feedback 	= (int)$data['alert_feedback'];
		$mapper->alert_comment		= (int)$data['alert_comment'];
		$mapper->alert_favourite	= (int)$data['alert_favourite'];
		
		$mapper->preferences		= json_encode
										([
											"ageconsent"	=> $data['p']['ageconsent'],
											"useEditor"		=> $data['p']['useEditor'],
											"sortNew"		=> $data['p']['sortNew'],
											"showTOC"		=> $data['p']['showTOC'],
											"language"		=> $data['p']['language'],
											"layout"		=> $data['p']['layout'],
											"hideTags"		=> @$data['p']['hideTags'],
										]);
		$mapper->save();
	}

}
