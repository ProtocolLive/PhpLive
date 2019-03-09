<?php
//Protocol Corporation Ltda.
//https://github.com/protocollive
//Version 2018061201

function PhpUpdate($Linux = false){
  if(extension_loaded("openssl") == false){
    return false;
  }
  if($Linux = false){
    $pagina = @fopen("https://windows.php.net/download/", "r");
    if($pagina !== null and $pagina !== false){
      do{
	$linha = fgets($pagina);
      }while(strpos($linha, "id=\"php-7.3\"") === false);
      $linha = substr($linha, strpos($linha, "(") + 1);
      $linha = substr($linha, 0, strpos($linha, ")"));
    }
  }else{
    $pagina = fopen("https://secure.php.net/downloads.php", "r");
    if($pagina !== null and $pagina !== false){
      do{
	$linha = fgets($pagina);
      }while(strpos($linha, "release-state") === false);
      $linha = fgets($pagina);
      $linha = substr($linha, strpos($linha, "PHP") + 4);
      $linha = substr($linha, 0, strpos($linha, "("));
    }
  }
  return trim($linha);
}