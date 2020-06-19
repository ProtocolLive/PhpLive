<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.06.19.01

if(version_compare(phpversion(), '7.3')):
  require(__DIR__ . '/pdo73.php');
elseif(version_compare(phpversion(), '7.4')):
  require(__DIR__ . '/pdo74.php');
endif;