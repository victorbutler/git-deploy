<?php

class Database {

	/**
	 * @var  database  Singleton
	 */
	protected static $instance;

	/**
	 * Singleton static function
	 * @param   array  default configuration
	 * @return  Database
	 */
	public static function instance($config = array()) {
		if (!(self::$instance instanceof Database)) {
			self::$instance = new Database($config);
		}
		return self::$instance;
	}

	/**
	 * @var  array  default configuration
	 */
	protected $_config = array(
		'dsn'		=> 'sqlite:db/gitdeploy.db'
	);

	/**
	 * @var  PDO  database resource
	 */
	protected $_db;

	/**
	 * Constructor
	 * @param   array   configuration to override
	 */
	public function __construct($config = array()) {
		$this->_config = array_merge($this->_config, $config);
	}

	/**
	 * Lazy loading database resource
	 * @return  PDO
	 * @uses    PDO
	 */
	public function db() {
		if (!$this->_db) {
			try {
				$db = new PDO($this->_config['dsn']);
				$db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
				if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
					$stmt = $db->prepare('SELECT name FROM sqlite_master WHERE type="table" ORDER BY name');
					$result = $stmt->execute();
					if ($result && ($rows = $stmt->fetchAll(PDO::FETCH_ASSOC)) !== false) {
						if (count($rows) === 0) {
							$db->exec(file_get_contents('db/schema.sql'));
						}
					}
				}
				$this->_db = $db;
			} catch(PDOException $e) {
				halt("Connexion failed: ".$e); # raises an error / renders the error page and exit.
			}
		}
		return $this->_db;
	}

	/**
	 * Add a new repository to the database
	 * @param   string   name
	 * @return  mixed    integer of id on success, false on error
	 */
	public function add_repository($name, $hash, $location) {
		$sql = <<<SQL
		INSERT INTO `repositories` ("name", "hash", "location")
		VALUES (:name, :hash, :location)
SQL;
		$stmt = $this->db()->prepare($sql);
		$stmt->bindValue(':name', $name, PDO::PARAM_STR);
		$stmt->bindValue(':hash', $hash, PDO::PARAM_STR);
		$stmt->bindValue(':location', $location, PDO::PARAM_STR);
		if ($stmt->execute()) {
			return $this->db()->lastInsertId();
		}
		return false;
	}

	/**
	 * Add a new project into the database
	 * @param   string   repository id
	 * @param   string   name
	 * @param   string   branch
	 * @param   string   destination
	 * @return  mixed    integer of id on success, false on error
	 */
	public function add_project($repository_id, $name, $branch, $destination) {
		$sql = <<<SQL
		INSERT INTO `projects` ("name", "branch", "destination", "repository_id")
		VALUES (:name, :branch, :destination, :repository_id)
SQL;
		$stmt = $this->db()->prepare($sql);
		$stmt->bindValue(':name', $name, PDO::PARAM_STR);
		$stmt->bindValue(':branch', $branch, PDO::PARAM_STR);
		$stmt->bindValue(':destination', $destination, PDO::PARAM_STR);
		$stmt->bindValue(':repository_id', $repository_id, PDO::PARAM_INT);
		if ($stmt->execute()) {
			return $this->db()->lastInsertId();
		}
		return false;
	}

	/**
	 * Search the database for one result
	 * @param   array   columns to look for
	 * @param   array   associative array of values to look for
	 * @param   int     offset
	 * @return  mixed   object on success, false on error
	 */
	public function find_one($table, $columns = array(), $values = array(), $offset = 0) {
		return $this->find($table, $columns, $values, 1, $offset);
	}

	/**
	 * Search the database
	 * @param   array   columns to look for
	 * @param   array   associative array of values to look for
	 * @param   int     limit
	 * @param   int     offset
	 * @return  mixed   array (or object when limit = 1) on success, false on error
	 */
	public function find($table, $columns = array(), $values = array(), $limit = -1, $offset = 0) {
		$sql = 'SELECT * FROM `'.$table.'` ';
		if (count($columns) && count($values)) {
			$sql .= 'WHERE ';
		}
		foreach ($columns as $name) {
			$sql .= $name.'=:'.$name.' ';
		}
		$sql .= 'ORDER BY id ASC LIMIT '.$offset.', '.$limit;
		$stmt = $this->db()->prepare($sql);
		foreach ($values as $name => $value) {
			$stmt->bindValue(':'.$name, $value, PDO::PARAM_STR);
		}
		if ($limit === 1 && $stmt->execute() && ($row = $stmt->fetch(PDO::FETCH_OBJ)) !== false) {
			return $row;
		}
		if ($stmt->execute() && ($rows = $stmt->fetchAll(PDO::FETCH_OBJ)) !== false) {
			return $rows;
		}
		return false;
	}

	/**
	 * Function Description
	 * @param   string   description
	 * @return  boolean
	 * @uses    Class::method
	 */
	public function update_deploy($project_id) {
		$sql = 'UPDATE `projects` SET last_deployed='.time().' WHERE id='.$project_id;
		$stmt = $this->db()->prepare($sql);
		if ($stmt->execute()) {
			return $this->db()->lastInsertId();
		}
		return false;
	}

}