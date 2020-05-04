<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.04.03

class PhpLiveForms{
  private ?object $PhpLivePdo = null;

  public function __construct(object &$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Form(array $Options):bool{
    if($this->PhpLivePdo === null):
      if(isset($Options["PhpLivePdo"]) == false):
        return false;
      else:
        $PhpLivePdo = &$Options["PhpLivePdo"];
      endif;
    else:
      $PhpLivePdo = $this->PhpLivePdo;
    endif;
    $Options["PdoDebug"] ??= false;
    $Options["AjaxAppend"] ??= false;

    // Get site
    if(isset($Options["Site"])):
      $site[0] = "site=:site";
      $site[1] = [
        [":site", $Options["Site"], PdoStr],
        [":form", $Options["Form"], PdoStr]
      ];
    elseif(session_name() != "PHPSESSID"):
      $site[0] = "site=:site";
      $site[1] = [
        [":site", session_name(), PdoStr],
        [":form", $Options["Form"], PdoStr]
      ];
    else:
      $site[0] = "site is null";
      $site[1] = [[":form", $Options["Form"], PdoStr]];
    endif;
    // Get form
    $form = $PhpLivePdo->SQL("
      select *
      from forms_forms
      where $site[0]
        and form=:form",
      $site[1],
      ["Debug" => $Options["PdoDebug"]]
    );
    // check if form exist
    if(count($form) == 0):
      if(ini_get("display_errors")):
        echo "PhpLiveForms - Form " . $Options["Form"];
        if(session_name() != "PHPSESSID"):
          echo ", site <strong>" . session_name();
        endif;
        echo " not found";
      endif;
      return false;
    endif;
    echo '<form name="' . $form[0]["form"] . '"';
    if($form[0]["method"] == "ajax"):
      echo ' onsubmit="return false;"';
    else:
      echo ' method="' . $form[0]["method"] . '" action="' . $form[0]["action"] . '"';
    endif;
    if($form[0]["autocomplete"] == 0):
      echo ' autocomplete="off"';
    endif;
    echo ">";
    // Get fields
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
    foreach($fields as $field):
      if($field["type"] == "select"):
        // Check if select data exist
        if(isset($Options["Selects"][$field["name"]]) == false):
          if(ini_get("display_errors")):
            echo "PhpLiveForms - Data for select " . $field["name"] . ", form ". $Options["Form"];
            if(session_name() != "PHPSESSID"):
              echo ", site " . session_name();
            endif;
            echo " not found";
          endif;
          return false;
        endif;
        echo $field["label"] . ":<br>";
        echo '<select name="' . $field["name"] . '">';
        // Check the first option of select data
        // Show a default value if not specified
        // or a default value not specified
        if($Options["Selects"][$field["name"]][0][0] > 0 and $field["default"] == null):
          echo '<option value="0" selected disabled></option>';
        endif;
        foreach($Options["Selects"][$field["name"]] as $select):
          echo '<option value="' . $select[0] . '"';
          if(isset($Options["Data"]) and $select[0] == $Options["Data"][$field["name"]]):
            echo " selected";
          elseif($field["default"] !== null and $select[0] == $field["default"]):
            echo " selected";
          endif;
          echo ">" . $select[1] . "</option>";
        endforeach;
        echo "</select><br>";
      elseif($field["type"] == "checkbox"):
        echo '<p><input type="checkbox" name="' . $field["name"] . '"';
        if(isset($Options["Data"])):
          if($Options["Data"][$field["name"]] == 1):
            echo " checked";
          endif;
        elseif($field["default"] == 1):
          echo " checked";
        endif;
        echo "> " . $field["label"] . "</p>";
      elseif($field["type"] == "hidden"):
        echo '<input type="' . $field["type"] . '" name="' . $field["name"] . '"';
        if(isset($Options["Hiddens"])):
          echo ' value="' . $Options["Hiddens"][$field["name"]] . '"';
        endif;
        echo "><br>";
      elseif($field["type"] == "textarea"):
        echo $field["label"] . ":<br>";
        echo '<textarea name="' . $field["name"] . '">';
        if(isset($Options["Data"])):
          echo $Options["Data"][$field["name"]];
        elseif($field["default"] != null):
          echo $field["default"];
        endif;
        echo "</textarea>";
      else:
        echo $field["label"] . ":<br>";
        echo '<input type="' . $field["type"] . '" name="' . $field["name"] . '"';
        if($field["size"] != null):
          echo ' size="' . $field["size"] . '"';
        endif;
        if(isset($Options["Data"])):
          echo ' value="' . $Options["Data"][$field["name"]] . '"';
        elseif($field["default"] != null):
          echo ' value="' . $field["default"] . '"';
        endif;
        if($field["style"] != null):
          echo ' style="' . $field["style"] . '"';
        endif;
        if($field["class"] != null):
          echo ' class="' . $field["class"] . '"';
        endif;
        if($field["js_event"] != null):
          echo " " . $field["js_event"] . '="' . $field["js_code"] . '"';
        endif;
        if($field["mode"] == 1 and isset($Options["Data"])):
          echo " disabled";
        elseif($field["mode"] == 2 and isset($Options["Data"]) == false):
          echo " disabled";
        endif;
        echo "><br>";
      endif;
    endforeach;
    echo "</p>";
    // Submit button
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
    echo '<p><input type="submit" value="' . $fields[0]["label"] . '" onclick="';
    if($form[0]["method"] == "ajax"):
      if($Options["AjaxAppend"]):
        echo "AjaxAppend('" . $Options["Page"] . "','" . $Options["Place"] . "','" . $Options["Form"] . "'," . $Options["AjaxAppend"] . ");";
      else:
        echo "Ajax('" . $Options["Page"] . "','" . $Options["Place"] . "','" . $Options["Form"] . "');";
      endif;
    endif;
    if($fields[0]["js_event"] == "onclick"):
      echo $fields[0]["js_code"];
    endif;
    echo '"></p>';
    echo "</form>";
    return true;
  }
}