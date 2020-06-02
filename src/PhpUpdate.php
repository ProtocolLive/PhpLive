<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/PhpLive
//Version 2020.06.02.00

function PhpUpdate():string{
  if(extension_loaded('openssl') == false):
    return false;
  endif;
  $version = @file_get_contents('https://www.php.net/releases/index.php?json&max=1');
  if($version === false):
    return false;
  else:
    $version = json_decode($version, true);
    $version = reset($version);
    return $version['version'];
  endif;
}