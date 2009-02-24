<?php
// $Id$

/**
 * Function that runs the Qcubed Code Generator
 */ 
function qdrupal_application_codegen($shortname) {
	global $qdrupal_app;
	$app = qdrupal_application_load_by_name($shortname);
	$qdrupal_app = $app;
	drupal_set_title($app->title ." Codegen");
	drupal_set_breadcrumb(array(
			l(t('Home'),NULL),
      l(t('QDrupal Applications'),'qdrupal/applications'),
			l(t($app->title),'qdrupal/applications/'.$app->shortname),
			l(t('Codegen'),'qdrupa/applications/'.$app->shortname.'/codegen')
		));

	qdrupal_prepend($app);
	return _qdrupal_run_qform(
		$app,
		'QDrupalCodegen',
		QDRUPAL_ROOT . '/pages/qdrupal_codegen.php',
		QDRUPAL_ROOT . '/templates/qdrupal_codegen.tpl.php');
}

function qdrupal_run_codegen() {
	global $qdrupal_app;
	$app = $qdrupal_app;

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

  // get codegen settings from database
  // put settings xml variable
  $settings = qdrupal_settings_load($app->aid);
  watchdog("qdrupal", "codegen started for settings: " . print_r($settings,1));
  $count = 1;
  if ($settings) {
    foreach ($settings as $s) {
      watchdog("qdrupal", "setting: " . print_r($s,1));
      $database = $objXML->dataSources->addChild('database');
      $database->addAttribute('index', $count);

      $className = $database->addChild('className');
      $className->addAttribute('prefix', $s->setting['classNamePrefix']);
      $className->addAttribute('suffix', $s->setting['classNameSuffix']);

      $associatedObjectName = $database->addChild('associatedObjectName');
      $associatedObjectName->addAttribute('prefix', $s->setting['associatedObjectNamePrefix'] ? $s->setting['associatedObjectNamePrefix'] : '');
      $associatedObjectName->addAttribute('suffix', $s->setting['associatedObjectNameSuffix'] ? $s->setting['associatedObjectNameSuffix'] : '');

      $typeTableIdentifier = $database->addChild('typeTableIdentifier');
      $typeTableIdentifier ->addAttribute('suffix', $s->setting['typeTableIdentifierSuffix'] ? $s->setting['typeTableIdentifierSuffix'] : '');

      $associationTableIdentifier = $database->addChild('associationTableIdentifier');
      $associationTableIdentifier ->addAttribute('suffix', $s->setting['associationTableIdentifierSuffix'] ? $s->setting['associationTableIdentifierSuffix'] : '');

      $excludeTables = $database->addChild('excludeTables');
      $excludeTables->addAttribute('list', $s->setting['excludeTablesList'] );
      $excludeTables->addAttribute('pattern', $s->setting['excludeTablesPattern'] );

      $includeTables = $database->addChild('includeTables');
      $includeTables->addAttribute('list', $s->setting['includeTablesList'] );
      $includeTables->addAttribute('pattern', $s->setting['includeTablesPattern'] );

      $relationships = $database->addChild('relationshipsScript');
      $relationships->addAttribute('filepath',$s->setting['relationships']);
      $relationships->addAttribute('format','qcubed');
      $count++;
    }
  }

	qdrupal_prepend($app);
  $codegen_file = QDRUPAL_APPLICATION_PATH . DS . 'codegen_settings.xml';

  // Output xml to filesystem
  $strXML = $objXML->asXML();
  file_put_contents($codegen_file,$strXML);

	require(__QCODO__ . DIRECTORY_SEPARATOR . 'codegen' . DIRECTORY_SEPARATOR . 'QCodeGen.class.php');
	QCodeGen::Run($codegen_file);
	?>
	<div class="page">
    <?php if ($strErrors = QCodeGen::$RootErrors) { ?>
      <p><b>The following root errors were reported:</b></p>
      <div class="code"><xmp><?php echo ($strErrors); ?></xmp></div>
      <p></p>
    <?php } else { ?>
      <p><b>CodeGen Settings:</b></p>
      <div class="code"><xmp><?php echo (QCodeGen::GetSettingsXml()); ?></xmp></div>
      <p></p>
    <?php } ?>

    <?php foreach (QCodeGen::$CodeGenArray as $objCodeGen) { ?>
      <p><b><?php _p($objCodeGen->GetTitle()); ?></b></p>
      <div class="code"><span class="code_title"><?php _p($objCodeGen->GetReportLabel()); ?></span><br/><br/>
        <xmp><?php echo ($objCodeGen->GenerateAll()); ?></xmp>
        <?php if ($strErrors = $objCodeGen->Errors) { ?>
          <p class="code_title">The following errors were reported:</p>
          <xmp><?php echo ($objCodeGen->Errors); ?></xmp>
        <?php } ?>
      </div><p></p>
    <?php } ?>
    
    <?php foreach (QCodeGen::GenerateAggregate() as $strMessage) { ?>
      <p><b><?php _p($strMessage); ?></b></p>
    <?php } ?>
  </div>
  <?php
  _qdrupal_restore_drupal_error_handler();
  return ob_get_clean();
}
