<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.04.23.00

class PhpLiveForms{
  private $PhpLivePdo = null;

  public function __construct(&$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Form($Options){
    if($this->PhpLivePdo === null){
      if(isset($Options["PhpLivePdo"]) == false){
        return false;
      }else{
        $PhpLivePdo = &$Options["PhpLivePdo"];
      }
    }else{
      $PhpLivePdo = $this->PhpLivePdo;
    }
    if(isset($Options["PdoDebug"]) == false) $Options["PdoDebug"] = false;

    if(isset($Options["Site"])){
      $site[0] = "site=:site";
      $site[1] = [
        [":site", $Options["Site"], PdoStr],
        [":form", $Options["Form"], PdoStr]
      ];
    }elseif(session_name() != "PHPSESSID"){
      $site[0] = "site=:site";
      $site[1] = [
        [":site", session_name(), PdoStr],
        [":form", $Options["Form"], PdoStr]
      ];
    }else{
      $site[0] = "site is null";
      $site[1] = [[":form", $Options["Form"], PdoStr]];
    }
    $form = $PhpLivePdo->SQL("
      select *
      from forms_forms
      where $site[0]
        and form=:form",
      $site[1],
      ["Debug" => $Options["PdoDebug"]]
    );
    echo '<form name="' . $form[0]["form"] . '"';
    if($form[0]["method"] == "ajax"){
      echo ' onsubmit="return false;"';
    }else{
      echo ' method="' . $form[0]["method"] . '" action="' . $form[0]["action"] . '"';
    }
    if($form[0]["autocomplete"] == 0){
      echo ' autocomplete="off"';
    }
    echo ">";
    $fields = $PhpLivePdo->SQL("
      select *
      from forms_fields
      where form_id=?
        and type<>'submit'
      order by `order`",
      [
        [1, $form[0]["form_id"], PdoInt]
      ],
      ["Debug" => $Options["PdoDebug"]]
    );
    echo "<p>";
    foreach($fields as $field){
      if($field["type"] == "select"){
        echo $field["label"] . ":<br>";
        echo '<select name="' . $field["name"] . '">';
        if($Options["Selects"][$field["name"]][0][0] > 0){
          echo '<option value="0" selected disabled></option>';
        }
        foreach($Options["Selects"][$field["name"]] as $select){
          echo '<option value="' . $select[0] . '"';
          if(isset($Options["Data"]) and $select[0] == $Options["Data"][$field["name"]]){
            echo " selected";
          }elseif($field["default"] !== null and $select[0] == $field["default"]){
            echo " selected";
          }
          echo ">" . $select[1] . "</option>";
        }
        echo "</select><br>";
      }elseif($field["type"] == "checkbox"){
        echo '<p><input type="checkbox" name="' . $field["name"] . '"';
        if(isset($Options["Data"])){
          if($Options["Data"][$field["name"]] == 1){
            echo " checked";
          }
        }elseif($field["default"] == 1){
          echo " checked";
        }
        echo "> " . $field["label"] . "</p>";
      }elseif($field["type"] == "hidden"){
        echo '<input type="' . $field["type"] . '" name="' . $field["name"] . '"';
        if(isset($Options["Hiddens"])){
          echo ' value="' . $Options["Hiddens"][$field["name"]] . '"';
        }
        echo "><br>";
      }else{
        echo $field["label"] . ":<br>";
        echo '<input type="' . $field["type"] . '" name="' . $field["name"] . '"';
        if($field["size"] != null){
          echo ' size="' . $field["size"] . '"';
        }
        if(isset($Options["Data"])){
          echo ' value="' . $Options["Data"][$field["name"]] . '"';
        }elseif($field["default"] != null){
          echo ' value="' . $field["default"] . '"';
        }
        if($field["style"] != null){
          echo ' style="' . $field["style"] . '"';
        }
        if($field["class"] != null){
          echo ' class="' . $field["class"] . '"';
        }
        if($field["js_event"] != null){
          echo " " . $field["js_event"] . '="' . $field["js_code"] . '"';
        }
        if($field["mode"] == 1 and isset($Options["Data"])){
          echo " disabled";
        }elseif($field["mode"] == 2 and isset($Options["Data"]) == false){
          echo " disabled";
        }
        echo "><br>";
      }
    }
    echo "</p>";
    $fields = $PhpLivePdo->SQL("
      select *
      from forms_fields
      where form_id=?
        and type='submit'",
      [
        [1, $form[0]["form_id"], PdoInt]
      ],
      ["Debug" => $Options["PdoDebug"]]
    );
    echo '<p><input type="submit" value="' . $fields[0]["label"] . '"';
    if($form[0]["method"] == "ajax"){
      echo " onclick=\"Ajax('" . $Options["Page"] . "','" . $Options["Place"] . "','" . $Options["Form"] . "');" . $fields[0]["js_code"] . '"';
    }
    echo "></p>";
    echo "</form>";
  }
}