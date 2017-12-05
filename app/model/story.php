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
	
//	public function author(int $id)
	public function author($id)
	{
		$limit = $this->config['stories_per_page'];
		$author = "SELECT SQL_CALC_FOUND_ROWS U.uid, U.nickname as name, COUNT(rSA.sid) as counter FROM `tbl_stories_authors`rSA INNER JOIN `tbl_users`U ON ( rSA.aid = U.uid AND rSA.aid = :aid ) GROUP BY rSA.aid";
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

		if ( isset($terms['chapters']) )
		{
			if ( $terms['chapters'] == "multichapters" )
				$where[] = "AND S.chapters > 1";
			elseif ( $terms['chapters'] == "oneshot" )
				$where[] = "AND S.chapters = 1";

		}
		if ( isset($terms['story_title']) )
		{
			$where[] = "AND S.title LIKE :title";
			$bind  = array_merge( $bind, [ ":title" => "%{$terms['story_title']}%" ] );
		}

		if ( isset($terms['tagIn']) )
		{
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_tags` WHERE tid IN (".implode(",",$terms['tagIn']).") GROUP BY sid having count(lid)=".count($terms['tagIn']).") iT ON ( iT.sid = S.sid )";

		}
		if ( isset($terms['tagOut']) )
		{
			$join[] = "LEFT JOIN (SELECT sid FROM `tbl_stories_tags` WHERE tid IN (".implode(",",$terms['tagOut']).") GROUP BY sid having count(lid)=".count($terms['tagOut']).") iTo ON ( iTo.sid = S.sid )";
			$where[] = "AND iTo.sid IS NULL";

		}
		if ( isset($terms['category']) )
		{
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_categories` WHERE cid IN (".implode(",",$terms['category']).") GROUP BY sid having count(lid)=".count($terms['category']).") iC ON ( iC.sid = S.sid )";

		}
		if ( isset($terms['author']) )
		{
			// sidebar stuff!
			$join[] = "INNER JOIN (SELECT sid FROM `tbl_stories_authors` WHERE aid IN (".implode(",",$terms['author']).") GROUP BY sid having count(lid)=".count($terms['author']).") iA ON ( iA.sid = S.sid )";
		}
		if ( isset($terms['saved']) AND $_SESSION['userID']>0 )
		{
			// bookmarks & favourites
			$saved_sql = "INNER JOIN `tbl_user_favourites`FavS ON (FavS.uid=".(int)$_SESSION['userID']." AND FavS.item=S.sid AND FavS.type='ST'";
			if ($terms['saved']=="both")
				$join[] = $saved_sql.")";
			elseif ($terms['saved']=="fav")
				$join[] = $saved_sql." AND FavS.bookmark=0)";
			elseif ($terms['saved']=="bm")
				$join[] = $saved_sql." AND FavS.bookmark=1)";
		}
		
		$limit = $this->config['stories_per_page'];
		$pos = (int)\Base::instance()->get('paginate.page') - 1;
		
		$replacements =
		[
			"ORDER"	=> "ORDER BY updated DESC" ,
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
	
//	public function updates(int $year, $month=0, $day=0)
	public function updates($year, $month=0, $day=0)
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
	
	public function searchPrepopulate ($item, $id)
	{
		if ( $item == "author")
			$sql = "SELECT `nickname` as name, `uid` as id FROM `tbl_users` WHERE `groups` & 4 AND `uid` IN ({$id})";

		elseif ( $item == "category")
			$sql = "SELECT `category` as name, `cid` as id FROM `tbl_categories` WHERE `cid` IN ({$id})";

		elseif ( $item == "tag")
			$sql = "SELECT `label` as name, `tid` as id FROM `tbl_tags` WHERE `tid` IN ({$id})";

		if (empty($sql)) return "[]";
		return json_encode( $this->exec($sql) );
	}
	
	public function searchAjax ($item, $bind = NULL)
	{
		if( $item=="tag" )
		{
			$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 5";
			$bind = [ "tag" =>  "%{$bind}%" ];
		}
		elseif( $item=="author" )
		{
			$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND ( U.groups & 4 ) ORDER BY U.nickname ASC LIMIT 5";
			$bind = [ "nickname" =>  "%{$bind}%" ];
		}
		elseif( $item=="category" )
		{
			$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 5";
			$bind = [ "category" =>  "%{$bind}%" ];
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
	
	public function getChapter( $story, $chapter, $counting = TRUE )
	{
		return parent::getChapter( $story, $chapter, $counting );
	}
	
	public function storyData(array $replacements=[], array $bind=[])
	{
		$sql = $this->storySQL($replacements);
		$data = $this->exec($sql, $bind);

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
	
	public function storySQL(array $replacements=[])
	{
		$sql_StoryConstruct = "SELECT SQL_CALC_FOUND_ROWS
				S.sid, S.title, S.summary, S.storynotes, S.completed, S.wordcount, UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified, 
				S.count,GROUP_CONCAT(Ser.seriesid,',',rSS.inorder,',',Ser.title ORDER BY Ser.title DESC SEPARATOR '||') as in_series @EXTRA@,
				".((isset($this->config['optional_modules']['contests']))?"GROUP_CONCAT(rSC.relid) as contests,":"")."
				GROUP_CONCAT(Fav.bookmark,',',Fav.fid SEPARATOR '||') as is_favourite,
				Ra.rating as rating_name, Edit.uid as can_edit,
				S.cache_authors, S.cache_tags, S.cache_characters, S.cache_categories, S.cache_rating, S.chapters, S.reviews,
				S.translation, S.trans_from, S.trans_to
			FROM `tbl_stories`S
				@JOIN@
			".((isset($this->config['optional_modules']['contests']))?"LEFT JOIN `tbl_contest_relations`rSC ON ( rSC.relid = S.sid AND rSC.type = 'story' )":"")."
			LEFT JOIN `tbl_series_stories`rSS ON ( rSS.sid = S.sid )
				LEFT JOIN `tbl_series`Ser ON ( Ser.seriesid=rSS.seriesid )
			LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.ratingid )

            LEFT JOIN `tbl_stories_authors`rSAE ON ( S.sid = rSAE.sid )
				LEFT JOIN `tbl_users`Edit ON ( ".(int)$_SESSION['userID']." = rSAE.aid OR ( Edit.uid = rSAE.aid AND Edit.curator = ".(int)$_SESSION['userID']." ) )

				LEFT JOIN `tbl_user_favourites`Fav ON ( Fav.item = S.sid AND Fav. TYPE = 'ST' AND Fav.uid = ".(int)$_SESSION['userID'].")
			WHERE S.completed @COMPLETED@ 2 AND S.validated >= 30 @WHERE@
			GROUP BY S.sid
			@ORDER@
			@LIMIT@";

		// default replacements
		$replace =
		[
			"@EXTRA@"		=> "",
			"@JOIN@"		=> "",
			"@COMPLETED@"	=> ">=",
			"@WHERE@"		=> "",
			"@ORDER@"		=> "",
			"@LIMIT@"		=> ""
		];
		
		// insert custom replacements
		foreach ( $replacements as $key => $value )
		{
			$replace["@{$key}@"] = $value;
		}
		return str_replace(array_keys($replace), array_values($replace), $sql_StoryConstruct);
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
			$sql = "SELECT C.category, C.description, C.image, C.stats, C.parent_cid
							FROM `tbl_categories`C 
							WHERE C.cid ='{$cid}' ";
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
			foreach ( $entry['stats']['sub'] as $name => $count )
			{
				$entry['stats']['count'] -= $count;
			}
		}
		
		return $data;
	}

	public function loadReviews($storyID,$selectedReview,$chapter=NULL)
	{
		$limit=5;
		$tree = [];

		/*
			get the amount of reviews defines by $limit (offset to come) and count the total amount for pagination setup
		*/
		$sql = "SELECT 
					SQL_CALC_FOUND_ROWS F.fid as review_id, 
					Ch.inorder,
					F.text as review_text, 
					F.reference as review_story, 
					F.reference_sub as review_chapter, 
					IF(F.writer_uid>0,U.nickname,F.writer_name) as review_writer_name, 
					F.writer_uid as review_writer_uid, 
					UNIX_TIMESTAMP(F.datetime) as date_review
				FROM `tbl_feedback`F 
					JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
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
			// remember current review ID
			$current_id = $item['review_id'];
			$data['r'.$current_id] =
			[
				"level"		=>	1,
				"story"		=>	$item['review_story'],
				"date"		=>	date( \Config::getPublic('date_format_short'), $item['date_review']),
				"date_long"	=>	date( \Config::getPublic('date_format_long'), $item['date_review']),
				"time"		=>	date( \Config::getPublic('time_format'), $item['date_review']),
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

		/*
			for the above comments ( id in array $parent), get the associated comments
		*/
		$sql = "SELECT 
					F2.fid as comment_id, 
					F2.text as comment_text, 
					F2.reference_sub as parent_item, 
					IF(F2.writer_uid>0,U2.nickname,F2.writer_name) as comment_writer_name, 
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
					"date"		=>	date( \Config::getPublic('date_format_short'), $item['date_comment']),
					"date_long"	=>	date( \Config::getPublic('date_format_long'), $item['date_comment']),
					"time"		=>	date( \Config::getPublic('time_format'), $item['date_comment']),
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
		// SELECT Ch.inorder FROM `new5d_feedback`F INNER JOIN `new5d_chapters`Ch ON ( F.reference_sub = Ch.chapid ) WHERE F.fid = 4098
		$data = $this->exec( "SELECT Ch.inorder 
					FROM `new5d_feedback`F 
						INNER JOIN `new5d_chapters`Ch ON ( F.reference_sub = Ch.chapid )
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
		
		$statSQL = [
			"SET @users = (SELECT COUNT(*) FROM `tbl_users`U WHERE U.groups > 0);",
			// more precise stats, only counting authors with actual stories
			"SET @authors = ( SELECT COUNT(DISTINCT rSA.aid) FROM `tbl_stories_authors`rSA INNER JOIN `tbl_stories`S ON ( S.sid = rSA.sid AND S.validated >= 20 AND S.completed >= 2 ) );",
			//"SET @authors = (SELECT COUNT(*) FROM `tbl_users`U WHERE ( U.groups & 4 ) );",
			"SET @reviews = (SELECT COUNT(*) FROM `tbl_feedback`F WHERE F.type='ST');",
			"SET @stories = (SELECT COUNT(DISTINCT sid) FROM `tbl_stories`S WHERE S.validated >= 30 );",
			"SET @chapters = (SELECT COUNT(DISTINCT chapid) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated >= 30 AND C.validated >= 20 ) );",
			"SET @words = (SELECT SUM(C.wordcount) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated >= 30 AND C.validated >= 20 ) );",
			"SET @newmember = (SELECT CONCAT_WS(',', U.uid, U.nickname) FROM `tbl_users`U WHERE U.groups>0 ORDER BY U.registered DESC LIMIT 1);",
			"SELECT @users as users, @authors as authors, @reviews as reviews, @stories as stories, @chapters as chapters, @words as words, @newmember as newmember;",
		];
		$statsData = $this->exec($statSQL)[0];
		
		foreach($statsData as $statKey => $statValue)
		{
			$stats[$statKey] = ($statKey=="newmember") ? explode(",",$statValue) : $statValue;
		}

		return $stats;
	}
	
	public function blockNewStories($items)
	{
		return $this->exec('SELECT S.sid, S.title, S.summary, 
											S.cache_authors
										FROM `tbl_stories`S
										WHERE (datediff(S.updated,S.date) = 0)
										ORDER BY S.updated DESC
										LIMIT 0,'.(int)$items);
	}
	
	public function blockRandomStory($items=1)
	{
		return $this->exec('SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories, S.cache_tags
				FROM `tbl_stories`S WHERE S.validated >= 30
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
	
	public function blockRecommendedStory($items=1, $order=FALSE)
	{
		$limit = ($items) ? "LIMIT 0,".$items : "";
		$sort = ( $order == "random" ) ? 'RAND()' : 'Rec.date DESC';
		
		return $this->exec("SELECT Rec.recid, Rec.title, Rec.summary, Rec.author, Rec.url, Rec.cache_categories, Rec.cache_rating,
					U.uid, U.nickname
						FROM `tbl_recommendations`Rec
							LEFT JOIN `tbl_users`U ON ( Rec.uid = U.uid)
						WHERE Rec.validated > 0
						ORDER BY {$sort} {$limit}");
	}

	public function blockFeaturedStory($items=1, $order=FALSE)
	{
		$limit = ($items) ? "LIMIT 0,".$items : "";
		$sort = ( $order == "random" ) ? 'RAND()' : 'S.featured DESC';

		return $this->exec("SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories
				FROM `tbl_stories`S 
					INNER JOIN `tbl_featured`F ON ( type='ST' AND F.id = S.sid AND F.status=1 OR ( F.status IS NULL AND F.start < NOW() AND F.end > NOW() ))
				WHERE S.validated >= 30
			ORDER BY {$sort} {$limit}");
		// 1 = aktuell, 2, ehemals
		//return $this
	}
	
	public function printEPub($id)
	{
		$epubSQL =	"SELECT 
			S.sid, S.title,
			GROUP_CONCAT(DISTINCT U.nickname ORDER BY U.nickname ASC SEPARATOR ', ') as authors,
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
			GROUP_CONCAT(DISTINCT U.nickname ORDER BY U.nickname ASC SEPARATOR ', ') as authors,
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
