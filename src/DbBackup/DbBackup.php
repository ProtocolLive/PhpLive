<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.11.29.00

if(version_compare(phpversion(), '8', '>=')):
  require(__DIR__ . '/DbBackup80.php');
else:
  require(__DIR__ . '/DbBackup74.php');
endif;