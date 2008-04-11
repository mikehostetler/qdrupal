<?php
// $Id$

/**
 * Displays list of database settings.
 */
function qdrupal_settings_overview($nid) {
  $output = '';
  $settings = qdrupal_settings_load($nid);
  if ($settings) {
    $header = array(t('Database'), t('Operations'));
    foreach ($settings as $s) {
      $rows[] = array(array('data' => $s->name, 'valign' => 'top'), array('data' => l(t('edit'), 'node/'.$nid.'/databases/edit/'. urlencode($s->name)) . ' '. l(t('delete'), 'node/'.$nid.'/databases/delete/'. urlencode($s->name)), 'valign' => 'top'));
    }
    $output .= theme('table', $header, $rows);
    $output .= t('<p><a href="!create-settings-url">Create new Database settings</a></p>', array('!create-settings-url' => url('node/'.$nid.'/databases/add')));
  }
  else {
    drupal_set_message(t('No Database settings found. Click here to <a href="!create-settings-url">create a new database settings profile</a>.', array('!create-settings-url' => url('node/'.$nid.'/databases/add'))));
  }
  return $output;
}

/**
 * Load all qcodo settings for a given application node. Just load one settings array if $name is passed in.
 */
function qdrupal_settings_load($nid,$name = '') {
  static $settings = array();

  if (!$settings) {
    $result = db_query('SELECT * FROM {qdrupal_setting} where nid = %d',$nid);
    while ($data = db_fetch_object($result)) {
      $data->setting = unserialize($data->setting);
      $settings[$data->name] = $data;
    }
  }
  return ($name ? $settings[$name] : $settings);
}

/**
 * Return an HTML form for profile configuration.
 */
function qdrupal_settings_form($edit,$nid) {
  $output .= drupal_get_form('qdrupal_settings_form_build', $edit, $nid);
  return $output;
}

/**
 * Return an HTML form for database settings configuration.
 */
