<?php

class Logging extends \DB\SQL\Mapper
{
	
	public function __construct()
	{
		parent::__construct( \Base::instance()->get('DB'), \Config::instance()->prefix."log" );
		/*
			eFiction 3 log types:
			"AM" => "Admin Maintenance"
			"BL" => _BADLOGIN
			"DL" => _ADMINDELETE
			"EB" => _EDITBIO
			"ED" => _ADMINEDIT
			"LP"=> _LOSTPASSWORD
			"RE" => "Reviews"
			"RG" => _NEWREG
			"VS" => _VALIDATESTORY
		*/
		
		/*
			eFiction 5 log subtypes added:
			"DL"	Delete
				=> "c"	Chapter
				=> "f"	From series
				=> "s"	Series
			"ED"	Edit
				=> "a"	Author in story
				=> "c"	Chapter
				=> "s"	Series
			"RG"
				=> "f" Registration failed
		*/
	}

	static public function addEntry($type, $action, $uid = FALSE)
	{
		if (\Registry::exists('LOGGER'))
			$logger = \Registry::get('LOGGER');
		else {
			$logger = new self;
			\Registry::set('LOGGER',$logger);
		}

		// Force add entry
		$logger->reset();

		// determine the subtype
		$logger->subtype = NULL;
		if ( is_array($type) )
		{
			$logger->subtype = $type[1];
			$type	 		 = $type[0];
		}

		// Use id of active user, unless specified
		$logger->uid	 = ( $uid ) ? $uid : $_SESSION['userID'];
		$logger->ip		 = $ip = sprintf("%u",ip2long($_SERVER['REMOTE_ADDR']));
		$logger->version = 2;
		// Submitted data:
		$logger->type	 = $type;

		switch ( $type )
		{
			case "VS":
				$data = $logger->entryVS($logger->subtype, $action, $uid);
				break;
			default:
				$data = $logger->entryGeneric($logger->subtype, $action, $uid);
		}

		$geo = \Web\Geo::instance();
		$data['origin'] =
		[
			$geo->location($_SERVER['REMOTE_ADDR'])['country_code'], 
			$geo->location($_SERVER['REMOTE_ADDR'])['country_name'], 
			$geo->location($_SERVER['REMOTE_ADDR'])['continent_code']
		];

		$logger->action	 = json_encode($data);
		// Add entry
		$logger->save();
	}
	
	protected function entryVS($subtype, $action, $uid)
	{
		// Log story validation 'VS' events
		
		// As this is based on a database mapper, system methods must be treated non-global
		$model = \Model\Base::instance();
		
		if ( $subtype == "c" )
		// Log a validated chapter
		{
			$data = $model->exec("SELECT GROUP_CONCAT(aid) as aid, GROUP_CONCAT(nickname) as author, S.sid, S.title, Ch.chapid, Ch.inorder
										FROM `tbl_stories_authors`rSA
											LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
											LEFT JOIN `tbl_stories`S ON ( rSA.sid = S.sid )
											LEFT JOIN `tbl_chapters`Ch ON ( S.sid = Ch.sid AND Ch.chapid = :chapid )
										WHERE rSA.sid = :sid
										GROUP BY rSA.sid;",
								[ ":sid" => $action[0], ":chapid" => $action[1] ]
			);
			if ( sizeof($data)==1 )
			return [
				'sid' 	 => $data[0]['sid'], 	 'title'  => $data[0]['title'],
				'chapter'=> $data[0]['inorder'], 'chapid' => $data[0]['chapid'],
				'aid' 	 => $data[0]['aid'],	 'author' => $data[0]['author']
			];
			return FALSE;
		}
		else
		// default is story validation
		{
			$data = $model->exec("SELECT GROUP_CONCAT(aid) as aid, GROUP_CONCAT(nickname) as author, S.sid, S.title
											FROM `tbl_stories_authors`rSA
												LEFT JOIN `tbl_stories`S ON ( rSA.sid = S.sid )
												LEFT JOIN `tbl_users`U ON ( rSA.aid = U.uid )
											WHERE rSA.sid = :sid
											GROUP BY rSA.sid;",
								[":sid" => $action ]
			);
			if ( sizeof($data)==1 )
			return [
				'sid' 	 => $data[0]['sid'], 'title'  => $data[0]['title'],
				'aid' 	 => $data[0]['aid'], 'author' => $data[0]['author']
			];
			return FALSE;
		}
	}
}
