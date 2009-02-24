<?php
// $Id$

/**
 * Controller for qcubed application settings.
 */
 /*
function qdrupal_application_administer($app, $edit = NULL) {
	global $qdrupal_node;
	$qdrupal_node = $node;

  qdrupal_prepend($app);
  drupal_set_title($app->title . " Databases");
	drupal_set_breadcrumb(array(
			l(t('Home'),NULL),
			l(t($app->title),'application/'.$app->aid),
			l(t('Databases'),'application/'.$app->aid.'/databases')
	));

	if(!is_null($edit)) {
		return _qdrupal_run_qform(
			$app,
			'QDrupalSettingsEdit',
			QDRUPAL_ROOT . '/pages/qdrupal_settings_edit.php',
			QDRUPAL_ROOT . '/templates/qdrupal_settings_edit.tpl.php');
	}
	else {
		return _qdrupal_run_qform(
			$node,
			'QDrupalSettingsList',
			QDRUPAL_ROOT . '/pages/qdrupal_settings_list.php',
			QDRUPAL_ROOT . '/templates/qdrupal_settings_list.tpl.php');
	}
}
*/
function qdrupal_application_administer($app, $edit = NULL) {

}


/**
 * Page for creating new qcubed application
 */
function qdrupal_application_create() {

  $output .= drupal_get_form('qdrupal_application_form');

  return $output;
}

/**
 * Page for exporting a qcubed application
 */
function qdrupal_application_export() {

  //$output .= drupal_get_form('qdrupal_application_form');
  $output = "";
  return $output;
}

/**
 * Page for creating editing a qcubed application
 */
function qdrupal_application_edit($shortname) {
  $app = qdrupal_application_load_by_name($shortname);
  $output .= drupal_get_form('qdrupal_application_form', $app);
  return $output;
}

/**
 * Form for creating new qcubed application
 */
function qdrupal_application_form($form_state,$app) {
  // fixme a cancel button would be nice here
  if ($app) {
    drupal_set_title("$app->title");
    drupal_set_breadcrumb(array( l(t('Home'),NULL), 
                                l(t('QDrupal Applications'),'qdrupal/applications'),
                                l($app->title,'qdrupal/applications/'.$app->shortname),
                                l('edit','qdrupal/applications/'.$app->shortname.'/edit')
                                ));
    $btn = "Save";
    $form['aid'] = array('#type' => 'hidden', '#value' => $app->aid);
    $form['old_shortname'] = array('#type' => 'hidden', '#value' => $app->shortname);
  } else {
    $btn = "Create";
  }
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $app->title,
    '#required' => TRUE,
    '#weight' => -5,
    '#description' => t('Qcubed Application Title'),
  );
  $form['shortname'] = array(
    '#type' => 'textfield',
    '#title' => t('Short Name'),
    '#default_value' => $app->shortname,
    '#required' => TRUE,
    '#weight' => -4,
    '#description' => t('This will be used to generate a /qdrupal/applications/[shortname]/ URL for your application. The shortname cannot contain spaces.'),
  );
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
	  '#rows' => 4,
    '#default_value' => $app->description,
    '#weight' => 1,
    '#description' => t('Qcubed Application description'),
  );

  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Create',
    '#weight' => 10,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => $btn,
    '#weight' => 10,
  );
  if ($app) {
    // put delete option here
    $form['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete'),
      '#submit' => array('qdrupal_application_delete_submit'),
      '#validate' => array('qdrupal_application_delete_validate'),
      '#weight' => 10,
    );
  }
  return $form;
}

function qdrupal_application_delete_validate($form_id, &$form_state) {
  
}

function qdrupal_application_delete_submit($form_id, &$form_state) {
  watchdog("qdrupal","in app delete submit");
  $app=$form_id['#parameters'][2];
  $aid = $app->aid;
  qdrupal_application_delete($aid); 
  $form_state['redirect'] = 'qdrupal/applications'; 
}

