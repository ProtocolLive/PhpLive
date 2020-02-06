<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-02-06-00

$DbLastConn = null;
$DbPrefix = null;

/**
 * @param string $Drive (Optional) MySql as default
 * @param string $Ip
 * @param string $User
 * @param string $Pwd
 * @param string $Db
 * @param string $Prefix (Optional) Change ## for the tables prefix Ex: select * from ##users (Prefix = "sys") -> select * from sys_users
 * @param string $Charset (Optional) UTF8 as default
 * @param int $TimeOut (Optional) Connection timeout
 * @return object Connection
 */
function SqlConnect($Options = []){
  global $DbLastConn, $DbPrefix;
  if(isset($Options["Drive"]) == false) $Options["Drive"] = "mysql";
  if(isset($Options["Charset"]) == false) $Options["Charset"] = "utf8";
  if(isset($Options["TimeOut"]) == false) $Options["TimeOut"] = 5;
  if(isset($Options["Prefix"])){
    $DbPrefix = $Options["Prefix"];
  }
  $DbLastConn = new PDO(
    $Options["Drive"] . ":host=" . $Options["Ip"] . ";dbname=" . $Options["Db"] . ";charset=" . $Options["Charset"],
    $Options["User"],
    $Options["Pwd"]
  );
  $DbLastConn->setAttribute(PDO::ATTR_TIMEOUT, $Options["TimeOut"]);
  if(ini_get("display_errors") == true){
    $DbLastConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION || PDO::ERRMODE_WARNING);
  }
  return $DbLastConn;
}

/**
 * @param string $Query
 * @param array $Params
 * @param int $Log (Options)(Optional) Event to be logged
 * @param int $User (Options)(Optional) User executing the query
 * @param int $Target (Options)(Optional) User efected
 * @param object $Conn (Options)(Optional) Connection
 * @param boolean $Debug (Options)(Optional) Dump the query for debug
 * @return mixed
 */
function SQL($Query, $Params = null, $Options = []){
  global $DbLastConn, $DbPrefix;
  if(isset($Options["Target"]) == false) $Options["Target"] = null;
  
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
        if($Param[2] == PDO::PARAM_INT){
          $Param[1] = str_replace(",", ".", $Param[1]);
          if(strpos($Param[1], ".") !== false){
            $Param[2] = PDO::PARAM_STR;
          }
        }elseif($Param[2] == PDO::PARAM_BOOL){
          $Param[1] = $Param[1] == "true"? true: false;
        }
        $result->bindValue($Param[0], $Param[1], $Param[2]);
      }
    }
  }
  $result->execute();
  if(isset($Options["Debug"]) and $Options["Debug"] == true){?>
    <pre style="text-align:left"><?php $result->debugDumpParams();?></pre><?php
  }
  if($comando == "select" or $comando == "show" or $comando == "call"){
    $return = $result->fetchAll();
  }elseif($comando == "insert"){
    $return = $Options["Conn"]->lastInsertId();
  }else{
    $return = true;
  }
  if(isset($Options["Log"]) and $Options["Log"] != null and 
  isset($Options["User"]) and $Options["User"] != null){
    ob_start();
    $result->debugDumpParams();
    $dump = ob_get_contents();
    ob_end_clean();
    SqlLog([
      "User" => $Options["User"],
      "Dump" => $dump,
      "Log" => $Options["Log"],
      "Target" => $Options["Target"],
      "Conn" => $Options["Conn"]
    ]);
    
  }
  return $return;
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

/**
 * @param object $Conn (Optional)
 * @param int $User User executing the query
 * @param string $Dump The dump created by SQL function
 * @param int $Type Action identification
 * @param int $Target User afected by query
 */
function SqlLog($Options = []){
  global $DbLastConn, $DbPrefix;
  if(isset($Options["Conn"]) == false){
    if($DbLastConn == null){
      Erro("Você não iniciou uma conexão a um banco de dados");
    }else{
      $Options["Conn"] = &$DbLastConn;
    }
  }
  SQL("insert into sys_logs" .
    InsertHoles("time,user_id,log,ip,ipreverse,agent,query,target"), [
    [1, date("Y-m-d H:i:s"), PDO::PARAM_STR],
    [2, $Options["User"], PDO::PARAM_INT],
    [3, $Options["Log"], PDO::PARAM_INT],
    [4, $_SERVER["REMOTE_ADDR"], PDO::PARAM_STR],
    [5, gethostbyaddr($_SERVER["REMOTE_ADDR"]), PDO::PARAM_STR],
    [6, $_SERVER["HTTP_USER_AGENT"], PDO::PARAM_STR],
    [7, $Options["Dump"], PDO::PARAM_STR],
    [8, $Options["Target"], $Options["Target"] == null? PDO::PARAM_NULL : PDO::PARAM_INT]
  ]);
}