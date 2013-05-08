<?php

require_once('glip.php');
require_once('database.php');

/**
 * Handles common processes necessary for processing and procuring data
 */

class GitDeploy {

	// Static

	protected static $instance;
	
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
		'repo_root' => 'repositories/',
		'git_bin'   => '/usr/local/bin/git',
		'rsync_bin' => '/usr/bin/rsync'
	);

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
	public function latest_commit($project_obj_or_id) {
		if (!is_object($project_obj_or_id)) {
			$project_obj_or_id = $this->get_project($project_obj_or_id);
		}
		$repository = $this->get_repository($project_obj_or_id->repository_id);
		if ($repository && ($git = new Git($repository->location.'/.git'))) {
			$branch_name = $git->getTip($project_obj_or_id->branch);
			$last_commit = $git->getObject($branch_name);
			return $last_commit;
		}
		return false;
	}

	/**
	 * Get all projects
	 * @return  mixed    array on success, false on failure
	 */
	public function get_projects() {
		return Database::instance()->find('projects');
	}

	/**
	 * Get all projects from a specific repository
	 * @param	int		 repository id
	 * @return  mixed    array on success, false on failure
	 */
	public function get_projects_by_repository($id) {
		return Database::instance()->find('projects', array('repository_id'), array('repository_id' => $id));
	}

	/**
	 * Get single project
	 * @param   int      project id
	 * @return  mixed    boolean false or object
	 */
	public function get_project($id) {
		return Database::instance()->find_one('projects', array('id'), array('id' => $id));
	}

	/**
	 * Spit out any repositories we found (lazy loading)
	 * @return  array
	 */
	public function get_repositories() {
		return Database::instance()->find('repositories');
	}

	/**
	 * Get single repository
	 * @param   int      repository id
	 * @return  mixed    boolean false or object
	 */
	public function get_repository($id) {
		return Database::instance()->find_one('repositories', array('id'), array('id' => $id));
	}

	/**
	 * Get single repository from repo hash
	 * @param   string   repository name
	 * @return  mixed    boolean false or Git object
	 */
	public function get_repository_by_hash($hash) {
		return Database::instance()->find_one('repositories', array('hash'), array('hash' => $hash));
	}

	protected function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir") $this->rrmdir($dir."/".$object); else unlink($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	/**
	 * Delete project routine
	 * @param   int       project id
	 * @param   boolean   true to delete deploy
	 * @return  mixed     false on error
	 */
	public function delete_project($id, $delete_deploys = false) {
		$project = $this->get_project($id);
		$results = Database::instance()->delete('projects', array('id'), array('id' => $id));
		$projects = $this->get_projects_by_repository($project->repository_id);
		if ($delete_deploys) {
			if (is_dir($project->destination)) {
				$this->rrmdir($project->destination);
			}
		}
		if (count($projects) === 0) {
			$repository = $this->get_repository($project->repository_id);
			Database::instance()->delete('repositories', array('id'), array('id' => $repository->id));
			$this->rrmdir($repository->location);
		}
		return $results;
	}

	/**
	 * Updated serverside copy of repository
	 * @param   mixed   project id or object
	 * @return  boolean
	 * @throws  Exception
	 */
	public function pull($project_obj_or_id) {
		if (!is_object($project_obj_or_id)) {
			$project_obj_or_id = Database::instance()->find_one('projects', array('id'), array('id' => $project_obj_or_id));
		}
		if ($project_obj_or_id === false) {
			throw new Exception('Invalid project');
		}
		$repository = Database::instance()->find_one('repositories', array('id'), array('id' => $project_obj_or_id->repository_id));
		if ($repository === false) {
			throw new Exception('Repository not found');
		}
		$git = new Git($repository->location.'/.git');

		if ($git instanceof Git) {
			$command = 'cd '.realpath($repository->location).' && '.$this->_config['git_bin'].' checkout '.escapeshellarg($project_obj_or_id->branch).' && '.$this->_config['git_bin'].' pull origin '.escapeshellarg($project_obj_or_id->branch).' && '.$this->_config['git_bin'].' submodule update --init --recursive';
			$result = shell_exec($command);
			if ($result === NULL) {
				throw new Exception('Problem performing git pull on '.$repository->name.' Command: '.$command);
			}
			return true;
		}
		return false;
	}

	/**
	 * We iterate over all our defined projects and pull
	 * TODO: figure out how to make this faster (without using PCNTL)
	 * @return  void
	 * @uses    pcntl
	 */
	public function pull_all() {
		$projects = $this->get_projects();
		foreach ($projects as $project) {
            $this->pull($project);
		}
	}

	/**
	 * Deploy project to directory
	 * @param   mixed   project id or object
	 * @return  boolean
	 * @throws  Exception
	 */
	public function deploy($project_obj_or_id) {
		if (!is_object($project_obj_or_id)) {
			$project_obj_or_id = Database::instance()->find_one('projects', array('id'), array('id' => $project_obj_or_id));
		}
		if ($project_obj_or_id === false) {
			throw new Exception('Invalid project');
		}
		$repository = Database::instance()->find_one('repositories', array('id'), array('id' => $project_obj_or_id->repository_id));
		if ($repository === false) {
			throw new Exception('Repository not found');
		}
		
		if (is_dir($project_obj_or_id->destination) || (!is_dir($project_obj_or_id->destination) && mkdir($project_obj_or_id->destination, 0777, true))) {
			$command = 'cd '.realpath($repository->location).' && '.$this->_config['git_bin'].' checkout '.escapeshellarg($project_obj_or_id->branch).' && '.$this->_config['rsync_bin'].' --exclude=".git*" -vadrtuz  --progress --stats --delete '.realpath($repository->location).'/ '.realpath($project_obj_or_id->destination);
			$result = shell_exec($command);
			if ($result === NULL) {
				throw new Exception('Problem performing deploy on '.$repository->name.' Command: '.$command);
			}
			$update_db = Database::instance()->update_deploy($project_obj_or_id->id);
			$config = Config::instance();
			if ($config->get('hipchat_enabled') == 'yes') {
				$hipchat_ips = array(
					'23.23.169.177',
					'54.225.164.125',
					'107.21.238.207',
					'174.129.224.240'
				);
				$hipchat_sent = false;
				$hipchat_try = 0;
				while (!$hipchat_sent) {
					$hip_ip = $hipchat_ips[$hipchat_try];
					$url = $hip_ip.'/v1/rooms/message?auth_token='.$config->get('hipchat_auth_token');
					$destination = 'http://'.$_SERVER['HTTP_HOST'].option('base_path').'/'.$project_obj_or_id->destination;
					$proxy = ($config->get('curl_proxy') && $config->get('curl_proxy') == '' ? null : $config->get('curl_proxy')); // null disables proxy (if config item is undefined or empty string in DB)
					$latest_commit = $this->latest_commit($project_obj_or_id);
					$fields = array(
						'room_id' => $config->get('hipchat_room_id'),
						'from' => $config->get('hipchat_from'),
						'message_format' => 'html',
						'notify' => $config->get('hipchat_notify'),
						'color' => $config->get('hipchat_color'),
						'message' => '<strong>'.$project_obj_or_id->name.'</strong> deployed with last commit by <strong>'.$latest_commit->author->name.'</strong> ('.Formatter::relative_time($last_commit->author->time).'): '.$latest_commit->summary
					);
					if (function_exists('curl_init')) {
						$protocol = 'https://';
						$c = curl_init();
						curl_setopt($c, CURLOPT_POST, count($fields));
						curl_setopt($c, CURLOPT_URL, $protocol.$url);
						curl_setopt($c, CURLOPT_TIMEOUT, 3);
						curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($fields));
						if ($proxy) {
							curl_setopt($c, CURLOPT_PROXY, 'http://'.$proxy);
						}
						//curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyauth);
						curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($c, CURLOPT_HEADER, 1);
						curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);

						curl_exec($c);
						$curl_errno = curl_errno($c);
	        			$curl_error = curl_error($c);
						curl_close($c);

						if ($curl_errno > 0) {
							$hipchat_try++;
							if ($hipchat_try === count($hipchat_ips)) {
								//throw new Exception('Deploy was successful but HipChat message timed out');
								$hipchat_sent = true;
							}
						} else {
							$hipchat_sent = true;
						}
					} elseif (function_exists('stream_context_create')) {
						$protocol = 'http://';
						$data = http_build_query($fields);
						$context = array(
							'http' => array(
								'request_fulluri' => true,
								'method' => 'POST',
								'timeout' => 3,
								'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
										 . "Content-Length: " . strlen($data) . "\r\n",
								'content' => $data
							)
						);
						if ($proxy) {
							$context['http']['proxy'] = 'tcp://'.$proxy;
						}
						$stream = stream_context_create($context);
						$result = @fopen($protocol.$url, 'r', false, $stream);
						if ($result) {
							fclose($result);
							$hipchat_sent = true;
						} else {
							$hipchat_try++;
							if ($hipchat_try === count($hipchat_ips)) {
								throw new Exception('Deploy was successful but HipChat message timed out');
							}
						}
					} else {
						break;
					}
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Deploys all projects in the system
	 * TODO: figure out how to make this faster (without using PCNTL)
	 * @return      void
	 */
	public function deploy_all() {
		$projects = $this->get_projects();
		foreach ($projects as $project) {
			$this->deploy($project);
		}
	}

	/**
	 * Find branches in local repository
	 * @param   mixed   repository id or repository object
	 * @return  array
	 * @throws  Exception
	 */
	public function get_branches($repository) {
		if (!is_object($repository)) {
			$repository = $this->get_repository($repository);
		}
		if ($repository === false) {
			throw new Exception('Repository not found');
		}
		
		$output = shell_exec('cd '.realpath($repository->location).' && '.$this->_config['git_bin'].' branch');
		$lines = preg_split('/\n/', $output);
		$branches = array();
		foreach ($lines as $line) {
			if (strlen($cleaned = preg_replace('/^(\*?)\s*/', '', $line)) > 0) {
				array_push($branches, $cleaned);
			}
		}
		
		return $branches;
	}

	/**
	 * Function Description
	 * @param   string   description
	 * @return  boolean
	 * @uses    Class::method
	 */
	protected function _fix_submodules($path, $protocol) {
		$regex = '/\[submodule \"[^\"]+\"\]\n\s+path = (.*)/';
		if (is_file($path.'/.gitmodules')) {
			$submodules = realpath($path).'/.gitmodules';
			$file_contents = file_get_contents($submodules);
			$new_modules_file = preg_replace('/(\s+url \= )git\@bitbucket.org\:(.*)/', '$1'.$protocol.'$2', $file_contents);
			file_put_contents($submodules, $new_modules_file);
			$command = 'cd '.realpath($path).' && '.$this->_config['git_bin'].' submodule update --init';
			$result = shell_exec($command);

			preg_match_all($regex, $new_modules_file, $matches);
			//echo $new_modules_file.'<br />';
			foreach ($matches[1] as $new_path) {
				$this->_fix_submodules($path.'/'.$new_path, $protocol);
				//echo $path.'/'.$new_path.'<br />';
			}
		}
	}

	/**
	 * http://stackoverflow.com/questions/379081/track-all-remote-git-branches-as-local-branches
	 * @param   string   description
	 * @return  Git
	 */
	protected function _clone_repository($name, $location, $remote) {
		if (!is_dir(realpath($location))) {
			if (mkdir($location, 0777, true) === false) {
				throw new Exception('No permission on filesystem to the create directory');
			}
		}
		
		$command1 = 'cd '.realpath($location).' && '.$this->_config['git_bin'].' clone '.$remote.' .';
		$command3 = 'cd '.realpath($location).' && '.$this->_config['git_bin'].' branch -r';

		$result1 = shell_exec($command1);
		
		if (is_file($submodules = realpath($location).'/.gitmodules') && strpos($remote, 'bitbucket.org') > -1) {
			$protocol = substr($remote, 0, strpos($remote, '/', 8) + 1);
			$this->_fix_submodules($location, $protocol);
		}
		
		$result3 = shell_exec($command3);
		if ($result1 === NULL && $result3 === NULL) {
			throw new Exception('Problem performing git pull on '.$name.' Command: '.$command1);
		}
		$lines = preg_split('/\n/', $result3);
		foreach ($lines as $line) {
			if (strpos($line, 'origin/HEAD') === false && trim($line) !== '') {
				$branch = preg_replace('/^\s*origin\//', '', trim($line));
				shell_exec('cd '.realpath($location).' && '.$this->_config['git_bin'].' checkout -b "'.$branch.'" "'.trim($line).'"');
			}
		}
		return new Git(realpath($location).'/.git');
	}

	/**
	 * Created a repository
	 * @param   string   name
	 * @param   string   location
	 * @return  mixed    row id on success, false on failure
	 * @throws  Exception
	 */
	protected function _create_repository($name, $remote) {
		$hash = md5($name.$remote);
		$location = $this->_config['repo_root'].$hash;
		if (!is_dir($location) || (is_dir($location) && !is_dir($location.'/.git'))) {
			if (($result = Database::instance()->add_repository($name, $hash, $location, $remote)) !== false) {
				$git = $this->_clone_repository($name, $location, $remote);
				return $result;
			}
		}
		return false;
	}

	/**
	 * Creates a repository inside a transaction
	 * @param   string   name
	 * @param   string   location
	 * @return  mixed    row id on success, false on failure
	 */
	public function create_repository($name, $remote) {
		Database::instance()->db()->beginTransaction();
		$rowid = $this->_create_repository($name, $remote);
		if ($rowid === false) {
			$roll = Database::instance()->db()->rollBack();
		}
		if (Database::instance()->db()->commit()) {
			return $rowid;
		}
		return false;
	}

	/**
	 * Created a project
	 * @param   string   name
	 * @param   string   branch
	 * @param   string   destination for deploy
	 * @param   string   repository id
	 * @return  mixed    row id on success, false on failure
	 * @throws  Exception
	 */
	protected function _create_project($name, $branch, $destination, $repository_id) {
		return Database::instance()->add_project($repository_id, $name, $branch, $destination);
	}

	/**
	 * Creates a project inside a transaction
	 * @param   string   name
	 * @param   string   branch
	 * @param   string   destination for deploy
	 * @param   string   repository id
	 * @return  mixed    row id on success, false on failure
	 */
	public function create_project($name, $branch, $destination, $repository_id) {
		Database::instance()->db()->beginTransaction();
		if (($rowid = $this->_create_project($name, $branch, $destination, $repository_id)) === false) {
			$roll = Database::instance()->db()->rollBack();
		}
		if (Database::instance()->db()->commit()) {
			return $rowid;
		}
		return false;
	}

	/**
	 * Create both the repository and project at the same time within a transaction
	 * @param   array   associative array of repository values
	 * @param   array   associative array of project values
	 * @return  boolean
	 */
	public function create_repository_and_project($repository_values, $project_values) {
		Database::instance()->db()->beginTransaction();
		$rowid = $this->_create_repository($repository_values['name'], $repository_values['remote']);
		if ($rowid === false) {
			Database::instance()->db()->rollBack();
			return false;
		}
		if ($this->_create_project($project_values['name'], $project_values['branch'], $project_values['destination'], $rowid) && Database::instance()->db()->commit()) {
			return true;
		}
		Database::instance()->db()->rollBack();
		return false;
	}

}
