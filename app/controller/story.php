<?php
namespace Controller;

class Story extends Base
{

	public function __construct()
	{
		$this->model = \Model\Story::instance();
		$this->template = new \View\Story();
	}

	public function beforeroute()//: void
	{
		parent::beforeroute();
		$this->template->addTitle( \Base::instance()->get('LN__Stories') );
	}

	public function index(\Base $f3, array $params)//: void
	{
		switch(@$params['action'])
		{
			case 'read':
				$data = $this->read($params);
				break;
			case 'reviews':
				$data = $this->reviews($params['*']);
				break;
			case 'print':
				$this->printer($params['*']);
				break;
			case 'categories':
				$data = $this->categories($params);
				break;
			case 'updates':
				$data = $this->updates($params);
				break;
			case 'series':
				$data = $this->series($params);
				break;
			case 'contests':
				$data = $this->contests($params);
				break;
			/*
			case 'search':
			case 'browse':
				$data = $this->search($f3, $params);
				break;
				*/
			case 'archive':
			default:
				$data = $this->intro($params);
		}
		$this->buffer ($data);
	}
	
	public function save(\Base $f3, array $params)
	{
		list($requestpath, $returnpath) = array_pad(explode(";returnpath=",$params['*']), 2, '');
		//@list($story, $view, $selected) = explode(",",$requestpath);
		$params['returnpath'] = $returnpath;

		/* maybe deprecated? 

		if ( $params['action']=="read" )
		{
			if ( isset($_POST['s_data']) )
				parse_str($f3->get('POST.s_data'), $data);

			elseif ( isset($_POST['write']) )
				$data = $f3->get('POST');
			
			// write review or reply to a review

			if( is_numeric($story) AND isset($data) AND ($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews')) )
			{
				echo "Panik, schon wieder?";
				$errors = $this->validateReview($data['write']);
				
				if ( sizeof($errors)==0 )
				{
					// For now let's assume this always returns a proper result
					@list($insert_id, $routine_type, $routine_id) = $this->model->saveReview($story, $data);
					// Run notification routines
					Routines::instance()->notification($routine_type, $routine_id);
					
					// return to where we came from
					$return = (empty($params['returnpath']) ? $requestpath."#r".$insert_id : $returnpath);
					$f3->reroute($params['returnpath'], false);
					exit;
				}
				else
				{
					//echo "<pre>".print_r($params,TRUE).print_r(@$data,TRUE).print_r(@$errors,TRUE)."</pre>";
				}
			}
			else
			{
				// Error reporting
				
			}
			

		}
		*/
			
		if ( $params['action']=="reviews" )
		{
			/*
				this is a sort of stub, as it doesn't actually save any data
				all it does is to process a return-string from the ajax form
				and build a proper path ro relocate to
			*/
			if ( isset($_POST['s_data']) )
			{
				@list($feedback,$hash) = explode("-", $params['*']);
				@list($story,$chapter,$review) = explode(",", $feedback);
				
				if ( $chapter[0] == "r" )
				{
					$chapter = $this->model->getChapterByReview( substr($chapter, 1) );
				}
				
				$requestpath = "{$story},{$chapter},{$review}#{$hash}";
			}
		}
		
		// If nothing else has worked so far, return to where we came from and pretend this was intentional
		$f3->reroute($requestpath, false);
		exit;
	}
	
