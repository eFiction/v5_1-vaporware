<?php

namespace Model;

class News extends Base
{
	public function loadOverview($items)
	{
		$sql = "SELECT N.nid, N.headline, N.newstext, N.comments, UNIX_TIMESTAMP(N.datetime) as timestamp, 
			U.uid,U.nickname
			FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON ( U.uid = N.uid )
			ORDER BY datetime DESC
			LIMIT 0,".(int)$items;
		return $this->exec($sql);
	}
	
}
