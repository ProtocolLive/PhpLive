<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.07.17.00

class PhpLiveDbBackup{
  private ?PhpLivePdo $PhpLivePdo = null;
  private array $Delete = [];
  private string $Time;
  private object $Zip;

  public function __construct(PhpLivePdo &$PhpLivePdo = null){
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
    $Options['Progress'] ??= 1;
    $Options['Translate']['Tables'] ??= 'tables';
    $Options['Translate']['FK'] ??= 'foreign keys';

    $this->ZipOpen($Options['Folder'], 0);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] != 0):
      $TablesCount = count($tables);
      $TablesLeft = 0;
      printf('%d %s<br>0%%<br>', $TablesCount, $Options['Translate']['Tables']);
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
          COLUMN_KEY,
          COLLATION_NAME
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
          $line .= ' collate ' . $col['COLLATION_NAME'];
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
      fwrite($file, substr($line, 0, -2) . "\n) ");
      $table = $PhpLivePdo->Run('
        select
          ENGINE,
          TABLE_COLLATION
        from information_schema.tables
        where table_name=?
      ',[
        [1, $table[0], PdoStr]
      ]);
      fwrite($file, 'engine=' . $table[0]['ENGINE'] . ' ');
      fwrite($file, 'collate=' . $table[0]['TABLE_COLLATION'] . ";\n\n");
      if($Options['Progress'] != 0):
        printf('%d%%<br>', ++$TablesLeft * 100 / $TablesCount);
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
    $TablesCount = count($cols);
    if($Options['Progress'] != 0):
      $TablesLeft = 0;
      printf('%d %s<br>0%%<br>', $TablesCount, $Options['Translate']['FK']);
    endif;
    foreach($cols as $col):
      $line = 'alter table ' . $col['table_name'] . "\n";
      $line .= '  add constraint ' . $col['constraint_name'];
      $line .= ' foreign key(' . $col['column_name'] . ') references ';
      $line .= $col['referenced_table_name'] . '(' . $col['referenced_column_name'] . ') ';
      $line .= 'on delete ' . $col['delete_rule'] . ' on update ' . $col['update_rule'] . ",\n";
      fwrite($file, substr($line, 0, -2) . ";\n\n");
      if($Options['Progress'] != 0):
        printf('%d%%<br>', ++$TablesLeft * 100 / $TablesCount);
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
    $Options['Progress'] ??= 2;
    $Options['Translate']['Tables'] ??= 'tables';
    $Options['Translate']['Rows'] ??= 'rows';

    $last = null;
    $this->ZipOpen($Options['Folder'], 1);
    $tables = $PhpLivePdo->Run("show tables like '##%'");
    if($Options['Progress'] != 0):
      $TablesCount = count($tables);
      $TablesLeft = 0;
      printf('%d %s<br><br>0%%<br>', $TablesCount, $Options['Translate']['Tables']);
    endif;
    foreach($tables as $table):
      $PhpLivePdo->Run('lock table ' . $table[0] . ' write');
      $rows = $PhpLivePdo->Run('select * from ' . $table[0]);
      $RowsCount = count($rows);
      $RowsLeft = 0;
      if($Options['Progress'] == 2):
        printf('%s (%d %s)<br>', $table[0], $RowsCount, $Options['Translate']['Rows']);
      endif;
      if($RowsCount > 0):
        $file = fopen($Options['Folder'] . $table[0] . '.sql', 'w');
        $this->Delete[] = $Options['Folder'] . $table[0] . '.sql';
        foreach($rows as $row):
          $cols = '';
          $values = '';
          foreach($row as $col => $value):
            if(is_numeric($col) === false): // avoid duplicated rows returned by PDO
              $cols .= $PhpLivePdo->Reserved($col) . ',';
              if($value == ''):
                $values .= 'null,';
              elseif(is_numeric($value)):
                $values .= $value . ',';
              else:
                $values .= "'" . str_replace("'", "''", $value) . "',";
              endif;
            endif;
          endforeach;
          $cols = substr($cols, 0, -1);
          $values = substr($values, 0, -1);
          fwrite($file, 'insert into ' . $table[0] . '(' . $cols . ') values(' . $values . ");\n");
          if($Options['Progress'] == 2):
            $percent = ++$RowsLeft * 100 / $RowsCount;
            if(($percent % 25) == 0 and floor($percent) !== $last):
              printf('%d%%...', $percent);
              $last = floor($percent);
            endif;
          endif;
        endforeach;
        $PhpLivePdo->Run('unlock tables');
        fclose($file);
        $this->Zip->addFile($Options['Folder'] . $table[0] . '.sql', $table[0] . '.sql');
      endif;
      if($Options['Progress'] != 0):
        printf('<br><br>%d%%<br>', ++$TablesLeft * 100 / $TablesCount);
      endif;
    endforeach;
    $this->ZipClose();
    $this->Delete();
    return substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/')) . $Options['Folder'] . $this->Time . '.zip';
  }

  private function ZipOpen(string $Folder, int $Type):void{
    $this->Time = date('YmdHis');
    if(file_exists($Folder) == false):
      mkdir($Folder, 0755);
    endif;
    $this->Zip = new ZipArchive();
    if($Type == 0):
      $temp = 'tables';
    else:
      $temp = 'data';
    endif;
    $this->Zip->open($Folder . $temp . $this->Time . '.zip', ZipArchive::CREATE);
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