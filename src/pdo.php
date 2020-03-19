<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-03-19-02

$DbLastConn = null;
$DbPrefix = null;
define("PdoStr", PDO::PARAM_STR);
define("PdoInt", PDO::PARAM_INT);
define("PdoNull", PDO::PARAM_NULL);
define("PdoBool", PDO::PARAM_BOOL);
define("PdoSql", 6);

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
  if(isset($Options["Safe"]) == false) $Options["Safe"] = true;
  
  if(isset($Options["Conn"]) == false){
    if($DbLastConn == null){
      throw new Exception("You have not connected to a database");
    }else{
      $Options["Conn"] = &$DbLastConn;
    }
  }
  $Query = Clean($Query);
  if($DbPrefix != null){
    $Query = str_replace("##", $DbPrefix . "_", $Query);
  }else{
    $Query = str_replace("##", "", $Query);
  }
  $command = explode(" ", $Query);
  $command = strtolower($command[0]);
  //Search from PdoSql and parse
  if($Params != null){
    foreach($Params as $id => $Param){
      if($Param[2] == PdoSql){
        if(is_numeric($Param[0])){
          $out = 0;
          for($i = 1; $i <= $Param[0]; $i++){
            $in = strpos($Query, "?", $out);
            $out = $in + 1;
          }
        }else{
          $in = strpos($Query, $Param[0]);
          $out = strpos($Query, ",", $in);
          if($out === false){
            $out = strpos($Query, ")", $in);
          }
        }
        $temp = substr($Query, 0, $in);
        $temp .= $Param[1];
        $Query = $temp . substr($Query, $out);
        unset($Params[$id]);
      }
    }
  }
  //Prepare
  $result = $Options["Conn"]->prepare($Query);
  //Bind tokens
  if($Params != null){
    foreach($Params as &$Param){
      if(count($Param) != 3){
        throw new Exception("Incorrect number of parameters when specifying a token");
      }else{
        if($Param[2] == PdoInt){
          $Param[1] = str_replace(",", ".", $Param[1]);
          if(strpos($Param[1], ".") !== false){
            $Param[2] = PdoStr;
          }
        }elseif($Param[2] == PdoBool){
          $Param[1] = $Param[1] == "true"? true: false;
        }
        $result->bindValue($Param[0], $Param[1], $Param[2]);
      }
    }
  }
  //Safe execution
  if($Options["Safe"] == true){
    if($command == "truncate" or (($command == "update" or $command == "delete") and strpos($query, "where") === false)){
      return false;
    }
  }
  //Execute
  $result->execute();
  //Debug
  if(isset($Options["Debug"]) and $Options["Debug"] == true){?>
    <pre style="text-align:left">
      <?php $result->debugDumpParams();?><br>
      <?php debug_print_backtrace();?>
    </pre><?php
  }
  //Return
  if($command == "select" or $command == "show" or $command == "call"){
    $return = $result->fetchAll();
  }elseif($command == "insert"){
    $return = $Options["Conn"]->lastInsertId();
  }elseif($command == "update" or $command == "delete"){
    $return = $result->rowCount();
  }
  //Log
  if(isset($Options["Log"]) and $Options["Log"] != null and 
  isset($Options["User"]) and $Options["User"] != null){
    ob_start();
    $result->debugDumpParams();
    $dump = ob_get_contents();
    ob_end_clean();
    $dump = substr($dump, strpos($dump, "Sent SQL: ["));
    $dump = substr($dump, strpos($dump, "] ") + 2);
    $dump = substr($dump, 0, strpos($dump, "Params: "));
    $dump = trim($dump);
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
      throw new Exception("Você não iniciou uma conexão a um banco de dados");
    }else{
      $Options["Conn"] = &$DbLastConn;
    }
  }
  SqlInsert([
    "Table" => "sys_logs",
    "Fields" => [
      ["time", date("Y-m-d H:i:s"), PdoStr],
      ["user_id", $Options["User"], PdoInt],
      ["log", $Options["Log"], PdoInt],
      ["ip", $_SERVER["REMOTE_ADDR"], PdoStr],
      ["ipreverse", gethostbyaddr($_SERVER["REMOTE_ADDR"]), PdoStr],
      ["agent", $_SERVER["HTTP_USER_AGENT"], PdoStr],
      ["query", $Options["Dump"], PdoStr],
      ["target", $Options["Target"], $Options["Target"] == null? PdoNull : PdoInt]
    ]
  ]);
}

/**
 * @param string $Table
 * @param array $Fields
 * @return int
 */
function SqlInsert($Options = [], $Options2 = []){
  $return = "insert into " . $Options["Table"] . "(";
  $holes = [];
  $i = 1;
  foreach($Options["Fields"] as $field){
    if($field[0] == "order" or $field[0] == "default"){
      $return .= "`" . $field[0] . "`";
    }else{
      $return .= $field[0];
    }
    $return .= ",";
    $holes[] = [$i, $field[1], $field[2]];
    $i++;
  }
  $return = substr($return, 0, -1);
  $return .= ") values(";
  for($j = 1; $j < $i; $j++){
    $return .= "?,";
  }
  $return = substr($return, 0, -1);
  $return .= ");";
  return SQL($return, $holes, $Options2);
}

/**
 * @param string $Table
 * @param array $Fields
 * @param array $Where
 * @return int
 */
function SqlUpdate($Options = [], $Options2 = []){
  $return = "update " . $Options["Table"] . " set ";
  $holes = [];
  $i = 1;
  foreach($Options["Fields"] as $field){
    if($field[0] == "order" or $field[0] == "default"){
      $return .= "`" . $field[0] . "`";
    }else{
      $return .= $field[0];
    }
    $return .= "=?,";
    $holes[] = [$i, $field[1], $field[2]];
    if($field[2] != PdoSql){
      $i++;
    }
  }
  $return = substr($return, 0, -1);
  $return .= " where " . $Options["Where"][0] . "=?";
  $holes[] = [$i, $Options["Where"][1], $Options["Where"][2]];
  return SQL($return, $holes, $Options2);
}