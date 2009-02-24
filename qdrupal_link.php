<?php
// $Id$
// $Name$

/**
 * Function to select an application which will be a parent to this qdrupal-link
 */
function qdrupal_link_pick_application_page() {
  // TODO in drupal 6, you can do this on one page using AHAH actions all in hook_form
  drupal_set_title(t('Submit QDrupal Form Link'));
  return drupal_get_form('qdrupal_link_pick_application_form');
}

/**
 * Form builder for a simple form to select an application when creating a new
 * link (as the first "page", but this is not really a multi-page form).
 */
function qdrupal_link_pick_application_form() {
  $form = array();

  // Fetch a list of all applications 
  $applications = array_merge(array(t('<none>')),qdrupal_application_list());
  if (count($applications) == 1) {
    drupal_set_message(t('You do not have access to any applications.'), 'error');
  }
  $form['application_id'] = array(
    '#type' => 'select',
    '#title' => t('Application'),
    '#options' => $applications,
    '#required' => TRUE,
    '#description' => t('A QDrupal Form Link needs to be associated with a QDrupal application.  Please select the application to use for this link.'),
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Next'),
  );
  return $form;
}

function qdrupal_link_pick_application_form_validate($form_id, $form_values) {
  if (empty($form_values['application_id'])) {
    form_set_error('application_id', t('You must select an application.'));
  }
  $node = node_load($form_values['application_id']);
  if (empty($node) || $node->type != 'qdrupal_application') {
    form_set_error('application_id', t('Invalid application selected.'));
  }
}

function qdrupal_link_pick_application_form_submit($form_id, $form_values) {
  $application = node_load($form_values['application_id']);
  return 'node/add/qdrupal-link/'. $application->shortname;
}

/**
 * Form for qdrupal_link (hook_form()).
 */
function qdrupal_link_form($node) {
  global $user;
  watchdog("in form", "for qdrupal_link create");
  // Load the Application ID
  
  if (empty($node->application_id)) {
    // Try to get application from url arguments
    $application_token = arg(3);
    if (!empty($application_token)) {
      if (is_numeric($application_token)) {
        watchdog("Selecting app, id", $application_token);
        $node->application_id = db_result(db_query(db_rewrite_sql('SELECT q.nid FROM {qdrupal_application} q WHERE a.application_id = %d', 'q'), $application_token), 0);
      }
      else {
        watchdog("Selecting app,sh", $application_token);
        $node->application_id = db_result(db_query(db_rewrite_sql("SELECT q.aid FROM {qdrupal_application} q WHERE q.shortname = '%s'", 'q'), $application_token), 0);
      }
    }
  }
  // We should have a valid applicaiton id now
  $application_id = $node->application_id;
  watchdog("appid for link", $application_id);
  if (empty($application_id)) {
    drupal_set_message(t('Invalid application selected.'), 'error');
    drupal_goto('node/add/qdrupal-link');
    return;
  }

  $app = qdrupal_application_load($application_id);
  qdrupal_bootstrap($app);
  $type = node_get_types('type', $node);

  $form['application_id'] = array(
    '#type' => 'hidden',
    '#value' => $application_id,
    '#required' => TRUE,
  );

  if ($type->has_title) {
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => check_plain($type->title_label),
      '#default_value' => $node->title,
      '#required' => TRUE,
      '#weight' => -5,
      '#description' => t('The descriptive name of your QForm.'),
    );
  }

  $form_path = __QDRUPAL_PAGES__;
  file_check_directory($form_path, FILE_CREATE_DIRECTORY);

  $file_list = _qdrupal_walk_form_dir($form_path);
  ksort($file_list);

  //ob_clean(); echo '<pre>'; print_r($file_list); print_r($node->form_path); exit;

  $form['form_path'] = array(
    '#type' => 'select',
    '#title' => t('Select from the existing list of files'),
    '#default_value' => $node->form_path,
    '#options' => $file_list,
    '#required' => TRUE,
    '#weight' => -4,
    '#description' => t('Choose a file to link this node with.  Files must be placed within '.__QDRUPAL_PAGES__.'.')
  );

  return $form;
}

/**
 * Insert qcubed link (hook_insert).
 */
