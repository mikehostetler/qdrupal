<?php

/**
 * Show an overview page of all qdrupal_applications
 */
function qdrupal_admin_overview() {
  drupal_set_title(t('QDrupal Applications'));

  $apps = db_query("SELECT a.aid,a.title,a.description FROM {qdrupal_application} a ORDER BY a.title ASC");
  $header = array(t('Applications'), '', '');
  while( $app = db_fetch_object($apps) ) {
    $rows[] = array(
      array('data' => $app->title, 'valign' => 'top'),
      array('data' => l(t('view'), 'admin/build/qdrupal/'.$app->aid)),
      array('data' => l(t('delete'), 'admin/build/qdrupal/'.$app->aid.'/delete'))
    );
  }

  return theme('table', $header, $rows);
}

function qdrupal_application_overview($app) {
  drupal_set_title("$app->title");
  $application_detail = theme('application_detail', $app);
  $output = '<div class="application" id="application-detail">' . $application_detail . '</div>';

  $output .= qdrupal_application_form_links($app->aid);

  return $output;
}

/**
 * Form for creating new qcubed application
 */
function qdrupal_application_form(&$form_state,$app) {
  if ($app) drupal_set_title($app->title);

  $bc = drupal_get_breadcrumb();
  array_pop($bc);
  $bc[] = l($app->title,'admin/build/qdrupal/'.$app->aid);
  drupal_set_breadcrumb($bc);

  $form['aid'] = array(
    '#type' => 'textfield',
    '#title' => t('Application ID'),
    '#default_value' => $app->aid,
    '#required' => TRUE,
    '#weight' => -6,
    '#description' => t('This will be used to generate a /qdrupal/applications/[application ID]/ URL for your application. The application ID cannot contain spaces.'),
  );
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $app->title,
    '#required' => TRUE,
    '#weight' => -4,
    '#description' => t('Qcubed Application Title'),
  );
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
	  '#rows' => 4,
    '#default_value' => $app->description,
    '#weight' => -1,
    '#description' => t('Qcubed Application description'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save')
  );

  if($app->aid != '') {
    $form['aid']['#type'] = 'item';
    $form['aid']['#value'] = $app->aid;
    $form['action'] = array('#type' => 'value', '#value' => 'edit');
  }

  return $form;
}

/**
 * Validate new qcubed application
 */
function qdrupal_application_form_validate($form_id, &$form_state) {
  
  if( $form_state['values']['action'] == 'edit' ) return;

  $title = $form_state['values']['title'];
  $aid = $form_state['values']['aid'];

  // Make sure aid isn't already in use
  if (db_result(db_query("SELECT COUNT(*) FROM {qdrupal_application} WHERE aid = '%s'",$aid))) {
    form_set_error('title', t('This application ID is already in use.'));
  }
  
  // Make sure aid only includes valid characters
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $aid)) {
    form_set_error('aid', t('Please only use alphanumerical characters for the application ID.'));
  }

  // Make sure aid isn't reserved.  
  $reserved_names = array('users', 'links', 'pages', 'drafts', 'codegen', 'databases', 'add', 'svn', 'cvs', 'developers');
  if (in_array(strtolower($aid), $reserved_names)) {
      form_set_error('aid', t('This application ID is reserved.'));  
  }
}

/**
 * Submit new qcubed application
 */
function qdrupal_application_form_submit($form, &$form_state) {
  $title = $form_state['values']['title'];

  if( $form_state['values']['action'] == 'edit' ) $aid = $form['aid']['#value']; 
  else $aid = $form_state['values']['aid'];

  $is_module = $form_state['values']['is_module'] ? 1 : 0;
  $description = $form_state['values']['description'];

  if( $form_state['values']['action'] == 'edit' ) 
    qdrupal_application_update($aid,$title,$description,$is_module);
  else 
    qdrupal_application_create($aid,$title,$description,$is_module);

  $output = t('The Qcubed application \'@title\' was submitted successfully', array('@title' => $title));
  drupal_set_message($output);
  $form_state['redirect'] = 'admin/build/qdrupal';
}

