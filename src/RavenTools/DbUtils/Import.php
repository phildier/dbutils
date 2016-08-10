<?php

namespace RavenTools\DbUtils;

class Import extends SqlObject {

	public function getSql() {

		switch(pathinfo($this->getPath(),PATHINFO_EXTENSION)) {
			case "sql":
				return parent::getSql();
			case "json":
				return $this->formatJson();
		}

		throw new \RuntimeException("unsupported file extension");
	}

	protected function formatJson() {

		$json = file_get_contents($this->getPath());

		if($json !== false) {

			$data = json_decode($json,true);

			if(is_null($data) || !is_array($data)) {
				throw new \RuntimeException("could not parse json");
			}

			$db = $this->getDb();
			$queries = null;

			foreach($data['rows'] as $row) {

				$columns = array_keys($row);
				$values = array_map(function($v) use ($db) {
					return $db->quote($v);
				},$row);

				$queries .= sprintf(
					"INSERT INTO `%s` (%s) VALUES (%s);\n",
					$data['table'],
					implode(",",$columns),
					implode(",",$values)
				);
			}

			return $queries;
		}

		throw new \RuntimeException("error reading file");
	}
}