	public function ajax(\Base $f3, array $params)//: void
	{
		if ( isset($params['segment']) AND $params['segment']=="search" )
		{
			$query = $f3->get('POST');
			$item = NULL;

			if ( is_array($query) ) list ( $item, $bind ) = each ( $query );

			$data = $this->model->searchAjax($item, $bind);
			echo json_encode($data);

			exit;
		}

		elseif ( isset($params['segment']) AND $params['segment']=="review_comment_form" )
		{
			/*
				receive a new review or comment via AJAX-form
				
				Input data is:
				
				array: POST.write
							- name (only defined if guest)
							- text
				array: POST.structure
			*/
			$structure = 
			[
				"story"		=> (int)$f3->get('POST.structure.element'),
				"chapter"	=> (int)$f3->get('POST.structure.subelement'),
				"childof"	=> (int)$f3->get('POST.structure.childof'),
				"level"		=> (int)$f3->get('POST.structure.level'),
			];

			$errors = [];
			$relocate = FALSE;

			
			if($_SESSION['userID']!=0 || \Config::getPublic('allow_guest_reviews') )
			{
				if ( isset($_POST['write']) )
				{
					// Validate input
					$errors = $this->validateReview( $f3->get('POST.write') );

					// Write errors (or lack thereof) to form feedback handler
					$f3->set('formError', $errors);

					// If data is acceptable, store and process
					if ( sizeof($errors)==0 )
					{
						// $saveData = 1 will trigger an event in jQuery to reload page.
						$saveData = 1;
						// For now let's assume this always returns a proper result
						@list($relocate, $routine_type, $routine_id) = $this->model->saveReview( $structure, $f3->get('POST.write') );
						// Run notification routines (send mails)
						Routines::instance()->notification($routine_type, $routine_id);
					}
				}
			}
			if(empty($view)) $view = $this->template->commentForm($structure, $f3->get('POST.write'));
			$this->buffer( [ "", $view, $relocate, ($_SESSION['userID']==0) ], "BODY", TRUE );
		}
	}
	
	protected function validateReview(array $data): array
	{
		$errors = [];

		// Obviously, there should be some text ...
		if ( "" == $data['text'] = trim($data['text']) )
			$errors[]= 'MessageEmpty';

		if ( !$_SESSION['userID'] )
		{
			// Check if captcha is initialized and matches user entry
			if ( empty($_SESSION['captcha']) OR !password_verify(strtoupper($data['captcha']),$_SESSION['captcha']) )
				$errors[]= 'CaptchaMismatch';

			// Guest can't post with an empty name
			if ( "" == $data['name'] = trim($data['name']) )
				$errors[]= 'GuestNameEmpty';

			// guest can't post URL (reg ex is not perfect, but it's a start)
			if (preg_match("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i",$data['text']))
				$errors[]= 'GuestURL';
		}
		
		return $errors;
	}

	protected function intro(array $params): string
	{
		if ( isset($params['*']) ) $get = $this->parametric($params['*']);

		$data = $this->model->intro();
		
		return $this->template->viewList($data);
	}
	
	public function author(int $id): array
	{
		list($info, $data) = $this->model->author($id);

		$stories = $this->template->viewList($data);
		return [ $info[0], $stories];
	}
	
