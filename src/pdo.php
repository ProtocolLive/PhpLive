<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PHP-Live/
// Version 201903071104

function Erro($msg){
  // Backtrace = 1
  $Debug = 1;
  
  $debug = debug_backtrace();
  $debug = end($debug);
  echo "<br>";
  echo $msg . " <b>em</b> " . $debug["file"] . " <b>na linha</b> " . $debug["line"];
  if(($Debug & 1) == 1){
    echo "<pre>";
    var_dump(debug_backtrace());
  }
  die();
}

$ErrPdo = [
  "01000" => "Foram utilizados dados incorretos em um campo do banco de dados",
  "1040" => "Foi atingido o limite de conexões ao banco de dados",
  "1045" => "Acesso negado ao banco de dados",
  "2002" => "Não foi possível se conectar ao banco de dados. Atualize a página ou tente de novo em alguns minutos",
  "23000" => "Não foi possível atualizar o banco de dados devido a uma restrição de registro. Isso pode ocorrer quando você tentou cadastrar um dado duplicado ou apagar um dado que depende de outro",
  "42000" => "Erro de sintaxe no comando do banco de dados",
  "42S02" => "Tabela inexistente no banco de dados",
  "42S22" => "Campo inexistente na tabela do banco de dados",
  "HY000" => "Tipo de valor incompatível com o campo do banco de dados",
  "HY093" => "Quantidade incorreta de parâmetros especificado"
];

$DbLastConn = null;

function SqlConnect($Drive, $Ip, $User, $Pass, $Db, $CharSet = "utf8", &$Conn = null){
  global $ErrPdo, $DbLastConn;
  try{
    if($Conn == null){
      $Conn = &$DbLastConn;
    }
    $Conn = new PDO("$Drive:host=$Ip;dbname=$Db;charset=$CharSet", $User, $Pass);
    $Conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }catch(PDOException $e){
    if(ini_get("display_errors") or ! isset($ErrPdo[$e->getCode()])){
      Erro($e->getMessage());
    }else{
      Erro($ErrPdo[$e->getCode()]);
    }
  }
}

function SQL($Query, $Params = null, $Log = null, $User = null, $Target = null, &$Conn = null){
  global $ErrPdo, $DbLastConn;
  try{
    if($Conn == null){
      if($DbLastConn == null){
	      Erro("Você não iniciou uma conexão a um banco de dados");
      }else{
	      $Conn = &$DbLastConn;
      }
    }
    $Query = Limpa($Query);
    $comando = explode(" ", $Query);
    $comando = strtolower($comando[0]);
    $result = $Conn->prepare($Query);
    if(!is_null($Params)){
      foreach($Params as $Param){
        if(count($Param) != 3){
          Erro("Quantidade incorreta de parâmetros ao especificar um placehole");
        }else{
          if($Param[2] == PDO::PARAM_INT){
            $Param[1] = str_replace(",", ".", $Param[1]);
          }
          $result->bindValue($Param[0], $Param[1], $Param[2]);
        }
      }
    }
    $result->execute();
    if($comando == "select" or $comando == "show" or $comando == "call"){
      $retorno = $result->fetchAll();
    }elseif($comando == "insert"){
      $retorno = $Conn->lastInsertId();
    }
    if(!is_null($Log) and !is_null($User)){
      SqlLog($User, $Query, $Params, $Log, $Target, $Conn);
    }
    if(isset($retorno)){
      return $retorno;
    }
  }catch(PDOException $e){
    if(ini_get("display_errors") == true or isset($ErrPdo[$e->getCode()]) == false){
      Erro($e->getMessage());
    }else{
      Erro($ErrPdo[$e->getCode()]);
    }
  }
}

function Limpa($Query){
  $Query = str_replace("\n", "", $Query);
  $Query = str_replace("\t", "", $Query);
  $Query = str_replace("\r", " ", $Query);
  $Query = trim($Query);
  return $Query;
}

function SqlLog($User, $Query, $Params, $Tipo, $Target, $Conn){
  $Query = PlaceHoles($Query, $Params);
  $lixo = "insert into sys_logs(timestamp,user_id,tipo,ip,ipreverso,agent,query";
  if($Target != null){
    $lixo .= ",target";
  }
  $lixo .= ") values(?,?,?,?,?,?,?";
  if($Target != null){
    $lixo .= ",?";
  }
  $lixo .= ")";
  $lixo = $Conn->prepare($lixo);
  $lixo->bindValue(1, time(), PDO::PARAM_INT);
  $lixo->bindValue(2, $User, PDO::PARAM_INT);
  $lixo->bindValue(3, $Tipo, PDO::PARAM_INT);
  $lixo->bindValue(4, $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
  $lixo->bindValue(5, gethostbyaddr($_SERVER["REMOTE_ADDR"]), PDO::PARAM_STR);
  $lixo->bindValue(6, $_SERVER["HTTP_USER_AGENT"], PDO::PARAM_STR);
  $lixo->bindValue(7, $Query, PDO::PARAM_STR);
  if($Target != null){
    $lixo->bindValue(8, $Target, PDO::PARAM_INT);
  }
  $lixo->execute();
}

function PlaceHoles($Query, $Params){
  foreach($Params as $Param){
    if(is_numeric($Param[0])){
      if($Param[2] == PDO::PARAM_STR){
        $Query = preg_replace("/\?/", "'" . $Param[1] . "'", $Query, 1);
      }elseif($Param[2] == PDO::PARAM_NULL){
        $Query = preg_replace("/\?/", "null", $Query, 1);
      }else{
        $Query = preg_replace("/\?/", $Param[1], $Query, 1);
      }
    }else{
      if($Param[2] == PDO::PARAM_STR){
        $Query = str_replace($Param[0], "'" . $Param[1] . "'", $Query);
      }elseif($Param[2] == PDO::PARAM_NULL){
        $Query = str_replace($Param[0], "null", $Query);
      }else{
        $Query = str_replace($Param[0], $Param[1], $Query);
      }
    }
  }
  return $Query;
}

function SQLdebug($Query, $Params = null, $Log = null, $User = null, $Target = null, $Conn = null){
  echo "Query: |$Query|<br>";
  echo "Query editada: " . PlaceHoles($Query, $Params) . "<br>";
  echo "Comando: |" . strtoupper(reset(explode(" ", $Query))) . "|<br>";
  $result = SQL($Query, $Params, $Log, $User, $Target, $Conn);
  echo "<pre>";
  var_dump($result);
  die();
}