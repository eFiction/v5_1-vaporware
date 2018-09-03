<?php
namespace Model;

class AdminCP extends Base {

	protected $menu = [];
	protected $access = [];
	
	public function ajax($key, $data)
	{
		$bind = NULL;
		
		if ( $key == "search" )
		{
			if(isset($data['tagname']))
			{
				$ajax_sql = "SELECT T.label as name,T.tid as id FROM `tbl_tags`T WHERE T.label LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['tagname']}%" ];
			}
			elseif(isset($data['charname']))
			{
				$ajax_sql = "SELECT Ch.charname as name,Ch.charid as id FROM `tbl_characters`Ch WHERE Ch.charname LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['charname']}%" ];
			}
			elseif(isset($data['contestname']))
			{
				$ajax_sql = "SELECT C.title as name,C.conid as id FROM `tbl_contests`C WHERE C.title LIKE :label LIMIT 10";
				$bind = [ ":label" =>  "%{$data['contestname']}%" ];
			}
		}
		elseif ( $key == "editMeta" )
		{
			if(isset($data['category']))
			{
				$ajax_sql = "SELECT category as name, cid as id from `tbl_categories`C WHERE C.category LIKE :category ORDER BY C.category ASC LIMIT 5";
				$bind = [ ":category" =>  "%{$data['category']}%" ];
			}
			elseif(isset($data['author']))
			{
				$ajax_sql = "SELECT U.nickname as name, U.uid as id from `tbl_users`U WHERE U.nickname LIKE :nickname AND ( U.groups & 5 ) ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ ":nickname" =>  "%{$data['author']}%" ];
			}
			elseif(isset($data['tag']))
			{
				$ajax_sql = "SELECT label as name, tid as id from `tbl_tags`T WHERE T.label LIKE :tag ORDER BY T.label ASC LIMIT 10";
				$bind = [ ":tag" =>  "%{$data['tag']}%" ];
			}
			elseif(isset($data['character']))
			{
				$ajax_sql = "SELECT Ch.charname as name, Ch.charid as id from `tbl_characters`Ch WHERE Ch.charname LIKE :charname ORDER BY Ch.charname ASC LIMIT 5";
				$bind = [ ":charname" =>  "%{$data['character']}%" ];
			}
		}
		elseif ( $key == "storySearch" )
		{
			if(isset($data['storyID']))
			{
				$ajax_sql = "SELECT S.title as name,S.sid as id from `tbl_stories`S WHERE S.title LIKE :story OR S.sid = :sid ORDER BY S.title ASC LIMIT 5";
				$bind = [ ":story" =>  "%{$data['storyID']}%", ":sid" =>  $data['storyID'] ];
			}
		}
		elseif ( $key == "userSearch" )
		{
			if(isset($data['userID']))
			{
				$ajax_sql = "SELECT U.nickname as name,U.uid as id from `tbl_users`U WHERE ( U.nickname LIKE :user OR U.uid = :uid ) AND U.uid > 0 ORDER BY U.nickname ASC LIMIT 5";
				$bind = [ ":user" =>  "%{$data['userID']}%", ":uid" =>  $data['userID'] ];
			}
		}
		elseif ( $key == "chaptersort" )
		{
			$chapters = new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
			foreach ( $data["neworder"] as $order => $id )
			{
				if ( is_numeric($order) && is_numeric($id) && is_numeric($data["story"]) )
				{
					$chapters->load(array('chapid = ? AND sid = ?',$id, $data['story']));
					$chapters->inorder = $order+1;
					$chapters->save();
				}
			}
		}

		if ( isset($ajax_sql) ) return $this->exec($ajax_sql, $bind);
		return NULL;
	}
	
	public function settingsFields($select)
	{
		$f3 = \Base::instance();
		
		$sql = "SELECT `name`, `value`, `form_type`, `can_edit`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE :module 
						AND `can_edit` > 0 
					ORDER BY `section_order` ASC";
		$data = $this->exec($sql,[ ":module" => $select ]);
		foreach ( $data as &$d )
		{
			list ( $d['comment'], $d['comment_small'] ) = array_merge ( explode("@SMALL@", $f3->get('LN__CFG_'.$d['name'])), array(FALSE) );

			$d['form_type'] = explode("//", $d['form_type']);
			$d['type'] = @array_shift($d['form_type']);
			if ($d['type']=="select")
			{
				array_walk( $d['form_type'],
                      function(&$v) { $v = @explode("=",$v); },  NULL);
			}
		}
		return [ "section" => $select, "fields" => $data];
	}
	
	public function listTeam()
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `nickname`, `realname`, `groups` FROM `tbl_users` WHERE `groups` > 16 ORDER BY groups,nickname ASC";
		return $this->exec($sql);
	}
	
	public function listUserFields()
	{
		$sql = "SELECT `field_id`, `field_type`, `field_name`, `field_title`, `field_options`, `enabled` FROM `tbl_user_fields` ORDER BY `field_type` ASC";
		return $this->exec($sql);
	}
	
	public function listUsers($page, array $sort, $search=NULL)
	{
		$limit = 20;
		$pos = $page - 1;
		$bind = [];

		$sql = "SELECT SQL_CALC_FOUND_ROWS `uid`, `nickname`, `login`, `email`, UNIX_TIMESTAMP(registered) as registered, `groups`
					FROM `tbl_users`
					WHERE `uid`>0";

		if ( $search['fromlevel'] )
			$sql .= " AND `groups` >= POW(2,{$search['fromlevel']})";
		else 
			$sql .= " AND `groups` >= 1";
		
		if ( $search['tolevel'] )
			$sql .=	" AND groups < POW(2,".($search['tolevel']+1).") ";

		if($search['term'])
		{
			$sql .="	AND (`login` LIKE :term1
							or `nickname` LIKE :term2
							or `realname` LIKE :term3
							or `email` LIKE :term4
							or `about` LIKE :term5) ";
			$bind = [ ":term1" => "%{$search['term']}%", ":term2" => "%{$search['term']}%", ":term3" => "%{$search['term']}%", ":term4" => "%{$search['term']}%", ":term5" => "%{$search['term']}%" ];
		}

		$sql .= " ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql,$bind);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/members/edit/{$search['follow']}order={$sort['link']},{$sort['direction']}",
			$limit
		);
		return $data;
	}
	
	//public function loadUser(int $uid)
	public function loadUser($uid)
	{
		$sql = "SELECT uid, login, nickname, realname, email, UNIX_TIMESTAMP(registered) as registered, groups, curator, about
					FROM `tbl_users`
					WHERE uid = :uid;";
		$data = $this->exec( $sql, [ ":uid" => $uid ] );
		
		if ( sizeof($data)==1 ) $user = $data[0];
		else return FALSE;
		
		$sql = "SELECT F.field_id as id, F.field_title as title, F.field_type as type, F.field_options as options, I.info
					FROM `tbl_user_fields`F
					LEFT JOIN `tbl_user_info`I ON ( F.field_id = I.field AND I.uid = :uid )
					WHERE F.enabled = 1
					ORDER BY F.field_type, F.field_order";
		$user['fields'] = $this->exec( $sql, [ ":uid" => $uid ] );
		
		foreach ( $user['fields'] as &$field )
		{
			if ( $field['type']==2 )
				$field['options'] = json_decode($field['options'],TRUE);
		}
		
		return $user;
	}
	
	public function saveKeys($data)
	{
		$affected=0;
		$sqlUpdate = "UPDATE `tbl_config` SET `value` = :value WHERE `can_edit` = 1 and `name` = :key and `admin_module` = :section;";
		
		foreach ( $data as $section => $fields )
		{
			foreach($fields as $key => $value)
			{
				if ( $res = $this->exec($sqlUpdate,[ ":value" => $value, ":key" => $key, ":section" => $section ]) )
					$affected++;
			}
		}

		// Force re-caching right now
		\Cache::instance()->clear('config');
		\Base::instance()->set('CONFIG', \Config::instance()->load() );
		
		return [ $affected, FALSE ]; // prepare for error check
	}
	
	public function checkAccess($link, $exists = FALSE)
	{
		if ( $exists ) return isset($this->access[$link]);
		return ( isset($this->access[$link]) AND (int)$this->access[$link]&(int)$_SESSION['groups'] );
	}

	public function showMenu($selected=FALSE)
	{
		$sql = "SELECT M.label, M.link, M.icon, M.child_of, M.link, M.evaluate, ";
		if ( !($_SESSION['groups']&128) )
		{
			$sql .= "M.requires 
					FROM `tbl_menu_adminpanel`M2
						LEFT JOIN `tbl_menu_adminpanel`M ON ( M2.link = M.child_of OR M2.link=M.link )
					WHERE M2.child_of IS NULL AND M2.active = 1
					ORDER BY M.child_of,M.order ASC ";
		}
		else
		{
			if ( $selected )
				$select = " OR `child_of` = :selected ";

			$sql .= "1 as requires
					FROM `tbl_menu_adminpanel`M 
					WHERE ( `child_of` IS NULL {$select})
					ORDER BY M.child_of,M.order ASC";
		}

		$data = $this->exec($sql, [":selected"=> $selected]);
		
		foreach ( $data as $item )
		{
			// fixed to return 0 if module disabled or not present
			if( empty($item['evaluate']) OR 1 == eval("return (isset(\$this->config{$item['evaluate']})) ? \$this->config{$item['evaluate']} : 0;") )
			{
				if ( isset($menu[$item['child_of']]) )
					$menu[$item['child_of']]['sub'][$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];

				else $menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"], "requires" => $item["requires"] ];

				$this->access[$item['link']] = $item["requires"];
			}
		}

		/**
			If menu is created for a moderator, traverse the menu and clear branches that can't be accessed
		**/
		if ( !($_SESSION['groups']&128) )
		{
			$menu = $this->secureMenu($menu);
			foreach ( $menu as $key => &$m )
				if ( $key != $selected ) unset ( $m['sub'] );
		}

		return $menu;
	}
	
	protected function secureMenu($menu)
	{
		foreach ( $menu as &$m )
		{
			if ( isset($m['sub']) ) $m['sub'] = $this->secureMenu($m['sub']);
			if ( !((int)$_SESSION['groups']&(int)$m['requires']) AND @sizeof($m['sub'])==0) $m = [];
		}
		return array_filter($menu);
	}

	public function showMenuUpper($selected=FALSE)
	{
		if(!$selected) return NULL;
		$sql = "SELECT M.*
					FROM `tbl_menu_adminpanel`M
				WHERE `child_of` = :selected AND M.requires <= {$_SESSION['groups']}
				ORDER BY M.order ASC";
		return $this->exec( $sql, [ ":selected" => $selected ] );
	}
	
	public function tagsList($page, $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'tags' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS T.tid,T.tgid,T.label,T.count,G.description as `group`
				FROM `tbl_tags`T 
				LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/tags/edit/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

	public function tagGroupsList($page, $sort)
	{
		/*
		CREATE OR REPLACE VIEW tbl_list_tag_groups AS SELECT G.tgid,G.description,COUNT(T.tid) as `count`
				FROM `tbl_tag_groups`G 
				LEFT JOIN `tbl_tags`T ON ( G.tgid = T.tgid )
		

		$tags = new \DB\SQL\Mapper($this->db, 'tbl_list_tag_groups' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS G.tgid,G.description,COUNT(T.tid) as `count`
				FROM `tbl_tag_groups`G 
				LEFT JOIN `tbl_tags`T ON ( G.tgid = T.tgid )
				GROUP BY G.tgid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/tags/edit/",
			$limit
		);

		return $data;
	}
	
//	public function loadTag(int $tid)
	public function loadTag($tid)
	{
		$sql = "SELECT T.tid as id, T.tgid, T.label, T.description, T.count, G.description as groupname FROM `tbl_tags`T LEFT JOIN `tbl_tag_groups`G ON ( T.tgid=G.tgid) WHERE T.tid = :tid";
		$data = $this->exec($sql, [":tid" => $tid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

//	public function loadTagGroup(int $tgid)
	public function loadTagGroup($tgid)
	{
		$sql = "SELECT TG.tgid as id, TG.label as label, TG.description FROM `tbl_tag_groups`TG WHERE TG.tgid = :tgid";
		$data = $this->exec($sql, [":tgid" => $tgid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}
	
//	public function addTag(string $name) : int
	public function addTag($name)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->label = $name;
		$tag->save();
		return $tag->get('_id');
	}

	public function addTagGroup($name)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->description = $name;
		$taggroup->save();
		return $taggroup->get('_id');
	}

//	public function saveTag(int $tid, array $data)
	public function saveTag($tid, array $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$tag->copyfrom( [ "tgid" => $data['taggroup'], "label" => $data['label'], "description" => $data['description'] ]);

		//if ( TRUE === $i = $tag->changed("tgid") ) $this->tagGroupRecount();
		$i = $tag->changed("tgid");
		$i += $tag->changed("label");
		$i += $tag->changed("description");

		$tag->save();
		return $i;
	}
	
//	public function saveTagGroup(int $tgid, array $data)
	public function saveTagGroup($tgid, array $data)
	{
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$taggroup->copyfrom( [ "label" => $data['label'], "description" => $data['description'] ]);

		$i = $taggroup->changed("label");
		$i += $taggroup->changed("description");

		$taggroup->save();
		return $i;
	}
	
//	public function deleteTag(int $tid)
	public function deleteTag($tid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		$tag->load(array('tid=?',$tid));
		$_SESSION['lastAction'] = [ "deleteResult" => $tag->erase() ];
	}

//	public function deleteTagGroup(int $tgid)
	public function deleteTagGroup($tgid)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'tags');
		if($tag->count(array('tgid=?',$tgid)))
		{
			$_SESSION['lastAction'] = [ "deleteResult" => 0 ];
			return FALSE;
		}
		$taggroup=new \DB\SQL\Mapper($this->db, $this->prefix.'tag_groups');
		$taggroup->load(array('tgid=?',$tgid));
		$_SESSION['lastAction'] = [ "deleteResult" => $taggroup->erase() ];
		return TRUE;
	}

	public function tagGroups()
	{
		$sql = "SELECT TG.tgid as id, TG.description FROM `tbl_tag_groups`TG ORDER BY TG.description ASC";
		return $this->exec($sql);
	}
	
	public function charactersList($page, $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'characters' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS Ch.charid, Ch.charname, Ch.count, Cat.category
				FROM `tbl_characters`Ch 
				LEFT JOIN `tbl_categories`Cat ON ( Ch.catid=Cat.cid )
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/characters/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
//	public function loadCharacter(int $tid)
	public function loadCharacter($charid)
	{
		$sql = "SELECT Ch.charid as id, Ch.charname, Ch.biography, Ch.count, Cat.cid, Cat.category 
					FROM `tbl_characters`Ch
					LEFT JOIN `tbl_categories`Cat ON ( Ch.catid=Cat.cid )
					WHERE Ch.charid = :charid";
		$data = $this->exec($sql, [":charid" => $charid ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}
	
	public function getCategories()
	{
		$sql = "SELECT Cat.cid as id, Cat.category FROM `tbl_categories`Cat ORDER BY Cat.category ASC;";
		return $this->exec($sql);
	}

//	public function addTag(string $name) : int
	public function addCharacter($name)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->charname = $name;
		$character->save();
		return $character->get('_id');
	}
	
//	public function saveCharacter(int $charid, array $data)
	public function saveCharacter($charid, array $data)
	{
		$tag=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$tag->load(array('charid=?',$charid));
		$tag->copyfrom( [ "catid" => $data['catid'], "charname" => $data['charname'], "biography" => $data['biography'] ]);

		//if ( TRUE === $i = $tag->changed("tgid") ) $this->tagGroupRecount();
		$i = $tag->changed("catid");
		$i += $tag->changed("charname");
		$i += $tag->changed("biography");

		$tag->save();
		return $i;
	}

//	public function deleteCharacter(int $tid)
	public function deleteCharacter($charid)
	{
		$character=new \DB\SQL\Mapper($this->db, $this->prefix.'characters');
		$character->load(array('charid=? and count=0',$charid));
		
		$_SESSION['lastAction'] = [ "deleteResult" => $character->erase() ];
	}
	
//	public function contestsList(int $page, array $sort) : array
	public function contestsList($page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS
					C.conid, C.title, 
					UNIX_TIMESTAMP(C.date_open) as date_open, UNIX_TIMESTAMP(C.date_close) as date_close, UNIX_TIMESTAMP(C.vote_closed) as vote_closed, 
					C.cache_tags, C.cache_characters, 
					U.nickname, COUNT(R.lid) as count
				FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid = U.uid )
					LEFT JOIN `tbl_contest_relations`R ON ( C.conid = R.conid AND R.type='ST' )
				GROUP BY C.conid
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/contests",
			$limit
		);

		return $data;
		
	}

//	public function contestLoad(int $tid)
	public function contestLoad($conid)
	{
		$sql = "SELECT C.conid as id, C.title, C.summary, C.concealed, C.date_open, C.date_close,
					GROUP_CONCAT(T.tid,',',T.label SEPARATOR '||') as tag_list,
					GROUP_CONCAT(Ch.charid,',',Ch.charname SEPARATOR '||') as character_list, 
					GROUP_CONCAT(Cat.cid,',',Cat.category SEPARATOR '||') as category_list, 
					U.uid, U.nickname
					FROM `tbl_contests`C
					LEFT JOIN `tbl_users`U ON ( C.uid=U.uid )
					LEFT JOIN `tbl_contest_relations`RelC ON ( C.conid=RelC.conid )
						LEFT JOIN `tbl_tags`T ON ( RelC.relid = T.tid AND RelC.type='T' )
						LEFT JOIN `tbl_characters`Ch ON ( RelC.relid = Ch.charid AND RelC.type='CH' )
						LEFT JOIN `tbl_categories`Cat ON ( RelC.relid = Cat.cid AND RelC.type='CA' )
					WHERE C.conid = :conid";
					/*
					--GROUP_CONCAT(S.sid,',',S.title SEPARATOR '||') as story_list,
					--LEFT JOIN `tbl_stories`S ON ( RelC.relid = S.sid AND RelC.type='ST' )
					*/

		$data = $this->exec($sql, [":conid" => $conid ]);
		if (sizeof($data)==1) 
		{
			$data[0]['date_open'] = ($data[0]['date_open']>0)
				? $this->timeToUser($data[0]['date_open'],  $this->config['date_format_short'])
				: "";
			$data[0]['date_close'] = ($data[0]['date_close']>0)
				? $this->timeToUser($data[0]['date_close'], $this->config['date_format_short'])
				: "";

			//$data[0]['story_list']		 = parent::cleanResult($data[0]['story_list']);
			$data[0]['pre']['tag']		 = $this->jsonPrepop($data[0]['tag_list']);
			$data[0]['pre']['character'] = $this->jsonPrepop($data[0]['character_list']);
			$data[0]['pre']['category']	 = $this->jsonPrepop($data[0]['category_list']);
			return $data[0];
		}
		return NULL;
	}

//	public function contestLoad(int $tid)
	public function contestLoadEntries($conid)
	{
		$sql = "SELECT S.sid, S.title
					FROM `tbl_contests`C
						LEFT JOIN `tbl_contest_relations`RelC ON ( C.conid = RelC.conid )
						INNER JOIN `tbl_stories`S ON ( S.sid = RelC.relid AND RelC.type='ST' )
					WHERE C.conid = :conid";

		$data = $this->exec($sql, [":conid" => $conid ]);
		
		return $data;
	}
	
	public function contestAdd($name)
	{
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		$contest->uid = $_SESSION['userID'];	// create under current user
		$contest->title = $name;
		$contest->concealed = 1;				// create hidden
		$contest->save();
		return $contest->get('_id');			// return new ID for edit form
	}
	
	public function contestSave($conid, array $data)
	{
		if( empty($data['title']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		if ( $data['date_close'] < $data['date_open'] ) $data['date_close'] = $data['date_open'];

		$contest->load(array('conid=?',$conid));
		$contest->copyfrom( 
			[
				"title"			=> $data['title'],
				"concealed"		=> isset($data['concealed']) ? 1 : 0,
				"summary"		=> $data['summary'],
				"date_open"		=> empty($data['date_open']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format_short'], $data['date_open'])->format('Y-m-d')." 00:00:00",
				"date_close"	=> empty($data['date_close']) ?
										NULL :
										\DateTime::createFromFormat($this->config['date_format_short'], $data['date_close'])->format('Y-m-d')." 00:00:00",
			]
		);

		$i  = $contest->changed("title");
		$i += $contest->changed("concealed");
		$i += $contest->changed("summary");
		$i += $contest->changed("date_open");
		$i += $contest->changed("date_close");
		
		$contest->save();
		
		// update relation table
		$this->contestRelation( $conid, $data['tag'], "T" );
		$this->contestRelation( $conid, $data['character'], "CH" );
		$this->contestRelation( $conid, $data['category'], "CA" );

		$this->rebuildContestCache($contest->conid);

		return $i;
	}
	
	private function contestRelation( $conid, $data, $type )
	{
		// Check tags:
		$data = explode(",",$data);
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'contest_relations');

		foreach ( $relations->find(array('`conid` = ? AND `type` = ?',$conid,$type)) as $X )
		{
			if ( FALSE === $temp = array_search($X['relid'], $data) )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp )
			{
				if ( !empty($temp) )		// Fix adding empty entries
				{
					// Add relation to table
					$relations->reset();
					$relations->conid = $conid;
					$relations->relid = $temp;
					$relations->type = $type;
					$relations->save();
				}
			}
		}
		unset($relations);
	}

//	public function contestDelete(int $conid)
	public function contestDelete($conid)
	{
		$contest=new \DB\SQL\Mapper($this->db, $this->prefix.'contests');
		$contest->load(array('conid=?',$conid));
		$_SESSION['lastAction'] = [ "deleteResult" => $contest->erase() ];
	}

	public function jsonPrepop($rawData)
	{
		if ( $rawData == NULL ) return "[]";
		foreach ( parent::cleanResult($rawData) as $tmp )
			$data[] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		return json_encode( $data );	
	}

	public function categoriesListFlat()
	{
		$sql = "SELECT 
					C.cid, C.parent_cid, C.category, C.locked, C.leveldown, C.inorder, C.stats,
					COUNT(C1.cid) as counter 
				FROM `tbl_categories`C 
				INNER JOIN `tbl_categories`C1 ON C.parent_cid=C1.parent_cid 
				GROUP BY C.cid 
				ORDER BY C.leveldown DESC, C.inorder ASC";
		$data = $this->exec($sql);

		if ( sizeof($data) == 0 ) return NULL;
		
		foreach ( $data as $item )
		{
			$item['stats'] = json_decode($item['stats'],TRUE);
			$temp[$item['parent_cid']][] = $item;
			if ( isset($temp[$item['cid']]) ) $temp[$item['parent_cid']] = array_merge ( $temp[$item['parent_cid']], $temp[$item['cid']]);
		}
		return $temp[0];
	}

//	public function loadCategory(int $cid)
	public function loadCategory($cid)
	{
		if ( $cid == 0 ) return NULL;
		$sql = "SELECT cid as id, parent_cid, category, description, image, locked, leveldown, inorder, stats FROM `tbl_categories`C WHERE C.cid = :cid";
		$data = $this->exec($sql, [":cid" => $cid ]);
		if (sizeof($data)==1) return $data[0];
		return FALSE;
	}

//	public function loadCategoryPossibleParents(int $cid)
	public function loadCategoryPossibleParents($cid)
	{
		$sql = "SELECT C.cid, C.parent_cid, C.leveldown, C.category
					FROM `tbl_categories`C 
					INNER JOIN `tbl_categories`C2 ON ( ( C.parent_cid = C2.parent_cid OR C.cid = C2.parent_cid )AND C2.cid = :cid ) 
				WHERE C.cid != :cid2
				ORDER BY C.leveldown, C.inorder ASC ";
		
		$data = $this->exec($sql, [":cid" => $cid, ":cid2" => $cid ]);

		return $data;
	}

//	public function saveCategory(int $cid, array $data)
	public function saveCategory($cid, array $data)
	{
		$category=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$category->load(array('cid=?',$cid));
		$parent_cid = $category->parent_cid;
		$category->copyfrom( 
			[ 
				"category" => $data['category'], 
				"description" => $data['description'],
				"locked" => isset($data['locked']) ? : 0,
				"parent_cid" => $data['parent_cid'], 
			]
		);

		$i = $category->changed("category");
		$i += $category->changed("description");
		$i += $category->changed("locked");
		if ( $category->changed("parent_cid") )
		{
			$i++;
			if ( $data['parent_cid']==0 )
				$new_level = 0;
			else
			{
				$parent=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
				$parent->load(array('cid=?',$data['parent_cid']));
				$new_level = $parent->leveldown+1;
			}
			$this->adjustCategoryLevel($cid, $new_level);
			
			$category->save();

			\Model\Routines::instance()->cacheCategories($parent_cid);
			\Model\Routines::instance()->cacheCategories($data['parent_cid']);
		}
		else $category->save();

		return $i;
	}
	
//	protected function adjustCategoryLevel( int $cid, int $level)
	protected function adjustCategoryLevel($cid, $level)
	{
		$category=new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$category->load(array('cid=?',$cid));
		$category->leveldown = $level;
		$category->save();
		
		$subCategories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$subCategories->load(array('parent_cid=?',$cid));
		while ( !$subCategories->dry() )
		{
			$this->adjustCategoryLevel( $subCategories->cid, $level+1 );
			$subCategories->next();
		}
	}
	
//	public function addCategory( int $parent_cid, array $data=[] )
	public function addCategory($parent_cid, array $data=[] )
	{
		// "clean" target category level
		$this->moveCategory(0, NULL, $parent_cid);

		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		// get number of elements with same parent
		$count = $categories->count(["parent_cid = ?", $parent_cid ]);
		$leveldown = $categories->load(["parent_cid = ?", $parent_cid ])->leveldown;
		$categories->reset();
		
		$categories->category 		= $data['category'];
		$categories->description 	= $data['description'];
		$categories->locked 		= $data['locked'];
		$categories->inorder		= $count;
		$categories->leveldown		= $leveldown;
		$categories->parent_cid		= $parent_cid;
		
		$categories->save();

		$this->moveCategory(0, NULL, $parent_cid);

		return $categories->_id;
	}
	
//	public function moveCategory(int $catID=0, $direction=NULL, $parent=NULL)
	public function moveCategory($catID=0, $direction=NULL, $parent=NULL)
	{
		if ( $parent === NULL )
		{
			$sql = "SELECT C.parent_cid 
						FROM `tbl_categories`C 
						WHERE C.`cid`= :catID";

			$data = $this->exec ( $sql, [ ":catID" => $catID ] );
			if ( empty($data[0]) OR !is_numeric($data[0]['parent_cid']) ) return FALSE;
			$parent = $data[0]['parent_cid'];
		}
		
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$categories->load(
			["parent_cid = ?", $parent ],
			[
				'order' => "inorder ".(($direction=="up") ? "DESC" : "ASC"),
			]
		);
		$elements = $categories->count(["parent_cid = ?", $parent ]);

		// when moving elements upwards, we need to invert the entire logic
		if ( $direction == "up" ) $i = $elements + 1;
		else $i = 0;

		// init vacant spot
		$vacant = -1;
		
		while ( !$categories->dry() )
		{  // gets dry when we passed the last record
			if ( $direction == "up" ) $i--; 
			else $i++;
			if ( $categories->cid == $catID AND $direction!==NULL )
			{
				if ( $direction == "up" )
					$categories->inorder = max(1,($i-1));

				else
					$categories->inorder = min($elements,($i+1));

				// remember the vacant spot
				$vacant = $i;
			}
			elseif ( $direction == "down" AND $i == $vacant+1 )
				$categories->inorder = ($i-1);

			elseif ( $direction == "up" AND $i == $vacant-1 )
				$categories->inorder = ($i+1);

			else 
				$categories->inorder = $i;

			$categories->save();
			// moves forward even when the internal pointer is on last record
			$categories->next();
		}
		return $parent;
	}
	
//	public function deleteCategory( int $cid )
	public function deleteCategory( $cid )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'categories');
		$delete->load( ["cid = ?", $cid ] );
		if ( 1 != $delete->count( ["cid = ?", $cid ] ) ) return FALSE;
		$stats = json_decode( $delete->stats, TRUE );
		if ( $stats['sub']===NULL AND $stats['count']==0 )
		{
			$parent = $delete->parent_cid;
			$delete->erase( ["cid = ?", $cid ] );
			\Model\Routines::instance()->cacheCategories($parent);
			return TRUE;
		}
		else return FALSE;
	}

