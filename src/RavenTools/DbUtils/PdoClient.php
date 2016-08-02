<?php

namespace RavenTools\DbUtils;

use PDO;

class PdoClient {

	private static $connections = [];

	public static function set($name,$connection = null) {
		self::$connections[$name] = $connection;
	}

	public static function get($params = []) {

		$name = "default";
		if(array_key_exists('name',$params) && !empty($params['name'])) {
			$name = $params['name'];
		}

		$dbname = null;
		if(array_key_exists('dbname',$params) && !empty($params['dbname'])) {
			$dbname = $params['dbname'];
		}

		$host = "127.0.0.1";
		if(array_key_exists('host',$params) && !empty($params['host'])) {
			$host = $params['host'];
		}

		$port = 3306;
		if(array_key_exists('port',$params) && !empty($params['port'])) {
			$port = $params['port'];
		}

		$user = "root";
		if(array_key_exists('user',$params) && !empty($params['user'])) {
			$user = $params['user'];
		}

		$password = "";
		if(array_key_exists('password',$params) && !empty($params['password'])) {
			$password = $params['password'];
		}

		if(!array_key_exists($name,self::$connections) || !is_object(self::$connections[$name])) {
			$dsn = sprintf("mysql:host=%s;port=%s;dbname=%s",$host,$port,$dbname);
			self::$connections[$name] = new PDO($dsn,$user,$password);
		}

		return self::$connections[$name];
	}
}
