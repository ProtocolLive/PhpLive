<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-03-07-02

function DbBackup($Options = []){
  if(isset($Options["Folder"]) == false) $Options["Folder"] = "/sql/";

  $date = date("YmdHis");
  // Skip the GithubImport folder
  $folder = substr(__DIR__, 0, strrpos(__DIR__, "/"));
  $folder .=  $Options["Folder"];
  $delete = [];

  if(file_exists($folder) == false){
    mkdir($folder);
  }

  $tables = SQL("show tables like '##%'");
  $zip = new ZipArchive();
  $zip->open($folder . $date . ".zip", ZipArchive::CREATE);
  //Tables
  if($Options["Mode"] == "Tables"){
    $file = fopen($folder . "tables.sql", "w");
    foreach($tables as $table){
      $cols = SQL("select COLUMN_NAME,
          DATA_TYPE,
          CHARACTER_MAXIMUM_LENGTH,
          NUMERIC_PRECISION,
          NUMERIC_SCALE,
          IS_NULLABLE,
          COLUMN_DEFAULT,
          COLUMN_TYPE,
          EXTRA,
          COLUMN_KEY
        from information_schema.columns
        where table_name=?
        order by ordinal_position", [
        [1, $table[0], PdoStr]
      ]);
      $line = "create table " . $table[0] . "(\n";
      foreach($cols as $col){
        if($col["COLUMN_NAME"] == "order" or $col["COLUMN_NAME"] == "default"){
          $line .= "  `" . $col["COLUMN_NAME"] . "` " . $col["DATA_TYPE"];
        }else{
          $line .= "  " . $col["COLUMN_NAME"] . " " . $col["DATA_TYPE"];
        }
        if($col["DATA_TYPE"] == "varchar"){
          $line .= "(" . $col["CHARACTER_MAXIMUM_LENGTH"] . ")";
        }elseif($col["DATA_TYPE"] == "decimal"){
          $line .= "(" . $col["NUMERIC_PRECISION"] . "," . $col["NUMERIC_SCALE"] . ")";
        }
        if(strpos($col["COLUMN_TYPE"], "unsigned") !== false){
          $line .= " unsigned";
        }
        if($col["IS_NULLABLE"] == "NO"){
          $line .= " not null";
        }
        if($col["COLUMN_DEFAULT"] != null){
          $line .= " default ";
          if($col["DATA_TYPE"] == "varchar"){
            $line .= "'" . $col["COLUMN_DEFAULT"] . "'";
          }else{
            $line .= $col["COLUMN_DEFAULT"];
          }
        }
        if($col["EXTRA"] == "auto_increment"){
          $line .= " auto_increment";
        }
        if($col["COLUMN_KEY"] == "PRI"){
          $line .= " primary key";
        }elseif($col["COLUMN_KEY"] == "UNI"){
          $line .= " unique key";
        }
        $line .= ",\n";
      }
      fwrite($file, substr($line, 0, -2) . "\n);\n\n");
    }
    foreach($tables as $table){
      $cols = SQL("select CONSTRAINT_NAME,
          COLUMN_NAME,
          REFERENCED_TABLE_NAME,
          REFERENCED_COLUMN_NAME
        from information_schema.KEY_COLUMN_USAGE
        where TABLE_NAME=?
          and REFERENCED_TABLE_NAME is not null", [
        [1, $table[0], PdoStr]
      ]);
      if(count($cols) > 0){
        $line = "alter table " . $table[0] . "\n";
        foreach($cols as $col){
          $line .= "  add constraint " . $col["CONSTRAINT_NAME"];
          $line .= " foreign key(" . $col["COLUMN_NAME"] . ") references ";
          $line .= $col["REFERENCED_TABLE_NAME"] . "(" . $col["REFERENCED_COLUMN_NAME"] . ") ";
          $fk = SQL("select DELETE_RULE,
              UPDATE_RULE
            from information_schema.REFERENTIAL_CONSTRAINTS
            where CONSTRAINT_NAME=?", [
            [1, $col["CONSTRAINT_NAME"], PdoStr]
          ]);
          $line .= "on delete " . $fk[0]["DELETE_RULE"] . " on update " . $fk[0]["UPDATE_RULE"] . ",\n";
        }
        fwrite($file, substr($line, 0, -2) . ";\n\n");
      }
    }
    fclose($file);
    $zip->addFile($folder . "tables.sql", "tables.sql");
    $delete[] = $folder . "tables.sql";
  }

  //Data
  if($Options["Mode"] == "Tables"){
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
  }

  $zip->close();
  foreach($delete as $file){
    unlink($file);
  }
  return substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/")) . $Options["Folder"] . $date . ".zip";
}