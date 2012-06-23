<?php

function deploy() {
	return 'ok deploy '.params('project');
}

function deploy_post() {
	return count($_POST);
}