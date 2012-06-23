<?php

function projects() {
	$projects = GitDeploy::instance()->get_projects();
	$p_store = array();
	$count = 0;
	foreach ($projects as $id => $config) {
		$repo = GitDeploy::instance()->get_repository_by_hash($config['repository']);
		$r = new stdClass;
		$r->name = $config['name'];
		$r->repository = $repo->name;
		$r->branch = $config['branch'];
		$r->id = $id+1;
		$r->hash = $config['repository'];
		$r->last_commit = GitDeploy::instance()->latest_commit($repo, $config['branch']);
		array_push($p_store, $r);
	}
	
	set('projects', $p_store);
	set('title', 'Projects - Git Deploy');

	return render('projects.php');
}

function projects_pull() {
	if (count($_POST)) {
		$error = false;
		if (array_key_exists('repository', $_POST) && array_key_exists('branch', $_POST)) {
			$pull = GitDeploy::instance()->pull($_POST['repository'], $_POST['branch']);
			if ($pull === false) {
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
			$repository = GitDeploy::instance()->get_repository_by_hash($_POST['repository'], $_POST['branch']);
			$last_commit = GitDeploy::instance()->latest_commit($repository, $_POST['branch']);
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
