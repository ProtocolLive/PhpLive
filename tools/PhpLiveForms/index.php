<?php
//2020.05.03.00
require_once("system.php");

if(isset($_GET["a"]) == false):
  require_once("head.php");
  $forms = $PDO->SQL("select * from forms_forms order by site,form");?>
  <table class="center">
    <tr>
      <th class="sticky"><a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=formsform','AjaxWindow1Page');WindowOpen('New form');"><img src="/common/images/add1.gif"></a></th>
      <th class="sticky">Site</th>
      <th class="sticky">Form</th>
      <th class="sticky">Method</th>
      <th class="sticky">Action</th>
      <th class="sticky">Auto complete</th>
    </tr><?php
    foreach($forms as $form):?>
      <tr class="alternate mouse">
        <td>
          <a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=formsform&id=<?php echo $form['form_id'];?>','AjaxWindow1Page');WindowOpen('Form');"><img src="/common/images/edit.gif"></a>
          <a href="#" onclick="if(confirm('Do you realy want to delete this form?'))
            Ajax('/PhpLiveForms/index.php?a=formsdel&id=<?php echo $form['form_id'];?>','AjaxPage');"><img src="/common/images/del.gif"></a>
        </td>
        <td><?php echo $form["site"];?></td>
        <td><a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=fields&form=<?php echo $form['form_id'];?>','AjaxPage')"><?php echo $form["form"];?></a></td>
        <td><?php echo $form["method"];?></td>
        <td><?php echo $form["action"];?></td>
        <td><?php echo $form["autocomplete"] == 0? "No": "Yes";?></td>
      </tr><?php
    endforeach;?>
  </table><?php
  require_once("../common/foot.php");
elseif($_GET["a"] == "formsform"):
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
  if(isset($_GET["id"])):
    $form = $PDO->SQL("select * from forms_forms where form_id=?", [
      [1, $_GET["id"], PdoInt]
    ]);
    $Forms->Form([
      "Site" => "admin",
      "Form" => "form",
      "Page" => "/PhpLiveForms/index.php?a=formsok&id=" . $_GET["id"],
      "Place" => "AjaxWindow1Page",
      "Selects" => $selects,
      "Data" => $form[0]
    ]);
  else:
    $Forms->Form([
      "Site" => "admin",
      "Form" => "form",
      "Page" => "/PhpLiveForms/index.php?a=formsok",
      "Place" => "AjaxWindow1Page",
      "Selects" => $selects
    ]);
  endif;
elseif($_GET["a"] == "formsok"):
  if(isset($_GET["id"])):
    $PDO->SqlUpdate([
      "Table" => "forms_forms",
      "Fields" => [
        ["site", $_POST["site"], PdoStr],
        ["form", $_POST["form"], PdoStr],
        ["method", $_POST["method"], PdoStr],
        ["action", $_POST["action"], $_POST["action"] == ""? PdoNull: PdoStr],
        ["autocomplete", $_POST["autocomplete"], PdoStr]
      ],
      "Where" => ["form_id", $_GET["id"], PdoInt]
    ]);?>
    <p>Form edited</p><?php
  else:
    $PDO->SqlInsert([
      "Table" => "forms_forms",
      "Fields" => [
        ["site", $_POST["site"], PdoStr],
        ["form", $_POST["form"], PdoStr],
        ["method", $_POST["method"], PdoStr],
        ["action", $_POST["action"], $_POST["action"] == ""? PdoNull: PdoStr],
        ["autocomplete", $_POST["autocomplete"], PdoStr]
      ]
    ]);?>
    <p>Form added</p><?php
  endif;?>
  <p><a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=forms','AjaxPage');WindowClose();">Refresh</a><?php
elseif($_GET["a"] == "formsdel"):
  $PDO->SQL("delete from forms_forms where form_id=?", [
    [1, $_GET["id"], PdoInt]
  ]);
  header("location:index.php?a=forms");
elseif($_GET["a"] == "fields"):
  $fields = $PDO->SQL("select * from forms_fields where form_id=? order by `order`", [
    [1, $_GET["form"], PdoInt]
  ]);?>
  <table class="center">
    <tr>
      <th><a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=fieldsform&form=<?php echo $_GET['form'];?>','AjaxWindow1Page');WindowOpen('New field');"><img src="/common/images/add1.gif"></a></th>
      <th>Label</th>
      <th>Name</th>
      <th>Type</th>
      <th>Default</th>
      <th>Mode</th>
      <th>Size</th>
      <th>Style</th>
      <th>Class</th>
      <th>JS event</th>
      <th>JS code</th>
      <th>Order</th>
    </tr><?php
    foreach($fields as $field):?>
      <tr class="alternate mouse">
        <td>
          <a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=fieldsform&form=<?php echo $_GET['form'];?>&id=<?php echo $field['field_id'];?>','AjaxWindow1Page');WindowOpen('Edit field');"><img src="/common/images/edit.gif"></a>
          <a href="#" onclick="if(confirm('Do you realy want to delete this field?'))
            Ajax('/PhpLiveForms/index.php?a=fieldsdel&form=<?php echo $_GET['form'];?>&id=<?php echo $field['field_id'];?>','AjaxPage');"><img src="/common/images/del.gif"></a>
        </td>
        <td><?php echo $field["label"];?></td>
        <td><?php echo $field["name"];?></td>
        <td><?php
          if($field["type"] == "text"):
            echo "Text";
          elseif($field["type"] == "number"):
            echo "Number";
          elseif($field["type"] == "date"):
            echo "Date";
          elseif($field["type"] == "select"):
            echo "List";
          elseif($field["type"] == "checkbox"):
            echo "Checkbox";
          elseif($field["type"] == "submit"):
            echo "Submit button";
          endif;?>
        </td>
        <td><?php echo $field["default"];?></td>
        <td><?php 
          if($field["mode"] == 0):
            echo "Normal";
          elseif($field["mode"] == 1):
            echo "Only insert";
          else:
            echo "Only update";
          endif;?>
        </td>
        <td><?php echo $field["size"];?></td>
        <td><?php echo $field["style"];?></td>
        <td><?php echo $field["class"];?></td>
        <td><?php echo $field["js_event"];?></td>
        <td><?php echo $field["js_code"];?></td>
        <td><?php echo $field["order"];?></td>
      </tr><?php
    endforeach;?>
  </table><?php
