<?php

require_once('glip.php');

/**
 * Handles common processes necessary for processing and procuring data
 */

class GitDeploy {

	// Static

	public static $instance;
	
	/**
	 * Singleton
	 * @param   array   configuration to override
	 * @return  GitDeploy
	 */
	public static function instance($config = array()) {
		if (!(self::$instance instanceof GitDeploy)) {
			self::$instance = new GitDeploy($config);
		}
		return self::$instance;
	}

	// Object

	/**
	 * @var  array  default configuration
	 */
	protected $_config = array(
		'repo_root' => '../',
		'git_bin'   => '/usr/local/git/bin/git'
	);

	/**
	 * TODO: move this into permanent storage
	 * @var  array  description
	 */
	protected $_my_repositories = array(
		'30d8f1efbf5d3752f19c04a77a12c7f4' => array('repository' => 'tiaa-iwc'),
		'132555c79781177e0670ffd3da57a442' => array('repository' => 'tiaa-iwc-mutual-funds'),
		'f9035fba50904c22b98d725a4e8342b9' => array('repository' => 'tiaa-ifa-bt2')
	);

	/**
	 * TODO: move this into permanent storage
	 * @var  array  description
	 */
	protected $_my_projects = array(
		array('repository' => '30d8f1efbf5d3752f19c04a77a12c7f4', 'branch' => 'master', 'name' => 'TIAA IWC', 'destination' => '../deploy/tiaa-iwc'),
		array('repository' => '132555c79781177e0670ffd3da57a442', 'branch' => 'master', 'name' => 'TIAA IWC - Mutual Funds', 'destination' => '../deploy/tiaa-iwc-mutual-funds-master'),
		array('repository' => '132555c79781177e0670ffd3da57a442', 'branch' => 'master', 'name' => 'TIAA IWC - Mutual Funds Sprint 2', 'branch' => 'sprint2', 'destination' => '../deploy/tiaa-iwc-sprint2'),
		array('repository' => 'f9035fba50904c22b98d725a4e8342b9', 'branch' => 'master', 'name' => 'TIAA IFA - Bulk Trade', 'destination' => '../deploy/tiaa-ifa-bulk-trade')
	);

	/**
	 * @var  array  repository object storage
	 */
	protected $_repositories;

	/**
	 * Set up environment
	 * @param   array   configuration
	 */
	public function __construct($config = array()) {
		$this->_config = array_merge($this->_config, $config);
	}
	
	/**
	 * Finds the last commit in the repository
	 * @param   mixed    repository name or Git object
	 * @param   string   branch name (default: master)
	 * @return  mixed    boolean false or GitCommit object
	 * @uses    Git, GitCommit
	 */
	public function latest_commit($repository, $branch = 'master') {
		if (!($repository instanceof Git)) {
			$repository = $this->get_repository($repository);
		}
		if ($repository) {
			$branch_name = $repository->getTip($branch);
			$last_commit = $repository->getObject($branch_name);
			return $last_commit;
		}
		return false;
	}

	/**
	 * Function Description
	 * @param   string   description
	 * @return  boolean
	 * @uses    Class::method
	 */
	public function get_projects() {
		return $this->_my_projects;
	}

	/**
	 * Spit out any repositories we found (lazy loading)
	 * @return  array
	 */
	public function get_repositories() {
		if (!is_array($this->_repositories)) {
			$repos = array();
			foreach ($this->_my_repositories as $hash => $data) {
				$repos[$hash] = new Git((is_dir($this->_config['repo_root'].$data['repository'].'/.git') ? $this->_config['repo_root'].$data['repository'].'/.git' : $this->_config['repo_root'].$data['repository']));
				$repos[$hash]->name = $data['repository'];
			}
			$this->_repositories = $repos;
		}
		return $this->_repositories;
	}

	/**
	 * Get single repository
	 * @param   string   repository name
	 * @return  mixed    boolean false or Git object
	 */
	public function get_repository($repository) {
		if (array_key_exists(md5($repository), $this->get_repositories())) {
			return $this->_repositories[$repository];
		}
		return false;
	}

	/**
	 * Get single repository from repo hash
	 * @param   string   repository name
	 * @return  mixed    boolean false or Git object
	 */
	public function get_repository_by_hash($repository) {
		if (array_key_exists($repository, $this->get_repositories())) {
			return $this->_repositories[$repository];
		}
		return false;
	}

	/**
	 * Updated serverside copy of repository
	 * @param   mixed   repository name or Git object
	 * @return  boolean
	 * @throws  Exception
	 */
	public function pull($repository, $branch = 'master') {
		if (array_key_exists($repository, $this->get_repositories())) {
			$repository = $this->_repositories[$repository];
		}

		if ($repository instanceof Git) {
			$command = 'cd '.realpath($this->_config['repo_root'].$repository->name).' && '.$this->_config['git_bin'].' checkout '.escapeshellarg($branch).' && '.$this->_config['git_bin'].' pull origin '.escapeshellarg($branch);
			$result = shell_exec($command);
			if ($result === NULL) {
				throw new Exception('Problem performing git pull on '.$repository->name.' Command: '.$command);
			}
			return true;
		}
		return false;
	}

	/**
	 * Function Description
	 * @param   string   description
	 * @return  boolean
	 * @uses    Class::method
	 */
	public function clonerepo($repository) {
		
	}

}