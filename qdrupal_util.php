<?php

class QDrupalException extends Exception {
}

function _qdrupal_error_handler($__exc_errno, $__exc_errstr, $__exc_errfile, $__exc_errline, $blnExit = TRUE) {
	if( $__exc_errno == E_NOTICE ) 
		return;

  ob_start();
  QcodoHandleError($__exc_errno, $__exc_errstr, $__exc_errfile, $__exc_errline,$blnExit = FALSE);
  $strContent = ob_get_clean();

  throw new QDrupalException($strContent);
}

function _qdrupal_restore_drupal_error_handler() {
  set_error_handler('drupal_error_handler');
}

function qdrupal_load_page($form_name) {
	if(file_exists(QDRUPAL_ROOT . "/pages/$form_name.php"))
		require_once(QDRUPAL_ROOT . "/pages/$form_name.php");
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


function _qdrupal_run_qform($app_node,$form_name,$form_template) {
	if(is_int($app_node))
		$app_node = node_load($app_node);

	qdrupal_prepend($app_node);

	if(!class_exists($form_name)) {
		qdrupal_load_page($form_name);
	}

	// TODO - Check that our form_template path is valid
	if(!in_array($form_template,get_included_files())) {
		qdrupal_tmpl_path($form_template);
	}

	try {
		ob_start();
		call_user_func(array($form_name,'Run'),$form_name,$form_template);
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

  _qdrupal_restore_drupal_error_handler();

	return $content;
}

function _qdrupal_walk_form_dir($str_path) {
  $file_list = array();
  foreach(scandir($str_path) as $file) {
    if($file != '.' && $file != '..') {
      $file_path = $str_path.'/'.$file;
      if(is_dir($file_path)) 
        $file_list = array_merge(_qdrupal_walk_form_dir($file_path),$file_list);
      else {
        $short_path = str_replace($str_path,'',$file_path);
        $file_list[$short_path] = $short_path;
      }
    }
  }
  return $file_list;
}

// A function to copy files from one directory to another one, including subdirectories and
// nonexisting or newer files. Function returns number of files copied.
// This function is PHP implementation of Windows xcopy  A:\dir1\* B:\dir2 /D /E /F /H /R /Y
// Syntaxis: [$number =] dircopy($sourcedirectory, $destinationdirectory [, $verbose]);
// Example: $num = dircopy('A:\dir1', 'B:\dir2', 1);

function dircopy($srcdir, $dstdir, $verbose = false) {
  $num = 0;
  if(!is_dir($dstdir)) mkdir($dstdir);
  if($curdir = opendir($srcdir)) {
    while($file = readdir($curdir)) {
      if($file != '.' && $file != '..') {
        $srcfile = $srcdir . DIRECTORY_SEPARATOR . $file;
        $dstfile = $dstdir . DIRECTORY_SEPARATOR . $file;
        if(is_file($srcfile)) {
          if(is_file($dstfile)) $ow = filemtime($srcfile) - filemtime($dstfile); else $ow = 1;
          if($ow > 0) {
            if($verbose) echo "Copying '$srcfile' to '$dstfile'...";
            if(copy($srcfile, $dstfile)) {
              touch($dstfile, filemtime($srcfile)); $num++;
              if($verbose) echo "OK\n";
            }
            else echo "Error: File '$srcfile' could not be copied!\n";
          }                  
        }
        else if(is_dir($srcfile)) {
          $num += dircopy($srcfile, $dstfile, $verbose);
        }
      }
    }
    closedir($curdir);
  }
  return $num;
}

//
// Some utility functions for file management
//
function copyr($source, $dest){
	// Simple copy for a file
	if (is_file($source)) {
		$c = copy($source, $dest);
		return $c;
	}
	// Make destination directory
	if (!is_dir($dest)) {
		mkdir($dest);
	}
	// Loop through the folder
	$dir = dir($source);
	while (false !== $entry = $dir->read()) {
		// Skip pointers
		if ($entry == '.' || $entry == '..') {
			continue;
		}
		// Deep copy directories
		if ($dest !== "$source/$entry")
		{
			copyr("$source/$entry", "$dest/$entry");
		}
	}
	// Clean up
	$dir->close();
	return true;
}

function rmdirr ($dir) {
	if (is_dir ($dir) && !is_link ($dir)) {
		return cleardir ($dir) ? rmdir ($dir) : false;
	}
	return unlink ($dir);
}

function cleardir ($dir) {
	if (!($dir = dir ($dir))) {
		return false;
	}
	while (false !== $item = $dir->read()) {
		if ($item != '.' && $item != '..' && !rmdirr ($dir->path . DIRECTORY_SEPARATOR . $item)) {
			$dir->close();
			return false;
		}
	}
	$dir->close();
	return true;
}

/**
 * Quick helper function, TODO, add ability to flag overwrites
 */
function qdrupal_write_file($strFilename,$strContent) {
	$fp = fopen($strFilename,"w+");
	fwrite($fp,$strContent);
	fclose($fp);
}

/**
 * Quick helper function to read a file
 */
function qdrupal_read_file($strFilename) {
	return file_get_contents($strFilename);
}

	// May need this code later, saving here for posterity
	// Now create drupal nodes for each file
	// This is very Quick and Dirty
	// not a lot of checking done here
	// FIXME need to drop all form_draft nodes for an app first
		
	//$dir = __DOCROOT__ . __FORM_DRAFTS__;
	//$node_content = '<h1>Node Content</h1>' . 'Using directory:<br>' . $dir;
	 
	//if ($handle = opendir($dir)) { 
	//	$node_content .= '<br>Opened directory for reading<br>';
 	//	while(false !== ($file = readdir($handle))) { 
	//		//$node_content .= "$file present<br>";
   	//		if(is_file($dir . '/' . $file)){
	//			if(!ereg('index.php',$file)){
  	//				$node_content .= "$file is a regular file<br>";
	//				$qform_content = file_get_contents($dir . DIRECTORY_SEPARATOR . $file);
					// get template info as well. 
					// FIXME need better checking here
		//			$template_file= $string = ereg_replace(".php", ".tpl.php", $file);
		//			$node_content .= "$template_file is a the template<br>";
		//			$template_content = file_get_contents($dir . DIRECTORY_SEPARATOR . 'generated' . DIRECTORY_SEPARATOR . $template_file);
				
					// now put the node together
		//			$title = ereg_replace(".php", "", $file);
		//			$title = ereg_replace("_", " ", $title);
					// Create a new node
		//			$node = array('type' => 'qdrupal_page');
		//			$values['title'] = 'Draft ' . $title;
		//			$values['name'] = $user->name;
		//			$values['qform'] = $qform_content;
		//			$values['status'] = 0;
		//			$values['template'] = $template_content;
					//drupal_execute('qdrupal_page_node_form', $values, $node);
	//			}
  	//		}
 	//	} 
 	//	closedir($handle);  
	//} 
	//$output = $codegen_content  . $node_content;
