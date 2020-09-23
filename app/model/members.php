<?php

namespace Model;

class Members extends Base
{
	public function memberData(string $username, string $select=""): array
	{
		$userCheck = $this->exec("SELECT U.uid FROM `tbl_users`U WHERE U.username = :user AND U.groups>0", [ ":user" => $username ] );
		if ( empty($userCheck) OR $userCheck[0]['uid']===NULL ) return [];
		$uid = $userCheck[0]['uid'];

		if ( "" == $user = \Cache::instance()->get('memberProfileCache_'.$uid) )
		{
			$user = $this->exec( "SELECT U.uid, U.username, U.realname, U.groups, UNIX_TIMESTAMP(U.registered) as registered, U.about,
						C.username as curator,
						GROUP_CONCAT(F.field_title, ',', F.field_type, ',', I.info, ',', F.field_options ORDER BY F.field_order ASC SEPARATOR '||' ) as fields
					FROM `tbl_users`U
						LEFT JOIN `tbl_users`C ON ( U.curator = C.uid )
						LEFT JOIN `tbl_user_info`I ON ( U.uid = I.uid )
							LEFT JOIN `tbl_user_fields`F ON ( I.field = F.field_id )
					WHERE U.uid = {$uid}
					GROUP BY U.uid" )[0];
			
			$user['fields'] = $this->cleanResult($user['fields']);

			$stats[] = "SET @stories  := (SELECT COUNT(DISTINCT S.sid) 
											FROM `tbl_stories_authors`rSA
												LEFT JOIN `tbl_stories`S ON ( S.sid = rSA.sid
															AND S.validated >= 20
															AND S.completed >= 2
														)
										WHERE rSA.aid = {$uid} );";
			$stats[] = "SELECT @stories as stories;";
			$user['extras'] = $this->exec($stats)[0];
			\Cache::instance()->set('memberProfileCache_'.$uid, $user, 300);
		}

		// logged in
		if ( $_SESSION['userID']==0 )
			$visibility = 3;

		// is it me?
		elseif ( $_SESSION['userID']==$uid )
			$visibility = 0;

		// are we friends?
		elseif ( FALSE !== ($this->exec("SELECT Fr.link_id FROM `tbl_user_friends`Fr WHERE Fr.user_id = {$uid} AND Fr.friend_id = {$_SESSION['userID']} AND active = 1;")[0]??FALSE) )
			$visibility = 1;

		// regular member
		else
			$visibility = 2;

		if ( "" == $library = \Cache::instance()->get('memberProfileCache_'.$uid.'_LibV_'.$visibility) )
		{
			$status = $visibility<2 ? "('F','P','A')" : "('P','A')";
			$libSQL[] = "SET @series  := (SELECT COUNT(DISTINCT C.collid) 
											FROM `tbl_collections`C
										WHERE C.uid = {$uid} AND C.ordered=1 AND C.status IN {$status});";
			$libSQL[] = "SET @collections  := (SELECT COUNT(DISTINCT C.collid) 
											FROM `tbl_collections`C
										WHERE C.uid = {$uid} AND C.ordered=0 AND C.status IN {$status});";
			$libSQL[] = "SET @favourites := (SELECT GROUP_CONCAT(V SEPARATOR '||') FROM (SELECT CONCAT(COUNT(DISTINCT F.fid),',',F.type) as V  FROM `tbl_user_favourites`F WHERE `uid` = {$uid} AND `bookmark` = 0 AND `visibility` >= {$visibility} GROUP BY `type`) AS T);";
			$libSQL[] = "SET @bookmarks := (SELECT GROUP_CONCAT(V SEPARATOR '||') FROM (SELECT CONCAT(COUNT(DISTINCT F.fid),',',F.type) as V  FROM `tbl_user_favourites`F WHERE `uid` = {$uid} AND `bookmark` = 1 AND `visibility` >= {$visibility} GROUP BY `type`) AS T);";