function qdrupal_application_delete_confirm(&$form_state, $app) {
  $form['app'] = array('#type' => 'value', '#value' => $app);

  return confirm_form($form,
    t('Are you sure you want to delete the application, %app?', array('%app' => $app->title)),
    'admin/build/qdrupal/'.$app->aid,
    '<p>'.t('Deleting this application will completely remove the application and all its associated profiles. This action cannot be undone. The application files located at %app_path will not be deleted.'.'</p>', array('%app_path' => qdrupal_application_path($app))),
    t('Delete'),
    t('Cancel'));
}

function qdrupal_application_delete_confirm_submit($form, &$form_state) {
  qdrupal_application_delete($form_state['values']['app']);
  drupal_set_message(t('Deleted application %app.', array('%app' => $form_state['values']['app']->title)));
  $form_state['redirect'] = 'admin/build/qdrupal/';
  return;
}

function theme_application_detail($app) {
  $output = '<p class="' . $app->class . '">';
  $output .= $app->description;
  $output .= '</p>';
  return $output;
}

/**
 * Create the needed directories for an application
 */ 
function qdrupal_application_update_disk($app) {
  if( $app->is_module ) return;

  $app_path = qdrupal_application_path($app);

  $assets_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'assets');
  file_check_directory($assets_path, FILE_CREATE_DIRECTORY);

  $js_path = file_create_path($assets_path . DIRECTORY_SEPARATOR . 'js');
  file_check_directory($js_path, FILE_CREATE_DIRECTORY);

  $css_path = file_create_path($assets_path . DIRECTORY_SEPARATOR . 'css');
  file_check_directory($css_path, FILE_CREATE_DIRECTORY);

  $images_path = file_create_path($assets_path . DIRECTORY_SEPARATOR . 'images');
  file_check_directory($images_path, FILE_CREATE_DIRECTORY);

  $pages_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'pages');
  file_check_directory($pages_path, FILE_CREATE_DIRECTORY);

  $templates_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'templates');
  file_check_directory($templates_path, FILE_CREATE_DIRECTORY);

  $drafts_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'drafts');
  file_check_directory($drafts_path, FILE_CREATE_DIRECTORY);

  $error_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'error_log');
  file_check_directory($error_path, FILE_CREATE_DIRECTORY);
}

/**
 * Load all qdrupal links for a given application node
 */
function qdrupal_application_form_links($aid) {
  $app = qdrupal_application_load($aid);

  $result = db_query("SELECT n.*, q.*  FROM {node} n,{qdrupal_link} q
    where n.nid = q.nid and q.aid = '%s' ORDER BY n.changed DESC", $aid);

  if( $result->num_rows == 0 ) {
   return '<p>'.t("This application has no associated qdrupal form links. You are able to <a href='@node-add'>create</a> form links as you would other node types.",array('@node-add' => '/node/add')).'</p>';
  }

  $rows = array();
  $header = array(t('Form Link'),'','','');

  while ($l = db_fetch_object($result)) {
    $form_path = qdrupal_application_disk_path($app).'/pages'.$l->form_path;
    $verified = qdrupal_verify_qform($form_path);

    $verified_image = "<img src='";
    $verified_image .= $verified ? '/misc/watchdog-ok.png' : '/misc/watchdog-error.png';
    $verified_image .= "' />";

    $rows[] = array(
      array('data' => l($l->title,'node/'.$l->nid )), 
      array('data' => l(t('view'), 'node/'.$l->nid.'/view/')),
      array('data' => l(t('delete'), 'node/'.$l->nid.'/delete/')),
      array('data' => $verified_image)
    );
  }
    
  $output = theme('table', $header, $rows);

  return $output;
}
