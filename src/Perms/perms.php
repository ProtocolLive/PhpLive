<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.11.27.00

if(version_compare(phpversion(), '8', '>=')):
  require(__DIR__ . '/perms80.php');
elseif(version_compare(phpversion(), '7.4', '>=')):
  require(__DIR__ . '/perms74.php');
else:
  require(__DIR__ . '/perms73.php');
endif;