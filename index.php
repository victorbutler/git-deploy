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

dispatch('/projects', 'projects');

dispatch('/projects/pullall', 'projects_pullall');

dispatch('/projects/repositories', 'projects_repositories');

dispatch_post('/projects/pulldeploy', 'projects_pull_deploy');

dispatch_post('/projects/lookup', 'projects_lookup');

dispatch('/projects/pull', 'projects_pull');

dispatch('/projects/new', 'projects_new');

dispatch_post('/projects/new', 'projects_new');

dispatch_post('/projects/pull', 'projects_pull');

dispatch('/projects/deploy', 'projects_deploy');

dispatch('/projects/deployall', 'projects_deploy_all');

dispatch_post('/projects/deploy', 'projects_deploy');

run();


?>