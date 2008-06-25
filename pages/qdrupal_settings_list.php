<?php

	class QDrupalSettingsList extends QForm {
		protected $dtgSettings;
		protected $lblNoSettings;

		protected function Form_Create() {
			$this->dtgSettings = new QDataGrid($this);
			$this->dtgSettings->AddColumn(new QDataGridColumn('Database','<?= $_FORM->NameCol($_ITEM) ?>','HtmlEntities=false'));
			$this->dtgSettings->AddColumn(new QDataGridColumn('Operations','<?= $_FORM->OpCol($_ITEM) ?>','HtmlEntities=false'));
			$this->dtgSettings->SetDataBinder('dtgSettings_Bind');

			$this->lblNoSettings = new QLabel($this);
			$this->lblNoSettings->Text = "There are no database profiles for this QDrupal Application";
			$this->lblNoSettings->Visible = FALSE;
		}

		public function NameCol($objItem) {
			return $objItem->name;
		}

		public function OpCol($objItem) {
			$strId = preg_replace(':[^a-z]:i','',$objItem->name);
			$lblEdit = $this->GetControl('lblEdit'.$strId);
			if(!$lblEdit) {
				$lblEdit = new QLinkButton($this->dtgSettings,'lblEdit'.$strId);
				$lblEdit->Text = t('Edit');
				$lblEdit->ActionParameter = $objItem->name;
				$lblEdit->AddAction(new QClickEvent(), new QServerAction('lblEdit_Click'));
			}
			$lblDelete = $this->GetControl('lblDelete'.$strId);
			if(!$lblDelete) {
				$lblDelete = new QLinkButton($this->dtgSettings,'lblDelete'.$strId);
				$lblDelete->Text = t('Delete');
				$lblDelete->ActionParameter = $objItem->name;
				$lblDelete->AddAction(new QClickEvent(), new QConfirmAction('Are you sure you would like to delete this database profile?'));
				$lblDelete->AddAction(new QClickEvent(), new QServerAction('lblDelete_Click'));
				$lblDelete->AddAction(new QClickEvent(), new QTerminateAction());
			}
			return $lblEdit->Render(false) ." | " .$lblDelete->Render(false);
		}

		protected function lblEdit_Click($strFormId,$strControlId,$strParameter) {
			global $qdrupal_node;
			drupal_goto('node/'.$qdrupal_node->nid.'/databases/'.urlencode($strParameter));
			exit;
		}

		protected function lblDelete_Click($strFormId,$strControlId,$strParameter) {
			global $qdrupal_node;
			qdrupal_settings_delete($qdrupal_node->nid,$strParameter);
			$this->dtgSettings->DataSource = array();
			$this->dtgSettings_Bind();
		}

		protected function dtgSettings_Bind() {
			global $qdrupal_node;
			$this->dtgSettings->DataSource = qdrupal_settings_load($qdrupal_node->nid);

			if(sizeof($this->dtgSettings->DataSource) == 0) {
				$this->lblNoSettings->Visible = TRUE;
				$this->dtgSettings->Visible = FALSE;
			}
			else {
				$this->lblNoSettings->Visible = FALSE;
				$this->dtgSettings->Visible = TRUE;
			}
		}
	}
