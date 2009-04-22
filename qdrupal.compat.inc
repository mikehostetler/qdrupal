<?php

/**
 * Run a qdrupal page.  This is a simplistic front controller
 */
function qdrupal_front() {
  global $args;
  $args = func_get_args();

  foreach($args as $k => $item) {
    if(preg_match('/[^\/,-_;a-z0-9*]/i', $args[$k]))
      unset($args[$k]);
    elseif(trim($args[$k]) == "")
      unset($args[$k]);
  }
  // Throw on a Default
  if(sizeof($args) == 0) {
    $args[] = 'index';
  }
  // Backards compat?
  if(sizeof($args) == 1) {
    // FIXME do qdrupal_font backwards compat here?
  }
  $nid=$args[0];
  $node = node_load($nid);
  qdrupal_bootstrap($node);
  $formDrafts = __DOCROOT__ . __FORM_DRAFTS__ . DIRECTORY_SEPARATOR;
  $customForms = __QDrupal_PAGES__. DIRECTORY_SEPARATOR;

  // Recursively search for a file to display
  $pageFound = false;
  $type="";
  // Construct the file path
  // FIXME this is now a more simplistic front controller, may need enhancement
  $pageStr = $args[1];
  if(!stristr($pageStr,'.php')) {
    $pageStr .= ".php";
  }
  if(file_exists($customForms . $pageStr)) {
    // We've got a hit! 
    $page = $customForms . $pageStr;
    $pageFound = true;
    $type= "pages";
    
  }
  elseif(file_exists($formDrafts . $pageStr)) {
    // We've got a hit!
    $page = $formDrafts . $pageStr;
    $pageFound = true;
    $type="Form Drafts";
  }
  if(!$pageFound) {
    drupal_not_found($pageStr);
    return;
  }
  $new_breadcrumb[] = l("Home","");
  $path = $config['NAME'];

  $newpath = $path . "/node/".$node->nid;
  $newarg = $node->title;
  $new_breadcrumb[] = l($newarg,$newpath);
  
  // FIXME This should be a case statement on type
  $newpath = $path ."/node/".$node->nid. "/formdrafts";
  $newarg = "Form Drafts";
  $new_breadcrumb[] = l($newarg,$newpath);
  
  $newarg = ucwords(str_replace(array('-','_','/','\\',',',';','.php'),' ',$args[1]));
  $newpath = $path ."/qdrupal/".$node->nid. "/".$args[1];
  $new_breadcrumb[] = l($newarg,$newpath);
      
  drupal_set_breadcrumb($new_breadcrumb);
  drupal_set_title(" ");

  ob_start();
  // FIXME need to work on qdrupal profiling options
  if(variable_get("qdrupal_enable_profiling",false))
    QApplication::$Database[1]->EnableProfiling();
  require_once($page);
  if(variable_get("qdrupal_enable_profiling",false))
    QApplication::$Database[1]->OutputProfiling();
  $content = ob_get_clean();
  return $content;
}

