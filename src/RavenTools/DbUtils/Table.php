<?php

namespace RavenTools\DbUtils;

class Table extends SqlObject {

	public function __construct($params) {

		parent::__construct($params);

		if(array_key_exists("truncate",$params) && $params["truncate"] === true) {
			$this->truncate();
		}
	}

	private function truncate() {

		$db = $this->getDb();
		$name = $this->getName();

		// if table exists, truncate it
		if($db->query(sprintf("SHOW TABLES LIKE '%s'",$name))->rowCount() > 0) {
			$db->query(sprintf("TRUNCATE TABLE `%s`",$name));
		}
	}
}
