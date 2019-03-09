<?php
//Protocol Corporation Ltda.
//https://github.com/protocollive
//Version 2018061001

function Cpf($Cpf){
  $Cpf = str_replace(".", "", $Cpf);
  $Cpf = str_replace("-", "", $Cpf);
  $Erro = ["00000000000", "11111111111", "22222222222", "33333333333", "44444444444",
  "55555555555", "66666666666", "77777777777", "88888888888", "99999999999"
  ];
  if(strlen($Cpf) != 11 or in_array($Cpf, $Erro)){
    return false;
  }

  $temp = substr($Cpf, 0, -2);
  $temp = str_split($temp);
  $temp = array_reverse($temp);

  $c1 = 0;
  for($i = 0; $i < 9; $i++){
    $c1 += $temp[$i] * ($i + 2);
  }
  $c1 %= 11;
  if($c1 < 2){
    $c1 = 0;
  }else{
    $c1 = 11 - $c1;
  }
  $temp = array_merge([$c1], $temp);

  $c2 = 0;
  for($i = 0; $i < 10; $i++){
    $c2 += $temp[$i] * ($i + 2);
  }
  $c2 %= 11;
  if($c2 < 2){
    $c2 = 0;
  }else{
    $c2 = 11 - $c2;
  }

  if(substr($Cpf, -2) == $c1 . $c2){
    return true;
  }else{
    return false;
  }
}