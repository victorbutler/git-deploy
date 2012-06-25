<div class="navbar">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?=url_for('/')?>">Git Deploy</a>
			<ul class="nav">
				<li<?=(request_uri() == '/projects' ? ' class="active"' : '')?>>
					<a href="<?=url_for('/projects')?>">
<?php
if ($num_of_repositories):
?>
					<span class="badge"><?=$num_of_repositories?></span>
<?php
endif;
?>					 Projects</a></li>
				<!-- <li><a href="<?=url_for('/activity')?>"><span class="badge badge-success">2</span> Activity</a></li> -->
			</ul>
		</div>
	</div>
</div>