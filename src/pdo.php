<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.04.27.01

define("PdoStr", PDO::PARAM_STR);
define("PdoInt", PDO::PARAM_INT);
define("PdoNull", PDO::PARAM_NULL);
define("PdoBool", PDO::PARAM_BOOL);
define("PdoSql", 6);

class PhpLivePdo{
  private ?object $Conn = null;
  private string $Prefix = "";
  private array $Error = [];

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
  public function __construct(array $Options){
    $Options["Drive"] ??= "mysql";
    $Options["Charset"] ??= "utf8";
    $Options["TimeOut"] ??= 5;
    $this->Prefix = $Options["Prefix"]?? "";

    $this->Conn = new PDO(
      $Options["Drive"] . ":host=" . $Options["Ip"] . ";dbname=" . $Options["Db"] . ";charset=" . $Options["Charset"],
      $Options["User"],
      $Options["Pwd"]
    );
    $this->Conn->setAttribute(PDO::ATTR_TIMEOUT, $Options["TimeOut"]);
  }

  /**
   * @param string $Query
   * @param array $Params
   * @param int $Log (Options)(Optional) Event to be logged
   * @param int $User (Options)(Optional) User executing the query
   * @param int $Target (Options)(Optional) User efected
   * @param boolean $Debug (Options)(Optional) Dump the query for debug
   * @return mixed
   */
  public function SQL(string $Query, array $Params = [], array $Options = []){
    $Options["Target"] ??= null;
    $Options["Safe"] ??= true;

    $Query = $this->Clean($Query);
    if($this->Prefix !== null){
      $Query = str_replace("##", $this->Prefix . "_", $Query);
    }else{
      $Query = str_replace("##", "", $Query);
    }
    $command = explode(" ", $Query);
    $command = strtolower($command[0]);
    //Search from PdoSql and parse
    foreach($Params as $id => $Param){
      if($Param[2] == PdoSql){
        if(is_numeric($Param[0])){
          $out = 0;
          for($i = 1; $i <= $Param[0]; $i++){
            $in = strpos($Query, "?", $out);
          }
          $Query = substr_replace($Query, $Param[1], $in, 1);
        }else{
          $in = strpos($Query, $Param[0]);
          $out = strpos($Query, ",", $in);
          if($out === false){
            $out = strpos($Query, ")", $in);
          }
          $Query = substr_replace($Query, $Param[1], $in, $out);
        }
        unset($Params[$id]);
      }
    }
    //Prepare
    $result = $this->Conn->prepare($Query);
    //Bind tokens
    if($Params != null){
      foreach($Params as &$Param){
        if(count($Param) != 3){
          $this->SetError(1, "Incorrect number of parameters when specifying a token");
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
      if($command == "truncate" or (($command == "update" or $command == "delete") and strpos($Query, "where") === false)){
        $this->SetError(2, "Query not allowed in safe mode");
      }
    }
    //Execute
    $result->execute();
    //Error
    $error = $result->errorInfo();
    if($error[0] != "00000"){
      $this->SetError($error[0], $error[2]);
    }
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
      $return = $this->Conn->lastInsertId();
    }elseif($command == "update" or $command == "delete"){
      $return = $result->rowCount();
    }else{
      $return = true;
    }
    //Log
    if(isset($Options["Log"]) and $Options["Log"] != null and isset($Options["User"]) and $Options["User"] != null){
      ob_start();
      $result->debugDumpParams();
      $dump = ob_get_contents();
      ob_end_clean();
      $dump = substr($dump, strpos($dump, "Sent SQL: ["));
      $dump = substr($dump, strpos($dump, "] ") + 2);
      $dump = substr($dump, 0, strpos($dump, "Params: "));
      $dump = trim($dump);
      $this->SqlLog([
        "User" => $Options["User"],
        "Dump" => $dump,
        "Log" => $Options["Log"],
        "Target" => $Options["Target"]
      ]);
    }
    return $return;
  }

  /**
   * @param string $Table
   * @param array $Fields
   * @return int
   */
  public function SqlInsert(array $Options, array $Options2 = []):int{
    $return = "insert into " . $Options["Table"] . "(";
    $holes = [];
    $i = 1;
    foreach($Options["Fields"] as $field){
      $return .= $this->Reserved($field[0]) . ",";
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
    return $this->SQL($return, $holes, $Options2);
  }

  /**
   * @param string $Table
   * @param array $Fields
   * @param array $Where
   * @return int
   */
  public function SqlUpdate(array $Options, array $Options2 = []):int{
    $return = "update " . $Options["Table"] . " set ";
    $holes = [];
    $i = 1;
    foreach($Options["Fields"] as $field){
      $return .= $this->Reserved($field[0]) . "=?,";
      $holes[] = [$i, $field[1], $field[2]];
      if($field[2] != PdoSql){
        $i++;
      }
    }
    $return = substr($return, 0, -1);
    $return .= " where " . $Options["Where"][0] . "=?";
    $holes[] = [$i, $Options["Where"][1], $Options["Where"][2]];
    return $this->SQL($return, $holes, $Options2);
  }

  /**
   * @return array
   */
  public function GetError():array{
    return $this->Error;
  }

  private function SetError(int $Number, string $Msg):void{
    $this->Error = [$Number, $Msg];
    $folder = __DIR__ . "/errors-pdo/";
    if(is_dir($folder) == false){
      mkdir($folder);
    }
    file_put_contents($folder . date("Y-m-d_H-i-s") . ".txt", json_encode(debug_backtrace(), JSON_PRETTY_PRINT));
    if(ini_get("display_errors") == true){
      echo "<pre style=\"text-align:left\">";
      var_dump(debug_backtrace());
      die();
    }
  }

  /**
   * @param string $Query
   * @return string
   */
  private function Clean(string $Query):string{
    $Query = str_replace("\n", "", $Query);
    $Query = str_replace("\t", "", $Query);
    $Query = str_replace("\r", "", $Query);
    $Query = str_replace("\n", " ", $Query);
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
  private function SqlLog(array $Options):void{
    $this->SqlInsert([
      "Table" => "sys_logs",
      "Fields" => [
        ["time", date("Y-m-d H:i:s"), PdoStr],
        ["user_id", $Options["User"], PdoInt],
        ["log", $Options["Log"], PdoInt],
        ["ip", $_SERVER["REMOTE_ADDR"], PdoStr],
        ["ipreverse", gethostbyaddr($_SERVER["REMOTE_ADDR"]), PdoStr],
        ["agent", $_SERVER["HTTP_USER_AGENT"], PdoStr],
        ["query", $Options["Dump"], PdoStr],
        ["target", $Options["Target"], $Options["Target"] == null? PdoNull: PdoInt]
      ]
    ]);
  }

  /**
   * @param string $Field
   * @return string
   */
  private function Reserved(string $Field):string{
    if($Field == "order" or $Field == "default"){
      $Field = "`" . $Field . "`";
    }
    return $Field;
  }
}