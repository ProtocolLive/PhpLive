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
        [1, $_GET["id"], PDO::PARAM_INT]
      ]);
      Form([
        "Form" => "form",
        "Page" => "ajax.php?a=formsok?id=" . $_GET["id"],
        "Place" => "AjaxWindow1Page",
        "Selects" => $selects,
        "Data" => $form[0]
      ]);
    }else{
      Form([
        "Form" => "form",
        "Page" => "ajax.php?a=formsok",
        "Place" => "AjaxWindow1Page",
        "Selects" => $selects
      ]);
    }
  }elseif($_GET["a"] == "formsok"){
    if(isset($_GET["id"])){
      SQL("update forms_forms set site=?,form=?,method=?,action=?,autocomplete=? where form_id=?", [
        [1, $_POST["site"], PDO::PARAM_STR],
        [2, $_POST["form"], PDO::PARAM_STR],
        [3, $_POST["method"], PDO::PARAM_STR],
        [4, $_POST["action"], $_POST["action"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
        [5, $_POST["autocomplete"], PDO::PARAM_STR],
        [6, $_GET["id"], PDO::PARAM_INT]
      ]);?>
      <p>Form saved</p><?php
    }else{
      SQL("insert into forms_forms" . InsertHoles("site,form,method,action,autocomplete"), [
        [1, $_POST["site"], PDO::PARAM_STR],
        [2, $_POST["form"], PDO::PARAM_STR],
        [3, $_POST["method"], PDO::PARAM_STR],
        [4, $_POST["action"], $_POST["action"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
        [5, $_POST["autocomplete"], PDO::PARAM_STR]
      ]);?>
      <p>Form saved</p><?php
    }?>
    <p><a href="#" onclick="Ajax('ajax.php?a=forms','AjaxPage');WindowClose();">Continue</a><?php
  }elseif($_GET["a"] == "formsdel"){
    SQL("delete from forms_forms where form_id=?", [
      [1, $_GET["id"], PDO::PARAM_INT]
    ]);
    header("location:ajax.php?a=forms");
  }elseif($_GET["a"] == "fields"){
    $fields = SQL("select * from forms_fields where form_id=? order by `order`", [
      [1, $_GET["form"], PDO::PARAM_INT]
    ]);?>
    <table class="center">
      <tr>
        <th><a href="#" onclick="Ajax('ajax.php?a=fieldsform&form=<?php echo $_GET["form"];?>','AjaxWindow1Page');WindowOpen('Campo');"><img src="/common/images/add1.gif"></a></th>
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
            <a href="#" onclick="Ajax('ajax.php?a=fieldsform&form=<?php echo $_GET["form"];?>&id=<?php echo $field["field_id"];?>','AjaxWindow1Page');WindowOpen('Field');"><img src="/common/images/edit.gif"></a>
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
        ["select", "List"],
        ["submit", "Submit button"]
      ],
      "onlyedit" => [
        [0, "No"],
        [1, "Yes"]
      ]
    ];
    if(isset($_GET["id"])){
      $form = SQL("select * from forms_fields where field_id=?", [
        [1, $_GET["id"], PDO::PARAM_INT]
      ]);
      Form([
        "Form" => "fields",
        "Page" => "ajax.php?a=fieldsok&form=" . $_GET["form"] . "&id=" . $_GET["id"],
        "Place" => "AjaxWindow1Page",
        "Data" => $form[0],
        "Selects" => $selects
      ]);
    }else{
      Form([
        "Form" => "fields",
        "Page" => "ajax.php?a=fieldsok&form=" . $_GET["form"],
        "Place" => "AjaxWindow1Page",
        "Selects" => $selects
      ]);
    }
  }elseif($_GET["a"] == "fieldsok"){
    if(isset($_GET["id"])){
      SqlUpdate([
        "Table" => "forms_fields",
        "Fields" => [
          ["label", $_POST["label"], PDO::PARAM_STR],
          ["name", $_POST["name"], $_POST["name"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["type", $_POST["type"], PDO::PARAM_STR],
          ["default", $_POST["default"], $_POST["default"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["size", $_POST["size"], $_POST["size"] == ""? PDO::PARAM_NULL: PDO::PARAM_INT],
          ["style", $_POST["style"], $_POST["style"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["class", $_POST["class"], $_POST["class"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["js_event", $_POST["js_event"], $_POST["js_event"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["js_code", $_POST["js_code"], $_POST["js_code"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["order", $_POST["order"], PDO::PARAM_INT]
        ],
        "Where" => ["field_id", $_GET["id"], PDO::PARAM_INT]
      ]);?>
      <p>Field saved</p><?php
    }else{
      SqlInsert([
        "Table" => "forms_fields",
        "Fields" => [
          ["form_id", $_GET["form"], PDO::PARAM_INT],
          ["label", $_POST["label"], PDO::PARAM_STR],
          ["name", $_POST["name"], $_POST["name"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["type", $_POST["type"], PDO::PARAM_STR],
          ["default", $_POST["default"], $_POST["default"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["size", $_POST["size"], $_POST["size"] == ""? PDO::PARAM_NULL: PDO::PARAM_INT],
          ["style", $_POST["style"], $_POST["style"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["class", $_POST["class"], $_POST["class"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["js_event", $_POST["js_event"], $_POST["js_event"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["js_code", $_POST["js_code"], $_POST["js_code"] == ""? PDO::PARAM_NULL: PDO::PARAM_STR],
          ["order", $_POST["order"], PDO::PARAM_INT]
        ]
      ]);?>
      <p>Field Saved</p><?php
    }?>
    <p><a href="#" onclick="Ajax('ajax.php?a=fields&form=<?php echo $_GET["form"];?>','AjaxPage');WindowClose();">Continue</a><?php
  }elseif($_GET["a"] == "fieldsdel"){
    SQL("delete from forms_fields where field_id=?", [
      [1, $_GET["id"], PDO::PARAM_INT]
    ]);
    header("location:ajax.php?a=fields&form=" . $_GET["form"]);
  }
}else{
  $forms = SQL("select * from forms_forms order by site,form");?>
  <table class="center">
    <tr>
      <th><a href="#" onclick="Ajax('ajax.php?a=formsform','AjaxWindow1Page');WindowOpen('Form');"><img src="/common/images/add1.gif"></a></th>
      <th>Site</th>
      <th>Form</th>
      <th>Method</th>
      <th>Action</th>
      <th>Auto complete</th>
    </tr><?php
    foreach($forms as $form){?>
      <tr class="alternate mouse">
        <td>
          <a href="#" onclick="Ajax('ajax.php?a=formsform&id=<?php echo $form["form_id"];?>','AjaxWindow1Page');WindowOpen('Form');"><img src="/common/images/edit.gif"></a>
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