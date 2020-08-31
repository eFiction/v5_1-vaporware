<?php

namespace Model;

class Story extends Base
{
	
	public function intro()
	{
		$limit = $this->config['story_intro_items'];
		$pos = (int)\Base::instance()->get('paginate.page') - 1;
		
		$replacements =
		[
			"ORDER" => "ORDER BY ". $this->config['story_intro_order']." DESC" ,
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
		];
		$data = $this->storyData($replacements);

		$this->paginate(
			(int)$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/archive",
			$limit
		);

		return $data;
	}
	
	public function author(int $id)
	{
		$limit = $this->config['stories_per_page'];
		$author = "SELECT SQL_CALC_FOUND_ROWS U.uid, U.username as name, COUNT(rSA.sid) as counter FROM `tbl_stories_authors`rSA INNER JOIN `tbl_users`U ON ( rSA.aid = U.uid AND rSA.aid = :aid ) GROUP BY rSA.aid";
		$info = $this->exec( $author, ["aid" => $id] );
		
		$pos = (int)\Base::instance()->get('paginate.page') - 1;

		$replacements =
		[
			"ORDER" => "ORDER BY S.updated DESC" ,
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"JOIN" => "INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid AND rSA.aid = :aid )"
		];
		$data = $this->storyData($replacements, ["aid" => $id]);
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/authors/".$id,
			$limit
		);

