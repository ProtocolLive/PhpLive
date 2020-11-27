<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.11.27.00

if(version_compare(phpversion(), '8', '>=')):
  require(__DIR__ . '/pdo80.php');
elseif(version_compare(phpversion(), '7.4', '>=')):
  require(__DIR__ . '/pdo74.php');
else:
  require(__DIR__ . '/pdo73.php');
endif;