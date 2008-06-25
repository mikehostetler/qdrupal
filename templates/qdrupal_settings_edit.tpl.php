<?php drupal_add_js('misc/collapse.js'); ?>
<style type="text/css">
.instructions {
	font-weight: normal;
	font-size: x-small;
}
</style>
<?php $this->RenderBegin(); ?>

	<?php $this->txtProfile->RenderWithName(); ?>	

	<fieldset>
		<legend><?php echo t('Database Settings'); ?></legend>
		<?php $this->lstAdapter->RenderWithName(); ?>	
		<?php $this->txtServer->RenderWithName(); ?>	
		<?php $this->txtPort->RenderWithName(); ?>	
		<?php $this->txtDbName->RenderWithName(); ?>	
		<?php $this->txtUsername->RenderWithName(); ?>	
		<?php $this->txtPassword->RenderWithName(); ?>	
		<?php $this->lstProfiling->RenderWithName(); ?>	
	</fieldset>

	<fieldset class="collapsible collapsed">
		<legend><?php echo t('Codegen Settings'); ?></legend>
		<?php $this->txtClassNamePrefix->RenderWithName(); ?>	
		<?php $this->txtClassNameSuffix->RenderWithName(); ?>	
		<?php $this->txtAssociatedObjectNamePrefix->RenderWithName(); ?>	
		<?php $this->txtAssociatedObjectNameSuffix->RenderWithName(); ?>	

		<?php $this->txtTypeTableIdentifierSuffix->RenderWithName(); ?>	
		<?php $this->txtAssociationTableIdentifierSuffix->RenderWithName(); ?>	

		<?php $this->txtExcludeTableList->RenderWithName(); ?>	
		<?php $this->txtExcludeTablePattern->RenderWithName(); ?>	

		<?php $this->txtIncludeTableList->RenderWithName(); ?>	
		<?php $this->txtIncludeTablePattern->RenderWithName(); ?>	

		<?php $this->txtRelationships->RenderWithName(); ?>	

	</fieldset>

	<?php $this->btnSubmit->Render(); ?>	
	<?php $this->btnCancel->Render(); ?>	
<?php $this->RenderEnd(); ?>
