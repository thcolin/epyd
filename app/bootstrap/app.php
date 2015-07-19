<?php
	
	# App
	
	$app = include __DIR__.'/../app.php';
	
	# Vars
	
	if(is_file(__DIR__.'/../vars.php'))
	
		include(__DIR__.'/../vars.php');
	
	# Paths
	
	include __DIR__.'/../paths.php';
	
	# Configs
	
	foreach(glob(__DIR__.'/../config/*.php') as $config)
	
		include $config;
	
	# Env
	
	include __DIR__.'/../env.php';
	
	# Routes
	
	include __DIR__.'/../routes.php';
	
?>