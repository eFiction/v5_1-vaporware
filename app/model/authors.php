<?php
namespace Model;

class Authors extends Base
{
    //protected $db = 'DB';
	public function getAuthors($letter=FALSE)
	{
		$where = ($letter)?"WHERE U.nickname LIKE :letter":"";
		$sql =     	"SELECT U.nickname AS authorname, U.uid AS aid, COUNT(DISTINCT S.sid) as counted, IF((U.nickname REGEXP '^[[:alpha:]]'),UPPER(SUBSTRING(U.nickname,1,1)),'#') as letter
				FROM `tbl_users`U
					INNER JOIN `tbl_stories_authors`rSA ON ( rSA.aid = U.uid )
						INNER JOIN `tbl_stories`S ON ( S.sid = rSA.sid
					AND S.validated >= 20
					AND S.completed >= 2
 					)
					{$where} GROUP BY U.uid
  			HAVING counted > 0
				ORDER BY U.nickname ASC";
		$authors = $this->exec($sql,[ ":letter" => $letter."%"]);
		if(sizeof($authors)==0) return FALSE;
		
		// Initialize empty list
		$list = [];
		
		if ( $letter )
		{
			foreach ( $authors as $author )
				$list[] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
		}
		else
		{
			foreach ( $authors as $author )
			{
				if ( preg_match("/[A-Z]/is", $author['letter']) )
					$list[$author['letter']][] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
				else
					$list["#"][] = [ "name" => $author['authorname'], "id" => $author['aid'], "stories" => $author['counted'] ];
			}
		}

		return $list;
	}
	
	public function letters()
	{
		$BASE = \Base::instance()->get('BASE');
		$data = $this->exec
		( 
			"SELECT UPPER(SUBSTRING(U.nickname,1,1)) as letter, COUNT(DISTINCT U.nickname) as counted 
			FROM `tbl_users`U
				INNER JOIN `tbl_stories_authors`rSA ON ( rSA.aid = U.uid )
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
/*		$links[] =	[
							"label"	=> "__overview",
							"href"	=> [ "", "__overiew" ],
						];	*/

		// http://php.net/manual/en/control-structures.for.php#107427 <- smart guy
		$i=0;
		for($col = 'A'; $col != 'AA'; $col++)
		{
			if (isset($letters[$col]))
			{
				$i++;
				$href = [ "/".$col, $letters[$col] ];
			}
			else
			{
				$href = FALSE;
			}
			$links[] =	[
								"label"	=> $col,
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
