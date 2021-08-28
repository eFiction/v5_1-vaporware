<?php
namespace Model;

class Authors extends Base
{
    //protected $db = 'DB';
	public function getAuthors($letter=FALSE)
	{
		$where = ($letter)?"WHERE U.username LIKE :letter":"";
		$sql =     	"SELECT U.uid, U.username, COUNT(DISTINCT S.sid) as counted, IF((U.username REGEXP '^[[:alpha:]]'),UPPER(SUBSTRING(U.username,1,1)),'#') as letter
				FROM `tbl_users`U
					INNER JOIN `tbl_stories_authors`rSA ON ( rSA.aid = U.uid )
						INNER JOIN `tbl_stories`S ON ( S.sid = rSA.sid
					AND S.validated >= 20
					AND S.completed >= 2
 					)
					{$where} GROUP BY U.uid
  			HAVING counted > 0
				ORDER BY U.username ASC";
		$authors = ($letter)
								? $this->exec($sql,[ ":letter" => $letter."%"])
								: $this->exec($sql);
		if(sizeof($authors)==0) return FALSE;

		// Initialize empty list
		$list = [];

		if ( $letter )
		{
			foreach ( $authors as $author )
				$list[] = [ "name" => $author['username'], "id" => $author['uid'], "stories" => $author['counted'] ];
		}
		else
		{
			foreach ( $authors as $author )
			{
				if ( preg_match("/[A-Z]/is", $author['letter']) )
					$list[$author['letter']][] = [ "name" => $author['username'], "id" => $author['uid'], "stories" => $author['counted'] ];
				else
					$list["#"][] = [ "name" => $author['username'], "id" => $author['uid'], "stories" => $author['counted'] ];
			}
		}

		return $list;
	}

	public function letters()
	{
		$BASE = \Base::instance()->get('BASE');
		$data = $this->exec
		(
			"SELECT LOWER(SUBSTRING(U.username,1,1)) as letter, COUNT(DISTINCT U.username) as counted
			FROM `tbl_users`U
				INNER JOIN `tbl_stories_authors`rSA ON ( rSA.aid = U.uid )
						INNER JOIN `tbl_stories`S ON ( S.sid = rSA.sid
					AND S.validated >= 20
					AND S.completed >= 2
 					)
			GROUP BY letter"
		);
		if (sizeof($data)==0) return FALSE;

		foreach ( $data as $d)
		{
			$letters[$d['letter']] = $d['counted'];
		}
		return $letters;
	}

	public function menuLetters($letters)
	{
		// http://php.net/manual/en/control-structures.for.php#107427 <- smart guy
		$i=0;
		for($col = 'a'; $col != 'aa'; $col++)
		{
			if (isset($letters[$col]))
			{
				$i++;
				$href = TRUE;
			}
			else
				$href = FALSE;

			$links[] =	[
								"label"	=> strtoupper($col),
								"href"	=> $href,
							];
		}

		if ( $i < sizeof($letters) )
			$href = [ "/@", "" ];
		else
			$href = FALSE;

		$links[] =	[
							"label"	=> "#",
							"href"	=> $href,
						];

		return $links;
	}
}
