<?php

$this->modules = 
					[
					/*
						Namespace
						function
						(bool) static
					*/
						"calendar"		=> [ "\\View\Blocks", "calendarInit", TRUE ],
						"shoutbox"	=> [ "\\View\Blocks", "shoutboxInit", TRUE ],
						"menu"			=> [ "\\Controller\Blocks", "buildMenu" ],
						"tagcloud"		=> [ "\\Controller\Blocks", "tagcloud" ],
						"story"			=> [ "\\Controller\Story", "storyBlocks" ],
						"news"			=> [ "\\Controller\News", "blocks" ],
						"categories"	=> [ "\\Controller\Blocks", "categories" ],
					];


?>