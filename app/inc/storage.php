<?php
/**
	storage.php - just a little DB Object creator

	The contents of this file are subject to the terms of the GNU General
	Public License Version 3.0. You may not use this file except in
	compliance with the license. Any of the license terms and conditions
	can be waived if you get permission from the copyright holder.

	Copyright (c) 2013 ~ ikkez
	Christian Knuth <ikkez0n3@gmail.com>

		@version 0.2.0
		@date: 31.10.13
 **/

class storage extends Prefab {

	public function build() {
		//$cfg = Config::instance();
		$type = strtoupper(Config::getProtected('ACTIVE_DB'));
		switch ($type) {
				case 'MYSQL':
				\Base::instance()->set('DBType', 'MySQL');
				$db = new \DB\SQL(Config::getProtected('DB_MYSQL')['dsn'], Config::getProtected('DB_MYSQL')['user'], Config::getProtected('DB_MYSQL')['password']);
				break;
/*
			case 'PGSQL':
				$db = new \DB\SQL('pgsql:host='.$cfg->DB_PGSQL['host'].
					';dbname='.$cfg->DB_PGSQL['dbname'],
					$cfg->DB_PGSQL['user'], $cfg->DB_PGSQL['password']);
				break;
			case 'SQLSRV':
				$db = new \DB\SQL('sqlsrv:SERVER='.$cfg->DB_SQLSRV['host'].
					';Database='.$cfg->DB_SQLSRV['dbname'],
					$cfg->DB_SQLSRV['user'], $cfg->DB_SQLSRV['password']);
				break;
			case 'MONGO':
				$db = new \DB\Mongo('mongodb://'.$cfg->DB_MONGO['host'].':'.
					$cfg->DB_MONGO['port'],$cfg->DB_MONGO['dbname']);
				break;
				*/
		}
		return isset($db) ? $db : false;
	}
	
	public function localChapterDB()
	{
		if ( $db = new \DB\SQL('sqlite:data/chapters.sq3') )
		{
			$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $db;
		}
		else return FALSE;
	}
/*
	public function update($type,$conf) {
		$cfg = Config::instance();
		$cfg->set('DB_'.strtoupper($type), $conf);
		$cfg->save();
	}
	*/
}
