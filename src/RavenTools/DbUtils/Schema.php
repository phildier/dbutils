<?php

namespace RavenTools\DbUtils;

use RavenTools\DbUtils\Table;

class Schema {

	private $path = null;
	private $db = null;

	private $tables = [];
	private $procedures = [];
	private $imports = [];

	private $table_params = null;

	// create tables by default
	private $do_tables = true;

	// create procedures by default
	private $do_procedures = true;

	// run data imports by default
	private $do_imports = true;

	public function __construct($params = []) {

		if(array_key_exists('path',$params) && file_exists($params['path'])) {
			$this->setPath($params['path']);
		} else {
			throw new \InvalidArgumentException("a valid path is required");
		}

		if(array_key_exists('db',$params) && is_object($params['db'])) {
			$this->setDb($params['db']);
		} else {
			throw new \InvalidArgumentException("a db object is required");
		}

		if(array_key_exists('table_params',$params) && is_array($params['table_params'])) {
			$this->setTableParams($params['table_params']);
		} 

		if(array_key_exists('do_tables',$params) && is_bool($params['do_tables'])) {
			$this->do_tables = $params['do_tables'];
		} 

		if(array_key_exists('do_procedures',$params) && is_bool($params['do_procedures'])) {
			$this->do_procedures = $params['do_procedures'];
		} 

		if(array_key_exists('do_imports',$params) && is_bool($params['do_imports'])) {
			$this->do_imports = $params['do_imports'];
		} 
	}

	public function setTableParams($params) {
		$this->table_params = $params;
	}

	public function getPath() {
		return realpath($this->path);
	}
	
	public function setPath($path) {
		if(file_exists($path)) {
			$this->path = $path;
		} else {
			throw new \InvalidArgumentException(sprintf("%s does not exist",$path));
		}
	}

	public function getDb() {
		return $this->db;
	}

	public function setDb($db) {
		$this->db = $db;
	}

	public function getProcedures() {

		if(empty($this->procedures) && $this->do_procedures === true) {
			$this->loadProcedures();
		}

		return $this->procedures;
	}

	public function getTables() {

		if(empty($this->tables) && $this->do_tables === true) {
			$this->loadTables();
		}

		return $this->tables;
	}

	public function getImports() {

		if(empty($this->imports) && $this->do_imports === true) {
			$this->loadImports();
		}

		return $this->imports;
	}

	/**
	 * lint all schema files by importing them into a temporary db instance
	 */
	public function lint() {

		$test_db = uniqid("test_");

		$db = $this->getDb();

		$db->query(sprintf("CREATE DATABASE %s",$test_db));
		$db->query(sprintf("USE %s",$test_db));

		$objects = array_merge(
			$this->getProcedures(),
			$this->getTables(),
			$this->getImports()
		);

		$error = false;

		foreach($objects as $object) {
			$response = $db->exec($object->getSql());
			if($response === false) {
				printf("%s syntax ERROR %s (%s)\n",get_class($object),$object->getName(),$db->errorInfo()[2]);
				$error = true;
			} else {
				printf("%s syntax OK %s\n",get_class($object),$object->getName());
			}
		}

		return !$error;
	}

	/**
	 * create and initialize schema with test data
	 */
	public function create() {

		$objects = array_merge(
			$this->getProcedures(),
			$this->getTables(),
			$this->getImports()
		);

		foreach($objects as $object) {
			printf("creating: %s %s\n",get_class($object),$object->getName());
			$response = $this->getDb()->exec($object->getSql());
		}
	}

	private function loadTables() {
		$this->tables = $this->loadSql('Table','tables.json',$this->table_params);
	}

	private function loadProcedures() {
		$this->procedures = $this->loadSql('Procedure','procedures.json');
	}

	private function loadImports() {
		$this->imports = $this->loadSql('Import','imports.json');
	}

	private function loadSql($class,$metadata,$params=[]) {

		$path = sprintf("%s/%s",$this->getPath(),$metadata);

		$objects = json_decode(file_get_contents($path));

		if(is_null($objects)) {
			throw new \RuntimeException(sprintf("could not load %s metadata from %s",$class,$path));
		}

		$ret = [];
		
		$params['db'] = $this->getDb();

		foreach($objects as $object) {

			$name = preg_replace("#^{$class}-(.*)\.sql$#i",'$1',$object);
			$full_class = sprintf('RavenTools\\DbUtils\\%s',$class);
			$params['name'] = $name;
			$params['path'] = sprintf("%s/%s",$this->getPath(),$object);
			$ret[$name] = new $full_class($params);
		}

		return $ret;
	}
}
