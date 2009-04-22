<?php

function qdrupal_codegen(&$form_state, $app) {
  drupal_set_title($app->title);

  $bc = drupal_get_breadcrumb();
  array_pop($bc);
  $bc[] = l($app->title,'qdrupal/applications/'.$app->aid);
  drupal_set_breadcrumb($bc);

  if ( !isset($form_state['storage']['step']) ) {
    $form_state['storage']['step'] = 1;
  }

  $form['aid'] = array(
    '#type' => 'value',
    '#value' => $app->aid,
  );
  $form['explain'] = array( '#value' => '<p>'.t('Code generation will build database classes for each table described in the database profile. If you have more than one database profile defined, code will be generated based on each one. If you have not created a database profile, code generation will not produce any output.').'</p>'
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Run Codegen'),
    '#weight' => 10
  );
  $form['output'] = array(
    '#title' => t('Results'),
    '#weight' => 9
  );

  if($form_state['storage']['step'] == 2) {
    qdrupal_codegen_execute($form_state);
	$form['settings'] = $form_state['storage']['settings'];
	$form['results'] = $form_state['storage']['results'];
    $form['errors'] = $form_state['storage']['errors'];
	$form['submit']['#value'] = t('Run Codegen Again');
  }

  return $form;
}

function qdrupal_codegen_validate($form, &$form_state) {
  $app = qdrupal_application_load($form_state['values']['aid']);
  $codegen_file = qdrupal_application_path($app) . '/codegen_settings.xml';
  
  if(!file_exists($codegen_file)) {
    @touch($codegen_file);
  }

  if(!is_writable($codegen_file)) {
    form_set_error('submit',t('Cannot write %codegen_path file.  Please check your file permissions and try again.',array('%codegen_path' => $codegen_file)));
  }
}

function qdrupal_codegen_submit($form, &$form_state) {
  $form_state['storage']['step'] = 2;
  $form_state['rebuild'] = TRUE;
}

/**
 * Function that runs the Qcubed Code Generator
 */ 
function qdrupal_codegen_execute(&$form_state) {
	$app = qdrupal_application_load($form_state['values']['aid']);
	$strXML = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<codegen>
<name application="{$app->title}"/>
<templateEscape begin="&lt;%" end="%&gt;"/>
<dataSources>
</dataSources>
</codegen>
XML;

  $objXML = simplexml_load_string($strXML);

  $profiles = qdrupal_profile_load($app->aid);
  $count = 1;
  if ($profiles) {
    foreach ($profiles as $profile) {
      $database = $objXML->dataSources->addChild('database');
      $database->addAttribute('index', $count);

      $className = $database->addChild('className');
      $className->addAttribute('prefix', $profile->setting['class_name_prefix']);
      $className->addAttribute('suffix', $profile->setting['class_name_suffix']);

      $associatedObjectName = $database->addChild('associatedObjectName');
      $associatedObjectName->addAttribute('prefix', $profile->setting['associated_object_name_prefix']);
      $associatedObjectName->addAttribute('suffix', $profile->setting['associated_object_name_suffix']);

      $typeTableIdentifier = $database->addChild('typeTableIdentifier');
      $typeTableIdentifier ->addAttribute('suffix', $profile->setting['type_table_identifier_suffix']);

      $associationTableIdentifier = $database->addChild('associationTableIdentifier');
      $associationTableIdentifier ->addAttribute('suffix', $profile->setting['association_table_identifier_suffix']);

      $excludeTables = $database->addChild('excludeTables');
      $excludeTables->addAttribute('list', $profile->setting['exclude_tables_list'] );
      $excludeTables->addAttribute('pattern', $profile->setting['exclude_tables_pattern'] );

      $includeTables = $database->addChild('includeTables');
      $includeTables->addAttribute('list', $profile->setting['include_tables_list'] );
      $includeTables->addAttribute('pattern', $profile->setting['include_tables_pattern'] );

      $relationships = $database->addChild('relationships');
      $relationships = dom_import_simplexml($relationships);
      $no = $relationships->ownerDocument;
      $relationships->appendChild($no->createCDATASection($profile->setting['relationships']));

      $count++;
    }
  }

  qdrupal_prepend($app);
  $codegen_file = QDRUPAL_APPLICATION_PATH . DS . 'codegen_settings.xml';

  // Output xml to filesystem
  $strXML = $objXML->asXML();
  file_put_contents($codegen_file,$strXML);

  ob_start();
  require(__QCODO__ . DIRECTORY_SEPARATOR . 'codegen' . DIRECTORY_SEPARATOR . 'QCodeGen.class.php');
  QCodeGen::Run($codegen_file);
  ?>
  <?php if ($strErrors = QCodeGen::$RootErrors) {
    $form_state['storage']['errors'] = array(
      '#type' => 'fieldset',
      '#title' => 'Codegen Errors',
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      'content' => array(
      '#value' => '<span class="code"><xmp>'.($strErrors).'</xmp></span>')
    );
  } else {
    $form_state['storage']['settings'] = array(
      '#type' => 'fieldset',
      '#title' => 'CodeGen Settings',
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      'content' => array(
      '#value' => '<span class="code"><xmp>'.(QCodeGen::GetSettingsXml()).'</xmp></span>')
    );
  } ?>

  <?php $form_state['storage']['results'] = array(
    '#type' => 'fieldset',
    '#title' => 'CodeGen Results',
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
    'content' => array('#value' => '')
  ); ?>

  <?php foreach (QCodeGen::$CodeGenArray as $objCodeGen) {
	$form_state['storage']['results']['content']['#value'] .= 
      '<p><b>'.$objCodeGen->GetTitle().'</b></p>'
      .'<span class="code"><span class="code_title">'._p($objCodeGen->GetReportLabel()).'</span>'
      .'<xmp>'.($objCodeGen->GenerateAll()).'</xmp>';
    if ($strErrors = $objCodeGen->Errors) { 
      $form_state['storage']['errors']['content']['#value'] .=
      '<span class="code"><xmp>'.($objCodeGen->Errors).'</xmp></span>';
    }
  } ?>
    
  <?php foreach (QCodeGen::GenerateAggregate() as $strMessage) {
	$form_state['storage']['results']['content']['#value'] .= '<p><b>'.($strMessage).'</b></p>';
  } ?>
  <?php
  _qdrupal_restore_drupal_error_handler();
  return ob_get_clean();
}
