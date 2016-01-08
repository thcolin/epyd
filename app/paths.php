<?php
	
	# BasePaht
	
	define('BASE_PATH', substr($_SERVER['SCRIPT_NAME'], 0, (strlen($_SERVER['SCRIPT_NAME']) - strlen('/public/index.php'))));
	
	# Sytem
	
	$root = dirname(__DIR__);

	$paths = [
		'app' => __DIR__,
		'root' => dirname(__DIR__),
		'resources' => dirname(__DIR__).'/resources',
		'public' => dirname(__DIR__).'/public'
	];
	
	foreach($paths as $name => $path){
	
		define('PATH_'.strtoupper($name), $path);
		define('RPATH_'.strtoupper($name), substr($path, strlen($root) + 1));
		
	}
	
?>