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
		$this->getDb()->query(sprintf("TRUNCATE TABLE `%s`",$this->getName()));
	}
}
