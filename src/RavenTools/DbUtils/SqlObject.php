<?php

namespace RavenTools\DbUtils;

class SqlObject {

	private $name = null;
	private $path = null;
	private $db = null;

	public function __construct($params = []) {

		if(array_key_exists('name',$params) && !empty($params['name'])) {
			$this->setName($params['name']);
		} else {
			throw new \RuntimeException("name is required");
		}
	
		if(array_key_exists('path',$params) && file_exists($params['path'])) {
			$this->setPath($params['path']);
		} else {
			throw new \RuntimeException("a valid path is required");
		}
	
		if(array_key_exists('db',$params) && is_object($params['db'])) {
			$this->setDb($params['db']);
		} else {
			throw new \RuntimeException("a valid db object is required");
		}
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function getName() {
		return $this->name;
	}

	public function setPath($path) {
		$this->path = $path;
	}

	public function getPath() {
		return $this->path;
	}

	public function setDb($db) {
		$this->db = $db;
	}

	public function getDb() {
		return $this->db;
	}

	public function getSql() {
		return file_get_contents($this->getPath());
	}
}