			$libSQL[] = "SELECT @series as series, @collections as collections, @favourites as favourites, @bookmarks as bookmarks;";
			$library = $this->exec($libSQL)[0];

			// parse and count favourites
			$library['favourites_count'] = 0;
			if ( isset($library['favourites']) )
			{
				$library['favourites'] = $this->cleanResult( $library['favourites'] );
				foreach ( $library['favourites'] as $fav )
					$library['favourites_count'] += $fav[0];
			}

			// parse and count bookmarks
			$library['bookmarks_count'] = 0;
			if ( isset($library['bookmarks']) )
			{
				$library['bookmarks'] = $this->cleanResult( $library['bookmarks'] );
				foreach ( $library['bookmarks'] as $book )
					$library['bookmarks_count'] += $book[0];
			}
			\Cache::instance()->set('memberProfileCache_'.$uid.'_LibV_'.$visibility, $library, 300);
		}
		
		// merge the library data into the user data
		$user['extras'] = array_merge($user['extras'], $library);
		// check if we are friends with the user
		$user['friend'] = $this->exec("SELECT Fr.link_id FROM `tbl_user_friends`Fr WHERE Fr.user_id = {$_SESSION['userID']} AND Fr.friend_id = {$uid} AND active = 1;")[0]??FALSE;
		$user['visibility'] = $visibility;
		
		return $user;
	}
	
	public function memberStories(array $author, array $options): array
	{
		$data = $this->profileGetCount($author['uid']);
			
		$limit = $this->config['stories_per_page'];
		
		$pos = (int)\Base::instance()->get('paginate.page') - 1;

		$replacements =
		[
			"ORDER" => "ORDER BY S.updated DESC" ,
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"JOIN" => "INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid=S.sid AND rSA.aid=:aid )"
		];
		$data['stories'] = $this->storyData($replacements, ["aid" => $author['uid']]);
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/members/{$author['username']}/stories",
			$limit
		);
		
		return $data;
	}
	
	public function memberCollections(array $userData = [], string $selection)
	{
		$ordered = ($selection=="series");
		// common SQL creation for member profile and story view
		list ( $sql, $limit ) = $this->collectionsListBase($userData, $ordered);
		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			$ordered ? "/members/{$userData['username']}/series" : "/members/{$userData['username']}/collections",
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

	protected function profileGetCount(int $uid, bool $full=FALSE) : array
	{
		// all available jobs to get a member profile count of categories, tags characters
		$jobs =
		[
			"cat"	=>
			[
				"name" => "Categories",
				"sql"	=> "SELECT SQL_CALC_FOUND_ROWS 
							C.category as name, C.cid, count(C.cid) as counted
								FROM `tbl_stories_authors`rSA
									INNER JOIN `tbl_stories_categories`rSC ON (rSC.sid = rSA.sid AND (rSA.type='M' OR rSA.type='S') AND rSA.aid = :uid)
								LEFT JOIN `tbl_categories`C ON ( rSC.cid = C.cid )
							GROUP BY C.cid
							ORDER BY counted DESC"
			],
			"tag"	=>
			[
				"name" => "Categories",
				"sql"	=> "SELECT SQL_CALC_FOUND_ROWS 
							T.label, T.tid, count(T.tid) as counted
								FROM `tbl_stories_authors`rSA
									INNER JOIN `tbl_stories_tags`rST ON (rSA.sid = rST.sid AND rST.character=0 AND (rSA.type='M' OR rSA.type='S') AND rSA.aid = :uid)
										INNER JOIN `tbl_tags`T ON ( rST.tid = T.tid AND T.tgid = 1 )
							GROUP BY T.tid
							ORDER BY counted DESC, T.label ASC"
			],
			"char"	=>
			[
				"name" => "Categories",
				"sql"	=> "SELECT SQL_CALC_FOUND_ROWS 
							Ch.charname, Ch.charid, count(Ch.charid) as counted
								FROM `tbl_stories_authors`rSA
									INNER JOIN `tbl_stories_tags`rSC ON (rSA.sid = rSC.sid AND rSC.character=1 AND (rSA.type='M' OR rSA.type='S') AND rSA.aid = :uid)
										INNER JOIN `tbl_characters`Ch ON ( rSC.tid = Ch.charid )
							GROUP BY Ch.charid
							ORDER BY counted DESC, Ch.charname ASC"
			],
		];
		
		// run through the jobs defined above
		foreach ( $jobs as $key => $fields )
		{
			// create the proper name for the cache field
			$cachename = 'memberProfile'.$key.($full?'Full':'').'Cache_'.$uid;
			unset($cache);
			// if the cache field is empty, use sql statement from above to get data
			if ( "" == $cache = \Cache::instance()->get($cachename) )
			{
				if (!$full) $fields['sql'] .= " LIMIT 0,5";

				$tmp = $this->exec( $fields['sql'], [ ":uid" => $uid ] );

				// remember 'n' elements and the amount of total elements
				$cache = [ $tmp, $this->exec("SELECT FOUND_ROWS() as found")[0]['found'] ];
				\Cache::instance()->set($cachename, $cache, 300);
			}
			$data[$key] = $cache;
		}
		return $data;
	}

	public function loadBookmarks(array $author, array $options, int $page ): array
	{
		$data = $this->loadFavourites($author, $options, $page, TRUE);
		return $data;
	}
	
	public function loadFavourites(array $author, array $options, int $page, bool $bookmarks=FALSE): array
	{
		// visibility scope required
		$visibility = $author['visibility'];
		// selection parameters
		$select =
		[
			// Authors
			"AU"	=>
			[
				"fields"	=> "'AU' as type, U.uid as id, U.username as name",
				"from"		=> "`tbl_users`U",
				"join"		=> "U.uid = Fav.item AND Fav.type='AU'"
			],
			// Stories
			"ST"	=>
			[
				"fields"	=> "'ST' as type, S.sid as id, S.title as name, S.cache_authors as authorblock",
				"from"		=> "`tbl_stories`S",
				"join"		=> "S.sid = Fav.item AND Fav.type='ST'"
			],
			// Collections
			"CO"	=>
			[
				"fields"	=> "'CO' as type, Coll.collid as id, Coll.title as name, Coll.cache_authors",
				"from"		=> "`tbl_collections`Coll",
				"join"		=> "Coll.collid = Fav.item AND Fav.type='CO'"
			],
			// Series
			"SE"	=>
			[
				"fields"	=> "'SE' as type, Coll.collid as id, Coll.title as name, Coll.cache_authors",
				"from"		=> "`tbl_collections`Coll",
				"join"		=> "Coll.collid = Fav.item AND Fav.type='SE'"
			],
			// Recommendations
			"RC"	=>
			[
				"fields"	=> "'RC' as type, Rec.recid as id, Rec.title as name, Rec.author as cache_authors",
				"from"		=> "`tbl_recommendations`Rec",
				"join"		=> "Rec.recid = Fav.item AND Fav.type='RC'"
			]
		];
		
		if( empty($select[$options[0]]) )
			return [];
		
		$limit = 10;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS {$select[$options[0]]['fields']}, 
						Fav.comments, Fav.visibility, Fav.notify, Fav.fid, Fav.bookmark
						FROM {$select[$options[0]]['from']} 
						INNER JOIN `tbl_user_favourites`Fav ON
					( {$select[$options[0]]['join']} AND Fav.uid = {$author['uid']} AND Fav.visibility >= '{$visibility}' AND Fav.bookmark = ".(int)$bookmarks." )
					LIMIT ".(max(0,$pos*$limit)).",".$limit;		

		$data = $this->exec( $sql );
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/members/{$author['username']}/".(($bookmarks)?"bookmarks":"favourites")."/{$options[0]}",
			$limit
		);

		return $data;
	}
	
}
