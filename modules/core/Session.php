<?php

class Session {
	private static $storage = array();
	
	/**
	 * Retrieve stored object by it's name
	 * @param string $name Name of the stored object
	 * @param mixed
	 */
	public static function &get($name) {
		return self::$storage[$name];
	}

	/**
	 * Set stored object's value by name.\
	 * If object with given name doesn't exist it will be created.
	 * @param string $name Name for the stored object
	 * @param mixed $value Stored object
	 */
	public static function set($name, $value) {
		self::$storage[$name] = $value;
	}

	/**
	 * Remove object by it's name
	 * @param string $name Name of the stored object
	 */
	public static function unset($name) {
		unset(self::$storage[$name]);
	}

	/**
	 * Test if storage has object with specific name
	 * @param string $name Name of the stored object
	 * @return bool
	 */
	public static function has($name) {
		return array_key_exists($name, self::$storage);
	}

	/**
	 * Retrieve stored objects for this session (Luxon calls this method automatically so you don't have to)
	 */
	public static function retrieve() {
		if (isset($_SESSION['LUXION_SESSION'])) {
			$storage = unserialize($_SESSION['LUXION_SESSION']);
			if (!is_array($storage)) throw new Exception();
			self::$storage = $storage;
		}
	}

	/**
	 * Commits your changes so that they're available next time you call Session::retrieve()
	 */
	public static function commit() {
		$_SESSION['LUXION_SESSION'] = serialize(self::$storage);
	}

	/**
	 * Starts a new session
	 */
	public static function start() {
		if (session_status() !== PHP_SESSION_ACTIVE) session_start();
	}

	/**
	 * Ends the session and tell client to remove session cookie
	 */
	public static function end() {
		if (session_status() === PHP_SESSION_ACTIVE) {
			if ($session_id = session_name()) {
				session_destroy();
				setcookie($session_id, "", time() - 3600, "/");
			}
		}
	}

	/**
	 * Ends a session and then starts a new one
	 */
	public static function restart() {
		self::end();
		self::start();
	}
}

Session::start();
Session::retrieve();