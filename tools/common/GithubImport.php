<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.25.00

class GithubImport{
  private bool $Log = true;
  private object $Status;

  private function FactoryHead(){
    return new class{
      public string $Time = '';
      public bool $ApiIntegrity = false;
      public float $TimeToCheck = 0;
      public float $TotalTime = 0;
      public int $FilesCount = 0;
      public array $Files = [];
    };
  }

  private function FactoryFile(){
    return new class{
      public $Local = false;
      public $Server = false;
      public bool $ForceLocal = false;
      public bool $Downloaded = false;
      public string $Include = 'none';
      public float $TimeToCheck = 0;
      public float $TimeToDownload = 0;
    };
  }

  private function Error($Msg):void{
    if(ini_get('display_errors') == true):
      printf('GithubImport error: %s', $Msg);
    endif;
  }

  public function __construct($Options = null){
    if(extension_loaded('openssl') == false):
      $this->Error('PHP extension OpenSSL not loaded!');
      return false;
    endif;
    $this->Status = $this->FactoryHead();
    $this->Status->Time = date('Y-m-d H:i:s');
    list($a, $b) = $this->GetFile('https://raw.githubusercontent.com/ProtocolLive/GithubImport/master/src/GithubImport.php.md5');
    $this->Status->ApiIntegrity = $a == md5_file(__FILE__);
    $this->Status->TotalTime += $this->Status->TimeToCheck = $b;
    $this->Log = $Options['Log']?? true;
  }

  public function Get(array $Options):bool{
    if(isset($Options['File']) == false) return false;
    $Options['IncludeType'] ??= 0;
    $Options['Trunk'] ??= 'master';
    $Options['Folder'] ??= __DIR__ . '/GithubImport/';
    $Options['Download'] ??= true;

    $fileway = $Options['Folder'] . $Options['Repo'] . '/' . $Options['File'];

    $this->Status->FilesCount++;
    $this->Status->Files[$Options['File']] = $this->FactoryFile();
    $status = &$this->Status->Files[$Options['File']];

    if(file_exists($fileway) == true):
      $status->Local = md5_file($fileway);
    endif;
    $status->ForceLocal = !$Options['Download'];
    if($Options['Download'] == true):
      list(
        $status->Server,
        $status->TimeToCheck
      ) = $this->GetFile(
        sprintf(
          'https://raw.githubusercontent.com/%s/%s/%s/src/%s.md5',
          $Options['User'],
          $Options['Repo'],
          $Options['Trunk'],
          $Options['File']
        )
      );
      $this->Status->TotalTime += $status->TimeToCheck;
    endif;

    if(file_exists($fileway) == true
    and $Options['Download'] == true
    and $status->Server !== false
    and $status->Local != $status->Server):
      unlink($fileway);
    endif;
    if(file_exists($fileway) == false and $status->Server === false):
      $this->Error('Cant download the library ' . $Options['Repo'] . '/' . $Options['File']);
      return false;
    elseif(file_exists($fileway) == false and $Options['Download'] == true and $status->Server !== false):
      list(
        $file,
        $status->TimeToDownload
      ) = $this->GetFile(
        sprintf(
          'https://raw.githubusercontent.com/%s/%s/%s/src/%s',
          $Options['User'],
          $Options['Repo'],
          $Options['Trunk'],
          $Options['File']
        )
      );
      $this->Status->TotalTime += $status->TimeToDownload;
      if($file != false):
        if(is_dir($Options['Folder']) == false):
          mkdir($Options['Folder']);
        endif;
        if(is_dir($Options['Folder'] . '/' . $Options['Repo']) == false):
          mkdir($Options['Folder'] . '/' . $Options['Repo']);
        endif;
        file_put_contents($fileway, $file);
        $status->Downloaded = true;
      endif;
    endif;
    if($Options['IncludeType'] == 0):
      require($fileway);
      $status->Include = 'require';
    elseif($Options['IncludeType'] == 1):
      include($fileway);
      $status->Include = 'include';
    endif;
    if($this->Log == true):
      $log = $this->GetFile($Options['Folder'] . '/GithubImportLog.txt');
      $log = substr($log[0], 0, 9999);
      @file_put_contents($Options['Folder'] . '/GithubImportLog.txt', json_encode($this->Status, JSON_PRETTY_PRINT) . "\n" . $log);
    endif;
    return true;
  }

  public function Status():object{
    return $this->Status;
  }

  private function GetFile(string $File):array{
    $return = false;
    $temp = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', 1);
    $start = microtime(true);
    $return = @file_get_contents($File);
    $end = microtime(true);
    ini_set('default_socket_timeout', $temp);
    return [$return, $end - $start];
  }
}