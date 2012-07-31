<?php

function projects() {
	$projects = GitDeploy::instance()->get_projects();
	$p_store = array();
	$count = 0;
	foreach ($projects as $id => $project) {
		$repo = GitDeploy::instance()->get_repository($project->repository_id);
		$r = new stdClass;
		$r->name = $project->name;
		$r->repository = $repo->name;
		$r->branch = $project->branch;
		$r->destination = $project->destination;
		if ($project->last_deployed) {
			$r->last_deployed = date('n/j/Y g:i:s A', $project->last_deployed);
		} else {
			$r->last_deployed = 'Never';
		}
		$r->project_id = $project->id;
		$r->id = $id+1;
		$r->last_commit = GitDeploy::instance()->latest_commit($project);
		array_push($p_store, $r);
	}
	
	set('projects', $p_store);
	set('title', 'Projects - Git Deploy');

	return render('projects.php');
}

function projects_pull() {
	if (count($_POST)) {
		$error = false;
		if (array_key_exists('project_id', $_POST)) {
			try {
				$project = GitDeploy::instance()->get_project($_POST['project_id']);
				$pull = GitDeploy::instance()->pull($project);
				if ($project === false || $pull === false) {
					$error = 'Pull encountered an error';
				}
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		} else {
			$error = 'Improperly formatted input';
		}
		
		if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			if ($error !== false) {
				$o = new stdClass;
				$o->error = $error;
				$o->date = time();
				return json($o);
			}
			$last_commit = GitDeploy::instance()->latest_commit($project);
			$o = new stdClass;
			$o->error = false;
			$o->author = $last_commit->author->name.' <em class="muted">('.Formatter::relative_time($last_commit->author->time).')</em>';
			$o->summary = $last_commit->summary;
			return json($o);
		} else {
			set('error', $error);
			return render('projects.php');
		}
	} else {
		header('Location: '.url_for('/projects'));
	}
}

function projects_delete() {
	if (count($_POST)) {
		$error = false;
		if (array_key_exists('project_id', $_POST)) {
			try {
				$results = GitDeploy::instance()->delete_project($_POST['project_id'], (isset($_POST['remove_deploys']) && $_POST['remove_deploys'] === 'yes' ? true : false));
				if ($results === false) {
					$error = 'Delete encountered an error';
				}
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		} else {
			$error = 'Improperly formatted input';
		}
		
		if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			if ($error !== false) {
				$o = new stdClass;
				$o->error = $error;
				$o->date = time();
				return json($o);
			}
			$last_commit = GitDeploy::instance()->latest_commit($project);
			$o = new stdClass;
			$o->error = false;
			$o->author = $last_commit->author->name.' <em class="muted">('.Formatter::relative_time($last_commit->author->time).')</em>';
			$o->summary = $last_commit->summary;
			return json($o);
		} else {
			set('error', $error);
			return projects();
		}
	} else {
		header('Location: '.url_for('/projects'));
	}
}

function projects_new() {
	$error = false;
	$error_fields = array();
	if (count($_POST)) {
		$expected = array(
			'project_name',
			'project_destination'
		);
		foreach ($expected as $field) {
			if (!isset($_POST[$field]) || $_POST[$field] == '') {
				array_push($error_fields, $field);
			}
		}
		if (!isset($_POST['repository_id']) || $_POST['repository_id'] == '') {
			if (!isset($_POST['repository_name']) || $_POST['repository_name'] == '') {
				array_push($error_fields, 'repository_name');
			}
			if (!isset($_POST['repository_remote']) || $_POST['repository_remote'] == '') {
				array_push($error_fields, 'repository_remote');
			}
			if (!isset($_POST['project_branch']) || $_POST['project_branch'] === '') {
				array_push($error_fields, 'project_branch');
			}
		} else {
			if (!isset($_POST['project_branch_id']) || $_POST['project_branch_id'] === '') {
				array_push($error_fields, 'project_branch');
			}
		}
		if (count($error_fields)) {
			$error = 'Please make sure you\'ve filled out all the fields';
		} else {
			if ($_POST['repository_id'] == '') {
				// new repository
				try {
					$result = GitDeploy::instance()->create_repository_and_project(array(
						'name' => $_POST['repository_name'],
						'remote' => $_POST['repository_remote']
					), array(
						'name' => $_POST['project_name'],
						'branch' => $_POST['project_branch'],
						'destination' => $_POST['project_destination'],
					));
					header('Location: '.url_for('/projects'));
				} catch (Exception $ex) {
					$error = $ex->getMessage();
				}
			} else {
				$branches = GitDeploy::instance()->get_branches($_POST['repository_id']);
				$branch = $branches[$_POST['project_branch_id']];
				$result = GitDeploy::instance()->create_project($_POST['project_name'], $branch, $_POST['project_destination'], $_POST['repository_id']);
				header('Location: '.url_for('/projects'));
			}
		}
		set('num_of_repositories', count(GitDeploy::instance()->get_projects()));
	}
	$repositories = Database::instance()->find('repositories');
	foreach ($repositories as $repository) {
		$repository->branches = GitDeploy::instance()->get_branches($repository);
	}
	set('repositories', $repositories);
	set('error', $error);
	set('error_fields', $error_fields);
	return render('projects/new.php');
}

