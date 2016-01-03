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
					];


?>