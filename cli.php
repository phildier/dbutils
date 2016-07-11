<?php

require_once("vendor/autoload.php");

use RavenTools\DbUtils\Schema;
use RavenTools\DbUtils\Server\Mysqld;
use RavenTools\DbUtils\PdoClient;

$command = $argv[1];

switch($command) {
	case "startdb":
		$db = new Mysqld;
		$db->start();
		break;

	case "lint":
		$path = $argv[2];

		// temporary mysqld for the schema lint
		$server = new Mysqld;
		$port = $server->start();

        $db = PdoClient::get([
            'host' => '127.0.0.1',
            'port' => $port,
            'user' => 'root'
        ]);

		$schema = new Schema([
			'db' => $db,
			'path' => $path
		]);

		if($schema->lint() === false) {
			echo "lint failed\n";
			exit(1);
		}

		break;

	case "bootstrap":

		$path = $argv[2];
		$dsn = $argv[3];

		if(empty($dsn)) {
			echo "dsn argument required\n";
			exit(1);
		}
		$config = json_decode($dsn,true);

		$create_db = PdoClient::get([
			'name' => "create_db",
			'host' => $config['host'],
			'port' => $config['port'],
			'user' => $config['user'],
			'password' => $config['password']
		]);
		$create_db->exec(sprintf(
			"DROP DATABASE `%s`; CREATE DATABASE `%s`",
			$config['dbname'],
			$config['dbname']
		));

        $db = PdoClient::get($config);

		$schema = new Schema([
			'db' => $db,
			'path' => $path
		]);

		$schema->create();

		break;
}
