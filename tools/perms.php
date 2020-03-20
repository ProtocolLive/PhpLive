<?php
require_once("GithubImport.php");

GithubImport([
  "User" => "ProtocolLive",
  "Repo" => "PhpLive",
  "File" => "pdo.php"
]);
if(isset($_GET["a"]) == false){
  echo "<script>";
  GithubImport([
    "User" => "ProtocolLive",
    "Repo" => "Ajax",
    "File" => "ajax.js"
  ]);
  echo "</script>";
}

SqlConnect([
  "Ip" => "localhost",
  "User" => "root",
  "Pwd" => "teste",
  "Db" => "protocol"
]);

if(isset($_GET["a"])){
  if($_GET["a"] == "entitys"){
    $result = $PDO->SQL("select perm_id,`group`,name
      from sys_perms
        left join sys_groups using(group_id)
        left join sys_users using(user_id)
      where resource_id=?", [
      [1, $_POST["resource"], PdoInt]
    ]);
    if(count($result) == 0){?>
      <option value="0" disabled>Empty</option><?php
    }else{
      foreach($result as $line){?>
        <option value="<?php echo $line["perm_id"];?>"><?php echo $line["group"] == null? $line["name"]: $line["group"];?></option><?php
      }
    }
  }elseif($_GET["a"] == "perms"){
    if($_POST["entity"] == ""){
      FormDisabled();
    }else{
      $result = $PDO->SQL("select r,w,o
        from sys_perms
        where perm_id=?", [
        [1, $_POST["entity"], PdoInt]
      ]);?>
      <tr>
        <td></td>
        <td>Deny</td>
        <td>Granty</td>
      </tr>
      <tr>
        <td>Read</td>
        <td style="text-align:center"><input type="radio" name="r" value="0"<?php
          if($result[0]["r"] == 0) echo " checked";?>></td>
        <td style="text-align:center"><input type="radio" name="r" value="1"<?php
          if($result[0]["r"] == 1) echo " checked";?>></td>
      </tr>
      <tr>
        <td>Write</td>
        <td style="text-align:center"><input type="radio" name="w" value="0"<?php
          if($result[0]["w"] == 0) echo " checked";?>></td>
        <td style="text-align:center"><input type="radio" name="w" value="1"<?php
          if($result[0]["w"] == 1) echo " checked";?>></td>
      </tr>
      <tr>
        <td>Owner</td>
        <td style="text-align:center"><input type="radio" name="o" value="0"<?php
          if($result[0]["o"] == 0) echo " checked";?>></td>
        <td style="text-align:center"><input type="radio" name="o" value="1"<?php
          if($result[0]["o"] == 1) echo " checked";?>></td>
      </tr><?php
    }
  }elseif($_GET["a"] == "save"){
    $PDO->SqlUpdate([
      "Table" => "sys_perms",
      "Fields" => [
        ["r", $_POST["r"], PdoBool],
        ["w", $_POST["w"], PdoBool],
        ["o", $_POST["o"], PdoBool]
      ],
      "Where" => ["perm_id", $_POST["entity"], PdoInt]
    ]);
    echo "Saved!";
  }
}else{?>
  <form name="perms">
    <table>
      <tr>
        <td>
          <select name="resource" size="10" onclick="Ajax('perms.php?a=entitys','AjaxEntitys','perms');document.perms.entity.click()"><?php
            $result = $PDO->SQL("select * from sys_resources order by resource");
            foreach($result as $line){?>
              <option value="<?php echo $line["resource_id"];?>"><?php echo $line["resource"];?></option><?php
            }?>
          </select>
        </td>
        <td>
          <select id="AjaxEntitys" name="entity" size="10" style="width:200px;"
            onclick="Ajax('perms.php?a=perms','AjaxPerms','perms');">
          </select><br>
        </td>
        <td style="text-align:center">
          <table id="AjaxPerms">
            <?php FormDisabled();?>
          </table><br>
          <input type="button" value="Save" onclick="Ajax('perms.php?a=save','AjaxResult','perms')"><br>
          <br>
          <span id="AjaxResult"></span>
        </td>
      </tr>
    </table>
  </form><?php
}

function FormDisabled(){?>
  <tr>
    <td></td>
    <td>Deny</td>
    <td>Granty</td>
  </tr>
  <tr>
    <td>Read</td>
    <td style="text-align:center"><input type="radio" name="r" value="0" disabled></td>
    <td style="text-align:center"><input type="radio" name="r" value="1" disabled></td>
  </tr>
  <tr>
    <td>Write</td>
    <td style="text-align:center"><input type="radio" name="w" value="0" disabled></td>
    <td style="text-align:center"><input type="radio" name="w" value="1" disabled></td>
  </tr>
  <tr>
    <td>Owner</td>
    <td style="text-align:center"><input type="radio" name="o" value="0" disabled></td>
    <td style="text-align:center"><input type="radio" name="o" value="1" disabled></td>
  </tr><?php
}