/**
 * Validate new qcubed application
 */
function qdrupal_application_form_validate($form_id, &$form_state) {
  
  // Make sure title isn't already in use
  $title = $form_state['values']['title'];
  $shortname = $form_state['values']['shortname'];
  $old_shortname = $form_state['values']['old_shortname'];
  
  if (!($old_shortname == $shortname)) {
    // Make sure title isn't already in use
    $title = $form_state['values']['title'];
    $shortname = $form_state['values']['shortname'];
    if (db_result(db_query("SELECT COUNT(*) FROM {qdrupal_application} WHERE shortname = '%s'",$shortname))) {
      form_set_error('title', t('This application name is already in use.'));
    }
  }
  
  // Make sure uri only includes valid characters
  if (!preg_match('/^[a-zA-Z0-9_-]+$/', $shortname)) {
    form_set_error('uri', t('Please only use alphanumerical characters for the application name.'));
  }
  // Make sure shortname isn't reserved.  
  $reserved_names = array('users', 'links', 'pages', 'drafts', 'codegen', 'databases', 'add', 'svn', 'cvs', 'developers');
  if (in_array(strtolower($node->shortname), $reserved_names)) {
      form_set_error('shortname', t('This application name is reserved.'));  
  }
}

/**
 * Submit new qcubed application
 */
function qdrupal_application_form_submit($form_id, &$form_state) {
  $title = $form_state['values']['title'];
  $aid = $form_state['values']['aid'];
  $shortname = $form_state['values']['shortname'];
  $is_module = $form_state['values']['is_module'];
  $description = $form_state['values']['description'];

  if(is_null($is_module)) {
		$is_module = 0;
	}
  // if this is an update
  if (!is_null($aid)) {
    db_query("UPDATE {qdrupal_application} SET title = '%s', shortname = '%s', description = '%s', is_module = %d WHERE aid = %d", $title, $shortname, $description, $is_module, $aid);
    // Update disk again
    // fixme is_module check here not needed?        
    if($is_module != 1) {
      $app = qdrupal_application_load_by_name($shortname);
      _qdrupal_application_update_disk($app);
    }
  } else {
    db_query("INSERT INTO {qdrupal_application} (title, shortname, description, is_module) VALUES ('%s', '%s', '%s', '%s')", $title,  $shortname,$description,$is_module);
    // fixme must be a better way of handling this is_module stuff
    if($is_module != 1) {
      // Create our directories
      $app = qdrupal_application_load_by_name($shortname);    
      _qdrupal_application_update_disk($app);
    }
    $output = t('The Qcubed application \'@title\' was submitted successfully', array('@title' => $title));
    drupal_set_message($output);
    $form_state['redirect'] = 'qdrupal/applications';
  }
}

/**
 * Qdrupal application load.
 */
function qdrupal_application_load($aid) {
  $app = db_fetch_object(db_query('SELECT aid, title, shortname, description, is_module FROM {qdrupal_application} WHERE aid = %d', $aid));
  return $app;
  
}

/**
 * Qdrupal application load by shortname.
 */
function qdrupal_application_load_by_name($shortname) {
  $app = db_fetch_object(db_query("SELECT aid, title, shortname, description, is_module FROM {qdrupal_application} WHERE shortname = '%s'", $shortname));
  return $app;
}


function qdrupal_application_details($shortname) {
  $app = qdrupal_application_load_by_name($shortname);
  drupal_set_breadcrumb(array( l(t('Home'),NULL), 
                              l(t('QDrupal Applications'),'qdrupal/applications'),
                              l($app->title,'qdrupal/applications/'.$shortname)
                              ));
  drupal_set_title("$app->title");
  // FIXME need to work on the themeing fuction
  $output = theme('application_detail', $app);
  $output = '<div class="application" id="application-detail">' . $application_detail . '</div>';

  $output .= qdrupal_get_links($app->aid);

  return $output;

}

