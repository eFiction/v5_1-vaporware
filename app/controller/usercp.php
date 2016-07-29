<?php
namespace Controller;

class UserCP extends Base
{
	public function __construct()
	{
		$this->model = \Model\UserCP::instance();
		\Base::instance()->set('systempage', TRUE);
	}
	
	public function beforeroute()
	{
		parent::beforeroute();
		$this->response->addTitle( \Base::instance()->get('LN__UserCP') );
	}

	public function index(\Base $f3, $params)
	{
		
		$this->showMenu();
	}
	
	public function ajax(\Base $f3, $params)
	{
		$data = [];
		if ( empty($params['module']) ) return NULL;
		
		$post = $f3->get('POST');
		
		if ( $params['module']=="messaging" )
		{
			$data = $this->model->ajax("messaging", $post);
		}
		echo json_encode($data);
		exit;
	}
	
	public function library(\Base $f3, $params)
	{
		//$this->response->addTitle( $f3->get('LN__UserCP') );
		$this->response->addTitle( $f3->get('LN__UserMenu_MyLibrary') );

		if ( isset($params[1]) )
		{
			list($params, $returnpath) = array_pad(explode(";returnpath=",$params[1]), 2, '');
			$params = $this->parametric($params);
			$params['returnpath'] = $returnpath;
		}
		$sub = [ "bookmark", "favourite", "recommendation" ];
		if ( !in_array($params[0], $sub) ) $params[0] = "";
		
		// delete function get's accompanied by a pseudo-post, this doesn't count here. Sorry dude
		if( (NULL != $post = $f3->get('POST')) AND !array_key_exists("confirmed",$post))
		{
			if ( $params[0] == "recommendation" )
			{
				//
				
			}
			else
			{
				if ( FALSE === $result = $this->model->saveBookFav($post, $params) )
				{
					$params['error'] = "saving";
					$this->libraryBookFavAdd($f3, $params);
				}
				else
				{
					$f3->reroute($params['returnpath'], false);
					exit;
				}
			}
		}

		$this->counter = $this->model->getCount("library");

		$this->showMenu("library", [
									"BMS"	=> $this->counter['bookmark']['sum'],
									"FAVS"	=> $this->counter['favourite']['sum'],
									"RECS"	=> is_numeric($this->counter['recommendation']['sum']) ? $this->counter['recommendation']['sum'] : FALSE,
								   ]
						);

		switch ( $params[0] )
		{
			case "bookmark":
			case "favourite":
				$this->libraryBookFav($f3, $params);
				break;
			case "recommendation":
				$this->libraryRecommendations($f3, $params);
				break;
			default:
				$this->buffer ( "Empty page");
		}

	}
	
	private function libraryBookFav(\Base $f3, $params)
	{
		if(array_key_exists("toggle",$params))
		{
			$deleted = $this->model->toggleBookFav($params);
			if ( $deleted === TRUE )
			{
				// bookmark was deleted, let's go back to where we came from
				$f3->reroute($params['returnpath'], false);
				exit;
			}
			// show the add form, skip the menu
			$this->libraryBookFavAdd($f3, $params);
			return TRUE;
		}
		
		$menu_upper =
		[
			[ "link" => "AU", "label" => "Author" ],
			[ "link" => "RC", "label" => "Recomm" ],
			[ "link" => "SE", "label" => "Series" ],
			[ "link" => "ST", "label" => "Stories" ],
		];

		$counter = $this->counter[$params[0]]['details'];

		if ( is_array($counter) ) $this->buffer ( \View\UserCP::libraryBookFavMenu($menu_upper, $counter, $params[0]) );
		
		if ( isset($params[1]) AND isset($counter[$params[1]]) )
		{
			$page = ( empty((int)@$params['page']) || (int)$params['page']<0 )  ?: (int)$params['page'];

			// search/browse
			$allow_order = array (
					"id"		=>	"id",
					"visibility"=>	"visibility",
					"name"		=>	"name",
					"comments"	=>	"comments",
			);

			// sort order
			$sort["link"]		= (isset($allow_order[@$params['order'][0]]))	? $params['order'][0] 		: "name";
			$sort["order"]		= $allow_order[$sort["link"]];
			$sort["direction"]	= (isset($params['order'][1])&&$params['order'][1]=="desc") ?	"desc" : "asc";
			
			$data = $this->model->listBookFav($page, $sort, $params);
			
			$extra = [ "sub" => $params[0], "type" => $params[1] ];
			
			$this->buffer ( \View\UserCP::libraryListBookFav($data, $sort, $extra) );
		}
	}
	
	private function libraryRecommendations(\Base $f3, $params)
	{
		
	}
	
	private function libraryBookFavAdd(\Base $f3, $params)
	{
		if ( FALSE !== $data = $this->model->loadBookFav($params) )
		{
			//print_r($params);
			$this->buffer ( \View\UserCP::libraryBookFavAdd($data, $params) );
		}
		//print_r($data);
	}
	
	public function messaging(\Base $f3, $params)
	{
		//$this->response->addTitle( $f3->get('LN__UserCP') );
		$this->response->addTitle( $f3->get('LN__UserMenu_Message') );
		if ( isset($params[1]) )
		{
			$params = explode("/",$params[1]);
			$params[0] = explode(",",$params[0]);
		}
		
		switch ( $params[0][0] )
		{
			case "outbox":
				$this->msgOutbox($f3, $params);
				break;
			case "read":
				$this->msgRead($f3, $params);
				break;
			case "write":
				$this->msgWrite($f3, $params);
				break;
			default:
				$this->msgInbox($f3, $params);
		}
		$this->showMenu("messaging");
	}
	
	public function msgInbox(\Base $f3, $params)
	{
		$data = $this->model->msgInbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "inbox") );
	}
	
	public function msgOutbox(\Base $f3, $params)
	{
		$data = $this->model->msgOutbox();
		$this->buffer ( \View\UserCP::msgInOutbox($data, "outbox") );
	}
	
	public function msgRead(\Base $f3, $params)
	{
		if ( $data = $this->model->msgRead($params[0][1]) )
		{
			$this->buffer ( \View\UserCP::msgRead($data) );
		}
		else $this->buffer( "*** No such message or access violation!");
	}
	
	public function msgWrite(\Base $f3, $params)
	{
		if( isset($_POST['recipient']) )
			$this->msgSave($f3);

		if ( isset($params[0][1]) AND is_numeric($params[0][1]) )
			$data = $this->model->msgReply($params[0][1]);
		else $data = $this->model->msgReply();
		
		$this->buffer ( \View\UserCP::msgWrite($data) );
		//$this->buffer( "Write Message!");
		//$data = $this->model->msgRead();
		//$this->buffer ( \View\UserCP::msgInbox($data) );
		//print_r ( $data);
	}
	
	protected function msgSave(\Base $f3)
	{
		$save = $f3->get('POST');
		if ( sizeof($save)>0 )
		{
			if ( $save['recipient']== "" )
			{
				$f3->set('msgWriteError', "__noRecipient");
				return FALSE;
			}
			$save['recipient'] = explode(",",$save['recipient']);
			if ( sizeof($save['recipient'])>1 )
			{
				// Build an array of recipients
			}
			
			$status = $this->model->msgSave($save);

			print_r($save);
		}
	}
	
	protected function showMenu($selected=FALSE, array $data=[])
	{
		$menu = $this->model->showMenu($selected, $data);

		$this->buffer
		( 
			\View\UserCP::showMenu($menu), 
			"LEFT"
		);
	}
}
