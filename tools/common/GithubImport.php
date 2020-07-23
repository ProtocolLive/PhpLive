<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.07.14.00

class GithubImport{
  private bool $Log;
  private object $Status;

  private function FactoryApiIntegrity():object{
    return new class{
      public bool $SameWithServer = false;
      public float $TimeToCheck = 0;
    };
  }

  private function FactoryHead():object{
    return new class{
      public string $Time = '';
      public float $TotalTime = 0;
      public object $ApiIntegrity;
      public array $Libraries = [];
    };
  }

  private function FactoryLibrary():object{
    return new class{
      public string $ServerUrl;
      public float $TotalTime = 0;
      public $Md5Server = false;
      public $Md5Client = false;
      public float $TimeToCheck = 0;
      public string $Include = 'none';
      public array $Files = [];
    };
  }

  private function FactoryFile():object{
    return new class{
      public $Md5Server = false;
      public $Md5Client = false;
      public float $TimeToDownload = 0;
    };
  }

  private function PhpError(int $Type):bool{
    return (ini_get('error_reporting') & $Type) === $Type;
  }

  private function Error(string $Msg):void{
    if(ini_get('display_errors') === '1' and ($this->PhpError(E_ALL) or $this->PhpError(E_WARNING))):
      $debug = debug_backtrace();
      print 'GithubImport warning: '. $Msg;
      print ' in <b>' . $debug[1]['file'] . '</b>';
      print ' line <b>' . $debug[1]['line'] . '</b><br>';
    endif;
  }

  private function GetFile(string $File):array{
    $start = microtime(true);
    $return = @file_get_contents($File);
    $end = microtime(true);
    return [$return, $end - $start];
  }

  private function IndexServerGet(object &$Library, array &$Options){
    list($IndexServerJson, $time) = $this->GetFile($Library->ServerUrl . $Options['Library'] . '.json');
    $this->Status->TotalTime += $time;
    $Library->TotalTime += $time;
    $Library->TimeToCheck = $time;
    return $IndexServerJson;
  }

  private function Require(array &$Options, string $EntryPoint){
    $file = $Options['Folder'] . $Options['Library'] . '/' . $EntryPoint;
    if($Options['IncludeType'] === 0):
      require($file);
    elseif($Options['IncludeType'] === 1):
      include($File);
    endif;
  }

  private function GetOutdated(array $Index, string $Folder, string $LibraryName):array{
    if(is_dir($Folder) === false):
      return $Index;
    endif;
    $dir = scandir($Folder);
    unset($dir[0]);
    unset($dir[1]);
    $basedir = substr($Folder, strpos($Folder, $LibraryName) + strlen($LibraryName));
    if($basedir === false):
      $basedir = '';
    endif;
    foreach($dir as $file):
      if(is_dir($Folder . $file)):
        $Index = $this->GetOutdated($Index, $Folder . $file . '/', $LibraryName);
      else:
        if(isset($Index['Files'][$basedir . $file])):
          if(md5_file($Folder . $file) === $Index['Files'][$basedir . $file]['md5']):
            unset($Index['Files'][$basedir . $file]);
          endif;
        else:
          unlink($Folder . '/' . $file);
        endif;
      endif;
    endforeach;
    return $Index;
  }

  private function UpdateOutdated(array $Index, object &$Library, array &$Options):bool{
    foreach($Index['Files'] as $FileName => $FileData):
      $FilePath = $Options['Folder'] . $Options['Library'] . '/' . $FileName;
      $FileDir = dirname($FilePath);
      if(is_dir($FileDir) === false):
        mkdir($FileDir, 0755, true);
      endif;
      $Library->Files[$FileName] = $this->FactoryFile();
      list($FileServer, $time) = $this->GetFile($Library->ServerUrl . $Options['Library'] . '/' . $FileName);
      if($FileServer === false):
        $this->Error("Can't download file " . $FilePath);
        return false;
      endif;
      $this->Status->TotalTime += $time;
      $Library->TotalTime += $time;
      $Library->Files[$FileName]->TimeToDownload = $time;
      $Library->Files[$FileName]->Md5Server = md5($FileServer);
      $Library->Files[$FileName]->Md5Client = @md5_file($FilePath);
      file_put_contents($FilePath, $FileServer);
    endforeach;
    return true;
  }

  /**
   * @param array $Options
   * @return object
   */
  public function __construct(array $Options = null){
    if(extension_loaded('openssl') === false):
      $this->Error('PHP extension OpenSSL not loaded!');
      return false;
    endif;
    $this->Status = $this->FactoryHead();
    $this->Status->Time = date('Y-m-d H:i:s');
    list($a, $time) = $this->GetFile('https://raw.githubusercontent.com/ProtocolLive/GithubImport/master/src/GithubImport.php.md5');
    if($a === false):
      $this->Error("Can't check the GithubImport integrity");
    endif;
    $this->Status->ApiIntegrity = $this->FactoryApiIntegrity();
    $this->Status->ApiIntegrity->SameWithServer = $a === md5_file(__FILE__);
    $this->Status->ApiIntegrity->TimeToCheck = $time;
    $this->Status->TotalTime += $time;
    $this->Log = $Options['Log']?? true;
  }

  /**
   * @param array $Options
   * @return bool
   */
  public function Get(array $Options):bool{
    if(isset($Options['User']) === false):
      $this->Error('User are not set');
      return false;
    elseif(isset($Options['Repository']) === false):
      $this->Error('Repository are not set');
      return false;
    elseif(isset($Options['Library']) === false):
      $this->Error('Library are not set');
      return false;
    endif;
    $Options['IncludeType'] ??= 0;
    $Options['Trunk'] ??= 'master';
    $Options['Folder'] ??= __DIR__ . '/GithubImport/';

    if(is_dir($Options['Folder']) === false):
      mkdir($Options['Folder'], 0755);
    endif;
    $this->Status->Libraries[$Options['Library']] = $this->FactoryLibrary();
    $Library = &$this->Status->Libraries[$Options['Library']];

    $Library->ServerUrl = 'https://raw.githubusercontent.com/' . $Options['User'] . '/' . $Options['Repository'] . '/' . $Options['Trunk'] . '/src/';
    $IndexClientPath = $Options['Folder'] . $Options['Library'] . '.json';

    $IndexServerJson = $this->IndexServerGet($Library, $Options);
    if($IndexServerJson === false):
      $this->Error('Failed to get library ' . $Options['Library']);
      return false;
    endif;
    $IndexServer = json_decode($IndexServerJson, true);
    $Library->Md5Server = $IndexServer['md5'];
    if(file_exists($IndexClientPath) === false):
      $IndexOutdated = $IndexServer;
    else:
      $Library->Md5Client = $IndexServer['md5'];
      $IndexOutdated = $this->GetOutdated($IndexServer, $Options['Folder'] . $Options['Library'] . '/', $Options['Library'] . '/');
    endif;
    if($this->UpdateOutdated($IndexOutdated, $Library, $Options) === false):
      return false;
    endif;
    file_put_contents($IndexClientPath, $IndexServerJson);
    $this->Require($Options, $IndexServer['EntryPoint']);
    //Log
    if($this->Log == true):
      $log = @file_get_contents($Options['Folder'] . 'GithubImportLog.txt');
      $log = substr($log, 0, 9999);
      $json = json_encode($this->Status, JSON_PRETTY_PRINT);
      $json = str_replace('\\', '', $json);
      $log = $json . $log;
      file_put_contents($Options['Folder'] . 'GithubImportLog.txt', $log);
    endif;
    return true;
  }

  /**
   * @return object
   */
  public function Status():object{
    return $this->Status;
  }
}