<?php
//2020.05.08.00
require_once('system.php');

$types = [
  ['checkbox', 'Checkbox'],
  ['date', 'Date'],
  ['select', 'List'],
  ['number', 'Number'],
  ['submit', 'Submit button'],
  ['text', 'Text'],
  ['textarea', 'Textarea']
];

if(isset($_GET['a']) == false):
  $forms = $PDO->Run('select * from forms_forms order by site,form');?>
  <table class="center">
    <tr>
      <th class="sticky">
        <a href="#" onclick="Ajax('/admin/forms.php?a=formsform','AjaxWindow1Page');WindowOpen('New form');">
          <img src="/common/images/add1.gif">
        </a>
      </th>
      <th class="sticky">Site</th>
      <th class="sticky">Form</th>
      <th class="sticky">Method</th>
      <th class="sticky">Auto complete</th>
    </tr><?php
    foreach($forms as $form):?>
      <tr class="alternate mouse">
        <td>
          <a href="#" onclick="Ajax('/admin/forms.php?a=formsform&id=<?php print $form['form_id'];?>','AjaxWindow1Page');WindowOpen('Edit form');">
            <img src="/common/images/edit.gif">
          </a>
          <a href="#" onclick="if(confirm('Do you really want to delete this form?'))
          Ajax('/admin/forms.php?a=formsdel&id=<?php print $form['form_id'];?>','AjaxPage');">
            <img src="/common/images/del.gif">
          </a>
        </td>
        <td><?php print $form['site'];?></td>
        <td>
          <a href="#" onclick="Ajax('/admin/forms.php?a=fields&form=<?php print $form['form_id'];?>','AjaxPage')">
            <?php print $form['form'];?>
          </a>
        </td>
        <td><?php print $form['method'];?></td>
        <td><?php print $form['action'];?></td>
        <td><?php print $form['autocomplete'] == 0? 'No': 'Yes';?></td>
      </tr><?php
    endforeach;?>
  </table><?php
elseif($_GET['a'] == 'formsform'):
  $selects = [
    'autocomplete' => [
      [1, 'Yes'],
      [0, 'No']
    ],
    'method' => [
      ['ajax', 'Ajax'],
      ['get', 'Get'],
      ['post', 'Post']
    ]
  ];
  if(isset($_GET['id'])):
    $form = $PDO->Run('select * from forms_forms where form_id=?', [
      [1, $_GET['id'], PdoInt]
    ]);
    $Forms->Form([
      'Site' => 'admin',
      'Form' => 'form',
      'Page' => '/admin/forms.php?a=formsok&id=' . $_GET['id'],
      'Place' => 'AjaxWindow1Page',
      'Selects' => $selects,
      'Data' => $form[0]
    ]);
  else:
    $Forms->Form([
      'Site' => 'admin',
      'Form' => 'form',
      'Page' => '/admin/forms.php?a=formsok',
      'Place' => 'AjaxWindow1Page',
      'Selects' => $selects
    ]);
  endif;
elseif($_GET['a'] == 'formsok'):
  $_GET['id'] ??= null;
  $PDO->UpdateInsert([
    'Table' => 'forms_forms',
    'Fields' => [
      ['site', $_POST['site'], PdoStr],
      ['form', $_POST['form'], PdoStr],
      ['method', $_POST['method'], PdoStr],
      ['autocomplete', $_POST['autocomplete'], PdoStr]
    ],
    'Where' => ['form_id', $_GET['id'], PdoInt]
  ]);
  if(isset($_GET['id'])):
    print '<p>Form edited</p>';
  else:
    print '<p>Form added</p>';
  endif;?>
  <p><a href="#" onclick="Ajax('/admin/forms.php?a=forms','AjaxPage');WindowClose();">Continue</a><?php
elseif($_GET['a'] == 'formsdel'):
  $PDO->Run('delete from forms_forms where form_id=?', [
    [1, $_GET['id'], PdoInt]
  ]);
  header('location:forms.php?a=forms');
