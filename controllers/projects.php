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
		if ($project->last_deployed) {
			$r->last_deployed = date('n/j/Y g:i:s A', $project->last_deployed);
		} else {
			$r->last_deployed = 'Never';
		}
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
			$project = GitDeploy::instance()->get_project($_POST['project_id']);
			$pull = GitDeploy::instance()->pull($project);
			if ($project === false || $pull === false) {
				$error = 'Pull encountered an error';
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

function projects_new() {
	if (count($_POST)) {
		$error = false;
		$error_fields = array();
		$expected = array(
			'repository_id',
			'repository_name',
			'repository_remote',
			'project_name',
			'project_branch',
			'project_destination'
		);
		foreach ($expected as $field) {
			if (!isset($_POST[$field]) || $_POST[$field] == '') {
				if ($field === 'repository_id') continue;
				array_push($error_fields, $field);
			}
		}
		if (count($error_fields)) {
			$error = 'Please correct your errors on the form';
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
				} catch (Exception $ex) {
					$error = $ex->getMessage();
				}
			} else {
				$result = GitDeploy::instance()->create_project($_POST['project_name'], $_POST['project_branch'], $_POST['project_destination'], $_POST['repositroy_id']);
			}
		}
		set('num_of_repositories', count(GitDeploy::instance()->get_projects()));
		set('error', $error);
		set('error_fields', $error_fields);
	}
	set('repositories', Database::instance()->find('repositories'));
	return render('projects/new.php');
}
