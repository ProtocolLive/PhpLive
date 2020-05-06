<?php
//Protocol Corporation Ltda.
//https://github.com/ProtocolLive/PhpLive
//Version 2020.05.05.00

function PhpUpdate():string{
  if(extension_loaded('openssl') == false):
    return false;
  endif;
  $ini = ini_get('default_socket_timeout');
  ini_set('default_socket_timeout', 1);
  $text = @file_get_contents('https://www.php.net/downloads');
  ini_set('default_socket_timeout', $ini);
  $text = substr($text, strpos($text, 'Current Stable'));
  $text = substr($text, strpos($text, 'PHP') + 4);
  $text = substr($text, 0, strpos($text, ' '));
  return $text;
}