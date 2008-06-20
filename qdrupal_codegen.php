<?php
// $Id$

/**
 * Function that runs the Qcodo Code Generator
 */ 
function qdrupal_application_codegen($node) {
  drupal_set_title($node->title ." :: Codegen");
	drupal_set_breadcrumb(array(
			l(t('Home'),NULL),
			l(t($node->title),'node/'.$node->nid),
			l(t('Codegen'),'node/'.$node->nid.'/codegen')
		));

	qdrupal_prepend($node);
	return _qdrupal_run_qform(
		$node,
		'QDrupalCodegen',
		QDRUPAL_ROOT . '/pages/qdrupal_codegen.php',
		QDRUPAL_ROOT . '/templates/qdrupal_codegen.tpl.php');
}

function qdrupal_run_codegen() {
	global $qdrupal_node;
	$node = $qdrupal_node;

	$strXML = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<codegen>
<name application="{$node->title}"/>
<templateEscape begin="&lt;%" end="%&gt;"/>
<dataSources>
</dataSources>
</codegen>
XML;

  $objXML = simplexml_load_string($strXML);

  // get codegen settings from database
  // put settings xml variable
  $settings = qdrupal_settings_load($node->nid);
  $count = 1;
  if ($settings) {
    foreach ($settings as $s) {
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
      $count++;
    }
  }

  $strFilename = '/codegen_settings.xml';
  $strAppPath = file_create_path('qdrupal' . DIRECTORY_SEPARATOR .  preg_replace('/\s+/', '',strip_tags($node->shortname)));
  file_check_directory($strAppPath, FILE_CREATE_DIRECTORY);

  // Output xml to filesystem
  $strXML = $objXML->asXML();
  file_save_data($strXML,$strAppPath.$strFilename,FILE_EXISTS_REPLACE);

	qdrupal_prepend($node);
	require(__QCODO__ . DIRECTORY_SEPARATOR . 'codegen' . DIRECTORY_SEPARATOR . 'QCodeGen.class.php');
	QCodeGen::Run($strAppPath.$strFilename);
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