//	public function listCustompages(int $page, array $sort)
	public function listCustompages($page, array $sort)
	{
		$limit = 10;
		$textblocks = new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		$data = $textblocks->paginate(
					$page-1,
					$limit,
					NULL,
					array('order'=>"{$sort['order']} {$sort['direction']}")
		);

		$this->paginate(
			$data['total'],	//$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/custompages/order={$sort['link']},{$sort['direction']}",
			$limit
		);

		return $data['subset'];
	}

//	public function loadCustompage(int $id)
	public function loadCustompage($id)
	{
		$sql = "SELECT TB.* FROM `tbl_textblocks`TB WHERE TB.id = :id";
		$data = $this->exec($sql, [":id" => $id ]);
		if (sizeof($data)==1) return $data[0];
		return NULL;
	}

//	public function saveCustompage(int $id, array $data)
	public function saveCustompage($id, array $data)
	{
		if( empty($data['label']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$textblock=new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		if( $textblock->count(array('id!=? AND label=?',$id,$data['label'])) > 0 )
		{
			\Base::instance()->set('form_error', "__DuplicateLabel");
			return FALSE;
		}
		$textblock->load(array('id=?',$id));
		$textblock->copyfrom( 
			[ 
				"label"		=> $data['label'], 
				"title"		=> $data['title'],
				"content"	=> $data['content'],
				"as_page"	=> isset($data['page']) ? : 0,
			]
		);

		$i  = $textblock->changed("label");
		$i += $textblock->changed("title");
		$i += $textblock->changed("content");
		$i += $textblock->changed("as_page");
		
		$textblock->save();

		return $i;
	}
	
//	public function addCustompage( string $label )
	public function addCustompage( $label )
	{
		$textblock=new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		$conflicts = (int)$textblock->count(array('label=?',$label));
		if($conflicts>0) return FALSE;
		$textblock->reset();
		$textblock->label = $label;
		$textblock->save();
		return $textblock->_id;
	}

//	public function deleteCustompage( int $id )
	public function deleteCustompage( $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'textblocks');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

//	public function listNews(int $page, array $sort)
	public function listNews($page, array $sort)
	{
		/*
		$tags = new \DB\SQL\Mapper($this->db, $this->prefix.'tags' );
		$data = $tags->paginate($page, 10, NULL, [ 'order' => "{$sort['order']} {$sort['direction']}", ] );
		*/
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS N.nid, N.headline, U.nickname AS author, DATE(N.datetime) as date, UNIX_TIMESTAMP(N.datetime) as timestamp
				FROM `tbl_news`N
				LEFT JOIN `tbl_users`U ON (N.uid=U.uid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/news/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}

//	public function loadNews(int $id)
	public function loadNews($id)
	{
		$sql = "SELECT N.nid as id, N.headline, N.newstext, N.datetime, UNIX_TIMESTAMP(N.datetime) as timestamp FROM `tbl_news`N WHERE `nid` = :nid";
		$data = $this->exec($sql, [":nid" => $id ]);
		if (sizeof($data)!=1) 
			return NULL;

		$data[0]['datetime'] = $this->timeToUser($data[0]['datetime'], $this->config['date_format_long']);

		return $data[0];
	}

//	public function addNews( string $headline )
	public function addNews( $headline )
	{
		$news=new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		$news->uid = $_SESSION['userID'];
		$news->headline = $headline;
		$news->datetime = NULL;
		$news->save();
		return $news->_id;
	}

//	public function saveNews(int $id, array $data)
	public function saveNews($id, array $data)
	{
		if( empty($data['headline']) )
		{
			\Base::instance()->set('form_error', "__EmptyLabel");
			return FALSE;
		}
		$news=new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		if( $news->count(array('nid!=? AND headline=?',$id,$data['headline'])) > 0 )
		{
			\Base::instance()->set('form_error', "__DuplicateLabel");
			return FALSE;
		}
		$news->load(array('nid=?',$id));
		$news->copyfrom( 
			[ 
				"headline"	=> $data['headline'], 
				"newstext"	=> $data['newstext'],
				"datetime"	=> \DateTime::createFromFormat($this->config['date_format_short']." H:i", $data['datetime'])->format('Y-m-d H:i'),
			]
		);

		$i  = $news->changed("headline");
		$i += $news->changed("newstext");
		$i += $news->changed("datetime");
		
		$news->save();

		return $i;
	}
	
//	public function deleteNews( int $id )
	public function deleteNews( $id )
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'news');
		if ( $delete->count( ["nid = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["nid = ?", $id ] );
		return TRUE;
	}

//	public function listStoryFeatured ( int $page, array $sort, string &$status )
	public function listStoryFeatured ( $page, array $sort, &$status )
	{
		/*
		int status = 
			1: active
			2: past
			3: future
		*/

		/*
		active:
		SELECT * FROM `tbl_featured` WHERE status=1 OR ( start < NOW() AND end > NOW() )
		*/
		/*
		past:
		SELECT * FROM `tbl_featured` WHERE status=2 OR end < NOW()
		*/
		/*
		future:
		SELECT * FROM `tbl_featured` WHERE start > NOW()
		*/

		$limit = 20;
		$pos = $page - 1;
		
		switch( $status )
		{
			case "future":
				$join = "F.status=3 OR ( F.status IS NULL AND F.start > NOW() )";
				break;
			case "past":
				$join = "F.status=2 OR ( F.status IS NULL AND F.end < NOW() )";
				break;
			default:
				$status = "current";
				$join = "F.status=1 OR ( F.status IS NULL AND F.start < NOW() AND F.end > NOW() )";
		}

		$sql = str_replace
				(
					"%JOIN%",
					$join,
					"SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, S.cache_authors, S.cache_rating
						FROM `tbl_stories`S
						INNER JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid AND %JOIN% )
					ORDER BY {$sort['order']} {$sort['direction']}
					LIMIT ".(max(0,$pos*$limit)).",".$limit
				);

		$data = $this->exec($sql);
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/archive/featured/select={$status}/order={$sort['link']},{$sort['direction']}",
			$limit
		);
		
		return $data;
	}
	
//	public function loadFeatured ( int $sid )
	public function loadFeatured ( $sid )
	{
		$sql = "SELECT SQL_CALC_FOUND_ROWS S.title, S.sid, S.summary, F.status, F.start, F.end, F.uid, U.nickname, S.cache_authors, S.cache_rating
					FROM `tbl_stories`S
						LEFT JOIN `tbl_featured`F ON ( F.type='ST' AND F.id = S.sid )
						LEFT JOIN `tbl_users`U ON ( F.uid = U.uid )
				WHERE S.sid = :sid";
		$data = $this->exec($sql, [":sid" => $sid ]);
		if (sizeof($data)==1)
		{
			$data[0]['cache_authors'] = json_decode($data[0]['cache_authors'], TRUE);
			return $data[0];
		}
		return NULL;
	}

//	public function saveFeatured(int $sid, array $data)
	public function saveFeatured($sid, array $data)
	{
		$i = NULL;
		$feature=new \DB\SQL\Mapper($this->db, $this->prefix.'featured');
		$feature->load(array('sid=?',$sid));
		$feature->copyfrom( 
			[ 
				"status"	=> $data['status'], 
				"sid"		=> $sid,
				"uid"		=> $_SESSION['userID']
			]
		);

		if ( TRUE === $feature->changed("status") )
		{
			if ( $data['status'] < 3 AND $feature->start === NULL )
				$feature->start = date('Y-m-d H:i:s');
			if ( $data['status'] == 1 AND $feature->end === NULL )
				$feature->end = date('Y-m-d H:i:s');
			$i = 1;
		}

		$feature->save();
		return $i;
	}
	
	//public function loadStoryInfo(int $sid)
	public function loadStoryInfo($sid)
	{
		$data = $this->exec
		(
			"SELECT S.*, COUNT(DISTINCT Ch.chapid) as chapters
				FROM `tbl_stories`S
					LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid)
				WHERE S.sid = :sid",
			[":sid" => $sid ]
		);
		if (sizeof($data)==1)
		{
			$data[0]['ratings'] = $this->exec("SELECT rid, rating, ratingwarning FROM `tbl_ratings`");
			return $data[0];
		}
		return FALSE;
	}
	
	public function storyEditPrePop(array $storyData)
	{
		$pre['cat']  = $this->storyJsonPrepare($storyData['cache_categories']);
		$pre['tag']	 = $this->storyJsonPrepare($storyData['cache_tags']);
		$pre['char'] = $this->storyJsonPrepare($storyData['cache_characters']);
		
		$authors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'M' );", [ ":sid" => $storyData['sid'] ]);
		$pre['auth'] = json_encode($authors);

		$coauthors = $this->exec ( "SELECT U.uid as id, U.nickname as name FROM `tbl_users`U INNER JOIN `tbl_stories_authors`Rel ON ( U.uid = Rel.aid AND Rel.sid = :sid AND Rel.type = 'S' );", [ ":sid" => $storyData['sid'] ]);
		$pre['coauth'] = json_encode($coauthors);

		return $pre;
	}
	
	protected function storyJsonPrepare( $data )
	{
		// if the array is empty, take the short way out.
		if ( NULL === $data = json_decode($data,TRUE) )
			return "[]";

		// tags come in a more complex version, so we have to treat them a bit different
		if ( isset($data['simple']) )
			foreach ( $data['simple'] as $tmp ) $array[] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		// category or character
		else
			foreach ( $data as $tmp ) $array[] = [ "id" => $tmp[0], "name" => $tmp[1] ];
		
		// return a json encoded array for the prepopulation
		return json_encode($array);
	}
	
	public function loadChapterList($sid)
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.title,Ch.validated
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid ORDER BY Ch.inorder ASC",
			[":sid" => $sid ]
		);
		if (sizeof($data)>0) return $data;
		return [];
	}
	
	public function loadStoryMapper($sid)
	{
		$story=new \DB\SQL\Mapper($this->db, $this->prefix.'stories');
		$story->load(array('sid=?',$sid));
		return $story;
	}
	
	public function getChapter( $story, $chapter, $counting = FALSE )
	{
		$data = $this->exec
		(
			"SELECT Ch.sid,Ch.chapid,Ch.inorder,Ch.title,Ch.notes,Ch.validated,Ch.rating
				FROM `tbl_chapters`Ch
			WHERE Ch.sid = :sid AND Ch.chapid = :chapter",
			[":sid" => $story, ":chapter" => $chapter ]
		);
		if (empty($data)) return FALSE;
		$data = $data[0];
		$data['chaptertext'] = parent::getChapter( $story, $data['inorder'], $counting );
		
		return $data;
	}
	
	//public function saveChapterChanges( int $chapterID, array $post )
	public function saveChapterChanges( $chapterID, array $post )
	{
		$chapter=new \DB\SQL\Mapper($this->db, $this->prefix.'chapters');
		$chapter->load(array('chapid=?',$chapterID));
		
		$chapter->title 		= $post['chapter_title'];
		$chapter->notes 		= $post['chapter_notes'];
		$chapter->validated 	= $post['validated'].$post['valreason'];
		$chapter->save();
		
		// plain and visual return different newline representations, this will bring things to standard.
		$post['chapter_text'] = preg_replace("/<br\\s*\\/>\\s*/i", "\n", $post['chapter_text']);
		
		parent::saveChapter($chapterID, $post['chapter_text']);
	}
	
	//public function addChapter ( int $storyID, array $post )
	public function addChapter ( $storyID, array $post )
	{
		$location = $this->config['chapter_data_location'];
		
		// Get current chapter count and raise
		if ( FALSE == $chapterCount = @$this->exec("SELECT COUNT(chapid) as chapters FROM `tbl_chapters` WHERE `sid` = :sid ", [ ":sid" => $storyID ])[0]['chapters'] )
			return FALSE;
		$chapterCount++;
		
		$kv = [
			'title'			=> $post['chapter_title'],
			'inorder'		=> $chapterCount,
			'notes'			=> $post['chapter_notes'],
			//'workingtext'
			//'workingdate'
			//'endnotes'
			'validated'		=> "1".($_SESSION['groups']&128)?"3":"2",
			'wordcount'		=> $this->str_word_count_utf8($post['chapter_text']),
			'rating'		=> "0", // allow rating later
			'sid'			=> $storyID,
		];
		if ( $location != "local" )
			$kv['chaptertext'] = $post['chapter_text'];

		$chapterID = $this->insertArray($this->prefix.'chapters', $kv );
		
		if ( $location == "local" )
		{
			$db = \storage::instance()->localChapterDB();
			$chapterAdd= @$db->exec('INSERT INTO "chapters" ("chapid","sid","inorder","chaptertext") VALUES ( :chapid, :sid, :inorder, :chaptertext )', 
								[
									':chapid' 		=> $chapterID,
									':sid' 			=> $storyID,
									':inorder' 		=> $chapterCount,
									':chaptertext'	=> $post['chapter_text'],
								]
			);
		}
		
		$this->rebuildStoryCache($storyID);
		
		return $chapterID;
	}

	public function addChapterText( $chapterData )
	{
		return parent::addChapterText( $chapterData );
	}

	public function saveStoryChanges(\DB\SQL\Mapper $current, array $post)
	{
		// Step one: save the plain data
		$current->title			= $post['story_title'];
		$current->summary		= str_replace("\n","<br />",$post['story_summary']);
		$current->storynotes	= str_replace("\n","<br />",$post['story_notes']);
		$current->ratingid		= $post['ratingid'];
		$current->completed		= $post['completed'];
		$current->validated 	= $post['validated'].$post['valreason'];
		$current->save();
		
		// Step two: check for changes in relation tables

		// Check tags:
		$this->storyRelationTag( $current->sid, $post['tags'] );
		// Check Characters:
		$this->storyRelationTag( $current->sid, $post['characters'], 1 );
		
		// Check Categories:
		$post['category'] = array_filter(explode(",",$post['category']));
		$categories = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_categories');

		foreach ( $categories->find(array('`sid` = ?',$current->sid)) as $X )
		{
			$temp=array_search($X['cid'], $post['category']);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$categories->erase(['lid=?',$X['lid']]);
			}
			else unset($post['category'][$temp]);
		}
		
		// Insert any character IDs not already present
		if ( sizeof($post['category'])>0 )
		{
			foreach ( $post['category'] as $temp)
			{
				// Add relation to table
				$categories->reset();
				$categories->sid = $current->sid;
				$categories->cid = $temp;
				$categories->save();
			}
		}
		unset($categories);
		
		// Author and co-Author preparation:
		$post['mainauthor'] = array_filter(explode(",",$post['mainauthor']));
		$post['supauthor'] = array_filter(explode(",",$post['supauthor']));
		// remove co-authors that are already in the author field
		$post['supauthor'] = array_diff($post['supauthor'], $post['mainauthor']);

		// Check Authors:
		$this->storyRelationAuthor( $current->sid, $post['mainauthor'] );
		// Check co-Authors:
		$this->storyRelationAuthor( $current->sid, $post['supauthor'], 'S' );
		
		$this->rebuildStoryCache($current->sid);
		
		return TRUE;
	}
	
	protected function storyRelationTag( $sid, $data, $character = 0 )
	{
		// Check tags:
		$data = explode(",",$data);
		$relations = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_tags');

		foreach ( $relations->find(array('`sid` = ? AND `character` = ?',$sid,$character)) as $X )
		{
			$temp=array_search($X['tid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$relations->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}
		
		// Insert any tag IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp)
			{
				// Add relation to table
				$relations->reset();
				$relations->sid = $sid;
				$relations->tid = $temp;
				$relations->character = $character;
				$relations->save();
			}
		}
		unset($relations);
	}
	
	protected function storyRelationAuthor( $sid, $data, $type = 'M' )
	{
		$author = new \DB\SQL\Mapper($this->db, $this->prefix.'stories_authors');

		foreach ( $author->find(array('`sid` = ? AND `type` = ?',$sid,$type)) as $X )
		{
			$temp=array_search($X['aid'], $data);
			if ( $temp===FALSE )
			{
				// Excess relation, drop from table
				$author->erase(['lid=?',$X['lid']]);
			}
			else unset($data[$temp]);
		}

		// Insert any character IDs not already present
		if ( sizeof($data)>0 )
		{
			foreach ( $data as $temp)
			{
				// Add relation to table
				$author->reset();
				$author->sid = $sid;
				$author->aid = $temp;
				$author->type = $type;
				$author->save();
			}
		}
		unset($author);
	}

	//public function getPendingStories(int $page, array $sort)
	public function getPendingStories($page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;
		
		$sql = "SELECT SQL_CALC_FOUND_ROWS * FROM
				(					
					SELECT S.sid, S.title, '1' as story_pending, UNIX_TIMESTAMP(S.updated) as timestamp, Ch.chapid
						FROM `tbl_stories`S
							LEFT JOIN `tbl_chapters`Ch
						ON ( S.sid = Ch.sid AND Ch.validated >= 20 AND Ch.validated < 30 ) 
						WHERE S.validated >= 20 AND S.validated < 30
					UNION
					SELECT S.sid, S.title, IF((S.validated >= 20 AND S.validated < 30),1,0) as story_pending, UNIX_TIMESTAMP(S.updated) as timestamp, Ch.chapid
						FROM `tbl_stories`S
							INNER JOIN `tbl_chapters`Ch
						ON ( S.sid = Ch.sid AND Ch.validated >= 20 AND Ch.validated < 30)
				) P
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;
		
		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/stories/pending/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
	//public function listShoutbox(int $page, array $sort)
	public function listShoutbox($page, array $sort)
	{
		$limit = 20;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS S.id, S.message, IF(S.guest_name IS NULL,U.nickname,S.guest_name) AS author, S.uid, DATE(S.date) as date, UNIX_TIMESTAMP(S.date) as timestamp
				FROM `tbl_shoutbox`S
				LEFT JOIN `tbl_users`U ON (S.uid=U.uid)
				ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		$data = $this->exec($sql);

		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/shoutbox/order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
	public function deleteShout($id)
	{
		$delete = new \DB\SQL\Mapper($this->db, $this->prefix.'shoutbox');
		if ( $delete->count( ["id = ?", $id ] ) == 0 ) return FALSE;
		$delete->erase( ["id = ?", $id ] );
		return TRUE;
	}

	public function loadShoutbox($id)
	{
		$sql = "SELECT S.id, S.message FROM `tbl_shoutbox`S WHERE `id` = :id";
		$data = $this->exec($sql, [":id" => $id ]);
		if (sizeof($data)!=1) 
			return NULL;

		return $data[0];
	}

	public function saveShout($id, array $data)
	{
		$shout=new \DB\SQL\Mapper($this->db, $this->prefix.'shoutbox');
		$shout->load(array('id=?',$id));
		$shout->copyfrom( [ "message" => $data['message'] ]);

		$i = $shout->changed("message");

		$shout->save();
		return $i;
	}
	
	public function logGetCount()
	{
		$count = [];
		$countSQL = "SELECT L.type, COUNT(L.id) as items FROM `tbl_log`L @WHERE@ GROUP BY L.type;";
		$data = $this->exec(str_replace("@WHERE@","",$countSQL));
		if ( sizeof($data) )
		{
			foreach ( $data as $dat )
				$count[$dat['type']]['all'] = $dat['items'];
		}
		$data = $this->exec(str_replace("@WHERE@","WHERE L.new=1",$countSQL));
		if ( sizeof($data) )
		{
			foreach ( $data as $dat )
				$count[$dat['type']]['new'] = $dat['items'];
		}
		return $count;
	}
	
	public function logGetData($sub=FALSE, $page, array $sort)
	{
		$limit = 50;
		$pos = $page - 1;

		$sql = "SELECT SQL_CALC_FOUND_ROWS U.uid, U.nickname, 
					L.uid as uid_reg, L.id, L.action, L.ip, UNIX_TIMESTAMP(L.timestamp) as timestamp, L.type, L.version, L.new
				FROM `tbl_log`L LEFT JOIN `tbl_users`U ON L.uid=U.uid ";

		if ( $sub )
			$sql .= "WHERE L.type = :sub ";

		$sql .= "ORDER BY {$sort['order']} {$sort['direction']}
				LIMIT ".(max(0,$pos*$limit)).",".$limit;

		if ($sub)
			$data = $this->exec($sql, [":sub" => $sub]);
		else
			$data = $this->exec($sql);
		
		foreach ( $data as &$item )
		{
			if ( $item['version']==0 )
			{
				// eFiction 3 original, try to do some cleanup
				if ( $item['type']=="RG" )
				{
					//print_r($item);
					preg_match('/(\w+[\s\w]*)\s+\((\d*)\).*/iU', $item['action'], $matches);
					$item['action'] = [ 'name'=>$matches[1], 'uid'=>$matches[2], 'email'=>'', 'reason'=>'', 'admin'=>($matches[2]!=$item['uid_reg']) ];
					$this->update('tbl_log', ['action' => json_encode($item['action']), 'version'=>1], "id = {$item['id']}" );
					//print_r($matches);
					//print_r($item);
				}
			}
			elseif ( $item['version']==1 )
			{
				// eFiction 3 reworked
				$item['action'] = json_decode($item['action'], TRUE);
			}
			elseif ( $item['version']==2 )
			{
				// eFiction 5
				$item['action'] = json_decode($item['action'], TRUE);
			}
			
			$item['ip'] = long2ip($item['ip']);
			if(function_exists('geoip_country_code_by_name')) $item['country'] = geoip_country_code_by_name($item['ip']);
		}
				
		$this->paginate(
			$this->exec("SELECT FOUND_ROWS() as found")[0]['found'],
			"/adminCP/home/logs/".($sub?"{$sub}/":"")."order={$sort['link']},{$sort['direction']}",
			$limit
		);
				
		return $data;
	}
	
	public function getLanguageConfig()
	{
		$sql = "SELECT `name`, `value`, `form_type`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE 'settings_language_file'";
		$data = $this->exec($sql);
		
		foreach ( $data as $dat )
			$config[$dat['name']] = $dat['value'];

		$config['language_available'] = json_decode($config['language_available'], TRUE);
		if ( !is_array($config['language_available']) ) $config['language_available'] = [];

		return $config;
	}
	
	public function saveLanguage($data)
	{
		$default = $data['lang_default'];
		unset($data['lang_default']);

		foreach ( $data as $key => &$dat )
		{
			if ( $key == $default ) $dat['available'] = TRUE;
			if ( $dat['available'] == TRUE ) $available[$key] = $dat['localname'];
		}
		
		$post["settings_language_file"] =
			[
				"language_available" 	=> json_encode($available),
				"language_default"		=> $default,
			];
			
		return $this->saveKeys($post);
	}

	public function getLayoutConfig()
	{
		$sql = "SELECT `name`, `value`, `form_type`
					FROM `tbl_config` 
					WHERE 
						`admin_module` LIKE 'settings_layout_file'";
		$data = $this->exec($sql);
		
		foreach ( $data as $dat )
			$config[$dat['name']] = $dat['value'];

		$config['layout_available'] = json_decode($config['layout_available'],TRUE);
		if ( !is_array($config['layout_available']) ) $config['layout_available'] = [];

		return $config;
	}
	
	public function saveLayout($data)
	{
		$default = $data['lay_default'];
		unset($data['lay_default']);
		
		foreach ( $data as $key => &$dat )
		{
			if ( $key == $default ) $dat['available'] = TRUE;
			if ( $dat['available'] == TRUE ) $available[$key] = $dat['name'];
		}
		
		$post["settings_layout_file"] =
			[
				"layout_available" 	=> json_encode($available),
				"layout_default"	=> $default,
			];
			
		return $this->saveKeys($post);
	}

}