function qdrupal_link_insert($node) {
	db_query("INSERT INTO {qdrupal_link} (nid, vid, application_id, form_path) VALUES (%d, %d, %d, '%s')", $node->nid, $node->vid, $node->application_id, $node->form_path);
}

/**
 * Update qdrupal link (hook_update)
 */
function qdrupal_link_update($node) {
  db_query("UPDATE {qdrupal_link} SET form_path = '%s', application_id = %d WHERE nid = %d", $node->form_path, $node->application_id, $node->nid);
}

/**
 * Load qdrupal link (hook_load).
 */
function qdrupal_link_load($node) {
	$additions = db_fetch_object(db_query('SELECT application_id, form_path FROM {qdrupal_link} WHERE vid = %d', $node->vid));
	return $additions; 
} 

/**
 * Implementation of hook_view().
 */
function qdrupal_link_view($node, $teaser = FALSE, $page = FALSE) {
  if($page) {
    $app = qdrupal_application_load($node->application_id);
    qdrupal_prepend($app);
    /*
    if(variable_get('qdrupal_enable_profiling',false)) {
      foreach(QApplication::$Database as $objDb) {
        $objDb->EnableProfiling();
      }
    }
     */
    try {    
      $qform_path = __QDRUPAL_PAGES__ . $node->form_path;
      watchdog("qdrupal", "qform path is ".$qform_path);
      $template_path = qdrupal_tmpl_path($qform_path);
      watchdog("qdrupal", "template path is ".$template_path);
      ob_start();    	
    	require_once($node->form_path);
    	// todo add in profiling routine here
    	//if(variable_get("qdrupal_enable_profiling",false)) QApplication::$Database[1]->OutputProfiling();
    	$qcodo_content = ob_get_clean();
      $content= _qdrupal_run_qform(
    		$app,
    		$node->title,
    		$qform_path,
    		$template_path);
      //$content = ob_get_clean();
    }
    catch (QDrupalException $e) {
	    $content = $e->getMessage();
    }
    catch (Exception $e) {
      ob_start();
      QcodoHandleException($e,FALSE);
      $content = ob_get_clean();
    }

    /*
	  if(variable_get('qdrupal_enable_profiling',false)) {
	    ob_start();
	    foreach(QApplication::$Database as $objDb) {
        $objDb->OutputProfiling();
      }
	    $content .= ob_get_clean();
	  }
     */
  }
  else {
	  $content = "View this node to see the QForm";
  }

  _qdrupal_restore_drupal_error_handler();

  $node = node_prepare($node, $teaser);
  $node->content['info_content'] = array(
    '#value' => $content,
    '#weight' => 1,
  );
  return $node;
}

/**
 * Delete qcubed link (hook_delete()).
 */
function qdrupal_link_delete($node) {
 	db_query('DELETE FROM {qdrupal_link} WHERE nid = %d', $node->nid);
}

/**
 * Load all qdrupal links for a given application node
 */
function qdrupal_get_links($aid) {
  $result = db_query('SELECT n.*, q.*  FROM {node} n,{qdrupal_link} q
    where n.nid = q.nid and q.application_id = %d ORDER BY n.changed DESC', $aid);
  $output = '';
  $app = qdrupal_application_load($aid);
	$rows = array();
  if ($result) {
  $header = array(t('Link'), t('Operations'));
    while ($l = db_fetch_object($result)) {
      $rows[] = array(array('data' => l($l->title,'node/'.$l->nid ),'valign' => 'top'),
          array('data' => l(t('edit'), 'node/'.$l->nid.'/edit/') . ' '. l(t('delete'), 'node/'.$l->nid.'/delete/'), 'valign' => 'top')
          );
    }
    $output .= theme('table', $header, $rows);
    $output .= t('<p><a href="!create-qdrupal-link">Create new QDrupal link</a></p>', array('!create-qdrupal-link' => url("node/add/qdrupal-link/$app->shortname")));
  }
  else {
		// fixme this function should direct to the qdrupal-node add page and should PREPOPULATE application select box
		$output .= t('No QDrupal links found. Click here to <a href="!create-qdrupal-link">create a new qdrupal link for this application</a>.', array('!create-qdrupal-link' => url("node/add/qdrupal-link/$app->shortname")));
  }
  return $output;
}
