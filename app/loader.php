<?php

$this->modules = 
					[
					/*
						Namespace
						function
						[(bool) static = FALSE]
					*/
						"calendar"		=> [ "\\View\Blocks", "calendarInit", TRUE ],
						"shoutbox"		=> [ "\\View\Blocks", "shoutboxInit", TRUE ],
						"menu"			=> [ "\\Controller\Blocks", "buildMenu" ],
						"tagcloud"		=> [ "\\Controller\Blocks", "tagcloud" ],
						"story"			=> [ "\\Controller\Story", "storyBlocks" ],
						"home"			=> [ "\\Controller\Home", "blocks" ],
						"categories"	=> [ "\\Controller\Blocks", "categories" ],
						"profile"		=> [ "\\Controller\Blocks", "authorProfile" ],
					];


?>