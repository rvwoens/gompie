<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<title>Former: yaml based database forms for laravel</title>
	<meta name="viewport" content="width=device-width">

	<?=HTML::style(URL::$base.'/bundles/bootstrapper/css/bootstrap.css')?>
	<?=HTML::style(URL::$base.'/css/style.css')?>
	<?=HTML::script(URL::$base.'/laravel/js/modernizr-2.5.3.min.js') ?>
	<?=HTML::script(URL::$base.'/bundles/bootstrapper/js/bootstrap.js') ?>
	<?=HTML::script('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js') ?>
</head>
<body>
	<div class="container">
		<header>
			<h1>Former</h1>
			<h2>YAML based database forms for twitter bootstap</h2>
		</header>
		<?=$content?>
	</div>

</body>
</html>