function projects_deploy() {
	if (count($_POST)) {
		$error = false;
		if (array_key_exists('project_id', $_POST)) {
			try {
				$project = GitDeploy::instance()->get_project($_POST['project_id']);
				$deploy = GitDeploy::instance()->deploy($project);
				if ($project === false || $deploy === false) {
					$error = 'Deploy encountered an error';
				}
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		} else {
			$error = 'Improperly formatted input';
		}
		
		if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			if ($error !== false) {
				$o = new stdClass;
				$o->error = $error;
				$o->date = time();
				return json($o);
			}
			$last_commit = GitDeploy::instance()->latest_commit($project);
			$o = new stdClass;
			$o->error = false;
			$o->author = $last_commit->author->name.' <em class="muted">('.Formatter::relative_time($last_commit->author->time).')</em>';
			$o->summary = $last_commit->summary;
			return json($o);
		} else {
			set('error', $error);
			return render('deploy.php');
		}
	} else {
		header('Location: '.url_for('/deploy'));
	}
}

function projects_repositories() {
	if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		$repositories = Database::instance()->find('repositories');
		foreach ($repositories as $repository) {
			$repository->branches = GitDeploy::instance()->get_branches($repository);
		}
		return json($repositories);
	}
	header('Location: '.url_for('/projects'));
}

function projects_lookup() {
	if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
		if (count($_POST)) {
			$id = $_POST['project_id'];
			$project = GitDeploy::instance()->get_project($id);
			$o = new stdClass;
			$o->project = $project;
			$o->error = false;
			$o->date = time();
			if ($project->last_deployed) {
				$o->last_deployed = date('n/j/Y g:i:s A', $project->last_deployed);
			} else {
				$o->last_deployed = 'Never';
			}
			return json($o);
		}
		$o = new stdClass;
		$o->error = 'Invalid input';
		$o->date = time();
		return json($o);
	}
	header('Location: '.url_for('/'));
}

function projects_pullall() {
	GitDeploy::instance()->pull_all();
	header('Location: '.url_for('/projects'));
}

function projects_pull_deploy() {
	if (count($_POST)) {
		$error = false;
		if (array_key_exists('project_id', $_POST)) {
			try {
				$project = GitDeploy::instance()->get_project($_POST['project_id']);
				$pull = GitDeploy::instance()->pull($project);
				$deploy = GitDeploy::instance()->deploy($project);
				if ($project === false || $pull === false || $deploy === false) {
					$error = 'Pull &amp; deploy encountered an error';
				}
			} catch (Exception $ex) {
				$error = $ex->getMessage();
			}
		} else {
			$error = 'Improperly formatted input';
		}
		
		if (array_key_exists('HTTP_X_REQUESTED_WITH', $_SERVER) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
			if ($error !== false) {
				$o = new stdClass;
				$o->error = $error;
				$o->date = time();
				return json($o);
			}
			$last_commit = GitDeploy::instance()->latest_commit($project);
			$o = new stdClass;
			$o->error = false;
			$o->author = $last_commit->author->name.' <em class="muted">('.Formatter::relative_time($last_commit->author->time).')</em>';
			$o->summary = $last_commit->summary;
			return json($o);
		} else {
			set('error', $error);
			return render('projects.php');
		}
	} else {
		header('Location: '.url_for('/projects'));
	}
}
