<template OverwriteFlag="true" DocrootFlag="true" DirectorySuffix="" TargetDirectory="<%= __FORM_DRAFTS__ %>" TargetFileName="<%= QConvertNotation::UnderscoreFromCamelCase($objTable->ClassName) %>_edit.tpl.php"/>
<?php
	// This is the HTML template include file (.tpl.php) for the <%= QConvertNotation::UnderscoreFromCamelCase($objTable->ClassName) %>_edit.php
	// form DRAFT page.  Remember that this is a DRAFT.  It is MEANT to be altered/modified.

	// Be sure to move this out of the generated/ subdirectory before modifying to ensure that subsequent 
	// code re-generations do not overwrite your changes.
?>

	<?php $this->RenderBegin() ?>

<table style="width:600px;">
<thead> <th>Field</th><th>Value</th></thead>
<?php $class = 'odd'; ?>
<% foreach ($objTable->ColumnArray as $objColumn) { %>
<% ($class == 'odd') ? $class='even' : $class='odd'; %>
<tr class="<?php echo $class; ($class == 'odd') ? $class='even' : $class='odd'; ?>">
<td colspan="2">
 <?php $this-><%= $objCodeGen->FormControlVariableNameForColumn($objColumn)
; %>->RenderWithName(); ?>

</td>
</tr>
<% } %>
<% foreach ($objTable->ReverseReferenceArray as $objReverseReference) { %>
<tr>
      <% if ($objReverseReference->Unique) { %>
	  <td colspan=2>
        <?php $this-><%= $objCodeGen->FormControlVariableNameForUniqueReverseReference($objReverseReference); %>->RenderWithName(); ?>
        </td>
      <% } %>
</tr>
<% } %>
<% foreach ($objTable->ManyToManyReferenceArray as $objManyToManyReference) { %>
<tr>
        <td colspan=2><?php $this-><%= $objCodeGen->FormControlVariableNameForManyToManyReference($objManyToManyReference); %>->RenderWithName(true, "Rows=10"); ?>
        </td>
	</tr>
<% } %>
<tr><td colspan=2 align=center style="padding:10px;"> 
        <?php $this->btnSave->Render() ?>

        <?php $this->btnCancel->Render() ?>
 
        <?php $this->btnDelete->Render() ?>
</td>
</tr>
</table>

       

<?php $this->RenderEnd() ?>


