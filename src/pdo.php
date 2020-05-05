<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.05.04

define('PdoStr', PDO::PARAM_STR);
define('PdoInt', PDO::PARAM_INT);
define('PdoNull', PDO::PARAM_NULL);
define('PdoBool', PDO::PARAM_BOOL);
define('PdoSql', 6);

class PhpLivePdo{
  private ?object $Conn = null;
  private string $Prefix = '';
  private array $Error = [];

  /**
   * @param string $Drive (Optional) MySql as default
   * @param string $Ip
   * @param string $User
   * @param string $Pwd
   * @param string $Db
   * @param string $Prefix (Optional) Change ## for the tables prefix 
   * @param string $Charset (Optional) UTF8 as default
   * @param int $TimeOut (Optional) Connection timeout
   * @return object Connection
   */
  public function __construct(array $Options){
    $Options['Drive'] ??= 'mysql';
    $Options['Charset'] ??= 'utf8';
    $Options['TimeOut'] ??= 5;
    $this->Prefix = $Options['Prefix']?? '';

    $this->Conn = new PDO(
      $Options['Drive'] . ':host=' . $Options['Ip'] . ';dbname=' . $Options['Db'] . ';charset=' . $Options['Charset'],
      $Options['User'],
      $Options['Pwd']
    );
    $this->Conn->setAttribute(PDO::ATTR_TIMEOUT, $Options['TimeOut']);
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
  public function Run(string $Query, array $Params = [], array $Options = []){
    $Options['Target'] ??= null;
    $Options['Safe'] ??= true;

    $Query = $this->Clean($Query);
    if($this->Prefix !== null):
      $Query = str_replace('##', $this->Prefix . '_', $Query);
    else:
      $Query = str_replace('##', '', $Query);
    endif;
    $command = explode(' ', $Query);
    $command = strtolower($command[0]);
    //Search from PdoSql and parse
    foreach($Params as $id => $Param):
      if($Param[2] == PdoSql):
        if(is_numeric($Param[0])):
          $out = 0;
          for($i = 1; $i <= $Param[0]; $i++):
            $in = strpos($Query, '?', $out);
            $out = $in + 1;
          endfor;
          $Query = substr_replace($Query, $Param[1], $in, 1);
        else:
          $in = strpos($Query, $Param[0]);
          $out = strpos($Query, ',', $in);
          if($out === false):
            $out = strpos($Query, ')', $in);
          endif;
          $Query = substr_replace($Query, $Param[1], $in, $out);
        endif;
        unset($Params[$id]);
      endif;
    endforeach;
    //Prepare
    $result = $this->Conn->prepare($Query);
    //Bind tokens
    if($Params != null):
      foreach($Params as &$Param):
        if(count($Param) != 3):
          $this->SetError(1, 'Incorrect number of parameters when specifying a token');
        else:
          if($Param[2] == PdoInt):
            $Param[1] = str_replace(',', '.', $Param[1]);
            if(strpos($Param[1], '.') !== false):
              $Param[2] = PdoStr;
            endif;
          elseif($Param[2] == PdoBool):
            $Param[1] = $Param[1] == 'true'? true: false;
          endif;
          $result->bindValue($Param[0], $Param[1], $Param[2]);
        endif;
      endforeach;
    endif;
    //Safe execution
    if($Options['Safe'] == true):
      if($command == 'truncate' or (($command == 'update' or $command == 'delete') and strpos($Query, 'where') === false)):
        $this->SetError(2, 'Query not allowed in safe mode');
      endif;
    endif;
    //Execute
    $result->execute();
    //Error
    $error = $result->errorInfo();
    if($error[0] != '00000'):
      $this->SetError($error[0], $error[2]);
    endif;
    //Debug
    if(isset($Options['Debug']) and $Options['Debug'] == true):
      print '<pre style="text-align:left">';
        $result->debugDumpParams();
        print '<br>';
        print debug_print_backtrace();
      print '</pre>';
    endif;
    //Return
    if($command == 'select' or $command == 'show' or $command == 'call'):
      $return = $result->fetchAll();
    elseif($command == 'insert'):
      $return = $this->Conn->lastInsertId();
    elseif($command == 'update' or $command == 'delete'):
      $return = $result->rowCount();
    else:
      $return = true;
    endif;
    //Log
    if(isset($Options['Log']) and $Options['Log'] != null and isset($Options['User']) and $Options['User'] != null):
      ob_start();
      $result->debugDumpParams();
      $dump = ob_get_contents();
      ob_end_clean();
      $dump = substr($dump, strpos($dump, 'Sent SQL: ['));
      $dump = substr($dump, strpos($dump, '] ') + 2);
      $dump = substr($dump, 0, strpos($dump, 'Params: '));
      $dump = trim($dump);
      $this->SqlLog([
        'User' => $Options['User'],
        'Dump' => $dump,
        'Log' => $Options['Log'],
        'Target' => $Options['Target']
      ]);
    endif;
    return $return;
  }

  /**
   * @param string $Table
   * @param array $Fields
   * @return int
   */
  public function Insert(array $Options, array $Options2 = []):int{
    $return = 'insert into ' . $Options['Table'] . '(';
    $holes = [];
    $i = 1;
    foreach($Options['Fields'] as $field):
      $return .= $this->Reserved($field[0]) . ',';
      $holes[] = [$i, $field[1], $field[2]];
      $i++;
    endforeach;
    $return = substr($return, 0, -1);
    $return .= ') values(';
    for($j = 1; $j < $i; $j++):
      $return .= '?,';
    endfor;
    $return = substr($return, 0, -1);
    $return .= ');';
    return $this->Run($return, $holes, $Options2);
  }

  /**
   * @param string $Table
   * @param array $Fields
   * @param array $Where
   * @return int
   */
  public function Update(array $Options, array $Options2 = []):int{
    $return = 'update ' . $Options['Table'] . ' set ';
    $holes = [];
    $i = 1;
    foreach($Options['Fields'] as $field):
      $return .= $this->Reserved($field[0]) . '=?,';
      $holes[] = [$i, $field[1], $field[2]];
      if($field[2] != PdoSql):
        $i++;
      endif;
    endforeach;
    $return = substr($return, 0, -1);
    $return .= ' where ' . $Options['Where'][0] . '=?';
    $holes[] = [$i, $Options['Where'][1], $Options['Where'][2]];
    return $this->Run($return, $holes, $Options2);
  }

  /**
   * Update only the diferents fields
   * @param string $Table
   * @param array $Fields
   * @param array $Where
   * @return int
   */
  public function UpdateDiff(array $Options, array $Options2 = []):int{
    $data = $this->Run('select * from ' . $Options['Table'] . ' where ' . $Options['Where'][0] . '=' . $Options['Where'][1]);
    $data = $data[0];
    foreach($Options['Fields'] as &$field):
      if($field[1] == $data[$field[0]]):
        unset($field);
      endif;
    endforeach;
    if(count($Options['Fields']) > 0):
      return $this->Update($Options, $Options2);
    else:
      return 0;
    endif;
  }

  /**
   * Update a row, or insert if not exist
   * @param string $Table
   * @param array $Fields
   * @param array $Where
   * @return int
   */
  public function UpdateInsert(array $Options, array $Options2 = []):int{
    $data = $this->Run('select ' . $Options['Fields'][0][0] . ' from ' . $Options['Table'] . ' where ' . $Options['Where'][0] . '=?', [
      [1, $Options['Where'][1], $Options['Where'][2]]
    ]);
    if(count($data) == 1):
      return $this->Update([
        'Table' => $Options['Table'],
        'Fields' => $Options['Fields'],
        'Where' => $Options['Where']
      ], $Options2);
    else:
      return $this->Insert([
        'Table' => $Options['Table'],
        'Fields' => $Options['Fields']
      ], $Options2);
    endif;
  }

  /**
   * @return array
   */
  public function GetError():array{
    return $this->Error;
  }

  private function SetError(string $Number, string $Msg):void{
    $this->Error = [$Number, $Msg];
    $folder = __DIR__ . '/errors-pdo/';
    if(is_dir($folder) == false):
      mkdir($folder);
    endif;
    file_put_contents($folder . date('Y-m-d_H-i-s') . '.txt', json_encode(debug_backtrace(), JSON_PRETTY_PRINT));
    if(ini_get('display_errors')):
      if(ini_get('html_errors')):
        print '<pre style="text-align:left">';
      endif;
      var_dump(debug_backtrace());
      die();
    endif;
  }

  /**
   * @param string $Query
   * @return string
   */
  private function Clean(string $Query):string{
    $Query = str_replace('\n', '', $Query);
    $Query = str_replace('\t', '', $Query);
    $Query = str_replace('\r', '', $Query);
    $Query = str_replace('\n', ' ', $Query);
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
    $this->Insert([
      'Table' => 'sys_logs',
      'Fields' => [
        ['time', date('Y-m-d H:i:s'), PdoStr],
        ['user_id', $Options['User'], PdoInt],
        ['log', $Options['Log'], PdoInt],
        ['ip', $_SERVER['REMOTE_ADDR'], PdoStr],
        ['ipreverse', gethostbyaddr($_SERVER['REMOTE_ADDR']), PdoStr],
        ['agent', $_SERVER['HTTP_USER_AGENT'], PdoStr],
        ['query', $Options['Dump'], PdoStr],
        ['target', $Options['Target'], $Options['Target'] == null? PdoNull: PdoInt]
      ]
    ]);
  }

  /**
   * @param string $Field
   * @return string
   */
  private function Reserved(string $Field):string{
    if($Field == 'order' or $Field == 'default'):
      $Field = '`' . $Field . '`';
    endif;
    return $Field;
  }
}