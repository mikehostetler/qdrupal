<?php
// $Id$

function qdrupal_profile_overview($app) {
  drupal_set_title($app->title);

  $bc = drupal_get_breadcrumb();
  array_pop($bc);
  $bc[] = l($app->title,'qdrupal/applications/'.$app->aid);
  drupal_set_breadcrumb($bc);

  $output = '';
  $rows = array();
  $header = array(t('Profile Name'), '', '');

  $profiles = array();
  $profiles = qdrupal_profile_load($app->aid);
  if($profiles) {
    foreach($profiles as $name => $profile) {
      $rows[] = array(
        $profile->name,
        l(t('edit'),'admin/build/qdrupal/'.$app->aid.'/profile/'.$profile->name.'/edit'),
        l(t('delete'),'admin/build/qdrupal/'.$app->aid.'/profile/'.$profile->name.'/delete'),
      );
    }
  }

  $output .= theme('table', $header, $rows);
  return $output;
}

function qdrupal_profile_form(&$form_state, $app, $profile = NULL, $edit = array()) {
  $bc = drupal_get_breadcrumb();
  array_pop($bc);
  $bc[] = l($app->title,'qdrupal/applications/'.$app->aid);
  $bc[] = l('Profiles','qdrupal/applications/'.$app->aid.'/profile');
  drupal_set_breadcrumb($bc);

  $edit += array(
    'profile_name' => '',
    'adapter' => 'MySqli',
    'server' => 'localhost',
    'port' => '3306',
    'dbname' => '',
    'username' => '',
    'password' => '',
    'profiling' => FALSE,
    'class_name_prefix' => '',
    'class_name_suffix' => '',
    'associated_object_name_prefix' => '',
    'associated_object_name_suffix' => '',
    'type_table_identifier_suffix' => '_type',
    'association_table_identifier_suffix' => '_assn',
    'exclude_tables_list' => '',
    'exclude_tables_pattern' => '',
    'include_tables_list' => '',
    'include_tables_pattern' => '',
    'relationships' => ''
  );

  if(is_array($profile->setting)) {
    $edit = array_merge($edit,$profile->setting);
  }

  if(isset($profile->name)) {
    $edit['profile_name'] = $profile->name;
  }

  $form['profile_name'] = array( '#type' => 'textfield',
    '#title' => t('Profile name'),
    '#default_value' => $edit['profile_name'],
    '#size' => 40,
    '#maxlength' => 128,
    '#description' => t('Enter a name for this profile. This name is only visible within the qdrupal administration pages.'),
    '#required' => TRUE
  );

  if($edit['profile_name'] != '') {
    $form['profile_name']['#type'] = 'item';
    $form['profile_name']['#value'] = $edit['profile_name'];

    $form['action'] = array('#type' => 'value', '#value' => 'edit');
  }

  $form['connection'] = array( '#type' => 'fieldset',
    '#title' => t('Connection Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => False
  );
  $form['connection']['adapter'] = array( '#type' => 'select',
    '#title' => t('Adapter Type'),
    '#default_value' => $edit['adapter'],
    '#options' => array('MySql' => t('MySql'),'MySqli' => t('MySqli'),'MySqli5' => t('MySqli5')),
    '#description' => t('Mysql adapter type to use'),    
	  '#required' => TRUE
  );
  $form['connection']['server'] = array( '#type' => 'textfield',
    '#title' => t('Server'),
    '#default_value' => $edit['server'],
    '#description' =>  t('Server hosting your database.  Can be localhost.'),
    '#required' => TRUE
  );
  $form['connection']['dbname'] = array( '#type' => 'textfield',
    '#title' => t('Database Name'),
    '#default_value' => $edit['dbname'],
    '#description' =>  t('The Name of your database'),
    '#required' => TRUE
  );
  $form['connection']['username'] = array( '#type' => 'textfield',
    '#title' => t('Username'),
    '#default_value' => $edit['username'],
    '#description' =>  t('Username with access to this database'),
    '#required' => TRUE
  );
  $form['connection']['password'] = array( '#type' => 'textfield',
    '#title' => t('Password'),
    '#default_value' => $edit['password'],
    '#description' =>  t('Password for database user'),
  );
  $form['connection']['port'] = array( '#type' => 'textfield',
    '#title' => t('Port'),
    '#default_value' => $edit['port'],
    '#description' =>  t('Port your database listens on'),
    '#required' => TRUE
  );
  $form['connection']['profiling'] = array(
	  '#type' => 'select',
    '#title' => t('Profiling'),
    '#default_value' => $edit['profiling'],
    '#options' => array('false' => t('false'), 'true' => t('true')),
    '#description' => t('Enable/Disbable database profiling'),
  );

  $form['codegen'] = array(
    '#type' => 'fieldset',
    '#title' => t('Codegen Settings'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE
  );
  $form['codegen']['class_name_prefix'] = array( '#type' => 'textfield',
    '#title' => t('Class Name Prefix'),
    '#default_value' => $edit['class_name_prefix'],
    '#description' =>  t('Class name prefix for codegen'),
    '#required' => FALSE 
  );
  $form['codegen']['class_name_suffix'] = array( '#type' => 'textfield',
    '#title' => t('Class Name Suffix'),
    '#default_value' => $edit['class_name_suffix'],
    '#description' =>  t('Class name suffix for codegen'),
    '#required' => FALSE 
  );
  $form['codegen']['associated_object_name_prefix'] = array( '#type' => 'textfield',
    '#title' => t('Associated Object Prefix'),
    '#default_value' => $edit['associated_object_name_prefix'],
    '#description' =>  t('Associated Object Name Prefix for codegen'),
    '#required' => FALSE
  );
  $form['codegen']['associated_object_name_suffix'] = array( '#type' => 'textfield',
    '#title' => t('Associated Object Suffix'),
    '#default_value' => $edit['associated_object_name_suffix'],
    '#description' =>  t('Associated Object Name Suffix for codegen'),
    '#required' => FALSE
  );
  $form['codegen']['type_table_identifier_suffix'] = array( '#type' => 'textfield',
    '#title' => t('Type Table Identifier Suffix'),
    '#default_value' => $edit['type_table_identifier_suffix'],
    '#description' =>  t('Type Table Identifier Suffix for codegen'),
    '#required' => FALSE
  );
  $form['codegen']['association_table_identifier_suffix'] = array( '#type' => 'textfield',
    '#title' => t('Association Table Identifier Suffix'),
    '#default_value' => $edit['association_table_identifier_suffix'],
    '#description' =>  t('Association Identifier Suffix for codegen'),
    '#required' => FALSE
  );
  $form['codegen']['exclude_tables_list'] = array( '#type' => 'textfield',
    '#title' => t('Exclude Tables List'),
    '#default_value' => $edit['exclude_tables_list'],
    '#description' =>  t('Comma-separated list of tables to exclude from data generation'),
    '#required' => FALSE
  );
  $form['codegen']['exclude_tables_pattern'] = array( '#type' => 'textfield',
    '#title' => t('Exclude Tables Pattern'),
    '#default_value' => $edit['exclude_tables_pattern'],
    '#description' =>  t('A regular expression pattern that matches table names to exclude from data generation'),
    '#required' => FALSE 
  );
  $form['codegen']['include_tables_list'] = array( '#type' => 'textfield',
    '#title' => t('Include Tables List'),
    '#default_value' => $edit['include_tables_list'],
    '#description' =>  t('Comma-separated list of tables to include in data generation.  This setting overrides the exclude list.'),
    '#required' => FALSE
  );
  $form['codegen']['include_tables_pattern'] = array( '#type' => 'textfield',
    '#title' => t('Include Tables Pattern'),
    '#default_value' => $edit['include_tables_pattern'],
    '#description' =>  t('A regular expression pattern that matches table names to include in data generation. This setting overrides the exclude pattern.'),
    '#required' => FALSE 
  );
  $form['codegen']['relationships'] = array( '#type' => 'textarea',
    '#title' => t('Relationships'),
    '#default_value' => $edit['relationships'],
    '#description' =>  t('Manually define relationships between tables that will be used during code generation.'),
    '#required' => FALSE 
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save')
  );

  $form['aid'] = array('#type' => 'value', '#value' => $app->aid);

  return $form;
}

function qdrupal_profile_form_validate($form, &$form_state) {
  $values = $form_state['values'];
  $aid = $values['aid'];
  if(!isset($values['profile_name'])) {
    $name = $form['profile_name']['#value'];
  }
  else {
    $name = $values['profile_name'];
  }

  if($values['action'] != 'edit' && qdrupal_profile_load($aid,$name)) {
    form_set_error('name', t('A profile with that name already exists for this application'));
  }
}

function qdrupal_profile_form_submit($form, &$form_state) {
  $values = $form_state['values'];
  $aid = $values['aid'];
  if(!isset($values['profile_name'])) {
    $name = $form['profile_name']['#value'];
  }
  else {
    $name = $values['profile_name'];
  }

  qdrupal_profile_save($aid,$name,$values);

  $form_state['redirect'] = 'admin/build/qdrupal/'.$aid.'/profile';
  return;
}

function qdrupal_profile_delete_confirm(&$form_state, $app, $profile) {
  $form['aid'] = array('#type' => 'value', '#value' => $app->aid);
  $form['profile'] = array('#type' => 'value', '#value' => $profile->name);

  return confirm_form($form,
    t('Are you sure you want to delete the profile %profile?', array('%profile' => $profile->name)),
    'admin/build/qdrupal/'.$app->aid.'/profile',
    '<p>'.t('Deleting this profile will completely remove the profile settings and you will be unable to code-generate against this profile.  This action cannot be undone.'.'</p>'),
    t('Delete'),
    t('Cancel'));
}

function qdrupal_profile_delete_confirm_submit($form, &$form_state) {
  qdrupal_profile_delete($form_state['values']['aid'],$form_state['values']['profile']);
  drupal_set_message(t('Deleted profile %profile.', array('%profile' => $form_state['values']['profile'])));
  watchdog('qdrupal', 'Deleted profile %profile for application %app', array('%profile' => $form_state['values']['profile'], '%app' => $form_state['values']['aid']));
  $form_state['redirect'] = 'admin/build/qdrupal/'.$form_state['values']['aid'].'/profile';
  return;
}
