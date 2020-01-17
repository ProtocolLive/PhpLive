<?php
// Version 2020-01-17-00

function DbBackup($Options = []){
  if(isset($Options["Folder"]) == false) $Options["Folder"] = "/sql/";

  $date = date("YmdHis");
  // Prevent the GithubImport folder
  $folder = substr(__DIR__, 0, strrpos(__DIR__, "/"));
  $folder .=  $Options["Folder"];
  $delete = [];

  if(file_exists($folder) == false){
    mkdir($folder);
  }
  $tables = SQL("show tables like '##%'");
  $zip = new ZipArchive();
  $zip->open($folder . $date . ".zip", ZipArchive::CREATE);
  foreach($tables as $table){
    $file = fopen($folder . $table[0] . ".sql", "w");
    $delete[] = $folder . $table[0] . ".sql";
    fwrite($file, "insert into " . $table[0] . " values\n");
    $result = SQL("select * from " . $table[0]);
    $lines = count($result);
    for($i = 0; $i < $lines; $i++){
      fwrite($file, "(");
      $fields = count($result[$i]) / 2;
      for($j = 0; $j < $fields; $j++){
        if($result[$i][$j] == ""){
          fwrite($file, "null");
        }elseif(is_numeric($result[$i][$j]) == false){
          fwrite($file, "'" . $result[$i][$j] . "'");
        }else{
          fwrite($file, $result[$i][$j]);
        }
        if($j < $fields - 1){
          fwrite($file, ",");
        }
      }
      fwrite($file, ")");
      if($i < $lines - 1){
        fwrite($file, ",\n");
      }
    }
    fclose($file);
    $zip->addFile($folder . $table[0] . ".sql", $table[0] . ".sql");
  }
  $zip->close();
  foreach($delete as $file){
    unlink($file);
  }
  return substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/")) . $Options["Folder"] . $date . ".zip";
}