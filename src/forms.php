<?php
// Protocol Corporation Ltda.
// https://github.com/ProtocolLive/PhpLive/
// Version 2020.05.06.02

class PhpLiveForms{
  private ?object $PhpLivePdo = null;

  public function __construct(object &$PhpLivePdo = null){
    $this->PhpLivePdo = $PhpLivePdo;
  }

  public function Form(array $Options):bool{
    if($this->PhpLivePdo === null):
      if(isset($Options['PhpLivePdo']) == false):
        return false;
      else:
        $PhpLivePdo = &$Options['PhpLivePdo'];
      endif;
    else:
      $PhpLivePdo = $this->PhpLivePdo;
    endif;
    $Options['PdoDebug'] ??= false;
    $Options['AjaxAppend'] ??= false;

    // Get site
    if(isset($Options['Site'])):
      $site[0] = 'site=:site';
      $site[1] = [
        [':site', $Options['Site'], PdoStr],
        [':form', $Options['Form'], PdoStr]
      ];
    elseif(session_name() != 'PHPSESSID'):
      $site[0] = 'site=:site';
      $site[1] = [
        [':site', session_name(), PdoStr],
        [':form', $Options['Form'], PdoStr]
      ];
    else:
      $site[0] = 'site is null';
      $site[1] = [[':form', $Options['Form'], PdoStr]];
    endif;
    // Get form
    $form = $PhpLivePdo->Run('
      select *
      from forms_forms
      where ' . $site[0] . '
        and form=:form',
      $site[1],
      ['Debug' => $Options['PdoDebug']]
    );
    // check if form exist
    if(count($form) == 0):
      if(ini_get('display_errors')):
        printf('PhpLiveForms - Form %s', $Options['Form']);
        if(session_name() != 'PHPSESSID'):
          printf(' (site %s)', session_name());
        endif;
        print ' not found';
      endif;
      return false;
    endif;
    // Build form
    printf('<form name="%s"', $form[0]['form']);
    if($form[0]['method'] == 'ajax'):
      print ' onsubmit="return false;"';
    else:
      printf(' method="%s" action="%s"', $form[0]['method'], $Options['Page']);
    endif;
    if($form[0]['autocomplete'] == 0):
      print ' autocomplete="off"';
    endif;
    print '>';
    // Get fields
    $fields = $PhpLivePdo->Run("
      select *
      from forms_fields
      where form_id=?
        and type<>'submit'
      order by `order`",
      [
        [1, $form[0]['form_id'], PdoInt]
      ],
      ['Debug' => $Options['PdoDebug']]
    );
    print '<p>';
    foreach($fields as $field):
      if($field['type'] == 'select'):
        // Check if select data exist
        if(isset($Options['Selects'][$field['name']]) == false):
          if(ini_get('display_errors')):
            printf('PhpLiveForms - Data for select %s, form %s', $field['name'], $Options['Form']);
            if(session_name() != 'PHPSESSID'):
              printf(' (site %s)', session_name());
            endif;
            print ' not found';
          endif;
          return false;
        endif;
        printf('%s:<br>', $field['label']);
        printf('<select name="%s">', $field['name']);
        // Check the first option of select data
        // Show a default value if not specified
        // or a default value not specified
        if($Options['Selects'][$field['name']][0][0] > 0 and $field['default'] == null):
          print '<option value="0" selected disabled></option>';
        endif;
        foreach($Options['Selects'][$field['name']] as $select):
          printf('<option value="%s"', $select[0]);
          if(isset($Options['Data']) and $select[0] == $Options['Data'][$field['name']]):
            print ' selected';
          elseif($field['default'] !== null and $select[0] == $field['default']):
            print ' selected';
          endif;
          printf('>%s</option>', $select[1]);
        endforeach;
        print '</select><br>';
      elseif($field['type'] == 'checkbox'):
        printf('<p><input type="checkbox" name="%s"', $field['name']);
        if(isset($Options['Data'])):
          if($Options['Data'][$field['name']] == 1):
            print ' checked';
          endif;
        elseif($field['default'] == 1):
          print ' checked';
        endif;
        printf('> %s</p>', $field['label']);
      elseif($field['type'] == 'hidden'):
        printf('<input type="%s" name="%s"', $field['type'], $field['name']);
        if(isset($Options['Hiddens'])):
          printf(' value="%s"', $Options['Hiddens'][$field['name']]);
        endif;
        print '><br>';
      elseif($field['type'] == 'textarea'):
        printf('%s:<br>', $field['label']);
        printf('<textarea name="%s">', $field['name']);
        if(isset($Options['Data'])):
          print $Options['Data'][$field['name']];
        elseif($field['default'] != null):
          print $field['default'];
        endif;
        print '</textarea>';
      else:
        printf('%s:<br>', $field['label']);
        printf('<input type="%s" name="%s"', $field['type'], $field['name']);
        if($field['size'] != null):
          printf(' size="%d"', $field['size']);
        endif;
        if(isset($Options['Data'])):
          printf(' value="%s"', $Options['Data'][$field['name']]);
        elseif($field['default'] != null):
          printf(' value="%s"', $field['default']);
        endif;
        if($field['style'] != null):
          printf(' style="%s"', $field['style']);
        endif;
        if($field['class'] != null):
          printf(' class="%s"', $field['class']);
        endif;
        if($field['js_event'] != null):
          printf('%s="%s"', $field['js_event'], $field['js_code']);
        endif;
        if($field['mode'] == 1 and isset($Options['Data'])):
          print ' disabled';
        elseif($field['mode'] == 2 and isset($Options['Data']) == false):
          print ' disabled';
        endif;
        print '><br>';
      endif;
    endforeach;
    print '</p>';
    // Submit button
    $fields = $PhpLivePdo->Run("
      select *
      from forms_fields
      where form_id=?
        and type='submit'",
      [
        [1, $form[0]['form_id'], PdoInt]
      ],
      ['Debug' => $Options['PdoDebug']]
    );
    printf('<p><input type="submit" value="%s" onclick="', $fields[0]['label']);
    if($form[0]['method'] == 'ajax'):
      if($Options['AjaxAppend']):
        printf("AjaxAppend('%s','%s','%s',%s);",
          $Options['Page'],
          $Options['Place'],
          $Options['Form'],
          $Options['AjaxAppend']
        );
      else:
        printf("Ajax('%s','%s','%s');",
          $Options['Page'],
          $Options['Place'],
          $Options['Form']
        );
      endif;
    endif;
    if($fields[0]['js_event'] == 'onclick'):
      print $fields[0]['js_code'];
    endif;
    print '"></p>';
    print '</form>';
    return true;
  }
}