/**
 * Delete qdrupal application (hook_delete).
 */
function qdrupal_application_delete($aid) {
  watchdog("qdrupal","in app delete for aid" . $aid);
  $app = qdrupal_application_load($aid);  
	$app_path = _qdrupal_application_path($app);

  if(file_exists($app_path) && $app->is_module != 1)
  rmdirr($app_path);

  db_query('DELETE FROM {qdrupal_application} WHERE aid = %d', $aid);
  db_query('DELETE FROM {qdrupal_setting} WHERE aid = %d', $aid);
  db_query('DELETE FROM {qdrupal_link} WHERE application_id = %d', $aid);		
}

/**
 * Get a list of all qdrupal_applications  (for select boxes).
 */
//function qdrupal_application_list() {
//  $result = db_query(db_rewrite_sql('SELECT a.aid, a.title FROM {qdrupal_application} a'));
//  while ($app = db_fetch_object($result)) {
//		$db_node = node_load($node->nid);
//		if($db_node->is_module != 1) {
//			$list[$node->nid] = $node->title;
//		}
//  }
//  return $list;
//}

/**
 * Show an overview page of all qdrupal_applications
 */
function qdrupal_application_page_overview() {
	drupal_set_breadcrumb(array( l(t('Home'),NULL), l(t('QDrupal Applications'),'qdrupal/applications')));
  $result = db_query("SELECT a.aid,a.title,a.shortname,a.description FROM {qdrupal_application} a ORDER BY a.title ASC");
  $applications = '';
  $class = 'even';
  while ($application = db_fetch_object($result)) {
    watchdog('shortname:',$application->shortname);
		//$node = node_load($application->nid);
		//if($node->is_module == 1) {
		//	continue;
		//}
		// Fixme, since this isn't a node, we can't use the ->format function...
    //$application->description = check_markup($application->description, $application->format, FALSE);
    $application->links['application_more_info'] = array(
      'title' => t('Find out more'),
      'href' => "qdrupal/applications/$application->shortname",
    );
    $application->class = ($class == 'even') ? 'odd': 'even';
    $applications .= theme('application_summary', $application);
    $class = $application->class;
  }
  $output = '<div class="application" id="application-overview">' . $applications . '</div>';
  $output .= '<div>';
  $output .= l('Create a new QDrupal Application','qdrupal/application/add');
  $output .= '</div>';
  $output .= '<div>';
  $output .= l('Import a new QDrupal Application','qdrupal/application/import');
  $output .= '</div>';

  return $output;
}

/**
 * Theme a compact application view/summary.
 */
function theme_application_summary($application) {
  $output = '<div class="' . $application->class . '">';
  $output .= '<h2>'. l($application->title, "qdrupal/applications/$application->shortname") .'</h2>';
  if (!empty($application->changed)) {
    $output .= '<p><small>' . t('Last changed: !interval ago', array('!interval' => format_interval(time() - $application->changed, 2))) . '</small></p>';
  }
  $output .= $application->description;
  $output .= theme('links', $application->links);
  $output .= '</div>';
  return $output;
}

function theme_application_detail($application) {
  $output = '<div class="' . $application->class . '">';
  $output .= '<h2>'. l($application->title, "qdrupal/applications/$application->title") .'</h2>';
  $output .= $application->description;
  $output .= '</div>';
  return $output;
}

/**
 * Create the needed directories for an application
 */ 
function _qdrupal_application_update_disk($app) {
	$app_path = _qdrupal_application_path($app);

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
 * Return out the root of the application directory
 */
function _qdrupal_application_path($app) {
	// Create the examples directory
  $main_path = file_create_path('qdrupal');
  file_check_directory($main_path, FILE_CREATE_DIRECTORY);

	// Create our specific example directory
	$example_path = file_create_path($main_path . DIRECTORY_SEPARATOR . $app->shortname);
  file_check_directory($example_path, FILE_CREATE_DIRECTORY);
	return $example_path;
}
