<ul class="breadcrumb">
	<li>
		<a href="<?=url_for('/')?>">Home</a> <span class="divider">/</span>
	</li>
	<li>
		<a href="<?=url_for('/projects')?>">Projects</a> <span class="divider">/</span>
	</li>
	<li class="active">New</li>
</ul>
<div class="page-header">
	<h1>Add a New Project</h1>
</div>
<?php
if (isset($error) && $error !== false):
?>
<div class="alert alert-error">
	<strong>Uh oh!</strong> <?=$error?>
</div>
<?php
endif;
?>
<form action="<?=url_for('projects/new')?>" method="post">
	<div class="row">
		<div class="span6">
			<h2>Repository</h2>
		
		    <div class="well">
		    	<label>
		    		<span class="span2">Existing Repository:</span> 
				    <select name="repository_id"<?=(in_array('repository_id', $error_fields) ? ' class="control-group error"' : '')?>>
				    	<option value="">New Repository</option>
				    	<option disabled="disabled">---</option>
<?php
foreach ($repositories as $repository):
?>
						<option value="<?=$repository->id?>"<?=(array_key_exists('repository_id', $_POST) && $_POST['repository_id'] == $repository->id ? ' selected="selected"' : '')?>><?=$repository->name?></option>
<?php
endforeach;
?>
				    </select>
		    	</label>
		    	<div class="new-repository">
					<label<?=(in_array('repository_name', $error_fields) ? ' class="control-group error"' : '')?>><span class="span2">Name:</span> <input type="text" name="repository_name"<?=(array_key_exists('repository_name', $_POST) ? ' value="'.$_POST['repository_name'].'"' : '')?> /></label>
					<label<?=(in_array('repository_remote', $error_fields) ? ' class="control-group error"' : '')?>><span class="span2">Remote Origin:</span> <input type="text" name="repository_remote"<?=(array_key_exists('repository_remote', $_POST) ? ' value="'.$_POST['repository_remote'].'"' : '')?> /></label>
		    	</div>
			</div>
		</div>
		<div class="span6">
		    <h2>Project</h2>
		    <div class="well">
				<label<?=(in_array('project_name', $error_fields) ? ' class="control-group error"' : '')?>><span class="span2">Project Name:</span> <input type="text" name="project_name"<?=(array_key_exists('project_name', $_POST) ? ' value="'.$_POST['project_name'].'"' : '')?> /></label>
				<label<?=(in_array('project_branch', $error_fields) ? ' class="control-group error"' : '')?>><span class="span2">Branch:</span> 
					<select name="project_branch_id" class="hide"></select>
					<input type="text" name="project_branch"<?=(array_key_exists('project_branch', $_POST) ? ' value="'.$_POST['project_branch'].'"' : '')?> />
				</label>
				<label<?=(in_array('project_destination', $error_fields) ? ' class="control-group error"' : '')?>><span class="span2">Deploy To:</span> <input type="text" name="project_destination"<?=(array_key_exists('project_destination', $_POST) ? ' value="'.$_POST['project_destination'].'"' : '')?> /></label>
		    </div>
	    </div>
    </div>
    <div class="row">
    	<div class="span12">
	    	<a href="<?=url_for('/projects')?>" class="btn btn-large">Cancel</a>
    		<input type="submit" class="btn btn-primary btn-large pull-right" value="Add Project"/>
   		</div>
	</div>
</form>
<script type="text/javascript">
	$('select[name="repository_id"]').change(function(event){
		var self = $(this);
		$('select[name="project_branch_id"]').hide();
		if (self.val() == '') {
			$('input[name="project_branch"]').show();
			$('.new-repository').show();
		} else {
			var show_branches = function(repository_id) {
				var data = self.data('cache');
				for (var j = 0, jlimit = data.length; j < jlimit; j++) {
					if (data[j].id == repository_id) {
						var branches = data[j].branches;
						var select = $('select[name="project_branch_id"]').empty();
						for (var i = 0, ilimit = branches.length; i < ilimit; i++) {
							select.append('<option value="'+i+'">'+branches[i]+'</option>');
						}
						select.show();
					}
				}
			};
			if (!self.data('cache')) {
				$.getJSON('<?=url_for('/projects/repositories')?>', function(data, textStatus, jqXHR){
					self.data('cache', data);
					show_branches(self.val());
				});
			} else {
				show_branches(self.val());
			}
			$('input[name="project_branch"]').hide();
			$('.new-repository').hide();
		}
	}).trigger('change');
</script>