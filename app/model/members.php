<?php

namespace Model;

class Members extends Base
{
	public function memberData(string $username, string $select=""): array
	{
		$sql = "SELECT U.uid, U.username, U.realname, U.groups, UNIX_TIMESTAMP(U.registered) as registered, U.about,
					C.username as curator,
					GROUP_CONCAT(F.field_title, ',', F.field_type, ',', I.info, ',', F.field_options ORDER BY F.field_order ASC SEPARATOR '||' ) as fields
				FROM `tbl_users`U
					LEFT JOIN `tbl_users`C ON ( U.curator = C.uid )
					LEFT JOIN `tbl_user_info`I ON ( U.uid = I.uid )
						LEFT JOIN `tbl_user_fields`F ON ( I.field = F.field_id )
				WHERE U.username = :user
				GROUP BY U.uid";
		
		$user = $this->exec( $sql, [ ":user" => $username ] );
		// user does not exist
		if ( empty($user) OR $user[0]['uid']===NULL ) return [];
		
		$sql2[] = "SET @stories  := (SELECT COUNT(DISTINCT S.sid) 
										FROM `tbl_stories_authors`rSA
											LEFT JOIN `tbl_stories`S ON ( S.sid = rSA.sid
														AND S.validated >= 20
														AND S.completed >= 2
													)
									WHERE rSA.aid = {$user[0]['uid']} );";
		$sql2[] = "SET @favourites := (SELECT GROUP_CONCAT(V SEPARATOR '||') FROM (SELECT CONCAT(COUNT(DISTINCT F.fid),',',F.type) as V  FROM `tbl_user_favourites`F WHERE `uid` = {$user[0]['uid']} AND `bookmark` = 0 AND `visibility` = '2' GROUP BY `type`) AS T);";
		$sql2[] = "SET @bookmarks := (SELECT GROUP_CONCAT(V SEPARATOR '||') FROM (SELECT CONCAT(COUNT(DISTINCT F.fid),',',F.type) as V  FROM `tbl_user_favourites`F WHERE `uid` = {$user[0]['uid']} AND `bookmark` = 1 AND `visibility` = '2' GROUP BY `type`) AS T);";

		$sql2[] = "SELECT @stories as stories, @favourites as favourites, @bookmarks as bookmarks;";
		$user[0]['extras'] = $this->exec($sql2)[0];
		// parse and count favourites
		$user[0]['extras']['favourites'] = $this->cleanResult( $user[0]['extras']['favourites'] );
		$user[0]['extras']['favourites_count'] = 0;
		foreach ( $user[0]['extras']['favourites'] as $fav )
				$user[0]['extras']['favourites_count'] += $fav[0];
		// parse and count bookmarks
		$user[0]['extras']['bookmarks'] = $this->cleanResult( $user[0]['extras']['bookmarks'] );
		$user[0]['extras']['bookmarks_count'] = 0;
		if ( isset($user[0]['extras']['bookmarks'][0]) )
		foreach ( $user[0]['extras']['bookmarks'] as $book )
				$user[0]['extras']['bookmarks_count'] += $book[0];

		$user[0]['fields'] = $this->cleanResult($user[0]['fields']);

