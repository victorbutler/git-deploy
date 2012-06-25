<?php
if (isset($projects) && !empty($projects)):
?>
<div class="page-header">
	<div class="pull-right">
		<a href="#" class="btn btn btn-inverse btn-large"><i class="icon-arrow-down icon-white"></i> Pull All</a>
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
<div id="status-msg">
<?php
	if (isset($status) && $status !== false):
?>
	<div class="alert">
		<button class="close" data-dismiss="alert">×</button>
		<h3><?=$status?></h3>
	</div>
<?php
	endif;
?>
</div>
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
						<li><a href="<?=url_for('/projects/pull')?>" data-action="pull"><i class="icon-download-alt"></i> Pull</a></li>
						<li><a href="<?=url_for('/projects/deploy')?>" data-action="deploy"><i class="icon-arrow-right"></i> Deploy</a></li>
						<li class="divider"></li>
						<li><a href="#"><i class="icon-wrench"></i> Configure</a></li>
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
<div class="modal hide" id="status-modal-pull">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Pulling</h3>
	</div>
	<div class="modal-body">
		<p>Please wait while we update the requested repository. This should only take a minute.</p>
	</div>
</div>
<div class="modal hide" id="status-modal-deploy">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Deploying</h3>
	</div>
	<div class="modal-body">
		<p>Please wait while we deploy your project. This should only take a minute, but with big projects it may take a little longer.</p>
	</div>
</div>
<script type="text/javascript">
	(function($){
		$('.more-info').hover(function(event){
			var self = $(this);
			if (!self.data('cache')) {
				$.post('<?=url_for('/projects/lookup')?>', {project_id: $(this).closest('.btn-group').attr('data-project')}, function(data, textStatus, jqXHR){
					self.data('cache', data);
					self.popover({title: data.project.name+' Info', delay: {show: 400, hide: 0}, content: '<h4>Last Deployed</h4><p>'+data.last_deployed+'<p><h4>Deploy Location</h4><p>'+data.project.destination+'</p>'}).popover('show');
				});
			}
		});
		$('#projects-table .btn-group ul.dropdown-menu a').click(function(event){
			event.preventDefault();
			if ($(this).attr('href') != '#') {
				var self = $(this);
				$('#status-modal-'+self.attr('data-action')).modal({keyboard: false});
				var button = $(this).closest('.btn-group');
				$.post(self.attr('href'), {project_id: button.attr('data-project')}, function(data, textStatus, jqXHR){
					$('#status-modal-'+self.attr('data-action')).modal('hide');
					$('.alert').remove();
					if (data.error !== false) {
						var error_html = '<div class="alert fade in alert-error"><button class="close" data-dismiss="alert">×</button><strong>Uh oh!</strong> '+data.error+'</div>';
						$('#error-msg').html(error_html);
					} else {
						var status_html = '<div class="alert alert-success fade in"><button class="close" data-dismiss="alert">×</button><strong>Status</strong> '+self.attr('data-action').toUpperCase()+' was successful</div>';
						$('#error-msg').empty();
						$('#status-msg').html(status_html);
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