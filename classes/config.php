<?php

require_once('database.php');

/**
 * Handles common processes necessary for processing and procuring data
 */

class Config {

	// Static

	protected static $instance;
	
	/**
	 * Singleton
	 * @param   array   configuration to override
	 * @return  Config
	 */
	public static function instance() {
		if (!(self::$instance instanceof Config)) {
			self::$instance = new Config();
		}
		return self::$instance;
	}

	// Object

	/**
	 * @var  array  config cache
	 */
	protected $_cache = array();

	/**
	 * Get cache item
	 * @param   string   config key
	 * @return  string
	 * @uses    Database::find
	 */
	public function get($name) {
		if (array_key_exists($name, $this->_cache)) {
			return $this->_cache[$name];
		}
		$result = Database::instance()->find_one('config', array('key'), array('key' => $name));
		if ($result === false) {
			return null;
		}
		$this->_cache[$name] = $result->value;
		return $result->value;
	}

	/**
	 * Set item and reset cache
	 * @param   string   config key
	 * @param   string   config value
	 * @return  string
	 * @uses    Database::find_one
	 */
	public function set($name, $value) {
		$result = Database::instance()->find_one('config', array('key'), array('key' => $name));
		if ($result === false) {
			return null;
		}
		$result = Database::instance()->update('config', $name, $value, 'id='.$result->id);
		if ($result === false) {
			return null;
		}
		$this->_cache[$name] = $value;
		return $value;
	}

}