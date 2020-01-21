<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-01-21-05

function Form($Options = []){
  if(session_name() == "PHPSESSID"){
    $site[0] = "site is null";
    $site[1] = [[":form", $Options["Form"], PDO::PARAM_STR]];
  }else{
    $site[0] = "site=:site";
    $site[1] = [
      [":site", session_name(), PDO::PARAM_STR],
      [":form", $Options["Form"], PDO::PARAM_STR]
    ];
  }
  $form = SQL("select *
    from forms_forms
    where " . $site[0] . "
      and form=:form",
    $site[1]
  );
  echo "<form name=\"" . $form[0]["form"] . "\"";
  if($form[0]["method"] == "ajax"){
    echo " onsubmit=\"return false;\"";
  }else{
    echo " method=\"" . $form[0]["method"] . "\" action=\"" . $form[0]["action"] . "\"";
  }
  if($form[0]["autocomplete"] == 0){
    echo " autocomplete=\"off\"";
  }
  echo ">";
  if(isset($Options["Edit"]) == false){
    $edit = " and onlyedit=0";
  }
  $fields = SQL("select *
    from forms_fields
    where form_id=?
      and type<>'submit'
      $edit
    order by `order`", [
    [1, $form[0]["form_id"], PDO::PARAM_INT]
  ]);
  foreach($fields as $field){
    if($field["type"] == "select"){
      echo $field["label"] . ":<br>";
      echo "<select name=\"" . $field["name"] . "\">";
      echo "<option value=\"0\" selected disabled></option>";
      foreach($Options["Datas"][$field["name"]] as $select){
        echo "<option value=\"" . $select[0] . "\">" . $select[1] . "</option>";
      }
      echo "</select><br>";
    }elseif($field["type"] == "checkbox"){
      echo "<br><input type=\"checkbox\" name=\"" . $field["name"] . "\"";
      if(isset($Options["Data"]) and $Options["Data"][$field["name"]] == 1){
        echo " checked";
      }
      echo "> " . $field["label"] . "<br>";
    }else{
      echo $field["label"] . ":<br>";
      echo "<input type=\"" . $field["type"] . "\" name=\"" . $field["name"] . "\"";
      if(isset($Options["Data"])){
        echo " value=\"" . $Options["Data"][$field["name"]] . "\"";
      }
      if($field["style"] != null){
        echo " style=\"" . $field["style"] . "\"";
      }
      if($field["class"] != null){
        echo " class=\"" . $field["class"] . "\"";
      }
      if($field["js_event"] != null){
        echo " " . $field["js_event"] . "=\"" . $field["js_code"] . "\"";
      }
      echo "><br>";
    }
  }
  $fields = SQL("select *
    from forms_fields
    where form_id=?
      and type='submit'", [
    [1, $form[0]["form_id"], PDO::PARAM_INT]
  ]);
  echo "<p><input type=\"submit\" value=\"" . $fields[0]["label"] . "\"";
  if($form[0]["method"] == "ajax"){
    echo " onclick=\"Ajax('" . $Options["Page"] . "','" . $Options["Place"] . "','" . $Options["Form"] . "');" .
      $fields[0]["js_code"] . "\"";
  }
  echo "></p>";
  echo "</form>";
}