elseif($_GET['a'] == 'fields'):
  $fields = $PDO->Run('select * from forms_fields where form_id=? order by `order`', [
    [1, $_GET['form'], PdoInt]
  ]);?>
  <table class="center">
    <tr>
      <th>
        <a href="#" onclick="Ajax('/admin/forms.php?a=fieldsform&form=<?php print $_GET['form'];?>','AjaxWindow1Page');WindowOpen('New field');">
          <img src="/common/images/add1.gif">
        </a>
      </th>
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
          <a href="#" onclick="Ajax('/admin/forms.php?a=fieldsform&form=<?php print $_GET['form'];?>&id=<?php print $field['field_id'];?>','AjaxWindow1Page');WindowOpen('Editar campo');">
            <img src="/common/images/edit.gif">
          </a>
          <a href="#" onclick="if(confirm('Do you really want to delete this field?'))
          Ajax('/admin/forms.php?a=fieldsdel&form=<?php print $_GET['form'];?>&id=<?php print $field['field_id'];?>','AjaxPage');">
            <img src="/common/images/del.gif">
          </a>
        </td>
        <td><?php print $field['label'];?></td>
        <td><?php print $field['name'];?></td>
        <td><?php print $types[array_search($field['type'], array_column($types, 0))][1];?></td>
        <td><?php print $field['default'];?></td>
        <td><?php 
          if($field['mode'] == 0):
            print 'Normal';
          elseif($field['mode'] == 1):
            print 'Only insert';
          else:
            print 'Only update';
          endif;?>
        </td>
        <td><?php print $field['size'];?></td>
        <td><?php print $field['style'];?></td>
        <td><?php print $field['class'];?></td>
        <td><?php print $field['js_event'];?></td>
        <td><?php print $field['js_code'];?></td>
        <td><?php print $field['order'];?></td>
      </tr><?php
    endforeach;?>
  </table><?php
elseif($_GET['a'] == 'fieldsform'):
  $selects = [
    'type' => $types,
    'mode' => [
      [0, 'Normal'],
      [1, 'Only insert'],
      [2, 'Only update']
    ]
  ];
  if(isset($_GET['id'])):
    $data = $PDO->Run('select * from forms_fields where field_id=?', [
      [1, $_GET['id'], PdoInt]
    ]);
    $Forms->Form([
      'Site' => 'admin',
      'Form' => 'fields',
      'Page' => '/admin/forms.php?a=fieldsok&form=' . $_GET['form'] . '&id=' . $_GET['id'],
      'Place' => 'AjaxWindow1Page',
      'Data' => $data[0],
      'Selects' => $selects
    ]);
  else:
    $Forms->Form([
      'Site' => 'admin',
      'Form' => 'fields',
      'Page' => '/admin/forms.php?a=fieldsok&form=' . $_GET['form'],
      'Place' => 'AjaxWindow1Page',
      'Selects' => $selects
    ]);
  endif;
elseif($_GET['a'] == 'fieldsok'):
  if($_POST['js_event'] == ''):
    $_POST['js_code'] = '';
  endif;
  $PDO->UpdateInsert([
    'Table' => 'forms_fields',
    'Fields' => [
      ['label', $_POST['label'], PdoStr],
      ['name', $_POST['name'], $_POST['name'] == ''? PdoNull: PdoStr],
      ['type', $_POST['type'], PdoStr],
      ['default', $_POST['default'], $_POST['default'] == ''? PdoNull: PdoStr],
      ['mode', $_POST['mode'], PdoInt],
      ['size', $_POST['size'], $_POST['size'] == ''? PdoNull: PdoInt],
      ['style', $_POST['style'], $_POST['style'] == ''? PdoNull: PdoStr],
      ['class', $_POST['class'], $_POST['class'] == ''? PdoNull: PdoStr],
      ['js_event', $_POST['js_event'], $_POST['js_event'] == ''? PdoNull: PdoStr],
      ['js_code', $_POST['js_code'], $_POST['js_code'] == null? PdoNull: PdoStr],
      ['order', $_POST['order'], PdoInt]
    ],
    'Where' => ['field_id', $_GET['id'], PdoInt]
  ]);
  if(isset($_GET['id'])):
    print '<p>Field edited</p>';
  else:
    print '<p>Field added</p>';
  endif;?>
  <p><a href="#" onclick="Ajax('/admin/forms.php?a=fields&form=<?php print $_GET['form'];?>','AjaxPage');WindowClose();">Continue</a><?php
elseif($_GET['a'] == 'fieldsdel'):
  $PDO->Run('delete from forms_fields where field_id=?', [
    [1, $_GET['id'], PdoInt]
  ]);
  header('location:forms.php?a=fields&form=' . $_GET['form']);
endif;