		/*
		Attempt to use the paginate method, yet this requires SQL views to be installed.
		Must see if this is available to every user and across SQL platforms
		For now, stick to the "old" way
		
		$mapper = new \DB\SQL\Mapper($this->db, 'view_storyByAuthor' );
		// $pos=0,$size=10,$filter=NULL,array $options=NULL
		$data = $mapper->paginate(
			0,
			\Config::getPublic('story_intro_items'),
			array(
				'authorID = :aid',
				':aid' => array($id, \PDO::PARAM_INT),
			),
			array(
				'order'=>'modified DESC',
			)
		);
		
		$p = [
			'total' => $data['total'],
			'limit' => $data['limit'],
			'pages' => $data['count'],
			'pos'   => $data['pos']
		];
		
		$datax = json_decode(json_encode($data), true);
		*/
		return [$info, $data];
	}
	
	public function search ($terms, $return, $searchForm=FALSE)
	{
		if ( isset($terms['rating']) )
		{
			if ( $terms['rating'][0] == $terms['rating'][1] )
			{
				$where[] = "AND Ra.rid = :rating";
				$bind = [ ":rating" => $terms['rating'][0] ];
			}
			else
			{
				$where[] = "AND Ra.rid >= :ratingMin";
				$where[] = "AND Ra.rid <= :ratingMax";
				$bind = [ ":ratingMin" => $terms['rating'][0], ":ratingMax" => $terms['rating'][1] ];
			}
		}
		else
		{
			$where[] = "";
			$bind	 = [];
		}

		if ( isset($terms['chapters']) )
		{
			if ( $terms['chapters'] == "multi" )
				$where[] = "AND S.chapters > 1";
			elseif ( $terms['chapters'] == "single" )
				$where[] = "AND S.chapters = 1";

		}
		if ( isset($terms['story_title']) )
		{
			$where[] = "AND S.title LIKE :title";
			$bind  = array_merge( $bind, [ ":title" => "%{$terms['story_title']}%" ] );
		}

		if ( isset($terms['tagIn']) )
		{
			// find story that match all tags listed
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_tags` WHERE tid IN (".implode(",",$terms['tagIn']).") AND `character`=0 GROUP BY sid having count(lid)=".count($terms['tagIn']).") iT ON ( iT.sid = S.sid )";
		}
		if ( isset($terms['tagOut']) )
		{
			// find stories that match any tag listed
			$join[] = "LEFT JOIN (SELECT sid FROM `tbl_stories_tags` WHERE tid IN (".implode(",",$terms['tagOut']).") AND `character`=0 GROUP BY sid ) iTo ON ( iTo.sid = S.sid )";
			// and negate the results
			$where[] = "AND iTo.sid IS NULL";

		}

		if ( isset($terms['category']) )
		{
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_categories` WHERE cid IN (".implode(",",$terms['category']).") GROUP BY sid having count(lid)=".count($terms['category']).") iC ON ( iC.sid = S.sid )";
		}

		if ( isset($terms['characters']) )
		{
			// find stories that match all character ids listed
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_tags` WHERE tid IN (".implode(",",$terms['characters']).") AND `character`=1 GROUP BY sid having count(lid)=".count($terms['characters']).") iCh ON ( iCh.sid = S.sid )";
		}

		if ( isset($terms['author']) )
		{
			// sidebar stuff!
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_authors` WHERE aid IN (".implode(",",$terms['author']).") GROUP BY sid having count(lid)=".count($terms['author']).") iA ON ( iA.sid = S.sid )";
		}
		if ( !empty($terms['library']) AND $_SESSION['userID']>0 )
		{
			// bookmarks & favourites
			$saved_sql = "INNER JOIN `tbl_user_favourites`FavS ON (FavS.uid=".(int)$_SESSION['userID']." AND FavS.item=S.sid AND FavS.type='ST'";
			if ($terms['library']=="both")
				$join[] = $saved_sql.")";
			elseif ($terms['library']=="fav")
				$join[] = $saved_sql." AND FavS.bookmark=0)";
			elseif ($terms['library']=="bm")
				$join[] = $saved_sql." AND FavS.bookmark=1)";
		}
		
		$limit = $this->config['stories_per_page'];
		$pos = (int)\Base::instance()->get('paginate.page') - 1;
		
		$replacements =
		[
			"ORDER"	=> ( $_SESSION['userID']>0 AND empty($_SESSION['preferences']['sortNew']) ) ? "ORDER BY S.title ASC" : "ORDER BY updated DESC",
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"JOIN"	=> isset($join) ? implode("\n",$join) : "",
			"WHERE"	=> implode(" ", $where),
			"COMPLETED" => isset($terms['exclude_wip']) ? ">" : ">=",
		];

		$data = $this->storyData($replacements, $bind);

		$link = ( $searchForm ) ? "search" : "browse";
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/{$link}/".$return,
			$limit
		);
		
		return $data;
	}
	
	public function ratings()
	{
		return $this->exec("SELECT rid, rating from `tbl_ratings`");
	}
	
	public function updates(int $year, $month=0, $day=0)
	{
		if ( $year > 0 )
		{
			$filter_date[] = "YEAR(date) = ".$year;
			$filter_updated[] = "YEAR(updated) = ".$year;
		}
		if ( $month > 0 )
		{
			$filter_date[] = "MONTH(date) = ".$month;
			$filter_updated[] = "MONTH(updated) = ".$month;
		}
		if ( $day > 0 )
		{
			$filter_date[] = "DAY(date) = ".$day;
			$filter_updated[] = "DAY(updated) = ".$day;
		}
		
		if ( sizeof($filter_date)==0 ) return FALSE;

		$limit = $this->config['story_intro_items'];
		$pos = (int)\Base::instance()->get('paginate.page') - 1;
		
		$replacements =
		[
			"ORDER" => "ORDER BY date,updated DESC" ,
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"WHERE" => "AND ( (".implode(" AND ",$filter_date).") OR (".implode(" AND ",$filter_updated).") )"
		];
		$data = $this->storyData($replacements);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/updates/date={$year}-{$month}-{$day}",
			$limit
		);

		return $data;
	}
	
	public function collectionsList(bool $ordered = FALSE)
	{
		// common SQL creation for member profile and story view
		list ( $sql, $limit ) = $this->collectionsListBase([], $ordered);
		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			$ordered ? "/story/series" : "/story/collections",
			$limit
		);

		if ( sizeof($data)>0 )
		{
			foreach ( $data as &$dat)
			{
				$favs = $this->cleanResult($dat['is_favourite']);
				$dat['is_favourite'] = [];
				if(!empty($favs))
				foreach ( $favs as $value )
					if ( isset($value[1]) ) $dat['is_favourite'][$value[0]] = $value[1];
			}
		}
		
		return $data;
	}

	public function collectionsLoad(int $collID, bool $ordered = FALSE)
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.collid, C.parent_collection, C.title, C.summary, C.open, C.max_rating,
					COUNT(DISTINCT rCS.sid) as stories, C.chapters, C.wordcount, C.reviews,
					C.cache_authors, C.cache_tags, C.cache_characters, C.cache_categories,
					C2.title as parent_title
				FROM `tbl_collections`C 
					LEFT JOIN `tbl_collections`C2 ON ( C.parent_collection = C2.collid )
					LEFT JOIN `tbl_collection_stories`rCS ON ( C.collid = rCS.collid AND rCS.confirmed = 1 )
				WHERE C.collid=:collid AND C.ordered=".(int)$ordered." AND C.chapters>0 AND C.status IN ('P','A')
				GROUP BY C.collid;";
		$data = $this->exec($sql, [":collid" => $collID ]);
		if ( sizeof($data)==1 )
		{
			return [ "data" => $data[0], "stories" => $this->collectionStories($collID, $ordered) ];
		}
		return NULL;
	}
	
	private function collectionStories(int $collID, bool $ordered)
	{
		$bind = [ ":collid" => $collID ];
		$join[] = "INNER JOIN `tbl_collection_stories`rCollS ON ( rCollS.sid = S.sid AND rCollS.collid = :collid )";
		
		$limit = $this->config['stories_per_page'];
		$pos = (int)\Base::instance()->get('paginate.page') - 1;

		$replacements =
		[
			"EXTRA"	=> ( $ordered ) ? ", rCollS.inorder" : "",
			"ORDER"	=> ( $ordered ) ? "ORDER BY rCollS.inorder ASC" : "ORDER BY updated DESC",
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"JOIN"	=> isset($join) ? implode("\n",$join) : "",
			"COMPLETED" => isset($terms['exclude_wip']) ? ">" : ">=",
		];

		$data = $this->storyData($replacements, $bind);
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/".($ordered?'series':'collections')."/id={$collID}",
			$limit
		);
		
		return $data;
	}
	
	// wrapper for collectionsList
	public function seriesList()
	{
		return $this->collectionsList(TRUE);
	}

	// wrapper for collectionsLoad
	public function seriesLoad(int $collID)
	{
		return $this->collectionsLoad($collID, TRUE);
	}

	public function contestsList() : array
	{
		$limit = 5;
		$pos = (int)$this->f3->get('paginate.page') - 1;
		
		/*
			IF
			(
				C.votable='date',
				then IF
				(
					C.date_close<NOW() OR C.date_close IS NULL,
					then IF
					(
						C.vote_close>NOW() OR C.vote_close IS NULL,
						then 'active',
						else 'closed'
					),
					else 'preparing'),
				else C.votable
			) as votable,
		*/

		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.conid, C.title, C.summary,
                    IF(C.active='date',IF(C.date_open<NOW(),IF(C.date_close>NOW() OR C.date_close IS NULL,'active','closed'),'preparing'),C.active) as active,
                    IF(C.votable='date',IF(C.date_close<NOW() OR C.date_close IS NULL,IF(C.vote_close>NOW() OR C.vote_close IS NULL,'active','closed'),'preparing'),C.votable) as votable,
					UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
					C.cache_tags, C.cache_characters, C.cache_categories, C.cache_stories,
					U.username, COUNT(R.lid) as count
				FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid = U.uid )
					LEFT JOIN `tbl_contest_relations`R ON ( C.conid = R.conid AND R.type='ST' )
				@WHERE@
				GROUP BY C.conid
				ORDER BY active ASC, votable ASC, C.conid DESC
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
		if ( 1 ) $sql = str_replace("@WHERE@", 
									(
										($_SESSION['groups']&64)?
										"":
										"WHERE concealed = 0 AND ((C.active='date' AND C.date_open<=NOW()) OR C.active>2)"
									),
									$sql);

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/contests",
			$limit
		);
		/*
		foreach ( $data as &$dat )
		{
			
		}*/
		return $data;
	}

	public function contestLoad(int $conid)
	{
		$sql = "SELECT C.conid as id, C.title, C.summary, C.description, 
                    IF(C.active='date',IF(C.date_open<NOW(),IF(C.date_close>NOW() OR C.date_close IS NULL,'active','closed'),'prepare'),C.active) as active,
                    IF(C.votable='date',IF(C.date_close<NOW() OR C.date_close IS NULL,IF(C.vote_close>NOW() OR C.vote_close IS NULL,'active','closed'),'prepare'),C.votable) as votable,
					UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
					C.cache_tags, C.cache_characters, C.cache_categories,
					U.uid, U.username,
					COUNT(DISTINCT rC.relid) as entries
					FROM `tbl_contests`C
						LEFT JOIN `tbl_users`U ON ( C.uid=U.uid )
						LEFT JOIN `tbl_contest_relations`rC ON ( C.conid = rC.conid and rC.type = 'ST' )
					WHERE @WHERE@ C.conid = :conid";
		if ( 1 ) $sql = str_replace("@WHERE@", 
									(
										($_SESSION['groups']&64)?
										"":
										"concealed = 0 AND ((C.active='date' AND C.date_open<=NOW()) OR C.active>2) AND"
									),
									$sql);

		$data = $this->exec($sql, [":conid" => $conid ]);

		if (sizeof($data)==1 AND !empty($data[0]['id'])) 
		{
			$data[0]['date_open'] = ($data[0]['date_open']>0)
				? $this->timeToUser("@".$data[0]['date_open'],  $this->config['date_format'])
				: "";
			$data[0]['date_close'] = ($data[0]['date_close']>0)
				? $this->timeToUser("@".$data[0]['date_close'], $this->config['date_format'])
				: "";
			$data[0]['vote_close'] = ($data[0]['vote_close']>0)
				? $this->timeToUser("@".$data[0]['vote_close'], $this->config['date_format'])
				: "";

			return $data[0];
		}
		return NULL;
	}
	
	public function contestEntries(int $conid): array
	{
		$limit = 5;
		$pos = (int)$this->f3->get('paginate.page') - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				E.* FROM (
					SELECT 
						IF(S.sid IS NULL,Coll.collid,S.sid) as id,
						IF(S.title IS NULL,Coll.title,S.title) as title, 
						IF(S.summary IS NULL,Coll.summary,S.summary) as summary, 
						IF(S.cache_authors IS NULL,Coll.cache_authors,S.cache_authors) as cache_authors, 
						IF(S.cache_categories IS NULL,Coll.cache_categories,S.cache_categories) as cache_categories, 
						IF(S.cache_tags IS NULL,Coll.cache_tags,S.cache_tags) as cache_tags, 
						IF(S.validated IS NULL,'39',S.validated) as validated, 
						IF(S.completed IS NULL,'9',S.completed) as completed, 
						RelC.type, RelC.lid, Coll.ordered
						FROM `tbl_contest_relations`RelC
							LEFT JOIN `tbl_stories`S ON ( S.sid = RelC.relid AND RelC.type='ST' )
							LEFT JOIN `tbl_collections`Coll ON ( Coll.collid = RelC.relid AND RelC.type='CO' )
						WHERE RelC.conid = :conid AND ( RelC.type='ST' OR RelC.type='CO' )
					) as E
				GROUP BY id
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
				
		$data = $this->exec($sql, [":conid" => $conid ]);
		
		if ( 0==sizeof($data) ) return[];
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/contests/id={$conid}/entries",
			$limit
		);

		return($data);
	}

	public function searchPrepopulate ($item, $id)
	{
		if ( $item == "author")
			$sql = "SELECT `username` as name, `uid` as id FROM `tbl_users` WHERE `groups` & 4 AND `uid` IN ({$id})";

		elseif ( $item == "category")
			$sql = "SELECT `category` as name, `cid` as id FROM `tbl_categories` WHERE `cid` IN ({$id})";

		elseif ( $item == "tag")
			$sql = "SELECT `label` as name, `tid` as id FROM `tbl_tags` WHERE `tid` IN ({$id})";

		elseif ( $item == "characters")
			$sql = "SELECT `charname` as name, `charid` as id FROM `tbl_characters` WHERE `charid` IN ({$id})";

		if (empty($sql)) return "[]";
		return json_encode( $this->exec($sql) );
	}
	
	public function searchAjax ($item, $bind = NULL)
	{
		if( $item=="tag" )
		{
			$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 10";
			$bind = [ "tag" =>  "%{$bind}%" ];
		}
		elseif( $item=="author" )
		{
			$ajax_sql = "SELECT U.username as name, U.uid as id from `tbl_users`U WHERE U.username LIKE :username AND ( U.groups & 4 ) ORDER BY U.username ASC LIMIT 5";
			$bind = [ "username" =>  "%{$bind}%" ];
		}
		elseif( $item=="category" )
		{
			$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 10";
			$bind = [ "category" =>  "%{$bind}%" ];
		}
		elseif( $item=="characters" )
		{
			$ajax_sql = "SELECT charname as name, charid as id from `tbl_characters`Ch WHERE Ch.charname LIKE :characters ORDER BY Ch.charname ASC LIMIT 10";
			$bind = [ "characters" =>  "%{$bind}%" ];
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	public function getStory($story, $chapter=0)
	{
		$replacements =
		[
			"WHERE" => "AND S.sid = :sid",
			"EXTRA"	=> ", C.chapid",
			"JOIN"	=> "LEFT JOIN `tbl_chapters`C ON ( S.sid = C.sid and C.inorder = :chapter )"
		];

		$data = $this->storyData($replacements, [ ":sid" => $story, ":chapter" => $chapter ]);
		
		if ( !@in_array($story, @$_SESSION['viewed']) )
		{
			$this->exec("UPDATE `tbl_stories` SET count = count + 1 WHERE sid = :sid", [ ":sid" => $story ] );
			$_SESSION['viewed'][] = $story;
		}

		if ( sizeof($data)==1 )
			return $data[0];
		else return FALSE;
	}
		
	public function categories( $cid )
	{
		// $cid is safe
		// Get categories below selected category
		$sql = "SELECT C.cid, C.category, C.description, C.image, C.stats, C.parent_cid
						FROM `tbl_categories`C 
						WHERE C.parent_cid ='{$cid}' 
					GROUP BY C.cid 
					ORDER BY C.inorder ASC";
		$data['elements'] = $this->exec($sql);
		if ( sizeof($data) )
		{
			foreach ( $data['elements'] as &$entry ) $entry['stats'] = json_decode($entry['stats'],TRUE);
		}
		else return FALSE;
		
		// If not in root, get parent category information
		if ( $cid > 0 )
		{
			$sql = "SELECT C.cid, C.category, C.description, C.image, C.stats, C.parent_cid,
						COUNT(DISTINCT rSC.lid) as count
							FROM `tbl_categories`C 
								LEFT JOIN `tbl_stories_categories`rSC ON ( C.cid = rSC.cid )
							WHERE C.cid ='{$cid}' 
							GROUP BY C.cid";
			$parent = $this->exec($sql);

			if ( sizeof($parent)==1 )
			{
				$data['parent'] = $parent[0];
				$data['parent']['stats'] = json_decode($data['parent']['stats'],TRUE);
			}
			else return FALSE;
			
			// subtract child stories from parent count
			$data['parent']['counter'] = $data['parent']['stats']['count'];
			foreach ( $data['elements'] as $E )
				$data['parent']['counter'] -= $E['stats']['count'];
		}
		
		foreach ( $data['elements'] as &$entry )
		{
			if ( is_array($entry['stats']['sub']) )
			foreach ( $entry['stats']['sub'] as $sub )
			{
				$entry['stats']['count'] -= $sub['count'];
			}
		}
		
		return $data;
	}

	public function loadReviews($storyID,$selectedReview,$chapter=NULL)
	{
		$limit=5;
		$tree = [];

		/*
			get the amount of reviews defined by $limit (offset to come) and count the total amount for pagination setup
		*/
		$sql = "SELECT 
					SQL_CALC_FOUND_ROWS F.fid as review_id, 
					Ch.inorder,
					F.text as review_text, 
					F.reference as review_story, 
					F.reference_sub as review_chapter, 
					IF(F.writer_uid>0,U.username,F.writer_name) as review_writer_name, 
					F.writer_uid as review_writer_uid, 
					UNIX_TIMESTAMP(F.datetime) as date_review
				FROM `tbl_feedback`F 
					LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
					LEFT JOIN `tbl_chapters`Ch ON ( Ch.chapid = F.reference_sub )
				WHERE F.reference = :storyid @CHAPTER@ AND F.type='ST' 
				ORDER BY F.datetime DESC
				LIMIT 0,".$limit."";

		if ( $chapter )
			$reviews = $this->exec( str_replace("@CHAPTER@", "AND F.reference_sub = :chapter", $sql), [':storyid' => $storyID, ':chapter' => $chapter] );

		else $reviews = $this->exec( str_replace("@CHAPTER@", "", $sql), [':storyid' => $storyID] );

		/*
			initiate data array with root elements
		*/
		foreach ( $reviews as $item )
		{
			// Fix for deleted users
			if ( $item['review_writer_uid'] > 0 AND $item['review_writer_name'] === NULL )
			{
				$item['review_writer_uid'] = -1;
				$item['review_writer_name'] = \Base::instance()->get("LN__DeletedUser");
			}

			// remember current review ID
			$current_id = $item['review_id'];
			$data['r'.$current_id] =
			[
				"level"		=>	1,
				"story"		=>	$item['review_story'],
				"date"		=>	date( $this->config['date_format'], $item['date_review']),
				"date_long"	=>	date( $this->config['datetime_format'], $item['date_review']),
				"time"		=>	date( $this->config['time_format'], $item['date_review']),
				"chapter"	=>	$item['review_chapter'],
				"chapternr"	=>	$item['inorder'],
				"id"		=>	$item['review_id'],
				"text"		=>	$item['review_text'],
				"name"		=>	$item['review_writer_name'],
				"uid"		=>	$item['review_writer_uid'],
				"timestamp"	=>	$item['date_review'],
				"elements"	=> 0,
			];
			$chapters[$item['review_id']] = 
				[ 
					"chapter"	=>	$item['review_chapter'],
					"chapternr"	=>	$item['inorder']
				];
			// When there is a review from the user, we don't want a new on - there's always the edit button
			if ( $_SESSION['userID']>0 AND $_SESSION['userID']==$item['review_writer_uid'] )
				\Base::instance()->set('nocomment',1);
			$parents[] = $item['review_id'];
			
			$tree += [ 'r'.$current_id => null ];
		}
		
		// if $parents is empty, there are no reviews, and we are done here
		if ( empty($parents) ) return NULL;

		/*
			for the above comments ( id in array $parent), get the associated comments
		*/
		$sql = "SELECT 
					F2.fid as comment_id, 
					F2.text as comment_text, 
					F2.reference_sub as parent_item, 
					IF(F2.writer_uid>0,U2.username,F2.writer_name) as comment_writer_name, 
					F2.writer_uid as comment_writer_uid,
					F2.reference as review_id, 
					UNIX_TIMESTAMP(F2.datetime) as date_comment
				FROM `tbl_feedback`F2
					LEFT JOIN `tbl_users`U2 ON ( F2.writer_uid = U2.uid )
				WHERE F2.type='C' AND F2.reference IN (".implode(",",$parents).")
				ORDER BY F2.datetime ASC";
		$comments = $this->exec($sql);

		/*
			insert the comments to the base structure created above
		*/
		foreach ( $comments as $item )
		{
			if ( $item['review_id']==(int)$selectedReview OR $selectedReview=="all" )
			{
				// showing this branch, so make sure we don't offer a link to show it ... again
				$data['r'.$item['review_id']]['elements'] = NULL;
				// Check parent level and remember this node's level
				if ( isset($depth[$item['parent_item']]) )
					$depth[$item['comment_id']] = $depth[$item['parent_item']] + 1;
				else
					$depth[$item['comment_id']] = 2;

				// tell the tree where this item originates from
				if ( $item['parent_item'] == "" )
					$tree += [ 'c'.$item['comment_id'] => 'r'.$item['review_id'] ];
				else
					$tree += [ 'c'.$item['comment_id'] => 'c'.$item['parent_item'] ];
				
				$data['c'.$item['comment_id']] = 
				[
					"level"		=>	min ($depth[$item['comment_id']], 4),
					"story"		=>	(int)$storyID,
					"date"		=>	date( \Config::getPublic('date_format'), $item['date_comment']),
					"date_long"	=>	date( \Config::getPublic('datetime_format'), $item['date_comment']),
					"time"		=>	date( \Config::getPublic('time_preset'), $item['date_comment']),
					"chapter"	=>	$chapters[$item['review_id']]['chapter'],
					"chapternr"	=>	$chapters[$item['review_id']]['chapternr'],
					"id"		=>	$item['comment_id'],
					"parent"	=>	$item['parent_item'],
					"text"		=>	$item['comment_text'],
					"name"		=>	$item['comment_writer_name'],
					"uid"		=>	$item['comment_writer_uid'],
					"timestamp"	=>	$item['date_comment'],
				];
			}
			else

			$data['r'.$item['review_id']]['elements']++;
			// When there is a comment from the user, we don't want a new on - there's always the edit button
			if ( $_SESSION['userID']>0 AND $_SESSION['userID']==$item['comment_writer_uid'] )
			{
				if ( $item['parent_item']=="" )
					$data['r'.$item['review_id']]['nocomment'] = 1;
				else
					$data['c'.$item['parent_item']]['nocomment'] = 1;
			}		
		}
		
		// build an index-tree of all elements on their proper location
		$indexTree = $this->parseTree($tree);

		// flatten the tree and use it to order the data
		// based on http://stackoverflow.com/questions/21516892/flatten-a-multdimensional-tree-array-in-php/21517018#21517018
		array_walk_recursive($indexTree, function($item, $key) use (&$indexFlat, &$i, $data)
		{
			if ( $item != "" ) $indexFlat[(int) $i++] = $data[$item]; 
		});
		
		return $indexFlat;
	}
	
	public function loadReviewsArray(int $sid, $chapid) : array
	{
		$r = [];
		$limit=20;
		
		$reviews=new \DB\SQL\Mapper($this->db,'v_loadStoryReviews');
		$items = is_numeric($chapid)?
			$items=$reviews->paginate(0,$limit,array('review_story=? AND review_chapter=?',$sid, $chapid)):
			$items=$reviews->paginate(0,$limit,array('review_story=?',$sid));

		foreach($items['subset'] as $review)
		{
			$parents[] = $review['review_id'];
			// jQuery.comments requires comments to have a date at least of the parent
			// eFiction 3 did not record reply dates, so we'll have to fake this
			$datesave[$review['review_id']] = $review['date_review'];
			$r[] =
			[
				"id" 		=> $review['review_id'],
				"parent" 	=> NULL,
				"created"	=> date( 'Y-m-d', $review['date_review']),
				"content"	=> preg_replace("/<br\\s*\\/>\\s*/i", "\n", $review['review_text']),
				"creator"	=> $review['review_writer_uid'],
				"fullname"	=> $review['review_writer_name'],
			];
			
		}
		unset($reviews,$items);

		if(isset($parents))
		{
			$comments=new \DB\SQL\Mapper($this->db,'v_loadStoryReviewComments');
			$items=$comments->paginate(0,$limit,array('review_id IN ('.implode(",",$parents).')'));
			foreach($items['subset'] as $comment)
			{
				$r[] =
				[
					"id" 		=> $comment['comment_id'],
					"parent" 	=> $comment['review_id'],
					"created"	=> date( 'Y-m-d', 
										$comment['date_comment']!=NULL?:$datesave[$comment['review_id']]
										),
					"content"	=> preg_replace("/<br\\s*\\/>\\s*/i", "\n", $comment['comment_text']),
					"creator"	=> $comment['comment_writer_uid'],
					"fullname"	=> $comment['comment_writer_name'],
				];
			}
		}

		// WHERE F2.type='C' AND F2.reference IN (".implode(",",$parents).")
		return $r;
	}

	public function saveReview($structure, $data)
	{
		/*
			new review
		*/
		if ( $structure['childof']==0 )
		{
			// select story
			$sql = "SELECT chapid FROM `tbl_chapters`Ch WHERE Ch.sid = :sid";
			$bind[":sid"] = $structure['story'];
			// ... and chapter if provided
			if ($structure['chapter']>0)
			{
				$sql .= " AND Ch.inorder = :inorder";
				$bind[":inorder"] = $structure['chapter'];
			}
			$check = $this->exec($sql, $bind);

			// drop out if selection doesn't exist
			if ( empty($check) )
				return FALSE;

			$bind =
			[
				":reference"		=> $structure['story'],
				":reference_sub"	=> ( $structure['chapter']>0 ) ? $check[0]['chapid'] : NULL,
				":guest_name"		=> ( $_SESSION['userID']!=0 ) ? NULL : $data['name'],
				":uid"				=> (int)$_SESSION['userID'],
				":text"				=> $data['text'],
				":type"				=> "ST",
			];
		}
		else
		{
			// write a comment
			if ( $parent = $this->exec("SELECT reference as parent_id FROM `tbl_feedback` WHERE fid = :fid AND type='C';", [":fid"=>$structure['childof']]) )
			{
				$reference = $parent[0]['parent_id'];
				$reference_sub = $structure['childof'];
			}
			else
			{
				$reference = $structure['childof'];
				$reference_sub = NULL;
			}
			$structure['chapter'] = "r{$reference}";

			$bind =
			[
				":reference"		=> $reference,
				":reference_sub"	=> $reference_sub,
				":guest_name"		=> ( $_SESSION['userID']!=0 ) ? NULL : $data['name'],
				":uid"				=> (int)$_SESSION['userID'],
				":text"				=> $data['text'],
				":type"				=> "C",
			];
		}

		$sql = "INSERT INTO `tbl_feedback`
					(`reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `datetime`,        `type`) VALUES 
					(:reference,  :reference_sub,  :guest_name,   :uid,         :text,  CURRENT_TIMESTAMP, :type)";

		if ( 1== $this->exec($sql, $bind) )
		{
			// cache insert_id, will get destroyed by routines
			$insert_id = (int)$this->db->lastInsertId();

			$relocate = "/mvc/story/reviews/{$structure['story']},{$structure['chapter']},";
			if ( $structure['childof']==0 )
				$relocate .= $insert_id;
			else
				$relocate .= $reference;
			$relocate .= "-{$insert_id}";

			// run maintenance routines
			\Model\Routines::dropUserCache("feedback");
			\Cache::instance()->clear('stats');
			
			return [ $relocate, $bind[':type'], ($bind[':type']=="ST")?$storyID:$structure['childof'] ] ;
		}
		else
		{
			/*
			echo "Fehler";
			print_r($bind);
			echo $sql;
			echo $this->log();
			exit;
			*/
			return FALSE;
		}
		
		//return FALSE;
	}

	public function getChapterByReview($reviewID)
	{
		$data = $this->exec( "SELECT Ch.inorder 
					FROM `tbl_feedback`F 
						INNER JOIN `tbl_chapters`Ch ON ( F.reference_sub = Ch.chapid )
					WHERE F.fid = :review;", [ ":review" => $reviewID ] );
		if ( sizeof ( $data ) )
			return $data[0]['inorder'];
		return FALSE;
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
							WHERE Ch.sid = :story AND Ch.validated >= 30 
							GROUP BY Ch.inorder 
							ORDER BY Ch.inorder ASC", 
							[ ":story" => $story, ":user" => \Base::instance()->get('SESSION.userID') ]
						);
	}

	public function getMiniTOC($story)
	{
		return $this->exec( "SELECT Ch.title, Ch.inorder as chapter, Ch.chapid
								FROM `tbl_chapters`Ch 
								WHERE Ch.sid = :story AND Ch.validated >= 30 
								ORDER BY Ch.inorder ASC;",
							[ ":story" => $story ]
						);
	}

	public function blockStats()
	{
		if ( "" == $stats = \Cache::instance()->get('statsCache') )
		{
			$statSQL = [
				"SET @users = (SELECT COUNT(*) FROM `tbl_users`U WHERE U.groups > 0);",
				// more precise stats, only counting authors with actual stories
				"SET @authors = ( SELECT COUNT(DISTINCT rSA.aid) FROM `tbl_stories_authors`rSA INNER JOIN `tbl_stories`S ON ( S.sid = rSA.sid AND S.validated >= 20 AND S.completed >= 6 ) );",
				//"SET @authors = (SELECT COUNT(*) FROM `tbl_users`U WHERE ( U.groups & 4 ) );",
				"SET @reviews = (SELECT COUNT(*) FROM `tbl_feedback`F WHERE F.type='ST');",
				"SET @stories = (SELECT COUNT(DISTINCT sid) FROM `tbl_stories`S WHERE S.validated >= 30 );",
				//"SET @chapters = (SELECT COUNT(DISTINCT chapid) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated >= 30 AND C.validated >= 20 ) );",
				// Count chapters from validated stories that are at least w.i.p.
				"SET @chapters = (SELECT SUM(S.chapters) FROM `tbl_stories`S WHERE S.validated >= 30 AND S.completed >= 6 );",
				//"SET @words = (SELECT SUM(C.wordcount) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated >= 30 AND C.validated >= 30 ) );",
				// Count words from validated stories that are at least w.i.p.
				"SET @words = (SELECT SUM(S.wordcount) FROM `tbl_stories`S WHERE S.validated >= 30 AND S.completed >= 6 );",
				"SET @newmember = (SELECT CONCAT_WS(',', U.uid, U.username) FROM `tbl_users`U WHERE U.groups>0 ORDER BY U.registered DESC LIMIT 1);",
				"SELECT @users as users, @authors as authors, @reviews as reviews, @stories as stories, @chapters as chapters, @words as words, @newmember as newmember;",
			];
			$statsData = $this->exec($statSQL)[0];
			
			foreach($statsData as $statKey => $statValue)
			{
				$stats[$statKey] = ($statKey=="newmember") ? explode(",",$statValue) : $statValue;
			}
			// Cache stats for 1 hour
			\Cache::instance()->set('statsCache', $stats, 3600);
		}

		return $stats;
	}
	
	public function blockNewStories($items)
	{
		return $this->exec('SELECT S.sid, S.title, S.summary, 
											S.cache_authors
										FROM `tbl_stories`S
										WHERE (datediff(S.updated,S.date) = 0) AND S.completed >= 6 AND S.validated >= 30
										ORDER BY S.updated DESC
										LIMIT 0,'.(int)$items);
	}
	
	public function blockRandomStory($items=1)
	{
		if ( "" == $data = \Cache::instance()->get('randomStoryCache') )
		{
			$data = $this->exec('SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories, S.cache_tags
				FROM `tbl_stories`S WHERE S.validated >= 30 AND S.completed >= 6
			ORDER BY RAND() 
			LIMIT '.(int)$items);
			// Cache random story for 1 minute
			// this cache is not deleted anywhere and has to expire
			\Cache::instance()->set('randomStoryCache', $data, 60);
		}
		return $data;
	}
	
	public function blockTagcloud($items)
	{
		if ( "" == $data = \Cache::instance()->get('blockTagcloudCache') )
		{
			$data = $this->exec('SELECT T.tid, T.label, T.count
					FROM `tbl_tags`T
				WHERE T.tgid = 1 AND T.count > 0
				ORDER BY T.count DESC
				LIMIT 0, '.(int)$items);
			// cache the tagcloud for 15 minutes
			// this cache is not deleted anywhere and has to expire
			\Cache::instance()->set('blockTagcloudCache', $data, 900);
		}
		return $data;
	}
	
	public function blockRecommendedStory(int $items=1, $order=FALSE)
	{
		$limit = ($items) ? "LIMIT 0,".$items : "";
		$sort = ( $order == "random" ) ? 'RAND()' : 'Rec.date DESC';
		
		return $this->exec("SELECT Rec.recid, Rec.title, Rec.summary, Rec.author, Rec.url, Rec.cache_categories, Rec.cache_rating,
					U.uid, U.username
						FROM `tbl_recommendations`Rec
							LEFT JOIN `tbl_users`U ON ( Rec.uid = U.uid)
						WHERE Rec.validated > 0
						ORDER BY {$sort} {$limit}");
	}

	public function blockFeaturedStory(int $items=1, $order=FALSE): array
	{
		$limit = ($items) ? "LIMIT 0,".$items : "";
		$sort = ( $order ) ? 'RAND()' : 'S.updated DESC';

		return $this->exec("SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories
				FROM `tbl_featured`F
					INNER JOIN `tbl_stories`S ON ( F.id = S.sid AND S.validated >= 30 )
				WHERE F.type='ST' AND (F.status=1 OR ( F.status IS NULL AND F.start < NOW() AND F.end > NOW() ))
			ORDER BY {$sort} {$limit}");
		// 1 = aktuell, 2, ehemals
	}
	
	public function blockContests( $items=FALSE, $order=FALSE ): array
	{
		if ( "" == $data = \Cache::instance()->get('blockContestsCache') )
		{
			// no other sort for now
			$sort = ( $order == "random" ) ? 'RAND()' : 'RAND()';
			// because the results are being cached, there can be no limit on this query
			
			$open_sql = 
				"SELECT SQL_CALC_FOUND_ROWS
						C.conid, C.title, C.summary,
						C.active, C.votable,
						UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
						-- C.cache_tags, C.cache_characters, 
						U.username, COUNT(R.lid) as count
					FROM `tbl_contests`C
						LEFT JOIN `tbl_users`U ON ( C.uid = U.uid )
						LEFT JOIN `tbl_contest_relations`R ON ( C.conid = R.conid AND ( R.type='ST' OR R.type='CO' ) )
					WHERE C.concealed = 0 AND ( C.active='active' OR ( C.active='date' AND C.date_open<NOW() AND C.date_close>NOW() ) )
					GROUP BY C.conid
					ORDER BY {$sort}";
			
			$votable_sql = 
				"SELECT SQL_CALC_FOUND_ROWS
						C.conid, C.title, C.summary,
						C.active, C.votable,
						UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_close) as vote_close, 
						-- C.cache_tags, C.cache_characters, 
						U.username, COUNT(R.lid) as count
					FROM `tbl_contests`C
						LEFT JOIN `tbl_users`U ON ( C.uid = U.uid )
						LEFT JOIN `tbl_contest_relations`R ON ( C.conid = R.conid AND ( R.type='ST' OR R.type='CO' ) )
					WHERE C.concealed = 0 AND C.votable='active' OR ( C.votable='date' AND C.date_close<NOW() AND C.vote_close>NOW()  )
					GROUP BY C.conid
					ORDER BY {$sort}";
			
			$data = [ "open" => $this->exec($open_sql), "votable" => $this->exec($votable_sql)];
			\Cache::instance()->set('blockContestsCache', $data);
		}
		elseif ( $order == "random" )
		{
			shuffle( $data['open'] );
			shuffle( $data['votable'] );
		}
		
		// $data now holds the requested results
		
		// apply item limit if requested
		if ( $items AND 0<(int)$items )
			return [ "open" => array_slice($data['open'], 0, $items), "votable" => array_slice($data['votable'], 0, $items) ];
		else
			return $data;
	}
	
	public function printEPub($id)
	{
		$epubSQL =	"SELECT 
			S.sid, S.title,
			GROUP_CONCAT(DISTINCT U.username ORDER BY U.username ASC SEPARATOR ', ') as authors,
			'1' AS allow_ebook
			FROM `tbl_stories`S
				INNER JOIN `tbl_stories_authors`rSA ON ( S.sid=rSA.sid ) 
					INNER JOIN `tbl_users`U ON (rSA.aid=U.uid)
			WHERE S.sid=:sid AND S.completed >= 2 AND S.validated >= 30
			GROUP BY S.sid";

		if ( []!= $epubData = $this->exec( $epubSQL, array(':sid' => $id) ) )
			return $epubData[0];
		
		return NULL;
	}
	
	public function epubData($id)
	{
		$epubSQL =	"SELECT 
			S.sid, S.title, S.storynotes, S.summary, UNIX_TIMESTAMP(S.date) as written, UNIX_TIMESTAMP(S.date) as updated, 
			GROUP_CONCAT(DISTINCT U.username ORDER BY U.username ASC SEPARATOR ', ') as authors,
			GROUP_CONCAT(DISTINCT T.label ORDER BY T.tgid,T.label ASC SEPARATOR ', ') as tags
			FROM `tbl_stories`S
				INNER JOIN `tbl_stories_authors`rSA ON ( S.sid=rSA.sid ) 
					INNER JOIN `tbl_users`U ON (rSA.aid=U.uid)
				INNER JOIN `tbl_stories_tags`rST ON (S.sid=rST.sid) 
					INNER JOIN `tbl_tags`T ON (rST.tid=T.tid)
			WHERE S.sid=:sid 
			GROUP BY S.sid";

		return $this->exec( $epubSQL, array(':sid' => $id) );
	}
	
	public function epubChapters($sid)
	{
		$chapters = $this->exec("SELECT C.title, C.inorder
									FROM `tbl_chapters`C
									WHERE C.validated >= 30 AND C.sid = :sid
									ORDER BY C.inorder ASC ",
								[ ":sid" => $sid ] );
		return $chapters;
	}

}
