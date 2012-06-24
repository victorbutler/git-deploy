<?php
if (isset($projects) && !empty($projects)):
?>
<div class="page-header">
	<div class="pull-right">
		<a href="#" class="btn btn btn-inverse btn-large"><i class="icon-arrow-down icon-white"></i> Pull All</a>
		<a href="#" class="btn btn-primary btn-large"><i class="icon-plus icon-white"></i> Add Project</a>
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
		<strong>Status</strong> <?=$status?>
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
				<div class="btn-group" data-repository="<?=$project->hash?>" data-branch="<?=$project->branch?>">
					<button class="btn dropdown-toggle more-info" title="More Info" data-content="Last deployed: <?=$project->last_deployed?>" data-toggle="dropdown"><?=$project->name?> <span class="caret"></span></button>
					<ul class="dropdown-menu">
						<li><a href="<?=url_for('/projects/pull')?>"><i class="icon-download-alt"></i> Pull</a></li>
						<li><a href="#"><i class="icon-arrow-right"></i> Deploy</a></li>
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
<div class="modal hide" id="status-modal">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">×</button>
		<h3>Pulling</h3>
	</div>
	<div class="modal-body">
		<p>Please wait while we update the requested repository. This should only take a minute.</p>
	</div>
</div>
<script type="text/javascript">
	(function($){
		$('.more-info').popover({title: '', delay: { show: 400, hide: 0}});
		$('#projects-table .btn-group ul.dropdown-menu a').click(function(event){
			event.preventDefault();
			if ($(this).attr('href') != '#') {
				$('#status-modal').modal({keyboard: false});
				var button = $(this).closest('.btn-group');
				$.post('<?=url_for('/projects/pull')?>', {repository: button.attr('data-repository'), branch: button.attr('data-branch')}, function(data, textStatus, jqXHR){
					$('#status-modal').modal('hide');
					if (data.error !== false) {
						var error_html = '<div class="alert fade in alert-error"><button class="close" data-dismiss="alert">×</button><strong>Uh oh!</strong> '+data.error+'</div>';
						$('#error-msg').html(error_html);
					} else {
						var status_html = '<div class="alert fade in"><button class="close" data-dismiss="alert">×</button><strong>Status</strong> Pulled successfully</div>';
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
		<a href="#" class="btn btn-primary btn-large">Set one up now!</a>
	</p>
</div>
<?php
endif;
?>