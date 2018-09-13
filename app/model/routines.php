<?php
namespace Model;
/*
	This class offers database routines
*/
class Routines extends Base {
	
	public static function dropUserCache($module="", $uid=NULL)
	{
		$modules = [ "feedback", "messaging" ];
		if ( in_array($module, $modules) )
		{
			$sql = "UPDATE `tbl_users`U SET U.cache_{$module} = '' WHERE U.uid =";
			if ( $uid )
				parent::instance()->exec($sql . " :uid;", [ ":uid" => $uid ]);
			
			else
				parent::instance()->exec($sql . " {$_SESSION['userID']};");
		}
	}
	
	public function noteReview ( $storyID )
	{
		$sql = "SELECT IF(A.realname='',A.nickname,A.realname) as mailname, email
					FROM `tbl_users`A
						INNER JOIN `tbl_stories_authors`Rel ON ( A.uid = Rel.aid )
					WHERE Rel.sid = :sid AND Rel.type = 'M' AND A.alert_feedback = 1;";
		return $this->exec($sql, [":sid" => $storyID]);
	}
	
	public function noteComment ( $feedbackID )
	{
		$sql = "SELECT IF(A.realname='',A.nickname,A.realname) as mailname, email
					FROM `tbl_users`A
						INNER JOIN `tbl_feedback`F ON ( A.uid = F.writer_uid AND F.writer_uid > 0 )
					WHERE F.fid = :fid AND F.type = 'C' AND A.alert_comment = 1;";
		return $this->exec($sql, [":fid" => $feedbackID]);
	}
	
}
