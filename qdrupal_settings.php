<?php
// $Id$

/**
 * Displays list of database settings.
 */
function qdrupal_settings_overview($shortname) {
  watchdog('qdrupal',"in settings overview for ". $shortname);
  $app = qdrupal_application_load_by_name($shortname);
  drupal_set_title("$app->title");
  drupal_set_breadcrumb(array( l(t('Home'),NULL), 
                              l(t('QDrupal Applications'),'qdrupal/applications'),
                              l($app->title,'qdrupal/applications/'.$app->shortname),
                              l('Databases','qdrupal/applications/'.$app->shortname.'/databases')
                              ));
  $output = '';

  $settings = qdrupal_settings_load($app->aid);
  if ($settings) {
    $header = array(t('Database Profiles'), t('Operations'));
    foreach ($settings as $s) {
      $rows[] = array(array('data' => $s->name, 'valign' => 'top'), 
                array('data' => l(t('edit'), 'qdrupal/applications/'.$app->shortname.'/databases/'. urlencode($s->name). '/edit') . ' ' . 
                l(t('delete'), 'qdrupal/applications/'.$app->shortname.'/databases/'. urlencode($s->name). '/delete'), 'valign' => 'top'));
    }
    $output .= theme('table', $header, $rows);
    $output .= t('<p><a href="!create-settings-url">Create new Database settings</a></p>', array('!create-settings-url' => url('qdrupal/applications/'.$app->shortname.'/databases/add')));
  }
  else {
    drupal_set_message(t('No Database settings found. Click here to <a href="!create-settings-url">create a new database settings profile</a>.', array('!create-settings-url' => url('qdrupal/applications/'.$app->shortname.'/databases/add'))));
  }
  return $output;
}

/**
 * Controller for qcodo application settings.
 */
 /*
function qdrupal_settings_admin($shortname,$instance = NULL,$op = NULL) {
  $edit = $_POST;
  $op = $_POST['op'];
  $op = $arg && !$op ? $arg : $op;

  $app = node_load($nid);

  switch ($op) {
    case 'add':
      // fixme breadcrumbs are broken all over the place
      //$breadcrumb[] = array('path' => 'node/'.$nid, 'title' => t($node->title));
      //$breadcrumb[] = array('path' => 'node/'.$nid.'/databases', 'title' => t('databases'));
      //menu_set_location($breadcrumb);
      $output = qdrupal_settings_form($edit,$app);
      break;

    case 'edit':
      drupal_set_title(t('Edit database settings'));
      $output = qdrupal_settings_form(qdrupal_settings_load($nid,urldecode(arg(4))),$nid);
      break;

    case 'delete':
      qdrupal_settings_delete(urldecode(arg(4)));
      drupal_set_message(t('Deleted Settings'));
      drupal_goto('node/'.$nid.'/databases');
      break;

    case t('Create settings');
    case t('Update settings');
      if (qdrupal_settings_validate($edit,$nid)) {
        qdrupal_settings_save($edit,$nid);
        $edit['old_name'] ? drupal_set_message(t('Your qdrupal database settings have been updated.')) : drupal_set_message(t('Your qdrupal database settings have been created.'));
        drupal_goto('node/'.$nid.'/databases');
      }
      else {
        $output = qdrupal_settings_form($edit,$nid);
      }
      break;

    default:
      drupal_set_title(t('Database settings'));
      $output = qdrupal_settings_overview($nid);
  }

  return $output;
}
*/


/**
 * Load all qcubed settings for a given application. Just load one settings array if $name is passed in.
 */
function qdrupal_settings_load($aid,$name = '') {
	$result = db_query('SELECT * FROM {qdrupal_setting} where aid = %d',$aid);
	while ($data = db_fetch_object($result)) {
		$data->setting = unserialize($data->setting);
		$settings[$data->name] = $data;
	}
  return ($name ? $settings[$name] : $settings);
}

/**
 * Return an HTML form for profile configuration.
 */
function qdrupal_settings_add($shortname) {
  $app = qdrupal_application_load_by_name($shortname);
  $output .= drupal_get_form('qdrupal_settings_form', $app);
  return $output;
}

function qdrupal_settings_edit($shortname, $profilename) {
  $app = qdrupal_application_load_by_name($shortname);
  $settings = qdrupal_settings_load($app->aid, $profilename);
  $output .= drupal_get_form('qdrupal_settings_form', $app, $settings);
  return $output;
}



