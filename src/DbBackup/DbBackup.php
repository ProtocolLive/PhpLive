<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.07.14.00

if(version_compare(phpversion(), '7.3')):
  require(__DIR__ . '/DbBackup73.php');
elseif(version_compare(phpversion(), '7.4')):
  require(__DIR__ . '/DbBackup74.php');
endif;