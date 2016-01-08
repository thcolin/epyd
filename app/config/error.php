<?php

  $app -> error(function(Exception $e, $code) use($app){
    return $app -> json(['error' => $e -> getMessage(), 'type' => get_class($e)], $code);
  });

?>
