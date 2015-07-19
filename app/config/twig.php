<?php
	
	# Give $app['twig']

	$app -> register(new \Silex\Provider\TwigServiceProvider(), ['twig.path' => PATH_RESOURCES.'/views']);
	
?>