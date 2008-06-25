<?php
// $Id$

/**
 * Controller for qcodo application settings.
 */
function qdrupal_application_administer($node, $edit = NULL) {
	global $qdrupal_node;
	$qdrupal_node = $node;

	qdrupal_prepend($node);
  drupal_set_title($node->title . " Databases");
	drupal_set_breadcrumb(array(
			l(t('Home'),NULL),
			l(t($node->title),'node/'.$node->nid),
			l(t('Databases'),'node/'.$node->nid.'/databases')
	));

	if(!is_null($edit)) {
		return _qdrupal_run_qform(
			$node,
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
    '#weight' => -4,
    '#description' => t('This will be used to generate a /qdrupal/application/[shortname]/ URL for your application. The shortname cannot contain spaces.'),
  );
  $form['body'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
	  '#rows' => 4,
    '#default_value' => $node->body,
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
  if (db_result(db_query("SELECT COUNT(*) FROM {node} WHERE type = '%s' AND status = 1 AND title = '%s' AND nid <> %d", $node->type, $node->title, $node->nid))) {
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
    if (in_array(strtolower($node->shortname), $reserved_names) || db_result(db_query("SELECT COUNT(*) FROM {qdrupal_application} WHERE shortname = '%s' AND nid <> %d", $node->shortname, $node->nid))) {
      form_set_error('shortname', t('This application name is already in use.'));
    }
  }
}

/**
 * Implementation of hook_insert() for qdrupal_applications
 */
function qdrupal_application_insert($node) {
	if(is_null($node->is_module)) {
		$node->is_module = 0;
	}

  db_query("INSERT INTO {qdrupal_application} (nid, shortname, is_module) VALUES (%d, '%s', '%s')", $node->nid, $node->shortname,$node->is_module);

	if($node->is_module != 1) {
		// Create our directories
		_qdrupal_application_update_disk($node);
	}

}

/**
 * Update Qdrupal application (hook_update)
 */
function qdrupal_application_update($node) {
	if(is_null($node->is_module)) {
		$node->is_module = 0;
	}

  db_query("UPDATE {qdrupal_application} SET shortname = '%s', is_module = %d WHERE nid = %d", $node->shortname, $node->is_module, $node->nid);

	// Update disk again
	if($node->is_module != 1) {
		_qdrupal_application_update_disk($node);
	}
}

/**
 * Qdrupal application load (hook_load).
 */
function qdrupal_application_load($node) {
  $additions = db_fetch_object(db_query('SELECT shortname, is_module FROM {qdrupal_application} WHERE nid = %d', $node->nid));
  return $additions;
}

/**
 * Display qdrupal application.
 */
function qdrupal_application_view($node, $teaser = FALSE, $page = FALSE) { 

  qdrupal_bootstrap($node);

	$node = node_prepare($node, $teaser);
	if(!$teaser) {
		$node->content['nodelist'] = array(
			'#type' => 'fieldset',
			'#title' => t('Children Nodes'),
			'#weight' => 1,
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
		);

		$children = qdrupal_get_nodes($node->nid);
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

		$children = qdrupal_get_links($node->nid);
		$node->content['linklist']['children'] = array(
			'#value' => $children,
		);
	}

  return $node;
}

/**
 * Delete qdrupal application (hook_delete).
 */
function qdrupal_application_delete($node) {

	$app_path = _qdrupal_application_path($node);

  if(file_exists($app_path) && $node->is_module != 1)
    rmdirr($app_path);

  db_query('DELETE FROM {qdrupal_application} WHERE nid = %d', $node->nid);
  db_query('DELETE FROM {qdrupal_setting} WHERE nid = %d', $node->nid);
  db_query('DELETE FROM {qdrupal_node} WHERE application_id = %d', $node->nid);		
  db_query('DELETE FROM {qdrupal_link} WHERE application_id = %d', $node->nid);		
}

/**
 * Get a list of all qdrupal_applications  (for select boxes).
 */
function qdrupal_application_list() {
  $result = db_query(db_rewrite_sql('SELECT n.nid, n.title FROM {node} n where type="qdrupal_application"'));
  while ($node = db_fetch_object($result)) {
		$db_node = node_load($node->nid);
		if($db_node->is_module != 1) {
			$list[$node->nid] = $node->title;
		}
  }
  return $list;
}

/**
 * Show an overview page of all qdrupal_applications
 */
function qdrupal_application_page_overview() {
	drupal_set_breadcrumb(array( l(t('Home'),NULL), l(t('QDrupal Applications'),'qdrupal/applications')));
  $result = db_query(db_rewrite_sql("SELECT n.nid, n.title, nr.teaser, nr.format FROM {node} n INNER JOIN {node_revisions} nr ON n.vid = nr.vid  WHERE n.status = 1 AND n.type = 'qdrupal_application' ORDER BY n.title ASC"));
  $applications = '';
  $class = 'even';
  while ($application = db_fetch_object($result)) {
		$node = node_load($application->nid);
		if($node->is_module == 1) {
			continue;
		}
    $application->body = check_markup($application->teaser, $application->format, FALSE);
    $application->links['application_more_info'] = array(
      'title' => t('Find out more'),
      'href' => "node/$application->nid",
    );
    $application->class = ($class == 'even') ? 'odd': 'even';
    $applications .= theme('application_summary', $application);
    $class = $application->class;
  }
  $output = '<div class="application" id="application-overview">' . $applications . '</div>';
  $output .= l('Create a new QDrupal Application','node/add/qdrupal-application');

  return $output;
}

/**
 * Theme a compact application view/summary.
 */
function theme_application_summary($application) {
  $output = '<div class="' . $application->class . '">';
  $output .= '<h2>'. l($application->title, "node/$application->nid") .'</h2>';
  if (!empty($application->changed)) {
    $output .= '<p><small>' . t('Last changed: !interval ago', array('!interval' => format_interval(time() - $application->changed, 2))) . '</small></p>';
  }
  $output .= $application->body;
  $output .= theme('links', $application->links);
  $output .= '</div>';
  return $output;
}

function _qdrupal_application_update_disk($node) {
	$app_path = _qdrupal_application_path($node);

  $node_path = file_create_path($app_path . DIRECTORY_SEPARATOR . 'nodes');
  file_check_directory($node_path, FILE_CREATE_DIRECTORY);

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

function _qdrupal_application_path($node) {
	// Create the examples directory
  $main_path = file_create_path('qdrupal');
  file_check_directory($main_path, FILE_CREATE_DIRECTORY);

	// Handle a blank shortname.  This should be handled by the form 
	if(trim($node->shortname) == "") {
		$title = preg_replace(': +:','_',$node->title);
		$title = preg_replace("/[^a-z0-9_]/i","",$title);
		$title = strtolower($title);

		// Ensure a unique path
		$example_path = $main_path . DIRECTORY_SEPARATOR . $title;
		$counter = 0;
		while(is_dir($example_path)) {
			$title = $title . $counter++;
			$example_path = $main_path . DIRECTORY_SEPARATOR . $title;
		} 

		$node->shortname = $title;

		// Save our change
		node_save($node);
	}


	// Create our specific example directory
	$example_path = file_create_path($main_path . DIRECTORY_SEPARATOR . $node->shortname);
  file_check_directory($example_path, FILE_CREATE_DIRECTORY);

	return $example_path;
}
