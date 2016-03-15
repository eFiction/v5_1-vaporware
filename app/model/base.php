<?php
namespace Model;

class Base extends \Prefab {

	// persistence settings
	protected $table, $db, $fieldConf, $sqlTmp;

	public function __construct()
	{
		$this->i = 0;
		$this->db = \Base::instance()->get('DB');
		$this->prefix = \Config::instance()->prefix;
		$this->config = \Base::instance()->get('CONFIG');
	}
	
	public function exec($cmds,$args=NULL,$ttl=0,$log=TRUE)
	{
		return $this->db->exec(str_replace("`tbl_", "`{$this->prefix}", $cmds), $args,$ttl,$log);
	}
	
	protected function prepare($id, $sql)
	{
		$this->sqlTmp[$id]['sql'] = $sql;
		$this->sqlTmp[$id]['param'] = [];
	}
	
	protected function bindValue($id, $label, $value, $type)
	{
		$this->sqlTmp[$id]['param'] = array_merge( $this->sqlTmp[$id]['param'], [ $label => $value ] );
	}
	
	protected function execute($id)
	{
		return $this->exec($this->sqlTmp[$id]['sql'], $this->sqlTmp[$id]['param']);
	}
	
	protected function paginate(int $total, $route, int $limit=10)
	{
		/**
			Implementing parts of the

			Pagination class for the PHP Fat-Free Framework
			Copyright (c) 2012 by ikkez
			Christian Knuth <mail@ikkez.de>
			@version 1.4.1
			
			found at: https://github.com/ikkez/F3-Sugar/blob/master-v3/Pagination/pagination.php
		**/
		$f3 = \Base::instance();
		
		// Define a prefix
		$prefix = "/page=";
		
		// Get max page number
		$count = ceil($total/$limit);
		if ($count<2) return TRUE;
		// Current page should be at least 1, and $count at max
		$page = (int)min(max(1,$f3->get('paginate.page')),$count);

		// if the page number was too big, reroute to the highest page number
		if ( $f3->get('paginate.page') > $page )
		{
			$f3->reroute("{$route}{$prefix}{$page}", false);
			exit;
		}

		// really needed? must check
		$pos = (int)max(0,min($page-1,$count-1));
		
		// page link range, from config
		$range = $this->config['adjacent_paginations'];
		// build range link array
		$current_range = array( ($page-$range < 1 ? 1 : $page-$range),
            ($page+$range > $count ? $count : $page+$range));
        $rangeIDs = array();
        for($x = $current_range[0]; $x <= $current_range[1]; ++$x) {
            $rangeIDs[] = $x;
		}

		// add data to the global scope
		$f3->set('paginate',
		[
			'total' => $total, // Elements
			'limit' => $limit, // per page
			'count' => $count, // pages
			'pos'   => $pos, // current position
			'page'	=> $page,
			'route' => $route,
			'prefix' => $prefix,
			'firstPage' => ($page > 3) ? 1 : false,
			'lastPage'  => ( ($pos+3) < $count ) ? 1 : false,
			'rangePages' => $rangeIDs,
		]);
	}

	protected function panelMenu($selected=FALSE, $admin=FALSE)
	{
		$sql = "SELECT M.label, M.link, M.icon, M.evaluate FROM ";
		if ( $admin )
		{
			if ( $selected )
				$sql .= "`tbl_menu_adminpanel`M WHERE `child_of` = :selected ORDER BY M.child_of,M.order ASC";
			else
				$sql .= "`tbl_menu_adminpanel`M WHERE `child_of` IS NULL ORDER BY M.child_of,M.order ASC";
		}
		else
		{
			if ( $selected )
				$sql .= "`tbl_menu_userpanel`M WHERE M.child_of = :selected;";
			else
				$sql .= "`tbl_menu_userpanel`M WHERE M.child_of IS NULL;";
		}
		$data = $this->exec($sql, ["selected"=> $selected]);
		foreach ( $data as $item )
		{
			$menu[$item["link"]] = [ "label" => $item["label"], "icon" => $item["icon"] ];
		}
		return $menu;
	}
	
	//protected function update()
}
