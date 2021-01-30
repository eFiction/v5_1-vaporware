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

	public function pollCount(): array
	{
		if ( FALSE === \Cache::instance()->exists("pollMenuCount", $counter) )
		{
			$polls = new \DB\SQL\Mapper($this->db, $this->prefix."poll");
			$total	= $polls->count("start_date IS NOT NULL AND start_date <= NOW()");
			$closed = $polls->count("start_date IS NOT NULL AND start_date <= NOW() AND end_date < NOW()");
			$open		= $total - $closed;
			$counter = [ $open, $closed ];

			// cache the result for 1 day or changes occur
			\Cache::instance()->set("pollMenuCount", $counter, 86400);
		}
		return $counter;
	}

	/**
	* Show open polls (for blocks)
	* 2021-01-10 function naming
	* @param	int			$limit	limit to a maximum of polls
	*
	* @return array						Poll list
	*/
	public function pollListBlock(int $limit = 0): array
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
				WHERE P.start_date IS NOT NULL AND ( P.end_date IS NULL OR P.end_date>NOW() );";

		$data = $this->exec($sql);

		return $data;
	}


	/**
	* Load a poll by ID
	* 2021-01-17 function rewrite as wrapper
	*
	* @param	int			$pollID 	selected poll (0 for latest poll)
	*
	* @return array		Poll data
	*/
	public function pollSingle(int $pollID, bool &$closed): ?array
	{
		return $this->pollLoad(0, $pollID, $closed);
	}

	/**
	* Load polls by page
	* 2021-01-17 function rewrite as wrapper
	*
	* @param	int			$page 	pagination index
	* @param	bool		$open		show open polls
	*
	* @return array						Poll list
	*/
	public function pollList(int $page, bool &$closed = TRUE): ?array
	{
		return $this->pollLoad($page, 0, $closed);
	}

	/**
	* Load polls
	* 2021-01-10 function naming
	*
	* @param	int			$page 		pagination index		| either
	* @param	int			$pollID		Poll ID							| or
	* @param	bool		$closed		show open polls
	*
	* @return array						Poll list
	*/
	public function pollLoad(int $page=0, int $pollID=0, bool &$closed=FALSE): ?array
	{
		// if we have an ID, we must check if it's an open or a closed poll
		if ( $pollID )
		{
			$probe = $this->exec("SELECT P.poll_id, IF(( P.end_date>NOW() OR P.end_date IS NULL ),0,1) as closed FROM `tbl_poll`P WHERE P.poll_id=:pollID AND P.start_date IS NOT NULL AND P.start_date<NOW();",[":pollID"=>$pollID],60);
			if ( isset($probe[0]['closed']) )
				$closed = ($probe[0]['closed']==1);
			// no such poll
			else return [];
		}
		$limit = $closed ? 5 : 1;
		$pos = $page - 1;
		// set up a WHERE clause according to th $closed status
		$where = $closed ? " AND P.end_date < NOW()" : " AND ( P.end_date>=NOW() OR P.end_date IS NULL )";

		// back to th ID, now we know if this is an open or closed poll
		if ( $pollID )
		{
			//$count = $this->pollCount();
			$where2 = str_replace("P.", "X.", $where);
			$probe = $this->exec("SELECT P.poll_id,
			       (SELECT COUNT(*)
			          FROM `tbl_poll`X
			         WHERE X.start_date >= P.start_date AND X.start_date IS NOT NULL AND X.start_date <= NOW() {$where2} ORDER BY X.start_date DESC) AS position,
			       P.start_date
			       FROM  `tbl_poll`P
			 WHERE P.poll_id = :pollID AND P.start_date IS NOT NULL AND P.start_date <= NOW() {$where}", [":pollID"=>$pollID]);
			 // check if we have a position in the result stack and calculate a page
			 if ( @$probe[0]['position']>0 )
			 {
				 $page = ceil ( $probe[0]['position'] / $limit );
				 // overwrite the pagination page selector
				 \Base::instance()->set('paginate.page', $page);
				 $pos = $page - 1;
			 }
			 // no position means we don't have such a poll or it's not available
			 else return [];
		}

		// base SQL
		$sql = "SELECT SQL_CALC_FOUND_ROWS P.poll_id as id, P.question, P.options, P.votes, P.cache, P.open_voting,
					IF(( P.end_date<NOW() OR P.open_voting=1 ),1,0) as canview,
					IF(( P.end_date>NOW() OR P.end_date IS NULL ),1,0) as canvote,
					UNIX_TIMESTAMP(P.start_date) as start_date, UNIX_TIMESTAMP(P.end_date) as end_date,
					U.uid, U.username,
					V.option as myvote
				FROM `tbl_poll`P
					LEFT JOIN `tbl_users`U ON ( P.uid = U.uid )
					LEFT JOIN `tbl_poll_votes`V ON ( V.uid = {$_SESSION['userID']} AND P.poll_id = V.poll_id )
				WHERE P.start_date IS NOT NULL AND P.start_date<NOW()
				{$where}
				ORDER BY P.start_date DESC
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$polls = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			$closed ? "/home/polls/closed" : "/home/polls",
			$limit
		);

		foreach ( $polls as &$poll )
		{
			// build a cache array
			if ( $poll['cache']==NULL )
				$poll['cache'] = $this->pollBuildCache($poll['id']);
			// build the result array from the cache field
			else $poll['cache'] = json_decode($poll['cache'],TRUE);

			try {
			    $poll['factor'] = intdiv( 100, $poll['votes']);
			} catch(\DivisionByZeroError $e){
			    $poll['factor'] = 0;
			}

			$poll['options'] = json_decode($poll['options'],TRUE);
		}

		return $polls;
	}

	/**
	* Save a poll vote
	* 2021-01-10 initial
	*
	* @param	int			$pollID 	selected poll (0 for latest poll)
	* @param	int			$vote 		every vote counts
	*
	* @return bool		success
	*/
	public function pollVoteSave(int $pollID, int $vote): ?bool
	{
		// check if voting is possible in this election
		$voting = new \DB\SQL\Mapper($this->db, $this->prefix."poll");
		if(!$voting->load(["poll_id = ? AND start_date IS NOT NULL AND ( end_date IS NULL OR end_date>NOW() )",$pollID]))
			return FALSE;

		// open the voting booth
		$ballot = new \DB\SQL\Mapper($this->db, $this->prefix."poll_votes");
		// load this user's ballot
		if(!$ballot->load(["poll_id=? AND uid=?",$pollID,$_SESSION['userID']]))
		{
			$ballot->poll_id = $pollID;
			$ballot->uid		 = $_SESSION['userID'];
		}
		$ballot->option = $vote;
		$caching = $ballot->changed('option');
		$ballot->save();

		// recreate cache if required
		if ( $caching ) $this->pollBuildCache($pollID, $voting, $ballot);

		return TRUE;
	}
}
