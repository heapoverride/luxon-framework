<?php

class Storage {
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
}