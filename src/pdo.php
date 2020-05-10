<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.09.00

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
   * @param string $Drive ($Options)(Optional) MySql as default
   * @param string $Ip ($Options)
   * @param string $User ($Options)
   * @param string $Pwd ($Options)
   * @param string $Db ($Options)
   * @param string $Prefix ($Options)(Optional) Change ## for the tables prefix 
   * @param string $Charset ($Options)(Optional) UTF8 as default
   * @param int $TimeOut ($Options)(Optional) Connection timeout
   * @return object
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
   * @param int Log ($Options)(Optional) Event to be logged
   * @param int User ($Options)(Optional) User executing the query
   * @param int Target ($Options)(Optional) User efected
   * @param bool Debug ($Options)(Optional) Dump the query for debug
   * @param bool Safe ($Options)(Optional) Only runs a safe query
   * @return mixed
   */
  public function Run(string $Query, array $Params = [], array $Options = []){
    $Options['Target'] ??= null;
    $Options['Safe'] ??= true;

    try{
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
            $this->ErrorSet(1, 'Incorrect number of parameters when specifying a token');
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
          $this->ErrorSet(2, 'Query not allowed in safe mode');
        endif;
      endif;
      //Execute
      $result->execute();
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
    }catch(Exception $e){
      $this->ErrorSet($e->getCode, $e->getMessage);
      return false;
    }
  }

  /**
   * @param string Table
   * @param array Fields
   * @return int
   */
  public function Insert(array $Options, array $Options2 = []):int{
    $return = 'insert into ' . $Options['Table'] . '(';
    $tokens = [];
    $i = 1;
    foreach($Options['Fields'] as $field):
      $return .= $this->Reserved($field[0]) . ',';
      $tokens[] = [$i, $field[1], $field[2]];
      $i++;
    endforeach;
    $return = substr($return, 0, -1);
    $return .= ') values(';
    foreach($Options['Fields'] as $field):
      $return .= '?,';
    endforeach;
    $return = substr($return, 0, -1);
    $return .= ');';
    return $this->Run($return, $tokens, $Options2);
  }

  /**
   * Update only the diferents fields
   * @param string Table ($Options)
   * @param array Fields ($Options)
   * @param array Where ($Options)
   * @return int
   */
  public function Update(array $Options, array $Options2 = []):int{
    $query = '';
    $temp = $this->BuildWhere($Options['Where']);
    // Get fields list
    foreach($Options["Fields"] as $field):
      $query .= $field[0] . ',';
    endforeach;
    $query = 'select ' . substr($query, 0, -1) . ' from ' . $Options['Table'] . ' where ' . $temp['Query'];
    $data = $this->Run($query, $temp['Tokens'], $Options2);
    if(count($data) == 1):
      $data = $data[0];
      foreach($Options['Fields'] as $id => $field):
        if($field[1] == $data[$field[0]]):
          unset($Options['Fields'][$id]);
        endif;
      endforeach;
    endif;
    if(count($Options['Fields']) > 0):
      $temp = $this->BuildUpdate($Options);
      return $this->Run($temp['Query'], $temp['Tokens'], $Options2);
    else:
      return 0;
    endif;
  }

  /**
   * Update a row, or insert if not exist
   * @param string Table ($Options)
   * @param array Fields ($Options)
   * @param array Where ($Options)
   * @return int
   */
  public function UpdateInsert(array $Options, array $Options2 = []):int{
    $temp = $this->BuildWhere($Options['Where']);
    $query = 'select ' . $Options['Fields'][0][0] . ' from ' . $Options['Table'] . ' where ' . $temp['Query'];
    $data = $this->Run($query, $temp['Tokens'], $Options2);
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
  public function ErrorGet():array{
    return $this->Error;
  }

  /**
   * @param string Field
   * @return string
   */
  public function Reserved(string $Field):string{
    if($Field == 'order' or $Field == 'default'):
      $Field = '`' . $Field . '`';
    endif;
    return $Field;
  }

  private function BuildUpdate(array $Options):array{
    $return = ['Query' => '', 'Tokens' => []];
    $return['Query'] = 'update ' . $Options['Table'] . ' set ';
    $i = 1;
    foreach($Options['Fields'] as $field):
      $return['Query'] .= $this->Reserved($field[0]) . '=?,';
      $return['Tokens'][] = [$i, $field[1], $field[2]];
      if($field[2] != PdoSql):
        $i++;
      endif;
    endforeach;
    $return['Query'] = substr($return['Query'], 0, -1);
    $temp = $this->BuildWhere($Options['Where'], $i);
    $return['Query'] .= ' where ' . $temp['Query'];
    $return['Tokens'] = array_merge($return['Tokens'], $temp['Tokens']);
    return $return;
  }

  private function BuildWhere(array $Wheres, int $Count = 1):array{
    // 0 field, 1 value, 2 type, 3 operator, 4 condition
    $return = ['Query' => '', 'Tokens' => []];
    foreach($Wheres as $id => $where):
      $where[0] = $this->Reserved($where[0]);
      $where[3] ??= '=';
      $where[4] ??= 'and';
      if($where[3] == 'is' or $where[3] == 'is not'):
        $where[3] = ' ' . $where[3] . ' ';
      endif;
      if($id == 0):
        $return['Query'] = $where[0] . $where[3] . '?';
      else:
        $return['Query'] .= ' ' . $where[4] . ' ' . $where[0] . $where[3] . '?';
      endif;
      $return['Tokens'][] = [$Count++, $where[1], $where[2]];
    endforeach;
    return $return;
  }

  private function ErrorSet(string $Number, string $Msg):void{
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

  private function Clean(string $Query):string{
    $Query = str_replace("\t", '', $Query);
    $Query = str_replace("\r", '', $Query);
    $Query = str_replace("\n", ' ', $Query);
    $Query = trim($Query);
    return $Query;
  }

  private function SqlLog(array $Options):void{
    $this->Insert([
      'Table' => 'sys_logs',
      'Fields' => [
        ['time', date('Y-m-d H:i:s'), PdoStr],
        ['site', $this->Prefix, $this->Prefix == null? PdoNull: PdoStr],
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
}