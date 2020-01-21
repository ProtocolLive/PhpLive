<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020-01-21-00

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
  echo ">\n";
  $fields = SQL("select *
    from forms_fields
    where form_id=?
      and type<>'submit'", [
    [1, $form[0]["form_id"], PDO::PARAM_INT]
  ]);
  foreach($fields as $field){
    echo $field["field"] . ":<br>\n";
    echo "<input type=\"" . $field["type"] . "\" name=\"" . $field["db"] . "\"";
    if($field["js_event"] != null){
      echo " " . $field["js_event"] . "=\"" . $field["js_code"] . "\"";
    }
    echo "><br>\n";
  }
  $fields = SQL("select *
    from forms_fields
    where form_id=?
      and type='submit'", [
    [1, $form[0]["form_id"], PDO::PARAM_INT]
  ]);
  echo "<p><input type=\"submit\" value=\"" . $fields[0]["field"] . "\"";
  if($form[0]["method"] == "ajax"){
    echo " onclick=\"Ajax('" . $Options["Page"] . "','" . $Options["Place"] . "','" . $Options["Form"] . "');" .
      $fields[0]["js_code"] . "\"";
  }
  echo "></p>\n";
  echo "</form>";
}