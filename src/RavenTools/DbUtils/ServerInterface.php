<?php

namespace RavenTools\DbUtils;

interface ServerInterface {

	/**
	 * start the server daemon
	 */
	public function start();

	/**
	 * stop the server daemon
	 */
	public function stop();
}
