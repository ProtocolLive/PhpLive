<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PHP-Live/
// Version 2019122201

$DbLastConn = null;
$DbPrefix = null;
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

// Options
// Prefix (str) Change ## for the tables prefix
//    select * from ##users (Prefix = "sys") -> select * from sys_users
// Charset (str) UTF8 as default
// Conn (pointer) Return an object of connection
/**
 * @param string $Drive
 * @param string $Ip
 * @param string $User
 * @param string $Pwd
 * @param string $Db
 * @param string $Ip
 * @param array $Options
 */
function SqlConnect($Drive, $Ip, $User, $Pwd, $Db, $Options = null){
  global $ErrPdo, $DbLastConn, $DbPrefix;
  if(isset($Options["Charset"]) == false){
    $Options["Charset"] = "utf8";
  }
  try{
    if(isset($Options["Conn"]) == false){
      $Options["Conn"] = &$DbLastConn;
    }
    if(isset($Options["Prefix"])){
      $DbPrefix = $Options["Prefix"];
    }
    $Options["Conn"] = new PDO("$Drive:host=$Ip;dbname=$Db;charset=" . $Options["Charset"], $User, $Pwd);
    if(ini_get("display_errors") == true){
      $Options["Conn"]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION || PDO::ERRMODE_WARNING);
    }
  }catch(PDOException $e){
    if(ini_get("display_errors") == true or isset($ErrPdo[$e->getCode()]) == false){
      Erro($e->getMessage());
    }else{
      Erro($ErrPdo[$e->getCode()]);
    }
  }
}

// Options:
// Log (int) Event to be logged
// User (int) User executing the query
// Target (int) User efected
// Conn (object) Connection
// Debug (boolean) Dump the query for debug
/**
 * @param string $Query
 * @param array $Params
 * @param array $Options
 * @return mixed
 */
function SQL($Query, $Params = null, $Options = null){
  global $ErrPdo, $DbLastConn, $DbPrefix;
  try{
    if(isset($Options["Conn"]) == false){
      if($DbLastConn == null){
	      Erro("Você não iniciou uma conexão a um banco de dados");
      }else{
	      $Options["Conn"] = &$DbLastConn;
      }
    }
    $Query = Clean($Query);
    if($DbPrefix != null){
      $Query = str_replace("##", $DbPrefix . "_", $Query);
    }
    $comando = explode(" ", $Query);
    $comando = strtolower($comando[0]);
    $result = $Options["Conn"]->prepare($Query);
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
    if(isset($Options["Debug"]) and $Options["Debug"] == true){
      $result->debugDumpParams();
    }
    if($comando == "select" or $comando == "show" or $comando == "call"){
      $retorno = $result->fetchAll();
    }elseif($comando == "insert"){
      $retorno = $Conn->lastInsertId();
    }
    if(isset($Options["Log"]) and $Options["Log"] != null and 
    isset($Options["User"]) and $Options["User"] != null){
      SqlLog($Options["User"], $result->debugDumpParams(), $Options["Log"], $Options["Target"], $Options["Conn"]);
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

/**
 * @param string $Query
 * @return string
 */
function Clean($Query){
  $Query = str_replace("\n", "", $Query);
  $Query = str_replace("\t", "", $Query);
  $Query = str_replace("\r", " ", $Query);
  $Query = trim($Query);
  return $Query;
}

/**
 * @param string $Fields
 * @return string
 */
function InsertHoles($Fields){
  $count = substr_count($Fields, ",");
  $return = "";
  for($i = 0; $i <= $count; $i++){
    $return .= "?,";
  }
  $return = substr($return, 0, -1);
  return "(" . $Fields . ") values(" . $return . ")";
}

// Options:
// Conn (object) The database connection
// User (int) User executing the query
// Dump (str) The dump created by SQL function
// Type (int) Action identification
// Target (int) User afected by query
/**
 * @param array $Options
 */
function SqlLog($Options = []){
  global $DbPrefix;
  $temp = $Options["Conn"]->prepare("insert into " . $DbPrefix != null? "##" : null . "sys_logs" .
    InsertHoles("timestamp,user_id,type,ip,ipreverse,agent,query,target"));
  $temp->bindValue(1, time(), PDO::PARAM_INT);
  $temp->bindValue(2, $Options["User"], PDO::PARAM_INT);
  $temp->bindValue(3, $Options["Type"], PDO::PARAM_INT);
  $temp->bindValue(4, $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR);
  $temp->bindValue(5, gethostbyaddr($_SERVER["REMOTE_ADDR"]), PDO::PARAM_STR);
  $temp->bindValue(6, $_SERVER["HTTP_USER_AGENT"], PDO::PARAM_STR);
  $temp->bindValue(7, $Options["Dump"], PDO::PARAM_STR);
  $temp->bindValue(8, $Options["Target"], $Options["Target"] == null? PDO::PARAM_NULL : PDO::PARAM_INT);
  $temp->execute();
}