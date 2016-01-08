<?php

	# Angular
	$app -> mount(BASE_PATH.'/', new \Epyd\Controllers\AngularController());

	# API
	$app -> mount(BASE_PATH.'/api', new \Epyd\Controllers\EpydController());

?>
