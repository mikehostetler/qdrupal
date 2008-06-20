<?php
// $Id$
// $Name$

/**
 * Form for creating new qcodo page
 */
function qdrupal_node_form(&$node) {
  if (empty($node->application_id)) {
    $application = arg(3);
    if (!empty($application)) {
      if (is_numeric($application)) {
        $node->application_id = db_result(db_query(db_rewrite_sql('SELECT q.nid FROM {qdrupal_application} q WHERE q.nid = %d', 'q'), $application), 0);
      }
      else {
        $node->application_id = db_result(db_query(db_rewrite_sql("SELECT q.nid FROM {qdrupal_application} q WHERE q.shortname = '%s'", 'q'), $application), 0);
      }
    }
  }
  $application_id = $node->application_id;

  $form['application_id'] = array(
    '#type' => 'select',
    '#title' => t('Application'),
    '#default_value' => ($node->application_id ? $node->application_id : t('<none>')),
    '#options' => qdrupal_application_list($node->nid),
    '#weight' => -4,
  );
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $node->title,
    '#required' => TRUE,
    '#weight' => 0,
    '#description' => t('qcodo page title'),
  );
  $form['qform'] = array(
    '#type' => 'textarea',
    '#title' => t('Qform Definitions'),
    '#default_value' => ($node->qform ? $node->qform : '' ),
    '#rows' => 20,
    '#required' => TRUE,
    '#description' => t('Put Qform definitions here'),
  );
  $form['qform']['format'] = filter_form($node->format);
  $form['template']= array(
    '#type' => 'textarea',
    '#title' => t('Template definition'),
    '#default_value' => ($node->template ? $node->template : '' ),
    '#rows' => 20,
    '#required' => TRUE,
    '#description' => t('Put your template definition here'),
  );
  $form['template']['format'] = filter_form($node->format);

  return $form;
}

/**
 * Insert qcodo node (hook_insert).
 */
function qdrupal_node_insert($node) {
	//get application node
	$app_node = node_load($node->application_id);
  qdrupal_bootstrap($app_node);
	db_query("INSERT INTO {qdrupal_node} (nid, vid, application_id) VALUES (%d, %d, %d)", $node->nid, $node->vid, $node->application_id);

	$qform_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.php';
	$template_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.tpl.php';

  file_put_contents($qform_file, $node->qform);
  file_put_contents($template_file, $node->template);
}

/**
 * Update qdrupal page (hook_update)
 */
function qdrupal_node_update($node) {
	//get application node
	$app_node = node_load($node->application_id);
  qdrupal_bootstrap($app_node);

	// TODO - Handle orphans when the application id is changed
	db_query("UPDATE {qdrupal_node} SET application_id = %d WHERE nid = %d", $node->application_id, $node->nid);
	$qform_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.php';
	$template_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.tpl.php';

  file_put_contents($qform_file, $node->qform);
  file_put_contents($template_file, $node->template);
}

/**
 * Load qdrupal page (hook_load).
 */
function qdrupal_node_load($node) {
	//get additions
	$additions = db_fetch_object(db_query('SELECT application_id  FROM {qdrupal_node} WHERE vid = %d', $node->vid));
	//get application node
	$app_node = node_load($additions->application_id);
  qdrupal_bootstrap($app_node);
	$qform_file=  __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.php';
	$template_file=  __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.tpl.php';
	$qform = file_get_contents($qform_file);
	$template = file_get_contents($template_file);
	$additions->qform = $qform;
	$additions->template =  $template;
	return $additions; 
} 

/**
 * Display qdrupal page.
 */
function qdrupal_node_view($node, $teaser, $page) { 
  if($page) {

	  $app_node = node_load($node->application_id);
    qdrupal_prepend($app_node);

    try {
      ob_start();
      $qform_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.php';
	    require_once($qform_file);
      $content = ob_get_clean();
    }
    catch (QDrupalException $e) {
	    $content = $e->getMessage();
    }
    catch (Exception $e) {
      ob_start();
      QcodoHandleException($e,FALSE);
      $content = ob_get_clean();
    }
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
 * Delete qcodo page (hook_delete()).
 */
function qdrupal_node_delete($node) {
	$app_node = node_load($node->application_id);
 	qdrupal_bootstrap($app_node);
 	db_query('DELETE FROM {qdrupal_node} WHERE nid = %d', $node->nid);
 	$qform_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.php';
	$template_file = __QDRUPAL_NODES__ . DIRECTORY_SEPARATOR . $node->nid . '.tpl.php';
	unlink($qform_file);
	unlink($template_file);
}
