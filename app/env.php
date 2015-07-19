<?php
	
	/* Main */

	date_default_timezone_set('Europe/Paris');
	
	/* Dev */
	
	//error_reporting(E_ALL ^ (E_STRICT|E_DEPRECATED));
	//$app['debug'] = true;
	
	/* Prod */
		
	error_reporting(0);
	$app['debug'] = false;
	
?>