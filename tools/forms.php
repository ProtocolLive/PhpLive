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
  "Pwd" => "test",
  "Db" => "protocol"
]);

echo "<div id=\"AjaxPage\">";
if(isset($_GET["a"])){
  if($_GET["a"] == "formsform"){
    $selects = [
      "autocomplete" => [
        [1, "Yes"],
        [0, "No"]
      ],
      "method" => [
        ["ajax", "Ajax"],
        ["get", "Get"],
        ["post", "Post"]
      ]
      ];
    if(isset($_GET["id"])){
      $form = SQL("select * from forms_forms where form_id=?", [
        [1, $_GET["id"], PdoInt]
      ]);
      Form([
        "Form" => "form",
        "Page" => "ajax.php?a=formsok?id=" . $_GET["id"],
        "Place" => "AjaxPage",
        "Selects" => $selects,
        "Data" => $form[0]
      ]);
    }else{
      Form([
        "Form" => "form",
        "Page" => "ajax.php?a=formsok",
        "Place" => "AjaxPage",
        "Selects" => $selects
      ]);
    }
  }elseif($_GET["a"] == "formsok"){
    $fields = [
      ["site", $_POST["site"], PdoStr],
      ["form", $_POST["form"], PdoStr],
      ["method", $_POST["method"], PdoStr],
      ["action", $_POST["action"], $_POST["action"] == ""? PdoNull: PdoStr],
      ["autocomplete", $_POST["autocomplete"], PdoStr]
    ];
    if(isset($_GET["id"])){
      SqlUpdate([
        "Table" => "forms_forms",
        "Fields" => $fields,
        "Where" => ["form_id", $_GET["id"], PdoInt]
      ]);?>
      <p>Form saved</p><?php
    }else{
      SqlInsert([
        "Table" => "forms_forms",
        "Fields" => $fields
      ]);?>
      <p>Form created</p><?php
    }?>
    <p><a href="#" onclick="Ajax('ajax.php?a=forms','AjaxPage');">Continue</a><?php
  }elseif($_GET["a"] == "formsdel"){
    SQL("delete from forms_forms where form_id=?", [
      [1, $_GET["id"], PdoInt]
    ]);
    header("location:ajax.php?a=forms");
  }elseif($_GET["a"] == "fields"){
    $fields = SQL("select * from forms_fields where form_id=? order by `order`", [
      [1, $_GET["form"], PdoInt]
    ]);?>
    <table class="center">
      <tr>
        <th><a href="#" onclick="Ajax('ajax.php?a=fieldsform&form=<?php echo $_GET["form"];?>','AjaxPage');"><img src="/common/images/add1.gif"></a></th>
        <th>Label</th>
        <th>Name</th>
        <th>Type</th>
        <th>Default</th>
        <th>Only edit</th>
        <th>Size</th>
        <th>Style</th>
        <th>Class</th>
        <th>JS event</th>
        <th>JS code</th>
        <th>Order</th>
      </tr><?php
      foreach($fields as $field){?>
        <tr class="alternate mouse">
          <td>
            <a href="#" onclick="Ajax('ajax.php?a=fieldsform&form=<?php echo $_GET["form"];?>&id=<?php echo $field["field_id"];?>','AjaxPage');"><img src="/common/images/edit.gif"></a>
            <a href="#" onclick="if(confirm('Do you realy want to delete this field?'))
              Ajax('ajax.php?a=fieldsdel&form=<?php echo $_GET["form"];?>&id=<?php echo $field["field_id"];?>','AjaxPage');"><img src="/common/images/del.gif"></a>
          </td>
          <td><?php echo $field["label"];?></td>
          <td><?php echo $field["name"];?></td>
          <td><?php
            if($field["type"] == "text"){
              echo "Text";
            }elseif($field["type"] == "number"){
              echo "Number";
            }elseif($field["type"] == "date"){
              echo "Date";
            }elseif($field["type"] == "select"){
              echo "List";
            }elseif($field["type"] == "submit"){
              echo "Submit button";
            }?>
          </td>
          <td><?php echo $field["default"];?></td>
          <td><?php echo $field["onlyedit"] == 0? "No": "Yes";?></td>
          <td><?php echo $field["size"];?></td>
          <td><?php echo $field["style"];?></td>
          <td><?php echo $field["class"];?></td>
          <td><?php echo $field["js_event"];?></td>
          <td><?php echo $field["js_code"];?></td>
          <td><?php echo $field["order"];?></td>
        </tr><?php
      }?>
    </table><?php
  }elseif($_GET["a"] == "fieldsform"){
    $selects = [
      "type" => [
        ["text", "Text"],
        ["number", "Number"],
        ["date", "Date"],
        ["password", "Password"],
        ["select", "List"],
        ["submit", "Submit button"]
      ],
      "mode" => [
        [0, "Normal"],
        [1, "Only in insert"],
        [2, "Only in update"]
      ]
    ];
    if(isset($_GET["id"])){
      $form = SQL("select * from forms_fields where field_id=?", [
        [1, $_GET["id"], PdoInt]
      ]);
      Form([
        "Form" => "fields",
        "Page" => "ajax.php?a=fieldsok&form=" . $_GET["form"] . "&id=" . $_GET["id"],
        "Place" => "AjaxPage",
        "Data" => $form[0],
        "Selects" => $selects
      ]);
    }else{
      Form([
        "Form" => "fields",
        "Page" => "ajax.php?a=fieldsok&form=" . $_GET["form"],
        "Place" => "AjaxPage",
        "Selects" => $selects
      ]);
    }
  }elseif($_GET["a"] == "fieldsok"){
    $fields = [
      ["label", $_POST["label"], PdoStr],
      ["name", $_POST["name"], $_POST["name"] == ""? PdoNull: PdoStr],
      ["type", $_POST["type"], PdoStr],
      ["default", $_POST["default"], $_POST["default"] == ""? PdoNull: PdoStr],
      ["size", $_POST["size"], $_POST["size"] == ""? PdoNull: PdoInt],
      ["style", $_POST["style"], $_POST["style"] == ""? PdoNull: PdoStr],
      ["class", $_POST["class"], $_POST["class"] == ""? PdoNull: PdoStr],
      ["js_event", $_POST["js_event"], $_POST["js_event"] == ""? PdoNull: PdoStr],
      ["js_code", $_POST["js_code"], $_POST["js_code"] == ""? PdoNull: PdoStr],
      ["order", $_POST["order"], PdoInt]
    ];
    if(isset($_GET["id"])){
      SqlUpdate([
        "Table" => "forms_fields",
        "Fields" => $fields,
        "Where" => ["field_id", $_GET["id"], PdoInt]
      ]);?>
      <p>Field saved</p><?php
    }else{
      SqlInsert([
        "Table" => "forms_fields",
        "Fields" => $fields
      ]);?>
      <p>Field Saved</p><?php
    }?>
    <p><a href="#" onclick="Ajax('ajax.php?a=fields&form=<?php echo $_GET["form"];?>','AjaxPage');">Continue</a><?php
  }elseif($_GET["a"] == "fieldsdel"){
    SQL("delete from forms_fields where field_id=?", [
      [1, $_GET["id"], PdoInt]
    ]);
    header("location:ajax.php?a=fields&form=" . $_GET["form"]);
  }
}else{
  $forms = SQL("select * from forms_forms order by site,form");?>
  <table class="center">
    <tr>
      <th><a href="#" onclick="Ajax('ajax.php?a=formsform','AjaxPage');"><img src="/common/images/add1.gif"></a></th>
      <th>Site</th>
      <th>Form</th>
      <th>Method</th>
      <th>Action</th>
      <th>Auto complete</th>
    </tr><?php
    foreach($forms as $form){?>
      <tr class="alternate mouse">
        <td>
          <a href="#" onclick="Ajax('ajax.php?a=formsform&id=<?php echo $form["form_id"];?>','AjaxPage');"><img src="/common/images/edit.gif"></a>
          <a href="#" onclick="if(confirm('Do you realy want to delete this form?'))
            Ajax('ajax.php?a=formsdel&id=<?php echo $form["form_id"];?>','AjaxPage');"><img src="/common/images/del.gif"></a>
        </td>
        <td><?php echo $form["site"];?></td>
        <td><a href="#" onclick="Ajax('ajax.php?a=fields&form=<?php echo $form["form_id"];?>','AjaxPage')"><?php echo $form["form"];?></a></td>
        <td><?php echo $form["method"];?></td>
        <td><?php echo $form["action"];?></td>
        <td><?php echo $form["autocomplete"] == 0? "No": "Yes";?></td>
      </tr><?php
    }?>
  </table><?php
}
echo "</div>";