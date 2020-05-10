<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.10.00

class PhpLiveDbBackup{
  private ?object $PhpLivePdo = null;
  private array $Delete = [];
  private string $Time;
  private object $Zip;

  public function __construct(object &$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Tables(array $Options = []):string{
    if($this->PhpLivePdo === null):
      if(isset($Options['PhpLivePdo']) == false):
        return false;
      else:
        $PhpLivePdo = &$Options['PhpLivePdo'];
      endif;
    else:
      $PhpLivePdo = $this->PhpLivePdo;
    endif;
    $Options['Folder'] ??= '/sql/';
    $Options['Progress'] ??= true;
    $Options['Translate']['Tables'] ??= 'tables';
    $Options['Translate']['FK'] ??= 'foreign keys';

    $this->ZipOpen($Options['Folder']);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] == true):
      $count = count($tables);
      $left = 0;
      printf('%d %s<br>0%%<br>', $count, $Options['Translate']['Tables']);
    endif;

    $file = fopen($Options['Folder'] . 'tables.sql', 'w');
    foreach($tables as $table):
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
      $line = 'create table ' . $table[0] . "(\n";
      foreach($cols as $col):
        $line .= '  ' . $PhpLivePdo->Reserved($col['COLUMN_NAME']) . ' ' . $col['DATA_TYPE'];
        //Field size for integers is deprecated
        if($col['DATA_TYPE'] == 'varchar'):
          $line .= '(' . $col['CHARACTER_MAXIMUM_LENGTH'] . ')';
        elseif($col['DATA_TYPE'] == 'decimal'):
          $line .= '(' . $col['NUMERIC_PRECISION'] . ',' . $col['NUMERIC_SCALE'] . ')';
        endif;
        //Unsigned for decimal is deprecated
        if(strpos($col['COLUMN_TYPE'], 'unsigned') !== false and $col['DATA_TYPE'] != 'decimal'):
          $line .= ' unsigned';
        endif;
        if($col['IS_NULLABLE'] == 'NO'):
          $line .= ' not null';
        endif;
        if($col['COLUMN_DEFAULT'] != null and $col['COLUMN_DEFAULT'] != 'NULL'):
          $line .= ' default ';
          if($col['DATA_TYPE'] == 'varchar'):
            $line .= "'" . $col['COLUMN_DEFAULT'] . "'";
          else:
            $line .= $col['COLUMN_DEFAULT'];
          endif;
        endif;
        if($col['EXTRA'] == 'auto_increment'):
          $line .= ' auto_increment';
        endif;
        if($col['COLUMN_KEY'] == 'PRI'):
          $line .= ' primary key';
        elseif($col['COLUMN_KEY'] == 'UNI'):
          $line .= ' unique key';
        endif;
        $line .= ",\n";
      endforeach;
      fwrite($file, substr($line, 0, -2) . "\n);\n\n");
      if($Options['Progress'] == true):
        printf('%d%%<br>', ++$left * 100 / $count);
      endif;
    endforeach;
    //foreign keys
    $cols = $PhpLivePdo->Run('
      select
        rc.table_name,
        constraint_name,
        column_name,
        rc.referenced_table_name,
        referenced_column_name,
        delete_rule,
        update_rule
      from
        information_schema.referential_constraints rc
          left join information_schema.key_column_usage using(constraint_name)
      order by rc.table_name
    ');
    $count = count($cols);
    if($Options['Progress'] == true):
      $left = 0;
      printf('%d %s<br>0%%<br>', $count, $Options['Translate']['FK']);
    endif;
    foreach($cols as $col):
      $line = 'alter table ' . $col['TABLE_NAME'] . "\n";
      $line .= '  add constraint ' . $col['CONSTRAINT_NAME'];
      $line .= ' foreign key(' . $col['column_name'] . ') references ';
      $line .= $col['REFERENCED_TABLE_NAME'] . '(' . $col['referenced_column_name'] . ') ';
      $line .= 'on delete ' . $col['DELETE_RULE'] . ' on update ' . $col['UPDATE_RULE'] . ",\n";
      fwrite($file, substr($line, 0, -2) . ";\n\n");
      if($Options['Progress'] == true):
        printf('%d%%<br>', ++$left * 100 / $count);
      endif;
    endforeach;
    fclose($file);
    $this->Zip->addFile($Options['Folder'] . 'tables.sql', 'tables.sql');
    $this->ZipClose();
    unlink($Options['Folder'] . 'tables.sql');
    return substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . $Options['Folder'] . $this->Time . '.zip';
  }

  public function Data(array $Options = []):string{
    if($this->PhpLivePdo === null):
      if(isset($Options['PhpLivePdo']) == false):
        return false;
      else:
        $PhpLivePdo = &$Options['PhpLivePdo'];
      endif;
    else:
      $PhpLivePdo = $this->PhpLivePdo;
    endif;
    $Options['Folder'] ??= '/sql/';
    $Options['Progress'] ??= true;
    $Options['Translate']['Tables'] ??= 'tables';

    $this->ZipOpen($Options['Folder']);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] == true):
      $count = count($tables);
      $left = 0;
      printf('%d %s<br>0%%<br>', $count, $Options['Translate']['Tables']);
    endif;
    foreach($tables as $table):
      $PhpLivePdo->Run('lock table ' . $table[0] . ' write');
      $rows = $PhpLivePdo->Run('select * from ' . $table[0]);
      if(count($rows) > 0):
        $file = fopen($Options['Folder'] . $table[0] . '.sql', 'w');
        $this->Delete[] = $Options['Folder'] . $table[0] . '.sql';
        foreach($rows as $row):
          fwrite($file, 'insert into ' . $table[0] . ' values(');
          $values = '';
          foreach($row as $col => $value):
            if(is_numeric($col) == true): // avoid duplicated rows returned by PDO
              if($value == ''):
                $values .= 'null,';
              elseif(is_numeric($value)):
                $values .= $value . ',';
              else:
                $values .= "'" . str_replace("'", "''", $value) . "',";
              endif;
            endif;
          endforeach;
          $values = substr($values, 0, -1);
          fwrite($file, $values);
          fwrite($file, ");\n");
        endforeach;
        $PhpLivePdo->Run('unlock tables');
        fclose($file);
        $this->Zip->addFile($Options['Folder'] . $table[0] . '.sql', $table[0] . '.sql');
      endif;
      if($Options['Progress'] == true):
        printf('%d%%<br>', ++$left * 100 / $count);
      endif;
    endforeach;
    $this->ZipClose();
    $this->Delete();
    return substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . $Options['Folder'] . $this->Time . '.zip';
  }

  private function ZipOpen(string $Folder):void{
    $this->Time = date('YmdHis');
    if(file_exists($Folder) == false):
      mkdir($Folder);
    endif;
    $this->Zip = new ZipArchive();
    $this->Zip->open($Folder . $this->Time . '.zip', ZipArchive::CREATE);
  }

  private function ZipClose():void{
    $this->Zip->close();
  }

  private function Delete():void{
    foreach($this->Delete as $file):
      unlink($file);
    endforeach;
  }
}