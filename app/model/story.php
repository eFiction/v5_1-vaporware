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
		$data = $this->exec($this->storySQL($replacements));

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
		$data = $this->exec($this->storySQL($replacements),["aid" => $id]);
		
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
	
	public function search ($terms, $return)
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
//echo $this->storySQL($replacements);exit;
		$data = $this->exec($this->storySQL($replacements), $bind );

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/story/search/".$return,
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
		$data = $this->exec($this->storySQL($replacements));

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

		$data = $this->exec($this->storySQL($replacements), [ ":sid" => $story, ":chapter" => $chapter ]);
		
		if ( !@in_array($story, @$_SESSION['viewed']) )
		{
			$this->exec("UPDATE `tbl_stories` SET count = count + 1 WHERE sid = :sid", [ ":sid" => $story ] );
			$_SESSION['viewed'][] = $story;
		}

		if ( sizeof($data)==1 )
		{
			$favs = $this->cleanResult($data[0]['is_favourite']);
			$data[0]['is_favourite'] = [];
			if(empty($favs)) return $data[0];
			foreach ( $favs as $value )
				if ( isset($value[1]) ) $data[0]['is_favourite'][$value[0]] = $value[1];

			return $data[0];
		}
		else return FALSE;
	}
	
	public function getChapter( $story, $chapter, $counting = TRUE )
	{
		return parent::getChapter( $story, $chapter, $counting );
	}
	
	public function storySQL($replacements=[])
	{
		$sql_StoryConstruct = "SELECT SQL_CALC_FOUND_ROWS
				S.sid, S.title, S.summary, S.storynotes, S.completed, S.wordcount, UNIX_TIMESTAMP(S.date) as published, UNIX_TIMESTAMP(S.updated) as modified, 
				S.count,GROUP_CONCAT(Ser.seriesid,',',rSS.inorder,',',Ser.title ORDER BY Ser.title DESC SEPARATOR '||') as in_series @EXTRA@,
				".((isset($this->config['modules_enabled']['contests']))?"GROUP_CONCAT(rSC.relid) as contests,":"")."
				GROUP_CONCAT(Fav.bookmark,',',Fav.fid SEPARATOR '||') as is_favourite,
				Ra.rating as rating_name, Edit.uid as can_edit,
				S.cache_authors, S.cache_tags, S.cache_characters, S.cache_categories, S.cache_rating, S.chapters, S.reviews
			FROM `tbl_stories`S
				@JOIN@
			".((isset($this->config['modules_enabled']['contests']))?"LEFT JOIN `tbl_contest_relations`rSC ON ( rSC.relid = S.sid AND rSC.type = 'story' )":"")."
			LEFT JOIN `tbl_series_stories`rSS ON ( rSS.sid = S.sid )
				LEFT JOIN `tbl_series`Ser ON ( Ser.seriesid=rSS.seriesid )
			LEFT JOIN `tbl_ratings`Ra ON ( Ra.rid = S.ratingid )

            LEFT JOIN `tbl_stories_authors`rSAE ON ( S.sid = rSAE.sid )
				LEFT JOIN `tbl_users`Edit ON ( ".(int)$_SESSION['userID']." = rSAE.aid OR ( Edit.uid = rSAE.aid AND Edit.curator = ".(int)$_SESSION['userID']." ) )

				LEFT JOIN `tbl_user_favourites`Fav ON ( Fav.item = S.sid AND Fav. TYPE = 'ST' AND Fav.uid = ".(int)$_SESSION['userID'].")
			WHERE S.completed @COMPLETED@ 0 AND S.validated > 0 @WHERE@
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

	public function loadReviews($storyID,$chapter=NULL)
	{
		$limit=5;
		$sql = "SELECT 
						F1.*, 
						F2.fid as comment_id, 
						F2.text as comment_text, 
						F2.reference_sub as parent_item, 
						IF(F2.writer_uid>0,U2.nickname,F2.writer_name) as comment_writer_name, 
						F2.writer_uid as comment_writer_uid,
						UNIX_TIMESTAMP(F2.datetime) as date_comment
					FROM 
					(
						SELECT 
							F.fid as review_id, 
							Ch.inorder,
							F.text as review_text, 
							F.reference as review_chapter, 
							F.reference_sub as review_chapterid, 
							IF(F.writer_uid>0,U.nickname,F.writer_name) as review_writer_name, 
							F.writer_uid as review_writer_uid, 
							UNIX_TIMESTAMP(F.datetime) as date_review
						FROM `tbl_feedback`F 
							JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
							LEFT JOIN `tbl_chapters`Ch ON ( Ch.chapid = F.reference )
						WHERE F.reference = :storyid @CHAPTER@ AND F.type='ST' 
						ORDER BY F.datetime 
						DESC LIMIT 0,".$limit."
					) F1
				LEFT JOIN `tbl_feedback`F2  ON (F1.review_id = F2.reference AND F2.type='C')
					LEFT JOIN `tbl_users`U2 ON ( F2.writer_uid = U2.uid )
				ORDER BY F1.date_review DESC, F2.datetime ASC";

		if ( $chapter )
			$flat = $this->exec( str_replace("@CHAPTER@", "AND F.reference_sub = :chapter", $sql), [':storyid' => $storyID, ':chapter' => $chapter] );

		else $flat = $this->exec( str_replace("@CHAPTER@", "", $sql), [':storyid' => $storyID] );
		
		if ( sizeof($flat) == 0 ) return FALSE;

		$current_id = NULL;
		$tree = [];
		foreach ( $flat as $item )
		{
			// new review root element
			if ( $item['review_id']!=$current_id )
			{
				// remember current review ID
				$current_id = $item['review_id'];
				$data['r'.$current_id] =
				[
					"level"		=>	1,
					"chapter"	=>	$item['review_chapter'],
					"id"		=>	$item['review_id'],
					"text"		=>	$item['review_text'],
					"name"		=>	$item['review_writer_name'],
					"uid"		=>	$item['review_writer_uid'],
					"timestamp"	=>	$item['date_review'],
				];
			}
			
			$tree += [ 'r'.$current_id => null ];
			
			// Add the comment to the data structure
			if ( $item['comment_id'] != NULL )
			{
				// Check parent level and remember this node's level
				if ( isset($depth[$item['parent_item']]) )
					$depth[$item['comment_id']] = $depth[$item['parent_item']] + 1;
				else
					$depth[$item['comment_id']] = 2;

				// tell the tree where this item originates from
				if ( $item['parent_item'] == "" )
					$tree += [ 'c'.$item['comment_id'] => 'r'.$current_id ];
				else
					$tree += [ 'c'.$item['comment_id'] => 'c'.$item['parent_item'] ];
				
				$data['c'.$item['comment_id']] = 
				[
					"level"		=>	min ($depth[$item['comment_id']], 4),
					"chapter"	=>	$item['review_chapter'],
					"id"		=>	$item['comment_id'],
					"parent"	=>	$item['parent_item'],
					"text"		=>	$item['comment_text'],
					"name"		=>	$item['comment_writer_name'],
					"uid"		=>	$item['comment_writer_uid'],
					"timestamp"	=>	$item['date_comment'],
				];
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

	public function saveComment($id, $data, $member=FALSE)
	{
		$reference_sub = NULL;
		if ( $parent = $this->exec("SELECT reference as parent_id FROM `tbl_feedback` WHERE fid = :fid AND type='C';", [":fid"=>$id]) )
		{
			$reference_sub = $id;
			$id = $parent[0]['parent_id'];
			
		}
		$sql = "INSERT INTO `tbl_feedback`
					(`reference`, `reference_sub`, `writer_name`, `writer_uid`, `text`, `datetime`,        `type`) VALUES 
					(:reference,  :reference_sub,  :guest_name,   :uid,         :text,  CURRENT_TIMESTAMP, 'C')";
		$bind =
		[
			":reference"		=> $id,
			":reference_sub"	=> $reference_sub,
			":uid"				=> ( $member ) ? $_SESSION['userID'] : 0,
			":guest_name"		=> ( $member ) ? NULL : $data['name'],
			":text"				=> $data['text'],
		];
		if ( 1== $this->exec($sql, $bind) )
		{
			\Model\Routines::dropUserCache();
			\Cache::instance()->clear('stats');
			return (int)$this->db->lastInsertId();
		}
		else return FALSE;
		
		//return FALSE;
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
			"SET @authors = (SELECT COUNT(*) FROM `tbl_users`U WHERE ( U.groups & 4 ) );",
			"SET @reviews = (SELECT COUNT(*) FROM `tbl_feedback`F WHERE F.type='ST');",
			"SET @stories = (SELECT COUNT(DISTINCT sid) FROM `tbl_stories`S WHERE S.validated > 0 );",
			"SET @chapters = (SELECT COUNT(DISTINCT chapid) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated > 0 AND C.validated > 0) );",
			"SET @words = (SELECT SUM(C.wordcount) FROM `tbl_chapters`C INNER JOIN `tbl_stories`S ON ( C.sid=S.sid AND S.validated > 0 AND C.validated > 0) );",
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
		return $this->exec('SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories
				FROM `tbl_stories`S
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
						ORDER BY {$sort} {$limit}");
	}

	public function blockFeaturedStory($items=1, $order=FALSE)
	{
		$limit = ($items) ? "LIMIT 0,".$items : "";
		$sort = ( $order == "random" ) ? 'RAND()' : 'S.featured DESC';

		return $this->exec("SELECT S.title, S.sid, S.summary, S.cache_authors, S.cache_rating, S.cache_categories
				FROM `tbl_stories`S
					INNER JOIN `tbl_featured`F ON ( type='ST' AND F.id = S.sid AND F.status=1 OR ( F.status IS NULL AND F.start < NOW() AND F.end > NOW() ))
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
			header("Content-Disposition: filename=\"".$epubData['title']." by ".$epubData['authors'].".epub\"");
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
		/*
		if ( "" == @$this->config['epub_namespace'] )
		{
			$cfg = $this->config;
			$cfg['epub_namespace'] = uuid_v5("6ba7b810-9dad-11d1-80b4-00c04fd430c8", \Base::instance()->get('HOST').\Base::instance()->get('BASE') );
			$cfg->save();
		}
		*/

		/*
		This must be coming from admin panel at some point, right now we will fake it
		*/
		$epubData['version'] = 2;		// supported by most readers, v3 is still quite new
		$epubData['language'] = "de";
		$epubData['uuid']  = uuid_v5(
										uuid_v5
										(
											"6ba7b810-9dad-11d1-80b4-00c04fd430c8",
											(""==$this->config['epub_domain']) ? \Base::instance()->get('HOST').\Base::instance()->get('BASE') : $this->config['epub_domain']
										),
										$epubData['title']
									);
		
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
														WHERE C.validated > '0' AND C.sid = :sid
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
