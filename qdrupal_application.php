<?php
// $Id$
// $Name$

/**
 * Form for creating new qcodo application
 */
function qdrupal_application_form(&$node) {
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $node->title,
    '#required' => TRUE,
    '#weight' => -5,
    '#description' => t('Qcodo Application Title'),
  );
  $form['shortname'] = array(
    '#type' => 'textfield',
    '#title' => t('Short Name'),
    '#default_value' => $node->shortname,
    '#required' => TRUE,
    '#weight' => -4,
    '#description' => t('This will be used to generate a /qdrupal/application/[shortname]/ URL for your application. The shortname cannot contain spaces.'),
  );
  if(isset($node->vid)) {
    $form['shortname']['#disabled'] = TRUE;
  }
  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
	  '#rows' => 20,
    '#default_value' => $node->body,
    '#required' => TRUE,
    '#weight' => 1,
    '#description' => t('Qcodo Application description'),
  );
  return $form;
}

/**
 * Validate new qcodo application
 */
function qdrupal_application_validate(&$node) {

  // Make sure title isn't already in use
  if (db_num_rows(db_query("SELECT nid FROM {node} WHERE type = '%s' AND status = 1 AND title = '%s' AND nid <> %d", $node->type, $node->title, $node->nid))) {
    form_set_error('title', t('This application name is already in use.'));
  }

  // Validate uri.
  if (empty($node->shortname)) {
    form_set_error('shortname', t('An application short name is required.'));
  }
  else {
    // Make sure uri only includes valid characters
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $node->shortname)) {
      form_set_error('uri', t('Please only use alphanumerical characters for the application name.'));
    }
    // Make sure shortname isn't already in use, or reserved.  
    $reserved_names = array('users', 'links', 'pages', 'drafts', 'codegen', 'databases', 'add', 'svn', 'cvs', 'developers');
    if (in_array(strtolower($node->shortname), $reserved_names) || db_num_rows(db_query("SELECT nid FROM {qdrupal_application} WHERE shortname = '%s' AND nid <> %d", $node->shortname, $node->nid))) {
      form_set_error('shortname', t('This application name is already in use.'));
    }
  }

  // We need a description.
  if (empty($node->body)) {
    form_set_error('body', t('You must add an application description.'));
  }
}

/**
 * Implementation of hook_insert() for qdrupal_applications
 */
function qdrupal_application_insert($node) {
  db_query("INSERT INTO {qdrupal_application} (nid, shortname) VALUES (%d, '%s')", $node->nid, $node->shortname);

  // Base qdrupal directory
  $strQdrupalPath = file_create_path('qdrupal');
  file_check_directory($strQdrupalPath, FILE_CREATE_DIRECTORY);

  // Each application gets it own subdirectory
  $strAppPath = file_create_path($strQdrupalPath . DIRECTORY_SEPARATOR .  $node->shortname);
  file_check_directory($strAppPath, FILE_CREATE_DIRECTORY);

  // Nodes go in 'nodes' directory
  $strNodePath = file_create_path($strAppPath . DIRECTORY_SEPARATOR . 'nodes');
  file_check_directory($strNodePath, FILE_CREATE_DIRECTORY);

  // set a media folder for images, css , etc
  $strMediaPath = file_create_path($strAppPath . DIRECTORY_SEPARATOR . 'media');
  file_check_directory($strMediaPath, FILE_CREATE_DIRECTORY);

  // qcodo_link pages go here
  $strPagePath = file_create_path($strAppPath . DIRECTORY_SEPARATOR . 'pages');
  file_check_directory($strPagePath, FILE_CREATE_DIRECTORY);

  // qcodo drafts go here
  $strDraftPath = file_create_path($strAppPath . DIRECTORY_SEPARATOR . 'drafts');
  file_check_directory($strDraftPath, FILE_CREATE_DIRECTORY);
  
  // todo create option to copy qcodo framework into application directories

}

/**
 * Update Qdrupal application (hook_update)
 */
function qdrupal_application_update($node) {
  db_query("UPDATE {qdrupal_application} SET shortname = '%s' WHERE nid = %d", $node->shortname, $node->nid);
}

/**
 * Qdrupal application load (hook_load).
 */
function qdrupal_application_load($node) {
  // We don't want to support revisions for now
  $additions = db_fetch_object(db_query('SELECT shortname FROM {qdrupal_application} WHERE nid = %d', $node->nid));
  return $additions;
}

/**
 * Display qdrupal application.
 */
function qdrupal_application_view($node, $teaser, $page) { 
  drupal_set_title($node->title);
  drupal_add_js('misc/collapse.js');
  
  qdrupal_bootstrap($node);
  $breadcrumb[] = array('path' => 'node/'.$node->nid, 'title' => t($node->title));
  menu_set_location($breadcrumb);
  $node = node_prepare($node, $teaser);
  $node->content['nodelist'] = array(
    '#type' => 'fieldset',
    '#title' => t('Children Nodes'),
    '#weight' => 1,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $children=qdrupal_get_nodes($node->nid);
  $node->content['nodelist']['children'] = array(
    '#prefix' => '<div>',
    '#value' => $children,
    '#suffix' => '</div>',
  );
  $node->content['linklist'] = array(
    '#type' => 'fieldset',
    '#title' => t('Children Linked pages'),
    '#weight' => 2,
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $children=qdrupal_get_links($node->nid);
  $node->content['linklist']['children'] = array(
    '#value' => $children,
  );

  return $node;
}

/**
 * Delete qdrupal application (hook_delete).
 */
function qdrupal_application_delete($node) {

  $strQdrupalPath = file_create_path('qdrupal');
  $application_dir = $strQdrupalPath . DIRECTORY_SEPARATOR . $node->shortname;

  if(file_exists($application_dir))
    rmdirr($application_dir);

  db_query('DELETE FROM {qdrupal_application} WHERE nid = %d', $node->nid);
  db_query('DELETE FROM {qdrupal_setting} WHERE nid = %d', $node->nid);
  db_query('DELETE FROM {qdrupal_node} WHERE application_id = %d', $node->nid);		
  db_query('DELETE FROM {qdrupal_link} WHERE application_id = %d', $node->nid);		
}

/**
 * Get a list of all qdrupal_applications  (for select boxes).
 */
function qdrupal_application_list() {
  // todo, could take parameters for permission and access purposes
  $result = db_query(db_rewrite_sql('SELECT n.nid, n.title FROM {node} n where type="qdrupal_application"'));
  while ($node = db_fetch_object($result)) {
	  $list[$node->nid] = $node->title;
  }
  return $list;
}

/**
 * Show an overview page of all qdrupal_applications
 */
function qdrupal_application_page_overview() {
  // TODO - Add Correct Breadcrumbs
  $result = db_query(db_rewrite_sql("SELECT n.nid, n.title, nr.teaser, nr.format FROM {node} n INNER JOIN {node_revisions} nr ON n.vid = nr.vid  WHERE n.status = 1 AND n.type = 'qdrupal_application' ORDER BY n.title ASC"));
  $applications = '';
  $class = 'even';
  while ($application = db_fetch_object($result)) {
    $application->body = check_markup($application->teaser, $application->format, FALSE);
    $application->links['application_more_info'] = array(
      'title' => t('Find out more'),
      'href' => "node/$application->nid",
    );
    $application->class = ($class == 'even') ? 'odd': 'even';
    $applications .= theme('application_summary', $application);
    $class = $application->class;
  }
  $output .= '<div class="application" id="application-overview">' . $applications . '</div>';
  $output .= l('Create a new QDrupal Application','node/add/qdrupal-application');
  return $output;
}
