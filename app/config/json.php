<?php
	
	use Kumatch\Silex\JsonBodyProvider;
	
	$app -> register(new JsonBodyProvider());
	
?>