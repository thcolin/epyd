<?php

  $app -> register(new Silex\Provider\MonologServiceProvider, [
    'monolog.logfile' => PATH_APP.'/storage/epyd.log'
  ]);

?>
