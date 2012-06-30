<?php
content_for('head');
?>
<script type="text/javascript" src="assets/js/jquery.color.js"></script>
<script type="text/javascript" src="assets/js/mustache.min.js"></script>
<?php
end_content_for();

if (isset($projects) && !empty($projects)):
?>
<div class="page-header">
	<div class="pull-right">
		<a href="<?=url_for('/projects/pullall')?>" class="btn btn btn-inverse btn-large"><i class="icon-arrow-down icon-white"></i> Pull All</a>
		<a href="<?=url_for('projects/new')?>" class="btn btn-primary btn-large"><i class="icon-plus icon-white"></i> Add Project</a>
	</div>
	<h1>Projects</h1>
</div>
<div id="error-msg">
<?php
	if (isset($error) && $error !== false):
?>
	<div class="alert alert-error">
		<button class="close" data-dismiss="alert">×</button>
		<strong>Uh oh!</strong> <?=$error?>
	</div>
<?php
	endif;
?>
</div>
<div id="success-msg"></div>
<table class="table table-striped" id="projects-table">
	<thead>
		<tr>
			<th>#</th>
			<th>Name</th>
			<th>Repository</th>
			<th>Last Commit</th>
			<th class="span4"></th>
		</tr>
	</thead>
	<tbody>
<?php
	foreach ($projects as $project):
?>
		<tr>
			<td><?=$project->id?></td>
			<td>
				<div class="btn-group" data-project="<?=$project->id?>">
					<button class="btn dropdown-toggle more-info" data-toggle="dropdown"><?=$project->name?> <span class="caret"></span></button>
					<ul class="dropdown-menu">
						<li><a href="<?=url_for('/projects/pulldeploy')?>" data-action="pull+deploy"><i class="icon-download-alt"></i><i class="icon-arrow-right"></i> Pull &amp; Deploy</a></li>
						<li>
<?php
if ($project->last_deployed === 'Never'):
?>
							<a><i class="icon-globe"></i> <span class="muted">View</span></a>
<?php
else:
?>
							<a href="<?=$project->destination?>"><i class="icon-globe"></i> View</a>
<?php
endif;
?>
						</li>
						<li class="divider"></li>
						<li><a href="<?=url_for('/projects/pull')?>" data-action="pull"><i class="icon-download-alt"></i> Pull</a></li>
						<li><a href="<?=url_for('/projects/deploy')?>" data-action="deploy"><i class="icon-arrow-right"></i> Deploy</a></li>
						<li class="divider"></li>
						<li><a href="#"><i class="icon-wrench"></i> Configure</a></li>
						<li><a data-toggle="modal" href="#delete-modal"><i class="icon-remove"></i> <span class="muted">Delete</span></a></li>
					</ul>
				</div>
			</td>
			<td><?=$project->repository?>/<?=$project->branch?></td>
			<td class="author"><?=$project->last_commit->author->name?> <em class="muted">(<?=Formatter::relative_time($project->last_commit->author->time)?>)</em></td>
			<td class="summary span4"><?=$project->last_commit->summary?></td>
		</tr>
<?php
	endforeach;
?>
	</tbody>
</table>
<div class="modal hide" id="delete-modal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Are you sure you want to delete this project?</h3>
	</div>
	<div class="modal-body">
		<div class="alert fade in alert-error">
			<strong>This operation is irreversible. If no more projects are using the attached repository, it will also be deleted.</strong>
		</div>
		<form>
			<span class="span2">Remove Deploys?</span> <label class="checkbox" for="remote_deploys"><input id="remote_deploys" type="checkbox" name="remove_deploys" value="yes" /> Yes</label>
		</form>
	</div>
	<div class="modal-footer">
		<a class="btn btn-danger btn-large">Yes, I'm sure I want to delete this</a>
		<a class="btn btn-large pull-left">NO! I changed my mind</a>
	</div>
</div>
<script type="text/template" id="modal-template">
<div class="modal hide" id="action-modal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>{{action}}</h3>
	</div>
	<div class="modal-body">
		<p>{{message}}</p>
	</div>
</div>
</script>
<script type="text/template" id="error-template">
<div class="alert fade in alert-error">
	<button class="close" data-dismiss="alert">×</button>
	<strong>Uh oh!</strong> {{message}}
</div>
</script>
<script type="text/template" id="success-template">
<div class="alert alert-success fade in">
	<button class="close" data-dismiss="alert">×</button>
	<strong>{{message}}</strong>
</div>
</script>
<script type="text/javascript">
	(function($){
		$('.more-info').hover(function(event){
			var self = $(this);
			if (!self.data('cache')) {
				$.post('<?=url_for('/projects/lookup')?>', {project_id: $(this).closest('.btn-group').attr('data-project')}, function(data, textStatus, jqXHR){
					self.data('cache', data);
					if (self.filter(':hover').length) {
						self.popover({title: data.project.name+' Info', content: '<h4>Last Deployed</h4><p>'+data.last_deployed+'<p><h4>Deploy Location</h4><p>'+data.project.destination+'</p>'}).popover('show');
					}
				});
			}
		});
		$('#projects-table .btn-group ul.dropdown-menu a[data-action]').click(function(event){
			event.preventDefault();
			if ($(this).attr('href') != '#') {
				var self = $(this);
				var view = {action: self.attr('data-action').toUpperCase(), message: 'successful'};
				if (self.attr('data-action') == 'pull') {
					view = {action: 'Pulling', message: 'Please wait while we update the requested repository. This should only take a minute.'};
				} else if (self.attr('data-action') == 'deploy') {
					view = {action: 'Deploying', message: 'Please wait while we deploy your project. This should only take a minute, but with big projects it may take a little longer.'};
				} else if (self.attr('data-action') == 'pull+deploy') {
					view = {action: 'Pulling and Deploying', message: 'Please wait while we update the requested repository and deploy your project. This should only take a minute, but with big projects it may take a little longer.'};
				}
				var modal = $($.mustache($('#modal-template').html(), view)).appendTo('body').modal({keyboard: false});
				var button = $(this).closest('.btn-group');
				$.post(self.attr('href'), {project_id: button.attr('data-project')}, function(data, textStatus, jqXHR){
					modal.modal('hide').remove();
					$('.alert').remove();
					if (data.error !== false) {
						$('#error-msg').html($.mustache($('#error-template').html(), {message: data.error}));
					} else {
						$('#success-msg').html($.mustache($('#success-template').html(), {message: view.action+' was successful'}));
						var tr = button.closest('tr');
						tr.find('td.author').html(data.author);
						tr.find('td.summary').html(data.summary);
						// pulsate the target
						var tds = tr.find('td');
						var oldbg = tds.css('backgroundColor');
						tds.animate({'backgroundColor': '#f9d9e2'}, 800).delay(1200).animate({'backgroundColor': oldbg}, 800, function(){$(this).css('backgroundColor', '');});
					}
				});
			}
		});
	})(jQuery);
</script>
<?php
else:
?>
<div class="hero-unit">
	<h1>No Projects Found</h1>
	<p>It seems there aren't any projects set up yet.</p>
	<p>
		<a href="<?=url_for('projects/new')?>" class="btn btn-primary btn-large">Set one up now!</a>
	</p>
</div>
<?php
endif;
?>