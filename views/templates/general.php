<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta http-equiv="imagetoolbar" content="false">
	<title><?php if (isset($title)) echo $title; else echo 'Deploy System'; ?></title>
	<link rel="stylesheet" href="assets/css/bootstrap.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="assets/css/bootstrap-responsive.css" type="text/css" media="screen" />
	<link rel="stylesheet" href="assets/css/deploy.css" type="text/css" media="screen" />
	<script type="text/javascript" src="assets/js/jquery-1.7.2.min.js"></script>
	<script type="text/javascript" src="assets/js/bootstrap.js"></script>
	<?php if (isset($head)) echo $head; ?>
</head>
<body>
	<div class="container-fluid">
		<?php include('header.php'); ?>
		<?php echo $content; ?>
		<?php include('footer.php'); ?>
	</div>
</body>
</html>