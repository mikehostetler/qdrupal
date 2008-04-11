<template OverwriteFlag="true" DocrootFlag="true" DirectorySuffix="" TargetDirectory="<%= __FORM_DRAFTS__ %>" TargetFileName="<%= QConvertNotation::UnderscoreFromCamelCase($objTable->ClassName) %>_list.tpl.php"/>
<?php
      // This is the HTML template include file (.tpl.php) for the <%= QConvertNotation::UnderscoreFromCamelCase($objTable->ClassName) %>_list.php
      // form DRAFT page.  Remember that this is a DRAFT.  It is MEANT to be altered/modified.
      // Be sure to move this out of the generated/ subdirectory before modifying to ensure that subsequent
      // code re-generations do not overwrite your changes.

?>

      <?php $this->RenderBegin() ?>
        <?php $this->dtg<%= $objTable->ClassNamePlural %>->Render() ?>
        <br />
                <?php
                $components = explode('/', $_SERVER['REQUEST_URI']);
                $p = array_pop($components);
                $components[] = '';
                $f = implode('/', $components);
                ?>
        <a href="<?php echo $f . 'edit'?>"><?php _t('New'); ?></a>
      <?php $this->RenderEnd() ?>
