<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
//Version 2020.05.03.01

class GithubImport{
  private bool $Log = true;
  private array $Status = [
    "Time" => "",
    "ApiIntegrity" => false,
    "Files" => []
  ];

  public function __construct($Options = null){
    if(extension_loaded("openssl") == false){
      die("GithubImport error: PHP extension OpenSSL not loaded!");
    }
    $this->Status["Time"] = date("Y-m-d H:i:s");
    $this->Status["ApiIntegrity"] = $this->GetFile("https://raw.githubusercontent.com/ProtocolLive/GithubImport/master/src/GithubImport.php.md5") == md5_file(__FILE__);
    $this->Log = $Options["Log"]?? true;
  }

  public function Get(array $Options):bool{
    if(isset($Options["File"]) == false) return false;
    $Options["IncludeType"] ??= 0;
    $Options["Trunk"] ??= "master";
    $Options["Folder"] ??= __DIR__ . "/GithubImport/";
    $Options["Download"] ??= true;

    $fileway = $Options["Folder"] . $Options["Repo"] . "/" . $Options["File"];

    $this->Status["Files"][$Options["File"]] = [
      "Local" => false,
      "Server" => false,
      "ForceLocal" => !$Options["Download"],
      "Downloaded" => false,
      "Include" => null
    ];
    if(file_exists($fileway) == true){
      $this->Status["Files"][$Options["File"]]["Local"] = md5_file($fileway);
      $this->Status["Files"][$Options["File"]]["Server"] = $this->GetFile("https://raw.githubusercontent.com/" . $Options["User"] . "/" . $Options["Repo"] . "/" . $Options["Trunk"] . "/src/" . $Options["File"] . ".md5");
      if($Options["Download"] == true
      and $this->Status["Files"][$Options["File"]]["Server"] !== false
      and $this->Status["Files"][$Options["File"]]["Local"] != $this->Status["Files"][$Options["File"]]["Server"]){
        unlink($fileway);
      }
    }
    if(file_exists($fileway) == false and $Options["Download"] == true){
      $file = $this->GetFile("https://raw.githubusercontent.com/" . $Options["User"] . "/" . $Options["Repo"] . "/" . $Options["Trunk"] . "/src/" . $Options["File"]);
      if($file !== false){
        if(is_dir($Options["Folder"]) == false){
          mkdir($Options["Folder"]);
        }
        if(is_dir($Options["Folder"] . "/" . $Options["Repo"]) == false){
          mkdir($Options["Folder"] . "/" . $Options["Repo"]);
        }
        file_put_contents($fileway, $file);
        $this->Status["Files"][$Options["File"]]["Downloaded"] = true;
      }
    }
    if($Options["IncludeType"] == 0){
      require_once($fileway);
      $this->Status["Files"][$Options["File"]]["Include"] = "require_once";
    }elseif($Options["IncludeType"] == 1){
      include_once($fileway);
      $this->Status["Files"][$Options["File"]]["Include"] = "include_once";
    }
    if($this->Log == true){
      $log = @$this->GetFile($Options["Folder"] . "/GithubImportLog.txt");
      $log = substr($log, 0, 4096);
      @file_put_contents($Options["Folder"] . "/GithubImportLog.txt", json_encode($this->Status, JSON_PRETTY_PRINT) . "\n" . $log);
    }
    return true;
  }

  public function Status():array{
    return $this->Status;
  }

  private function GetFile(string $File):string{
    $return = @file_get_contents(
      $File,
      0,
      stream_context_create([
        "http" => [
          "timeout" => 1
        ]
      ])
    );
    if($return === "") $return = false;
    return $return;
  }
}