<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.05.03

class PhpLiveDbBackup{
  private ?object $PhpLivePdo = null;
  private array $Delete = [];
  private string $Time;
  private object $Zip;

  public function __construct(object &$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function BackupTables(array $Options = []):string{
    if($this->PhpLivePdo === null){
      if(isset($Options['PhpLivePdo']) == false){
        return false;
      }else{
        $PhpLivePdo = &$Options['PhpLivePdo'];
      }
    }else{
      $PhpLivePdo = $this->PhpLivePdo;
    }
    $Options['Folder'] ??= '/sql/';
    $Options['Progress'] ??= true;
    $Options['Translate']['Tables'] ??= 'tables';
    $Options['Translate']['FK'] ??= 'Foreign keys';

    $this->ZipOpen($Options['Folder']);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] == true){
      $count = count($tables);
      $left = 0;
      printf('%d %s<br>0%%<br>', $count, $Options['Translate']['Tables']);
    }

    $file = fopen($Options['Folder'] . 'tables.sql', 'w');
    foreach($tables as $table){
      $cols = $PhpLivePdo->Run('select COLUMN_NAME,
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
        order by ordinal_position', [
        [1, $table[0], PdoStr]
      ]);
      $line = 'create table ' . $table[0] . '(\n';
      foreach($cols as $col){
        $line .= '  ' . $PhpLivePdo->Reserved($col['COLUMN_NAME']) . ' ' . $col['DATA_TYPE'];
        //Field size for integers is deprecated
        if($col['DATA_TYPE'] == 'varchar'){
          $line .= '(' . $col['CHARACTER_MAXIMUM_LENGTH'] . ')';
        }elseif($col['DATA_TYPE'] == 'decimal'){
          $line .= '(' . $col['NUMERIC_PRECISION'] . ',' . $col['NUMERIC_SCALE'] . ')';
        }
        //Unsigned for decimal is deprecated
        if(strpos($col['COLUMN_TYPE'], 'unsigned') !== false and $col['DATA_TYPE'] != 'decimal'){
          $line .= ' unsigned';
        }
        if($col['IS_NULLABLE'] == 'NO'){
          $line .= ' not null';
        }
        if($col['COLUMN_DEFAULT'] != null and $col['COLUMN_DEFAULT'] != 'NULL'){
          $line .= ' default ';
          if($col['DATA_TYPE'] == 'varchar'){
            $line .= "'" . $col['COLUMN_DEFAULT'] . "'";
          }else{
            $line .= $col['COLUMN_DEFAULT'];
          }
        }
        if($col['EXTRA'] == 'auto_increment'){
          $line .= ' auto_increment';
        }
        if($col['COLUMN_KEY'] == 'PRI'){
          $line .= ' primary key';
        }elseif($col['COLUMN_KEY'] == 'UNI'){
          $line .= ' unique key';
        }
        $line .= ',\n';
      }
      fwrite($file, substr($line, 0, -2) . '\n);\n\n');
      if($Options['Progress'] == true){
        printf('%d%%<br>', ++$left * 100 / $count);
      }
    }
    if($Options['Progress'] == true){
      $left = 0;
      printf('%s<br>0%%<br>', $Options['Translate']['FK']);
    }
    foreach($tables as $table){
      $cols = $PhpLivePdo->Run('
        select CONSTRAINT_NAME,
          COLUMN_NAME,
          cu.REFERENCED_TABLE_NAME,
          REFERENCED_COLUMN_NAME,
          DELETE_RULE,
          UPDATE_RULE
        from information_schema.KEY_COLUMN_USAGE cu
          left join information_schema.REFERENTIAL_CONSTRAINTS using(CONSTRAINT_NAME)
        where cu.TABLE_NAME=?
          and REFERENCED_COLUMN_NAME is not null',
        [
          [1, $table[0], PdoStr]
        ]
      );
      if(count($cols) > 0){
        $line = 'alter table ' . $table[0] . '\n';
        foreach($cols as $col){
          $line .= '  add constraint ' . $col['CONSTRAINT_NAME'];
          $line .= ' foreign key(' . $col['COLUMN_NAME'] . ') references ';
          $line .= $col['REFERENCED_TABLE_NAME'] . '(' . $col['REFERENCED_COLUMN_NAME'] . ') ';
          $line .= 'on delete ' . $col['DELETE_RULE'] . ' on update ' . $col['UPDATE_RULE'] . ',\n';
        }
        fwrite($file, substr($line, 0, -2) . ';\n\n');
      }
      if($Options['Progress'] == true){
        printf('%d%%<br>', ++$left * 100 / $count);
      }
    }
    fclose($file);
    $this->Zip->addFile($Options['Folder'] . 'tables.sql', 'tables.sql');
    $this->ZipClose();
    unlink($Options['Folder'] . 'tables.sql');
    return substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . $Options['Folder'] . $this->Time . '.zip';
  }

  public function BackupData(array $Options = []):string{
    if($this->PhpLivePdo === null){
      if(isset($Options['PhpLivePdo']) == false){
        return false;
      }else{
        $PhpLivePdo = &$Options['PhpLivePdo'];
      }
    }else{
      $PhpLivePdo = $this->PhpLivePdo;
    }
    $Options['Folder'] ??= '/sql/';
    $Options['Progress'] ??= true;
    $Options['Translate']['Tables'] ??= 'tables';

    $this->ZipOpen($Options['Folder']);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] == true){
      $count = count($tables);
      $left = 0;
      printf('%d %s<br>0%%<br>', $count, $Options['Translate']['Tables']);
    }
    foreach($tables as $table){
      $PhpLivePdo->Run('lock table $table[0] write');
      $result = $PhpLivePdo->Run('select * from ' . $table[0]);
      $lines = count($result);
      if($lines > 0){
        $file = fopen($Options['Folder'] . $table[0] . '.sql', 'w');
        $this->Delete[] = $Options['Folder'] . $table[0] . '.sql';
        fwrite($file, 'insert into ' . $table[0] . ' values\n');
        for($i = 0; $i < $lines; $i++){
          fwrite($file, '(');
          $fields = count($result[$i]) / 2;
          for($j = 0; $j < $fields; $j++){
            if($result[$i][$j] == ''){
              fwrite($file, 'null');
            }elseif(is_numeric($result[$i][$j]) == false){
              fwrite($file, "'" . str_replace("'", "''", $result[$i][$j]) . "'");
            }else{
              fwrite($file, $result[$i][$j]);
            }
            if($j < $fields - 1){
              fwrite($file, ',');
            }
          }
          fwrite($file, ')');
          if($i < $lines - 1){
            fwrite($file, ',\n');
          }
        }
        $PhpLivePdo->Run('unlock tables');
        fclose($file);
        $this->Zip->addFile($Options['Folder'] . $table[0] . '.sql', $table[0] . '.sql');
      }
      if($Options['Progress'] == true){
        printf('%d%%<br>', ++$left * 100 / $count);
      }
    }
    $this->ZipClose();
    $this->Delete();
    return substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . $Options['Folder'] . $this->Time . '.zip';
  }

  private function ZipOpen(string $Folder):void{
    $this->Time = date('YmdHis');
    if(file_exists($Folder) == false){
      mkdir($Folder);
    }
    $this->Zip = new ZipArchive();
    $this->Zip->open($Folder . $this->Time . '.zip', ZipArchive::CREATE);
  }

  private function ZipClose():void{
    $this->Zip->close();
  }

  private function Delete():void{
    foreach($this->Delete as $file){
      unlink($file);
    }
  }
}