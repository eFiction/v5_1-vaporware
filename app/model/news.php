<?php

namespace Model;

class News extends Base
{
	public function loadOverview($items)
	{
		$pos = (int)\Base::instance()->get('paginate.page') - 1;
		
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS N.nid, N.headline, N.newstext, N.comments, UNIX_TIMESTAMP(N.datetime) as timestamp, 
			U.uid,U.nickname,
			COUNT(DISTINCT F.fid) as comments
			FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON ( U.uid = N.uid )
				LEFT JOIN `tbl_feedback`F ON ( N.nid = F.reference )
			WHERE N.datetime <= NOW()
			GROUP BY N.nid
			ORDER BY N.datetime DESC
			LIMIT ".(max(0,$pos*$items)).",".(int)$items;
		return $this->exec($sql);
	}
	
	public function listNews($items=5)
	{
		$data = $this->loadOverview($items);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/news",
			$items
		);
		return $data;
	}
	
	public function loadNews($id, $withComments=TRUE)
	{
		$sql = "SELECT N.nid, N.headline, N.newstext, N.comments, UNIX_TIMESTAMP(N.datetime) as timestamp, 
			U.uid,U.nickname
			FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON ( U.uid = N.uid )
			WHERE N.nid = :nid";
			
		if( $data = $this->exec($sql, [ ":nid" => $id])[0] )
		{
			$sql = "SELECT F.fid, F.text, UNIX_TIMESTAMP(F.datetime) as timestamp, 
						IF(F.writer_uid>0,U.nickname,F.writer_name) as comment_writer_name, F.writer_uid
						FROM `tbl_feedback`F
							LEFT JOIN `tbl_users`U ON ( F.writer_uid = U.uid )
						WHERE F.reference = {$data['nid']} AND F.type='N'
						ORDER BY datetime ASC";
			
			$data['comments'] = $this->exec($sql);
			return $data;
		}
		else return FALSE;
	}
	
	public function saveComment($id, $data, $member=FALSE)
	{
		$sql = "INSERT INTO `tbl_feedback`
					(`reference`, `writer_name`, `writer_uid`, `text`, `datetime`,        `type`) VALUES 
					(:nid,        :guest_name,   :uid,         :text,  CURRENT_TIMESTAMP, 'N')";
		$bind =
		[
			":nid"			=> $id,
			":uid"			=> ( $member ) ? $_SESSION['userID'] : 0,
			":guest_name"	=> ( $member ) ? NULL : $data['name'],
			":text"		=> $data['text'],
		];
		return $this->exec($sql, $bind);
	}
	
}