/**
 * Return an HTML form for database settings configuration.
 */
function qdrupal_settings_form(&$form_state = NULL, $app, $settings=NULL) {
  //$arg1 = print_r($form_state, 1);
  //$arg2 = print_r($app,1);
  //$arg3 = print_r($settings,1);
  //watchdog("qdrupal", "creating settings form");
  //watchdog("qdrupal", "form_state is = " . $arg1);
  //watchdog("qdrupal", "app = " . $arg2);
  //watchdog("qdrupal", "settings = " . $arg3);
  if ($settings) {

    drupal_set_title("Edit Settings for profile '".$settings->name."'");
    drupal_set_breadcrumb(array( l(t('Home'),NULL), 
                                l(t('QDrupal Applications'),'qdrupal/applications'),
                                l($app->title,'qdrupal/applications/'.$app->shortname),
                                l('Databases','qdrupal/applications/'.$app->shortname.'/databases'),
                                l($settings->name,'qdrupal/applications/'.$app->shortname.'/databases/'.$settings->name.'/edit')
                                ));

    $edit = (object) $settings;
    $btn = t('Save settings');
    $form['sid'] = array('#type' => 'hidden', '#value' => $settings->sid);
    //watchdog("qdrupal", "edit array is = " . print_r($edit,1));
    
  } else {
    drupal_set_title("New Settings Profile");
    drupal_set_breadcrumb(array( l(t('Home'),NULL), 
                                l(t('QDrupal Applications'),'qdrupal/applications'),
                                l($app->title,'qdrupal/applications/'.$app->shortname),
                                l('Databases','qdrupal/applications/'.$app->shortname.'/databases'),
                                l('Add','qdrupal/applications/'.$app->shortname.'/databases/add')
                                ));

    $edit = (object) $form_state['values'];
    $btn = t('Create settings');
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
    '#default_value' => $edit->setting['name'] ? $edit->setting['name'] : '',
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

  // FIXME add the rest of the codegen variables 
  //<manualQuery support="false"/>
  //<relationships><![CDATA[
  //]]></relationships>
  //<relationshipsScript filepath="" format="sql"/>
  
  // todo put a cancel button here
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => $btn
  );
  
  if ($settings) {
    // put delete option here
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('qdrupal_settings_delete_submit'),
      '#validate' => array('qdrupal_settings_delete_validate'),
      '#weight' => 10,
    );
  }
  return $form;
}


function qdrupal_settings_delete_validate($form_id, &$form_state) {
  
}

function qdrupal_settings_delete_submit($form_id, &$form_state) {
  $values = $form_state['values'];
  $app=$form_id['#parameters'][2];
  $sid=$values['sid'];
  qdrupal_settings_delete($sid);
  $form_state['redirect'] = 'qdrupal/applications/'.$app->shortname.'/databases'; 
}


/**
 * Settings validation.
 */
function qdrupal_settings_form_validate($form_id, &$form_state) {
 
}

/**
 * Save a profile to the database.
 */
function qdrupal_settings_form_submit($form_id, &$form_state) {
  $values = $form_state['values'];
  $app=$form_id['#parameters'][2];
  $aid = $app->aid;
  watchdog("qdrupal","in settings submit, values are " . print_r($values,1));
  if ($values['sid']) {
      db_query("UPDATE {qdrupal_setting} SET name='%s', setting='%s'
      WHERE sid = %d ",$values['name'],serialize($values),$values['sid']);
  } else {
    db_query("INSERT INTO {qdrupal_setting} (aid, name, setting) VALUES (%d,'%s', '%s')", $aid, $values['name'], serialize($values));
  }
  $output = t('The Qcubed application settings \'@title\' were saved successfully', array('@title' => $values['name']));
  drupal_set_message($output);
  $form_state['redirect'] = 'qdrupal/applications/'.$app->shortname.'/databases';

}

/**
 * Delete a database profile
 */
function qdrupal_settings_delete($sid) {
  db_query("DELETE FROM {qdrupal_setting} WHERE sid = %d",$sid);
}

/**
 * Delete a database profile by name
 */
function qdrupal_settings_delete_by_name($shortname,$name) {
  db_query("DELETE FROM {qdrupal_setting} WHERE name = '%s'",$name);
  $output = t('The Qcubed application settings \'@title\' were deleted', array('@title' => $name));
  drupal_set_message($output);
  drupal_goto("qdrupal/applications/".$shortname."/databases");
}





