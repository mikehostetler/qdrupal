<?php
// $Id$

/**
 * Function that runs a QDrupal Draft
 */ 
function qdrupal_drafts_overview($app) {
  $args = array();
  $output = '';

  qdrupal_prepend($app);
  drupal_set_title($app->title);

  $bc = drupal_get_breadcrumb();
  array_pop($bc);
  $bc[] = l($app->title,'admin/build/qdrupal/'.$app->aid);
  drupal_set_breadcrumb($bc);

  // Iterate through the files in this "form_drafts" folder, 
  // looking for files that end in _edit.php or _list.php
  $draft_suffixes = array('_edit.php', '_list.php', '_combo.php');
  $drafts = array();
  $drafts_dir = opendir(__DOCROOT__ . __FORM_DRAFTS__);
  while ($filename = readdir($drafts_dir)) {
    if (($filename != '.') && ($filename != '..')) {
      $draft_found = false;
      foreach ($draft_suffixes as $strSuffix) {
        if ((!$draft_found) && (substr($filename, strlen($filename) - strlen($strSuffix)) == $strSuffix)) {
          $filename = substr($filename, 0, strlen($filename) - strlen($strSuffix));
          $draft_found = true;
        }
      }
      if ($draft_found)
        $drafts[$filename] = true;
    }
  }

  // Sort the list of objects
  ksort($drafts);

  $rows = array();
  $header = array(t('Objects'), '', '');

  $profiles = array();
  foreach ($drafts as $draft => $val) {
    $rows[] = array(
      $draft,
      l(t('View List'),'admin/build/qdrupal/'.$app->aid.'/drafts/'.$draft),
      l(t('Create New'),'admin/build/qdrupal/'.$app->aid.'/drafts/'.$draft.'/edit'),
    );
  }

  $output .= theme('table', $header, $rows);
  return $output;
}

function qdrupal_draft_qform($app,$draft,$action = 'list') {
  $bc = drupal_get_breadcrumb();
  $bc[] = l($app->title,'admin/build/qdrupal/'.$app->aid);
  $bc[] = l(t('Drafts'),'admin/build/qdrupal/'.$app->aid.'/drafts');
  drupal_set_breadcrumb($bc);

  $app_path = $_SERVER['DOCUMENT_ROOT'] . '/' . qdrupal_application_path($app);
  $draft_file = $app_path . '/drafts/' . $draft . '_' . $action . '.php';

  return qdrupal_run_qform($app,$draft_file);
}

