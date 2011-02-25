<?php
// $Name$

/**
 * Load qdrupal link (hook_load).
 */
function qdrupal_link_load($node) {
	$additions = db_fetch_object(db_query('SELECT aid, form_path FROM {qdrupal_link} WHERE vid = %d', $node->vid));
	return $additions; 
} 

/**
 * Insert qcubed link (hook_insert).
 */
function qdrupal_link_insert($node) {
  db_query("INSERT INTO {qdrupal_link} (nid, vid, aid, form_path) VALUES (%d, %d, '%s', '%s')",
    $node->nid, $node->vid, $node->aid, $node->form_path);
}

/**
 * Update qdrupal link (hook_update)
 */
function qdrupal_link_update($node) {
  db_query("UPDATE {qdrupal_link} SET form_path = '%s', aid = '%s' WHERE nid = %d",
    $node->form_path, $node->aid, $node->nid);
}

/**
 * Delete qcubed link (hook_delete()).
 */
function qdrupal_link_delete($node) {
 	db_query('DELETE FROM {qdrupal_link} WHERE nid = %d', $node->nid);
}

/**
 * Implementation of hook_view().
 */
function qdrupal_link_view($node, $teaser = FALSE, $page = FALSE) {
  if( $teaser ) $content = "View this node to see the QForm";

  else if( $page ) {
    $app = qdrupal_application_load($node->aid);
    $app_path = qdrupal_application_disk_path($app);
    $qform_path = $app_path .'/pages'.$node->form_path;
    $template_path = ereg_replace(".php", ".tpl.php", $node->form_path);
    $template_path = $app_path . '/templates' . $template_path;

    $content = qdrupal_run_qform(
      $app,
      $qform_path,
      $template_path,
      $node->title);

    // handle profiling settings
  }

  $node = node_prepare($node, $teaser);
  $node->content['info_content'] = array(
    '#value' => $content,
    '#weight' => 1,
  );
  return $node;
}

/**
 * Form for qdrupal_link (hook_form()).
 */
function qdrupal_link_form($node) {
  $form['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Form Name'),
    '#default_value' => $node->title,
    '#required' => TRUE,
    '#weight' => -6,
    '#description' => t('The class name of your QForm.'),
  );

  if( !isset($node->aid) ) {
    $r = db_query("SELECT a.aid,a.title FROM {qdrupal_application} a ORDER BY a.title ASC");
	while( $row = db_fetch_object($r) ) $apps[$row->aid] = $row->title;

    $form['aid'] = array(
      '#type' => 'select',
      '#title' => t('Application'),
      '#options' => $apps,
      '#required' => TRUE,
      '#description' => t('A QDrupal Form Link needs to be associated with a QDrupal application.  Please select the application to use for this link.'),
      '#weight' => -5,
      '#ahah' => array(
        'event' => 'change',
        'path' => 'qdrupal-link/qforms',
        'wrapper' => 'form-path-wrapper',
      ),
    );
    $current_app = qdrupal_application_load(array_shift(array_keys($apps)));
  } else {
    $form['aid'] = array(
      '#type' => 'hidden',
      '#value' => $node->aid,
      '#required' => TRUE,
    );
    $current_app = qdrupal_application_load($node->aid);
  }

  $site_path = qdrupal_application_path($current_app).'/pages';
  $forms_path = qdrupal_application_disk_path($current_app).'/pages';
  $file_list = _qdrupal_walk_form_dir($forms_path);
  ksort($file_list);

  $form['form_path'] = array(
    '#type' => 'select',
    '#title' => t('QForm File'),
    '#default_value' => $node->form_path,
    '#weight' => -4,
    '#options' => $file_list,
    '#required' => TRUE,
    '#description' => t('Choose a file to link this node with.  Files must be placed within '.$site_path.'. a .tpl.php file for the selected form must also be present in the templates directory.'),
    '#prefix' => "<div id='form-path-wrapper'>",
    '#suffix' => "</div>"
  );

  return $form;
}

/**
 * AHAH callback for generating the qform select form element
 */
function qdrupal_link_qforms() {
  $current_app = qdrupal_application_load($_POST['aid']);
  $forms_path = qdrupal_application_disk_path($current_app).'/pages';
  $file_list = _qdrupal_walk_form_dir($forms_path);
  ksort($file_list);
  
  $qforms = array(
    '#type' => 'select',
    '#title' => t('QForm File'),
    '#options' => $file_list,
    '#required' => TRUE,
    '#description' => t('Choose a file to link this node with.  Files must be placed within '.$site_path.'. a .tpl.php file for the selected form must also be present in the templates directory.'),
    '#weight' => -4
  );

  $output = ahah_render($qforms,'form_path');
  drupal_json(array('status' => TRUE, 'data' => $output));
}

function ahah_render($fields, $name) {
  $form_state = array('submitted' => FALSE);
  $form_build_id = $_POST['form_build_id'];
  $form = form_get_cache($form_build_id, $form_state);
  $form[$name] = $fields;
  form_set_cache($form_build_id, $form, $form_state);
  $form += array(
    '#post' => $_POST,
    '#programmed' => FALSE,
  );
  $form = form_builder($_POST['form_id'], $form, $form_state);
  $new_form = $form[$name];
  return drupal_render($new_form);  
}

function _qdrupal_walk_form_dir($str_path) {
  $file_list = array();
  foreach(scandir($str_path) as $file) {
	if( !ereg('^\.',$file) ) {
      $file_path = $str_path.'/'.$file;
      if(is_dir($file_path)) {
        $file_list = array_merge(_qdrupal_walk_form_dir($file_path),$file_list);
      } else {
        $short_path = str_replace($str_path,'',$file_path);
        $file_list[$short_path] = $short_path;
      }
    }
  }
  return $file_list;
}

function qdrupal_tmpl_path($qform) {
  // FIXME tmpl_path needs some work
  // Update: now the function will look for either a form draft, a page, 
  // or a drupal node.  Still needs to be better tho. ;-)

  $basename = basename($qform);
  $templatename = ereg_replace(".php", ".tpl.php", $basename);
  // a 'page'
  if(file_exists( dirname($qform) . DIRECTORY_SEPARATOR  . $templatename))
    return dirname($qform) . DIRECTORY_SEPARATOR . $templatename;
}