		return $user[0];
	}
	
	public function memberStories(array $author, array $options): array
	{
		if ( empty($options['page']) OR $options['page']==1 )
		{
			$data['cat'] = $this->profileCategories($author['uid']);
			$data['tag'] = $this->profileTags($author['uid']);
			$data['char'] = $this->profileCharacters($author['uid']);
		}
			
		$limit = $this->config['stories_per_page'];
		
		$pos = (int)\Base::instance()->get('paginate.page') - 1;

		$replacements =
		[
			"ORDER" => "ORDER BY S.updated DESC" ,
			"LIMIT" => "LIMIT ".(max(0,$pos*$limit)).",".$limit,
			"JOIN" => "INNER JOIN `tbl_stories_authors`rSA ON ( rSA.sid = S.sid AND rSA.aid = :aid )"
		];
		$data['stories'] = $this->storyData($replacements, ["aid" => $author['uid']]);
		
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/member/{$author['username']}/stories",
			$limit
		);
		
		return $data;
	}

	protected function profileCategories(int $uid, bool $full=FALSE) : array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				C.category as name, C.cid, count(C.cid) as counted
					FROM `tbl_stories_authors`rSA
						INNER JOIN `tbl_stories_categories`rSC ON (rSC.sid = rSA.sid AND rSA.type='M' AND rSA.aid = :uid)
					LEFT JOIN `tbl_categories`C ON ( rSC.cid = C.cid )
				GROUP BY C.cid
				ORDER BY counted DESC";
		if (!$full) $sql .= " LIMIT 0,5";
		
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		// return 'n' elements and the amount of total elements
		return [ $data, $this->exec("SELECT FOUND_ROWS() as found")[0]['found'] ];
	}

	protected function profileTags(int $uid, bool $full=FALSE) : array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				T.label, T.tid, count(T.tid) as counted
					FROM `tbl_stories_authors`rSA
						INNER JOIN `tbl_stories_tags`rST ON (rSA.sid = rST.sid AND rST.character=0 AND rSA.type='M' AND rSA.aid = :uid)
							INNER JOIN `tbl_tags`T ON ( rST.tid = T.tid AND T.tgid = 1 )
				GROUP BY T.tid
				ORDER BY counted DESC, T.label ASC";
		if (!$full) $sql .= " LIMIT 0,5";
		
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		// return 'n' elements and the amount of total elements
		return [ $data, $this->exec("SELECT FOUND_ROWS() as found")[0]['found'] ];
	}
	
	protected function profileCharacters(int $uid, bool $full=FALSE) : array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				Ch.charname, Ch.charid, count(Ch.charid) as counted
					FROM `tbl_stories_authors`rSA
						INNER JOIN `tbl_stories_tags`rSC ON (rSA.sid = rSC.sid AND rSC.character=1 AND rSA.type='M' AND rSA.aid = :uid)
							INNER JOIN `tbl_characters`Ch ON ( rSC.tid = Ch.charid )
				GROUP BY Ch.charid
				ORDER BY counted DESC, Ch.charname ASC";
		if (!$full) $sql .= " LIMIT 0,5";
		
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		// return 'n' elements and the amount of total elements
		return [ $data, $this->exec("SELECT FOUND_ROWS() as found")[0]['found'] ];
	}
	
	public function loadFavourites(array $author, array $options): array
	{
		print_r( $options );
		//$sql = "SELECT ";
		
		return [];
	}
	
/*
//	public function profileData(int $uid)
	public function profileData(int $uid)
	{
		$user = $this->profileUser($uid);

		// is there any user data?
		if ( isset($user[0]['username']) )
		{
			$user[0]['fields'] = parent::cleanResult($user[0]['fields']);
			
			return
			[
				"cat"	=> $this->profileCategories($uid),
				"tag"	=> $this->profileTags($uid),
				"char"	=> $this->profileCharacters($uid),
				"user"	=> $user[0],
			];
		}
		
		// return FALSE if not
		return FALSE;
	}
	
//	public function profileUser(int $uid) : array
	public function profileUser($uid)
	{
		$sql = "SELECT
					U.username, U.realname, UNIX_TIMESTAMP(U.registered) as registered, U.groups, U.about, U.uid, 
					GROUP_CONCAT(F.field_title, ',', F.field_type, ',', I.info, ',', F.field_options ORDER BY F.field_order ASC SEPARATOR '||' ) as fields
					FROM `tbl_users`U
						LEFT JOIN `tbl_user_info`I ON ( U.uid = I.uid )
						INNER JOIN `tbl_user_fields`F ON ( I.field = F.field_id )
					WHERE U.uid = :uid AND U.groups > 0";
		return $this->exec( $sql, [ ":uid" => $uid ] );
	}

	
//	public function uidByName(string $name): int 
	public function uidByName($name)
	{
		$sql = "SELECT U.uid FROM `tbl_users`U WHERE U.username = :name;";
		$data = $this->exec( $sql, [ ":name" => $name ] );
		
		if( isset($data[0]['uid']) )
			return $data[0]['uid'];
		else return 0;
	}
*/	
}
