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
    if(ini_get("display_errors") == true){
      $Conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION || PDO::ERRMODE_WARNING);
    }
  }catch(PDOException $e){
    if(ini_get("display_errors") == true or isset($ErrPdo[$e->getCode()]) == false){
      Erro($e->getMessage());
    }else{
      Erro($ErrPdo[$e->getCode()]);
    }
  }
}

function SQL($Query, $Params = null, $Log = null, $User = null, $Target = null, &$Conn = null, $ShowDump = false){
  global $ErrPdo, $DbLastConn;
  try{
    if($Conn == null){
      if($DbLastConn == null){
	      Erro("Você não iniciou uma conexão a um banco de dados");
      }else{
	      $Conn = &$DbLastConn;
      }
    }
    $Query = Clean($Query);
    $comando = explode(" ", $Query);
    $comando = strtolower($comando[0]);
    $result = $Conn->prepare($Query);
    if($Params != null){
      foreach($Params as &$Param){
        if(count($Param) != 3){
          Erro("Quantidade incorreta de parâmetros ao especificar um placehole");
        }else{
          if($Param[2] == PDO::PARAM_INT and strpos($Param[1], ",") !== false){
            $Param[1] = str_replace(",", ".", $Param[1]);
            $Param[2] = PDO::PARAM_STR;
          }
          $result->bindValue($Param[0], $Param[1], $Param[2]);
        }
      }
    }
    $result->execute();
    if($ShowDump == true){
      $result->debugDumpParams();
    }
    if($comando == "select" or $comando == "show" or $comando == "call"){
      $retorno = $result->fetchAll();
    }elseif($comando == "insert"){
      $retorno = $Conn->lastInsertId();
    }
    if($Log != null and $User != null){
      SqlLog($User, $result->debugDumpParams(), $Log, $Target, $Conn);
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

function Clean($Query){
  $Query = str_replace("\n", "", $Query);
  $Query = str_replace("\t", "", $Query);
  $Query = str_replace("\r", " ", $Query);
  $Query = trim($Query);
  return $Query;
}

function SqlLog($User, $Dump, $Tipo, $Target, $Conn){
  $lixo = $Conn->prepare("insert into sys_logs(timestamp,user_id,tipo,ip,ipreverse,agent,query,target) values(?,?,?,?,?,?,?,?)");
  $lixo->bindValue(1, time(), PDO::PARAM_INT);
  $lixo->bindValue(2, $User, PDO::PARAM_INT);
  $lixo->bindValue(3, $Tipo, PDO::PARAM_INT);
  $lixo->bindValue(4, $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
  $lixo->bindValue(5, gethostbyaddr($_SERVER["REMOTE_ADDR"]), PDO::PARAM_STR);
  $lixo->bindValue(6, $_SERVER["HTTP_USER_AGENT"], PDO::PARAM_STR);
  $lixo->bindValue(7, $Dump, PDO::PARAM_STR);
  $lixo->bindValue(8, $Target, $Target == null? PDO::PARAM_NULL : PDO::PARAM_INT);
  $lixo->execute();
}