<?php
namespace Model;

class UserCP extends Base
{
	protected $menu = [];
	
	public function __construct()
	{
		parent::__construct();
		$this->menu = $this->panelMenu();
	}

	public function showMenu($selected=FALSE)
	{
		if ( $selected )
		{
			$this->menu[$selected]["sub"] = $this->panelMenu($selected);
		}
		return $this->menu;
	}
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "messaging" )
		{
			if(isset($data['recipient']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND U.groups > 0 AND U.uid != {$_SESSION['userID']} ORDER BY U.nickname ASC LIMIT 10";
				$bind = [ "nickname" =>  "%{$data['recipient']}%" ];
			}
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;

	}
	
	public function msgInbox($offset=0)
	{
		//$this->title[] = "__Inbox";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.sender as name_id,u.nickname as name, TRUE as can_delete
						FROM `tbl_messaging`m 
							INNER JOIN `tbl_users`u ON ( m.sender = u.uid ) 
						WHERE m.recipient = ".$_SESSION['userID']." 
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}

	public function msgOutbox($offset=0)
	{
		//$this->title[] = "__Inbox";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.recipient as name_id,u.nickname as name, IF(m.date_read IS NULL,TRUE,FALSE) as can_delete
						FROM `tbl_messaging`m 
							INNER JOIN `tbl_users`u ON ( m.recipient = u.uid ) 
						WHERE m.sender = ".$_SESSION['userID']." 
						ORDER BY date_sent DESC";
		return $this->exec($sql);
	}
	
	public function msgRead($msgID)
	{
		//$this->title[] = "__ReadMessage";
		
		$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.message,
								m.sender as sender_id, u1.nickname as sender,
								m.recipient as recipient_id, u2.nickname as recipient 
								FROM `tbl_messaging`m 
								INNER JOIN `tbl_users`u1 ON ( m.sender = u1.uid ) 
								INNER JOIN `tbl_users`u2 ON ( m.recipient = u2.uid ) 
								WHERE m.mid = :mid AND ( m.sender = {$_SESSION['userID']} OR m.recipient = {$_SESSION['userID']} ) ORDER BY date_sent DESC";
		$data = $this->exec($sql, ["mid" => $msgID]);
		if ( empty($data) ) return FALSE;

		/* if unread, set read date and change unread counter in SESSION */
		if($data[0]['date_read']==NULL AND $data[0]['recipient_id']==$_SESSION['userID'])
		{
			$this->exec("UPDATE `tbl_messaging`m SET m.date_read = CURRENT_TIMESTAMP WHERE m.mid = {$data[0]['mid']};");
			$_SESSION['mail'][1]--;
		}
		return $data[0];
	}
	
	public function msgReply($msgID=NULL)
	{
		//$this->title[] = "__ReadMessage";
		if ( $msgID )
		{
			$sql = "SELECT m.mid,UNIX_TIMESTAMP(m.date_sent) as date_sent,UNIX_TIMESTAMP(m.date_read) as date_read,m.subject,m.message,
									IF(m.recipient = {$_SESSION['userID']},m.sender,NULL) as recipient_id,
									IF(m.recipient = {$_SESSION['userID']},u1.nickname,NULL) as recipient,
									u1.nickname as sender
									FROM `tbl_messaging`m 
									INNER JOIN `tbl_users`u1 ON ( m.sender = u1.uid ) 
									INNER JOIN `tbl_users`u2 ON ( m.recipient = u2.uid ) 
									WHERE m.mid = :mid AND ( m.sender = {$_SESSION['userID']} OR m.recipient = {$_SESSION['userID']} ) ORDER BY date_sent DESC";
			$data = $this->exec($sql, ["mid" => $msgID]);
		}
		
		if ( empty($data) )
		{
			return
			[
				'recipient' => "",
				'subject' => "",
				'message' => ""
			];
		}

		$data = $data[0];
		$data['recipient'] = ($data['recipient']==NULL) ? "" : json_encode ( array ( "name"	=> $data['recipient'], "id" => $data['recipient_id'] ) );
		$data['subject'] = \Base::instance()->get('LN__PM_ReplySubject')." ".$data['subject'];

		return $data;
	}
	
}