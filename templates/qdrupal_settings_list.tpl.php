<?php $this->RenderBegin(); ?>

	<?php $this->dtgSettings->Render(); ?>
	<?php $this->lblNoSettings->Render(); ?>

	<p><?php 
	global $qdrupal_node;
	echo l('Create Database Profile','node/'.$qdrupal_node->nid.'/databases/add');
	?></p>
<?php $this->RenderEnd(); ?>
