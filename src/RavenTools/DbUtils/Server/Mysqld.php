<?php

namespace RavenTools\DbUtils\Server;

use RavenTools\DbUtils\ServerInterface;

class Mysqld implements ServerInterface {

	public static $start_port = 3307;
	public static $end_port = 3399;
	public static $temporary_directory_prefix = "/tmp";

	private $listen_port = null;
	private $temporary_directory = null;
	private $mysqld_handle = null;

	public function __construct($params = []) {
	}

	public function __destruct() {

		$this->stop();
	}

	public function start() {

		$this->listen_port = $this->getFreePort();
		$this->temporary_directory = $this->createTemporaryDirectory();
		$this->initializeDatabase();
		$this->startMysqld();

		return $this->listen_port;
	}

	public function stop() {

		if(!is_null($this->mysqld_handle)) {
			proc_terminate($this->mysqld_handle,9);
		}

		if(file_exists($this->temporary_directory) && !is_null($this->temporary_directory) && $this->temporary_directory != "/") {
			$cmd = sprintf("rm -rf %s",escapeshellarg($this->temporary_directory));
			exec($cmd,$output,$retval);
		}
	}

	public function getTemporaryDirectory() {
		return $this->temporary_directory;
	}

	/**
	 * finds an open port on which to listen
	 */
	private function getFreePort() {

		do {
			$try = rand(self::$start_port,self::$end_port);
			$cmd = sprintf("netstat -ln | grep ':%s '",$try);
			exec($cmd,$out,$retval);
		} while($retval === 0 && $tries++ < 10);

		if($retval === 0) {
			throw new \RuntimeException(sprintf("failed to find a free listen port after %s attempts",$tries));
		}

		return $try;
	}


	/**
	 * creates a new temporary directory to contain temporary database
	 */
	private function createTemporaryDirectory() {

		do {
			$path = sprintf("%s/%s",self::$temporary_directory_prefix,uniqid("mysqld-"));
		} while(file_exists($path) && $tries++ < 10);

		if(file_exists($path)) {
			throw new \RuntimeException(sprintf("failed to create a temporary directory after %s attempts",$tries));
		}

		if(mkdir($path)) {
			chmod($path,0777);
			if(posix_getuid() === 0) {
				chown($path,"mysql");
			}
			return $path;
		}

		return null;
	}

	/**
	 * initializes the temporary database directory with a blank database
	 */
	private function initializeDatabase() {

		$cmd = "which mysql_install_db";
		exec($cmd,$output,$retval);
		if($retval != 0) {
			throw new \RuntimeException("can't find mysql_install_db.  perhaps you need to install a mysql server?");
		}

		$install_db_path = $output[0];

		$user = null;
		if(posix_getuid() === 0) {
			$user = "--user=mysql";
		}

		$cmd = sprintf("%s %s --datadir=%s 2> /dev/null 1> /dev/null",$install_db_path,$user,$this->temporary_directory);
		exec($cmd,$output,$retval);
		if($retval != 0) {
			throw new \RuntimeException("failed to initialize test database");
		}

		return true;
	}

	/**
	 * starts mysql daemon using given port and data path
	 */
	private function startMysqld() {

		$port = $this->listen_port;
		$path = $this->temporary_directory;

		putenv('TZ=US/Eastern');

		$cmd = [
			"exec",
			$this->getMysqldPath(),
			(posix_getuid() === 0 ? "--user=mysql" : ""),
			"--datadir={$path}",
			"--socket={$path}/mysql.sock",
			"--pid-file={$path}/mysqld.pid",
			"--basedir=/usr",
			"--port={$port}",
			"--log-error={$path}/mysqld.log",
			"--innodb",
			"--innodb-log-file-size=5242880"
		];

		$pipes = [];
		$this->mysqld_handle = proc_open(
			implode(" ",$cmd),
			[["pipe","r"],["pipe","w"],["pipe","w"]],
			$pipes,
			$path // working dir
		);

		stream_set_blocking($pipes[1],0);

		if($this->mysqld_handle === false) {
			throw new \Exception("failed starting mysqld");
		}

		$this->mysqld_pid = proc_get_status($this->mysqld_handle);

		// give the daemon a second to start
		sleep(5);

		return true;
	}

	private function getMysqldPath() {

		$paths = [
			"/usr/libexec/mysqld",
			"/usr/sbin/mysqld"
		];

		foreach($paths as $path) {
			if(file_exists($path)) {
				return $path;
			}
		}

		throw new \RuntimeException("could not find mysqld binary");
	}

}