	protected function contests(array $params)
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);

		if ( isset($params['id']) )
		{
			if ( NULL === $contest = $this->model->contestLoad((int)$params['id']) )
			{
				// no contest
				\Base::instance()->reroute("/story/contests", false);
				exit;			
			}
			if ( isset($params['stories']) )
			{
				$stories = $this->model->contestStories($contest['id']);
				$buffer = $this->template->contestStories($contest, $stories);
			}
			else $buffer = $this->template->contestShow($contest);
		}
	
		if ( empty($buffer) )
		{
			$data = $this->model->contestsList();
			
			$buffer = $this->template->contestList($data);
		}

		return $buffer;
	}
	
	protected function updates(array $params): string
	{
		if ( isset($params['*']) ) $params = $this->parametric($params['*']);
		
		if ( isset($params['date']) AND $selection = explode("-",$params['date']) )
		{
			$year = $selection[0];
			$month = isset($selection[1]) ? min($selection[1],12) : FALSE;
			$day = isset($selection[2]) ? min($selection[2],date("t", mktime(0, 0, 0, $month, 1, $year))) : FALSE;
			
			$data = $this->model->updates($year, $month, $day);
			return $this->template->viewList($data);
		}
		else return $this->intro($params);
	}
	
	protected function categories(array $params): string
	{
		$id = empty($params['*']) ? 0 : $params['*'];
		if(empty($params[3]))
		{
			$data = $this->model->categories( (int)$id );

			return $this->template->categories($data);
		}
		else
		{
			// What was I doing here? Need more comments :/
			return "stub *controller-story-categories*";
		}
	}
	
	protected function printer(string $id)
	{
		$id = explode(",",$id);
		$printer = $id[1] ?? "paper";
//		$printer = ($id[1]=="") ? "paper" : $id[1];
		$id = $id[0];
		
		if ( $printer == "epub" )
		{
			// Get the main story data, check if the story is public and an eBook is available.
			// Gracefully fail if not
			if ( NULL === $epubData = $this->model->printEPub($id) )
			{
				echo "";
				exit;
			}
			
			if($file = realpath("tmp/epub/s{$epubData['sid']}.zip"))
			{
				$filesize = filesize($file);
				$ebook = @fopen($file,"rb");
			}
			else
			{
				list($ebook, $filesize) = $this->createEPub($epubData['sid']);
			}
			
			if ( $ebook )
			{
				// http://stackoverflow.com/questions/93551/how-to-encode-the-filename-parameter-of-content-disposition-header-in-http
				$filename = rawurlencode ( $epubData['title']." by ".$epubData['authors'].".epub" );
				
				header("Content-type: application/epub+zip; charset=utf-8");
				header("Content-Disposition: attachment; filename=\"{$filename}\"; filename*=utf-8''".$filename);
				header("Content-length: ".$filesize);
				header("Cache-control: private");

				while(!feof($ebook))
				{
					$buffer = fread($ebook, 8*1024);
					echo $buffer;
				}
				fclose ($ebook);
				exit;
			}
		}
		elseif ( $printer == "paper" )
		{
			//
		}
	}
	
	private function createEPub(int $sid): array
	{
		$epubData = $this->model->epubData($sid)[0];
		
		\Base::instance()->set('UI', "template/epub/");
		$filename = realpath("tmp/epub")."/s{$epubData['sid']}.zip";

		/*
		This must be coming from admin panel at some point, right now we will fake it
		*/
		$epubData['version'] = 2;		// supported by most readers, v3 is still quite new
		$epubData['language'] = "de";
		$epubData['uuid']  = uuid_v5(
										uuid_v5
										(
											"6ba7b810-9dad-11d1-80b4-00c04fd430c8",
											(""==$this->config['epub_domain']) ? \Base::instance()->get('HOST').\Base::instance()->get('BASE') : $this->config['epub_domain']
										),
										$epubData['title']
									);
		
		\Base::instance()->set('EPUB', $epubData);

		$body = "";
		$re_element = array (
			"old"	=>	array ("<center>", "</center>"),
			"new"	=>	array ("<span style=\"text-align: center;\">", "</span>"),
		);
		$elements_allowed = "<a><abbr><acronym><applet><b><bdo><big><br><cite><code><del><dfn><em><i><img><ins><kbd><map><ns:svg><q><samp><small><span><strong><sub><sup><tt><var>";

		// The folder *should* exist, but creating it and ignoring the outcome is the quickest way of making sure it really is there
		@mkdir("tmp/epub",0777,TRUE);
		
		// Auto-detect TidyHTML class
		if ( TRUE === class_exists('tidy') )
		{
			$tidy = new \tidy();
			$tidyConfig = [
							//"doctype"		=> "omit",
							'output-xml'		=> true,
							'show-body-only'	=> true,
						];
		}

		/*
		Create the Archive
		Since the mimetype file has to be at the beginning of the archive and uncompressed, we have to create the zip file from binary
		*/
		file_put_contents($filename, base64_decode("UEsDBAoAAAAAAOmRAT1vYassFAAAABQAAAAIAAAAbWltZXR5cGVhcHBsaWNhdGlvbi9lcHViK3ppcFBLAQIUAAoAAAAAAOmRAT1vYassFAAAABQAAAAIAAAAAAAAAAAAIAAAAAAAAABtaW1ldHlwZVBLBQYAAAAAAQABADYAAAA6AAAAAAA="));

	  	$zip = new \ZipArchive;
		$res = $zip->open($filename);
		if ($res === TRUE)
		{
			// memorize the XML opening tag
			$xml = $this->template->epubXMLtag();

			// add folder for container file & META-INF/container.xml
			$zip->addEmptyDir('META-INF');
			$zip->addFromString('META-INF/container.xml', $xml.$this->template->epubContainer() );

			// add folders for content
			$zip->addEmptyDir('OEBPS');
			//$zip->addEmptyDir('OEBPS/Images');
			$zip->addEmptyDir('OEBPS/Styles');
			$zip->addEmptyDir('OEBPS/Text');
			
			// add style sheet
			$zip->addFromString('OEBPS/Styles/stylesheet.css', $this->template->epubCSS() );

		    // title.xhtml
	    	$zip->addFromString('OEBPS/Text/title.xhtml', 
											$xml.$this->template->epubPage(
															$this->template->epubTitle(),
															$epubData['title'],
															$epubData['language']
														)
											);

			// page[n].xhtml							| epub_page

			$chapters = $this->model->epubChapters( $epubData['sid'] );

			if(sizeof($chapters)>0)
			{
				$n = 1;
				foreach($chapters as $chapter)
				{
					$chapterText = $this->model->getChapterText( $epubData['sid'], $chapter['inorder'], FALSE );
					$chapterTOC[] = array ( "number" => $n, "title" => "{$chapter['title']}" );
					
					$body = $this->template->epubChapter(
															$chapter['title'],
															strip_tags(
	    														str_replace(
	    															$re_element['old'],
	    															$re_element['new'],
	    															$chapterText
	    														),
	    														$elements_allowed
		    												)
													);
					
					if ( isset($tidyConfig) )
					{
						$tidy->parseString($body, $tidyConfig, 'utf8');
						$tidy->cleanRepair();
						$body = $tidy->body();
					}
					
					$page = $this->template->epubPage(
															$body,
															$chapter['title'],
															$epubData['language']
														);

					

					$zip->addFromString('OEBPS/Text/chapter'.($n++).'.xhtml', 
											$xml.$page
											);

				}
			}
			else return [ FALSE, "__StoryError" ];

			// root.opf
		    $zip->addFromString('OEBPS/content.opf', $xml.$this->template->epubRoot( $chapterTOC ) );
			
			// TOC
			$zip->addFromString('OEBPS/toc.ncx', $xml.$this->template->epubTOC( $chapterTOC ) );

			if( $epubData['version']==3 )
				$zip->addFromString('OEBPS/toc.xhtml', $xml.$this->template->epubTOC( $chapterTOC, 3 ) );

			$zip->close();
		}
		return [ @fopen($filename,"rb"), filesize($filename) ];

	}
	
	public function series(array $params)//: void
	{
		
		return \View\Base::stub("Series");
	}

	public function search(\Base $f3, array $params)
	{
		$searchForm = strpos($params[0],"search");
		$get = [];
		if ( isset($params['*']) ) $get = $this->parametric($params['*']); // 3.6
		unset($get['page']);

		// get search data from $_POST
		$searchData = ($f3->get('POST'));
		// merge with the $_GET scope (which is basically either or, but that should settle it
		$searchData = array_filter(array_merge($get, $searchData));

		// get the available ratings
		if ( [] !== $ratings = $this->model->ratings() )
		{
			$f3->set('searchRatings', $ratings);
			$ratingMaxID = end($ratings)['rid'];
			// Add personal search preferences at some point
			$searchData['rating'][0] = min( (@$searchData['rating'][0] ?: 0), $ratingMaxID);

			// Add personal search preferences at some point
			$searchData['rating'][1] = min (
										max ( (@$searchData['rating'][1] ?: end($ratings)['rid']), $searchData['rating'][0] ),
										$ratingMaxID
										);
		}
		$this->template->addTitle($f3->get('LN__Search'));
		
		// Author
		if ( empty($searchData['author']) )
			$f3->set('prepopulateData.author',"[]");
		else
			$f3->set('prepopulateData.author', $this->model->searchPrepopulate( "author", implode(",",$this->searchCleanInput($searchData['author']) ) ) );

		// Category
		if ( empty($searchData['category']) )
			$f3->set('prepopulateData.category',"[]");
		else
			$f3->set('prepopulateData.category', $this->model->searchPrepopulate( "category", implode(",",$this->searchCleanInput($searchData['category']) ) ) );

		// Tag
		if ( empty($searchData['tagIn']) )
			$f3->set('prepopulateData.tagIn',"[]");
		else
			$f3->set('prepopulateData.tagIn', $this->model->searchPrepopulate( "tag", implode(",",$this->searchCleanInput($searchData['tagIn']) ) ) );

		// excluded Tag
		if ( empty($searchData['tagOut']) )
			$f3->set('prepopulateData.tagOut',"[]");
		else
			$f3->set('prepopulateData.tagOut', $this->model->searchPrepopulate( "tag", implode(",",$this->searchCleanInput($searchData['tagOut']) ) ) );

		// characters
		if ( empty($searchData['characters']) )
			$f3->set('prepopulateData.characters',"[]");
		else
			$f3->set('prepopulateData.characters', $this->model->searchPrepopulate( "characters", implode(",",$this->searchCleanInput($searchData['characters']) ) ) );

		// return string
		if ( sizeof($searchData)>0 )
		{
			foreach ( $searchData as $k => $v )
			{
				if ( is_array($v) )
					$return[] = "{$k}=".implode(",",$v);
				elseif ( $v > "" )
					$return[] = "{$k}={$v}";
			}
			$return = implode(";",$return);
			$data = $this->model->search( $searchData, $return, $searchForm );
			
			// Show a header, the view will select browse or search template
			$this->buffer ( $this->template->searchHead($searchData, $return, $searchForm) );

			// append the stories
			$this->buffer ( $this->template->viewList($data) );
		}

		else
			$this->buffer ( $this->template->searchHead() );
	}
	
	protected function searchCleanInput(&$arr=array()): array
	{
		$arr = is_array($arr) ? $arr : explode(",",$arr);
		foreach( $arr as &$a ) $a = (int)$a;
		$arr = array_diff($arr, array(0));
		return $arr;
	}

	protected function read(array $id)//: void
	{
		@list($story, $view, $selected) = explode(",",$id['*']);
		
		// do away with malformed requests right here
		if ( $story == "" OR !is_numeric($story) )
		{
			\Base::instance()->reroute("/story", false);
			exit;			
		}
		elseif($storyData = $this->model->getStory($story,empty($view)?1:$view))
		{
			if ( empty($view) AND $storyData['chapters']>1 )
			{
				if ( $_SESSION['userID']==0 )
					$view = (TRUE===\Config::getPublic('story_toc_default')) ? "toc" : 1;
				else
					$view = (TRUE==$_SESSION['preferences']['showTOC']) ? "toc" : 1;
			}

			if ( $view == "toc" )
			{
				$tocData = $this->model->getTOC($story);
				$content = $this->template->buildTOC($tocData,$storyData);
			}
			else
			{
				$tocData = $this->model->getMiniTOC($story);

				if( empty($view) OR !is_numeric($view) ) $view = 1;
				$chapter = $view = max ( 1, min ( $view, $storyData['chapters']) );
				\Base::instance()->set('bigscreen',TRUE);
				
				// Will return error on empty chapter text
				$content = ($content = $this->model->getChapterText( $story, $chapter )) ? : "Error (Load chaptertext)";
				// Will allow empty chapter
				//if ( FALSE === $content = $this->model->getChapterText( $story, $chapter ))
				//	$content = "Error (Load chaptertext)";

				$storyData['chapternr'] = $chapter;
			}

			$dropdown = $this->template->dropdown($tocData,$view);
			return $this->template->buildStory($storyData,$content,$dropdown,$view);
		}
		else return "__Error, not found";
	}
	
	protected function reviews(string $id)//: void
	{
		@list($story, $chapter, $selected) = explode(",",$id);

		if($storyData = $this->model->getStory($story,(int)$chapter))
		{
			$chapter = max ( 0, min ( $chapter, $storyData['chapters']) );
			$reviewData = $this->model->loadReviews($story,$selected,$storyData['chapid']);

			return $this->template->buildReviews($storyData, $reviewData, $chapter, $selected);
		}
		else return "__Error, not found";
	}
	
	public function storyBlocks(string $select): string
	{
		$select = explode(".",$select);

		if ( $select[0] == "stats" )
		{
			if ( FALSE === $data = \Cache::instance()->get('stats') )
			{
				$data = $this->model->blockStats();
				\Cache::instance()->set('stats', $data, 3600);
			}

			return $this->template->archiveStats($data);
		}
		elseif ( $select[0] == "new" )
		{
			$items = (isset($select[1]) AND is_numeric($select[1])) ? $select[1] : 5;
			$data = $this->model->blockNewStories($items);
			$size = isset($select[2]) ? $select[2] : 'large';
			
			return $this->template->blockStory("new", $data, $size);
		}
		elseif ( $select[0] == "random" )
		{
			$items = (isset($select[1]) AND is_numeric($select[1])) ? $select[1] : 1;
			$data = $this->model->blockRandomStory($items);
			
			return $this->template->blockStory("random", $data);
		}
		elseif ( $select[0] == "featured" )
		{
			/*
				$items: 0 = all featured stories
				$order: "random" or NULL
			*/
			$items = (isset($select[1]) AND is_numeric($select[1])) ? $select[1] : 1;
			$order = $select[2] ?? NULL;
			$data = $this->model->blockFeaturedStory($items,$order);
			
			return $this->template->blockStory("featured", $data);
		}
		elseif ( $select[0] == "recommend" )
		{
			// break if module not enabled
			if ( empty(\Config::getPublic('optional_modules')['recommendations']) ) return "";
			/*
				$items: 0 = all featured stories
				$order: "random" or NULL
			*/
			$items = (isset($select[1]) AND is_numeric($select[1])) ? $select[1] : 1;
			$order = $select[2] ?? NULL;
			
			$data = $this->model->blockRecommendedStory($items,$order);
			
			return $this->template->blockStory("recommended", $data);
		}
		elseif ( $select[0] == "tagcloud" )
		{
			// Get number of desired items from template call or set to maximum items from config
			$items = (isset($select[2]) AND is_numeric($select[2])) ? $select[2] : \Config::getPublic('tagcloud_elements');
			
			// if there is a minimum amount of items requested, make sure the template call does not request less items
			if ( 0 < \Config::getPublic('tagcloud_minimum_elements') )
				$items = max(\Config::getPublic('tagcloud_minimum_elements'),$items);
			
			$data = $this->model->blockTagcloud($items);

			// If size of data is below minimum element threshhold, don't bother building a tagcloud
			if ( sizeof($data)<\Config::getPublic('tagcloud_minimum_elements') ) return "";

			// Wow, we are really getting a tag cloud, all eyes to the sky
			return $this->template->blockTagcloud($data);
		}
		elseif ( $select[0] == "contest" )
		{
			// break if module not enabled
			if ( empty(\Config::getPublic('optional_modules')['contests']) ) return "";
			// get the data
			$data = $this->model->blockContests( ($select[1]??FALSE), ($select[2]??FALSE) );
			
			return $this->template->blockContests($data);
		}
		/*
		elseif ( $select[1] == "fame" )
		{
			// Check if there is data in the Cache hive
			if ( "" == $data = \Cache::instance()->get('fameData') )
			{
				// check if a TTL was provided
				if ( isset($select[2]) )
				{
					if( is_numeric($select[2]) )
						$time = 60 * $select[2];

					elseif ( $select[2]=="hour" )
						$time = 60 * ( 60 - (int)date("i") ) - (int)date("s");

					elseif ( $select[2]=="day" )
						$time = strtotime('tomorrow') - time();
				}

				// apply default TTL values 
				if ( empty($time) )
				{
					$time = "900"; // 15 minutes, that's the default amount of fame-time
					$select[2] = 15;
				}

				// Get a random story and make the data last for $time seconds
				$data = $this->model->blockRandomStory(1);
				\Cache::instance()->set('fameData', $data, $time);
				\Cache::instance()->set('fameTime', $data, $select[2]);
			}
			
			/*
				update the TTL if a change was made to the template
				this will not work if the template does not provide a TTL
				
				in this case, the TTL has to be calculated again, bloating this update
				skipping for now until finding a better solution
				
			if ( isset($select[2]) AND $select[2] != \Cache::instance()->get('fameTime') )
				\Cache::instance()->set('fameData', \Cache::instance()->get('fameTime') );
			

			return $this->template->blockStory("random", $data, $select[2]);
		}
		*/
		return "";
	}

	
}

function uuid_v5(string $namespace, string $name)//: ?string
{
  if(!uuid_validate($namespace)) return null;

  // Get hexadecimal components of namespace
  $nhex = str_replace(array('-','{','}'), '', $namespace);

  // Binary Value
  $nstr = '';

  // Convert Namespace UUID to bits
  for($i = 0; $i < strlen($nhex); $i+=2) {
    $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
  }

  // Calculate hash value
  $hash = sha1($nstr . $name);

  return sprintf('%08s-%04s-%04x-%04x-%12s',

    // 32 bits for "time_low"
    substr($hash, 0, 8),

    // 16 bits for "time_mid"
    substr($hash, 8, 4),

    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 5
    (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

    // 48 bits for "node"
    substr($hash, 20, 12)
  );
}

function uuid_validate(string $uuid): int
{
  return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.
                    '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
}
