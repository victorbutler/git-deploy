<h1>Add a New Project</h1>
<form action="<?=url_for('projects/new')?>" method="post">
	<div class="well">
		<h2>Repository</h2>
		<label><span class="span2">Name</span> <input type="text" name="repositroy_name" /></label>
		<label><span class="span2">Remote Origin</span> <input type="text" name="repository_remote" /></label>
	</div>
	<label><span class="span2">Project Name</span> <input type="text" name="project_name" /></label>
	<label><span class="span2">Branch</span> <input type="text" name="project_branch" /></label>
	<input type="submit" class="btn btn-primary" value="Create"/>
</form>