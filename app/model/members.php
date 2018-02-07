<?php

namespace Model;

class Members extends Base
{
//	public function profileData(int $uid)
	public function profileData($uid)
	{
		$user = $this->profileUser($uid);

		// is there any user data?
		if ( isset($user[0]['nickname']) )
		{
			$user[0]['fields'] = parent::cleanResult($user[0]['fields']);
			
			return
			[
				"cat"	=> $this->profileCategories($uid),
				"tag"	=> $this->profileTags($uid),
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
					U.nickname, U.realname, UNIX_TIMESTAMP(U.registered) as registered, U.groups, U.about,
					GROUP_CONCAT(F.field_title, ',', F.field_type, ',', I.info, ',', F.field_options ORDER BY F.field_order ASC SEPARATOR '||' ) as fields
					FROM `tbl_users`U
						LEFT JOIN `tbl_user_info`I ON ( U.uid = I.uid )
						INNER JOIN `tbl_user_fields`F ON ( I.field = F.field_id )
					WHERE U.uid = :uid AND U.groups > 0";
		return $this->exec( $sql, [ ":uid" => $uid ] );
	}

//	public function profileCategories(int $uid, $full=FALSE) : array
	public function profileCategories($uid, $full=FALSE)
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

//	public function profileTags(int $uid, $full=FALSE) : array
	public function profileTags($uid, $full=FALSE)
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS 
				TG.description, T.label, T.tid, count(T.tid) as counted
					FROM `tbl_stories_authors`rSA
						INNER JOIN `tbl_stories_tags`rST ON (rSA.sid = rST.sid AND rSA.type='M' AND rSA.aid = :uid)
					LEFT JOIN `tbl_tags`T ON ( rST.tid = T.tid )
						INNER JOIN `tbl_tag_groups` TG ON ( T.tgid = TG.tgid )
				GROUP BY T.tid
				ORDER BY TG.description,counted DESC";
		if (!$full) $sql .= " LIMIT 0,5";
		
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		// return 'n' elements and the amount of total elements
		return [ $data, $this->exec("SELECT FOUND_ROWS() as found")[0]['found'] ];
	}
	
//	public function uidByName(string $name): int 
	public function uidByName($name)
	{
		$sql = "SELECT U.uid FROM `tbl_users`U WHERE U.nickname = :name;";
		$data = $this->exec( $sql, [ ":name" => $name ] );
		
		if( isset($data[0]['uid']) )
			return $data[0]['uid'];
		else return 0;
	}
	
}
