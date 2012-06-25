<?php

set_include_path(get_include_path().PATH_SEPARATOR.implode(PATH_SEPARATOR, array('classes', 'vendors')));

require_once('vendors/limonade.php');

function configure() {
	option('env', ENV_DEVELOPMENT);
	layout('templates/general.php');
	require_once_dir('classes');
}

function before($route) {
	require_once('gitdeploy.php');
	set('num_of_repositories', count(GitDeploy::instance()->get_projects()));
}

dispatch('/', 'homepage');

dispatch('/deploy/:project', 'deploy');

dispatch_post('/deploy', 'deploy_post');

dispatch('/projects', 'projects');

dispatch('/projects/pull', 'projects_pull');

dispatch('/projects/new', 'projects_new');

dispatch_post('/projects/new', 'projects_new');

dispatch_post('/projects/pull', 'projects_pull');

run();


?>