function qdrupal_settings_form_build($edit,$nid) {
  $edit = (object) $edit;

  if (arg(3) == 'add') {
    $btn = t('Create settings');
  }
  else {
    $form['old_name'] = array('#type' => 'hidden', '#value' => $edit->name);
    $btn = t('Update settings');
  }
  $form['basic']['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Profile name'),
    '#default_value' => $edit->name,
    '#size' => 40,
    '#maxlength' => 128,
    '#description' => t('Enter a name for this settings profile. This name is only visible within the qdrupal administration pages.'),
    '#required' => TRUE
  );
  $form['connection'] = array(
    '#type' => 'fieldset',
    '#title' => t('Connection Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => False
  );

  $form['connection']['adapter'] = array(
    '#type' => 'select',
    '#title' => t('Adapter Type'),
    '#default_value' => $edit->setting['adapter'] ? $edit->setting['adapter'] : 'MySqli5',
    '#options' => array('MySql' => t('MySql'),'MySqli' => t('MySqli'),'MySqli5' => t('MySqli5')),
    '#description' => t('Mysql adapter type to use'),    
  '#required' => TRUE

  );

  $form['connection']['server'] = array(
    '#type' => 'textfield',
    '#title' => t('Server'),
    '#default_value' => $edit->setting['server'] ? $edit->setting['server'] : 'localhost',
    '#description' =>  t('Server hosting your database.  Can be localhost.'),
    '#required' => TRUE
  );

  $form['connection']['port'] = array(
    '#type' => 'textfield',
    '#title' => t('Port'),
    '#default_value' => $edit->setting['port'] ? $edit->setting['port'] : '3306',
    '#description' =>  t('Port your database listens on'),
    '#required' => TRUE
  );

  $form['connection']['dbname'] = array(
    '#type' => 'textfield',
    '#title' => t('Name'),
    '#default_value' => $edit->setting['dbname'] ? $edit->setting['dbname'] : '',
    '#description' =>  t('The Name of your database'),
    '#required' => TRUE
  );

  $form['connection']['username'] = array(
    '#type' => 'textfield',
    '#title' => t('Username'),
    '#default_value' => $edit->setting['username'] ? $edit->setting['username'] : '',
    '#description' =>  t('Username with access to this database'),
    '#required' => TRUE
  );
  $form['connection']['password'] = array(
    '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => $edit->setting['password'] ? $edit->setting['password'] : '',
    '#description' =>  t('Password for database user'),
    '#required' => TRUE
  );
  $form['connection']['profiling'] = array(
  '#type' => 'select',
    '#title' => t('Profiling'),
    '#default_value' => $edit->setting['profiling'] ? $edit->setting['profiling'] : 'false',
    '#options' => array('false' => t('false'), 'true' => t('true')),
    '#description' => t('Enable/Disbable database profiling'),
  );

  $form['codegen'] = array(
    '#type' => 'fieldset',
    '#title' => t('Codegen Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $form['codegen']['classNamePrefix'] = array(
    '#type' => 'textfield',
    '#title' => t('Class Name Prefix'),
    '#default_value' => $edit->setting['classNamePrefix'] ? $edit->setting['classNamePrefix'] : '',
    '#description' =>  t('Class name prefix for codegen'),
    '#required' => False
  );
  $form['codegen']['classNameSuffix'] = array(
    '#type' => 'textfield',
    '#title' => t('Class Name Suffix'),
    '#default_value' => $edit->setting['classNameSuffix'] ? $edit->setting['classNameSuffix'] : '',
    '#description' =>  t('Class name suffix for codegen'),
    '#required' => False
  );
  $form['codegen']['associatedObjectNamePrefix'] = array(
    '#type' => 'textfield',
    '#title' => t('Associated Object Prefix'),
    '#default_value' => $edit->setting['associatedObjectNamePrefix'] ? $edit->setting['associatedObjectNamePrefix'] : '',
    '#description' =>  t('Associated Object Name Prefix for codegen'),
    '#required' => False
  );
  $form['codegen']['associatedObjectNameSuffix'] = array(
    '#type' => 'textfield',
    '#title' => t('Associated Object Suffix'),
    '#default_value' => $edit->setting['associatedObjectNameSuffix'] ? $edit->setting['associatedObjectNameSuffix'] : '',
    '#description' =>  t('Associated Object Name Suffix for codegen'),
    '#required' => False
  );
  $form['codegen']['typeTableIdentifierSuffix'] = array(
    '#type' => 'textfield',
    '#title' => t('Type Table Identifier Suffix'),
    '#default_value' => $edit->setting['typeTableIdentifierSuffix'] ? $edit->setting['typeTableIdentifierSuffix'] : '_type',
    '#description' =>  t('Type Table Identifier Suffix for codegen'),
    '#required' => False
  );
  $form['codegen']['associationTableIdentifierSuffix'] = array(
    '#type' => 'textfield',
    '#title' => t('Association Table Identifier Suffix'),
    '#default_value' => $edit->setting['associationTableIdentifierSuffix'] ? $edit->setting['associationTableIdentifierSuffix'] : '_assn',
    '#description' =>  t('Association Identifier Suffix for codegen'),
    '#required' => False
  );
  $form['codegen']['excludeTablesList'] = array(
    '#type' => 'textfield',
    '#title' => t('Exclude Tables List'),
    '#default_value' => $edit->setting['excludeTablesList'] ? $edit->setting['excludeTablesList'] : '',
    '#description' =>  t('Comma-separated list of tables to exclude from data generation'),
    '#required' => False
  );
  $form['codegen']['excludeTablesPattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Exclude Tables Pattern'),
    '#default_value' => $edit->setting['excludeTablesPattern'] ? $edit->setting['excludeTablesPattern'] : '',
    '#description' =>  t('A regexp pattern that matches table names to exclude from data generation'),
    '#required' => False
  );

  $form['codegen']['includeTablesList'] = array(
    '#type' => 'textfield',
    '#title' => t('Include Tables List'),
    '#default_value' => $edit->setting['includeTablesList'] ? $edit->setting['includeTablesList'] : '',
    '#description' =>  t('Comma-separated list of tables to include from data generation, defaults to all'),
    '#required' => False
  );
  $form['codegen']['includeTablesPattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Include Tables Pattern'),
    '#default_value' => $edit->setting['includeTablesPattern'] ? $edit->setting['includeTablesPattern'] : '',
    '#description' =>  t('A regexp pattern that matches table names to include from data generation, defaults to all'),
    '#required' => False
  );

  // todo add the rest of the codegen variables 
  //<manualQuery support="false"/>
  //<relationships><![CDATA[
  //]]></relationships>
  //<relationshipsScript filepath="" format="sql"/>
  
  // todo put a cancel button here
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => $btn
  );
  return $form;
}

/**
 * Profile validation.
 */
function qdrupal_settings_validate($edit,$nid) {
  $errors = array();

  if (!$edit['name']) {
    $errors['name'] = t('You must give the settings profile a name.');
  }

  foreach ($errors as $name => $message) {
    form_set_error($name, $message);
  }

  return count($errors) == 0;
} 

/**
 * Save a profile to the database.
 */
function qdrupal_settings_save($edit,$nid) {
  db_query("DELETE FROM {qdrupal_setting} WHERE nid = %d and (name = '%s' or name = '%s')",$nid, $edit['name'], $edit['old_name']);
  db_query("INSERT INTO {qdrupal_setting} (nid, name, setting) VALUES (%d,'%s', '%s')", $nid, $edit['name'], serialize($edit));
}

/**
 * Delete a database profile
 */
function qdrupal_settings_delete($nid,$name) {
  db_query("DELETE FROM {qdrupal_setting} WHERE nid = %d and name = '%s'",$nid, $name);
}
