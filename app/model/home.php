<?php
namespace Model;

class Home extends Base
{
	public function loadPage(string $page)
	{
		$data = $this->exec( "SELECT content, title FROM `tbl_textblocks`T WHERE T.label= :label ;" , array ( ":label" => $page ) );
		return ($data[0] ?? []);
	}
	
	public function loadNewsOverview($items)
	{
		$pos = (int)$this->f3->get('paginate.page') - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS N.nid, N.headline, N.newstext, N.comments, UNIX_TIMESTAMP(N.datetime) as timestamp, 
			U.uid,U.username,
			COUNT(DISTINCT F.fid) as comments
			FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON ( U.uid = N.uid )
				LEFT JOIN `tbl_feedback`F ON ( N.nid = F.reference AND F.type='N' )
			WHERE N.datetime <= NOW()
			GROUP BY N.nid
			ORDER BY N.datetime DESC
			LIMIT ".(max(0,$pos*$items)).",".(int)$items;
		return $this->exec($sql);
	}
	
	public function listNews($items=5)
	{
		$data = $this->loadNewsOverview($items);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/home/news",
			$items
		);
		return $data;
	}
	
	public function loadNews($id, $withComments=TRUE)
	{
		$sql = "SELECT N.nid, N.headline, N.newstext, N.comments, UNIX_TIMESTAMP(N.datetime) as timestamp, 
			U.uid,U.username
			FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON ( U.uid = N.uid )
			WHERE N.nid = :nid";
			
		if( $data = $this->exec($sql, [ ":nid" => $id])[0] )
		{
			$sql = "SELECT F.fid, F.text, UNIX_TIMESTAMP(F.datetime) as timestamp, 
						IF(F.writer_uid>0,U.username,F.writer_name) as comment_writer_name, F.writer_uid
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

	public function listPolls(): array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
				WHERE P.start_date IS NOT NULL AND ( P.end_date IS NULL OR P.end_date>NOW() );";

		$data = $this->exec($sql);
			
		return $data;
	}

	public function listPollArchive(int $page): array
	{
		$limit = 5;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, P.options, P.votes, P.cache, P.open_voting,
					IF(( P.end_date<NOW() OR P.open_voting=1 ),1,0) as canview, 
					IF(( P.end_date>NOW() OR P.end_date IS NULL ),1,0) as canvote, 
					UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username,
					V.option as myvote
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
					LEFT JOIN `tbl_poll_votes`V ON ( V.uid = {$_SESSION['userID']} AND P.poll_id = V.poll_id )
				WHERE P.start_date IS NOT NULL AND P.end_date<NOW()
				ORDER BY P.start_date DESC
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$polls = $this->exec($sql);
			
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			//"/home/polls/archive/order={$sort['link']},{$sort['direction']}",
			"/home/polls/archive",
			$limit
		);

		foreach ( $polls as &$poll )
		{
			// build a cache array
			if ( $poll['cache']==NULL )
				$poll['cache'] = $this->pollBuildCache($poll['id']);
			// build the result array from the cache field
			else $poll['cache'] = json_decode($poll['cache'],TRUE);

			if (current($poll['cache'])>0)
				$poll['factor'] = $poll['votes']/current($poll['cache']);

		}

		return $polls;
	}

	public function loadPoll(int $pollID): array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, P.options, P.votes, P.cache, P.open_voting,
					IF(( P.end_date<NOW() OR P.open_voting=1 ),1,0) as canview, 
					IF(( P.end_date>NOW() OR P.end_date IS NULL ),1,0) as canvote, 
					UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username,
					V.option as myvote
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
					LEFT JOIN `tbl_poll_votes`V ON ( V.uid = {$_SESSION['userID']} AND P.poll_id = V.poll_id )";
		if ( $pollID > 0 )
		{
			$sql .= "WHERE P.poll_id=:poll_id AND P.start_date IS NOT NULL;";
			if ( NULL === $data = @$this->exec($sql, [ ":poll_id" => $pollID ])[0] )
				return [];
		}
		else
		{
			$sql .= "WHERE P.start_date IS NOT NULL AND ( P.end_date<NOW() OR P.open_voting=1 )
				ORDER BY start_date DESC
				LIMIT 0,1;";
			if ( NULL === $data = @$this->exec($sql)[0] )
				return [];
		}

		if ( NULL === $data = @$this->exec($sql, [ ":poll_id" => $pollID ])[0] )
			return [];
		// build a cache array
		if ( $data['cache']==NULL )
			$data['cache'] = $this->pollBuildCache($data['id']);
		// build the result array from the cache field
		else $data['cache'] = json_decode($data['cache'],TRUE);

		$data['options'] = json_decode($data['options'],TRUE);
		return $data;
	}
}