elseif($_GET["a"] == "fieldsform"):
  $selects = [
    "type" => [
      ["checkbox", "Checkbox"],
      ["date", "Date"],
      ["select", "List"],
      ["number", "Number"],
      ["password", "Password"],
      ["submit", "Submit button"],
      ["text", "Text"],
      ["textarea", "Text area"]
    ],
    "mode" => [
      [0, "Normal"],
      [1, "Only insert"],
      [2, "Only update"]
    ]
  ];
  if(isset($_GET["id"])):
    $data = $PDO->SQL("select * from forms_fields where field_id=?", [
      [1, $_GET["id"], PdoInt]
    ]);
    $Forms->Form([
      "Site" => "admin",
      "Form" => "fields",
      "Page" => "/PhpLiveForms/index.php?a=fieldsok&form=" . $_GET["form"] . "&id=" . $_GET["id"],
      "Place" => "AjaxWindow1Page",
      "Data" => $data[0],
      "Selects" => $selects
    ]);
  else:
    $Forms->Form([
      "Site" => "admin",
      "Form" => "fields",
      "Page" => "/PhpLiveForms/index.php?a=fieldsok&form=" . $_GET["form"],
      "Place" => "AjaxWindow1Page",
      "Selects" => $selects
    ]);
  endif;
elseif($_GET["a"] == "fieldsok"):
  if($_POST["js_event"] == ""):
    $_POST["js_code"] = "";
  endif;
  if(isset($_GET["id"])):
    $PDO->SqlUpdate([
      "Table" => "forms_fields",
      "Fields" => [
        ["label", $_POST["label"], PdoStr],
        ["name", $_POST["name"], $_POST["name"] == ""? PdoNull: PdoStr],
        ["type", $_POST["type"], PdoStr],
        ["default", $_POST["default"], $_POST["default"] == ""? PdoNull: PdoStr],
        ["mode", $_POST["mode"], PdoInt],
        ["size", $_POST["size"], $_POST["size"] == ""? PdoNull: PdoInt],
        ["style", $_POST["style"], $_POST["style"] == ""? PdoNull: PdoStr],
        ["class", $_POST["class"], $_POST["class"] == ""? PdoNull: PdoStr],
        ["js_event", $_POST["js_event"], $_POST["js_event"] == ""? PdoNull: PdoStr],
        ["js_code", $_POST["js_code"], $_POST["js_code"] == ""? PdoNull: PdoStr],
        ["order", $_POST["order"], PdoInt]
      ],
      "Where" => ["field_id", $_GET["id"], PdoInt]
    ]);?>
    <p>Field edited</p><?php
  else:
    $PDO->SqlInsert([
      "Table" => "forms_fields",
      "Fields" => [
        ["form_id", $_GET["form"], PdoInt],
        ["label", $_POST["label"], PdoStr],
        ["name", $_POST["name"], $_POST["name"] == ""? PdoNull: PdoStr],
        ["type", $_POST["type"], PdoStr],
        ["default", $_POST["default"], $_POST["default"] == ""? PdoNull: PdoStr],
        ["mode", $_POST["mode"], PdoInt],
        ["size", $_POST["size"], $_POST["size"] == ""? PdoNull: PdoInt],
        ["style", $_POST["style"], $_POST["style"] == ""? PdoNull: PdoStr],
        ["class", $_POST["class"], $_POST["class"] == ""? PdoNull: PdoStr],
        ["js_event", $_POST["js_event"], $_POST["js_event"] == ""? PdoNull: PdoStr],
        ["js_code", $_POST["js_code"], $jstipo],
        ["order", $_POST["order"], PdoInt]
      ]
    ]);?>
    <p>Field added</p><?php
  endif;?>
  <p><a href="#" onclick="Ajax('/PhpLiveForms/index.php?a=fields&form=<?php echo $_GET['form'];?>','AjaxPage');WindowClose();">Refresh</a><?php
elseif($_GET["a"] == "fieldsdel"):
  $PDO->SQL("delete from forms_fields where field_id=?", [
    [1, $_GET["id"], PdoInt]
  ]);
  header("location:index.php?a=fields&form=" . $_GET["form"]);
endif;