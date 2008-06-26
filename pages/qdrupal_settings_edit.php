<?php

	class QDrupalSettingsEdit extends QForm {
		protected $objSetting;
		protected $blnAddEdit;

		protected $txtProfile;

		protected $lstAdapter;
		protected $txtServer;
		protected $txtPort;
		protected $txtDbName;
		protected $txtUsername;
		protected $txtPassword;
		protected $lstProfiling;

		protected $txtClassNamePrefix;
		protected $txtClassNameSuffix;
		protected $txtAssociatedObjectNamePrefix;
		protected $txtAssociatedObjectNameSuffix;
		protected $txtTypeTableIdentifierSuffix;
		protected $txtAssociationTableIdentifierSuffix;
		protected $txtExcludeTableList;
		protected $txtExcludeTablePattern;
		protected $txtIncludeTableList;
		protected $txtIncludeTablePattern;
		protected $txtRelationships;

		protected $btnSubmit;
		protected $btnCancel;

		protected function Form_Create() {

			global $qdrupal_node;
			$arg = arg(3);
			if($arg == 'add') {
				$this->blnAddEdit = FALSE;
				$this->objSetting = array();
			}
			else {
				$this->blnAddEdit = TRUE;
				$this->objSetting = qdrupal_settings_load($qdrupal_node,$arg);
				$this->objSetting = $this->objSetting->setting;
			}

			$this->txtProfile = new QTextBox($this);
			$this->txtProfile->Name = t('Profile');
			$this->txtProfile->Instructions = t('Name for this database profile');
			$this->txtProfile->Text = $this->objSetting['name'];
			$this->txtProfile->Required = TRUE;

			$objAdapters = array('MySql','MySqli','MySqli5','PostgreSql');
			$this->lstAdapter = new QListBox($this);
			$this->lstAdapter->Required = TRUE;
			$this->lstAdapter->Name = "Adapter";
			foreach($objAdapters as $strAdapter) {
				$blnSelected = FALSE;
				if($strAdapter == $this->objSetting['adapter']) 
					$blnSelected = TRUE;
				$this->lstAdapter->AddItem($strAdapter,$strAdapter,$blnSelected);
			}

			$this->txtServer = new QTextBox($this);
			$this->txtServer->Name = t('Server');
			$this->txtServer->Instructions = t('IP Address or Server name of the server hosting your database.  Can be localhost');
			$this->txtServer->Text = ($this->objSetting['server']?$this->objSetting['server']:'localhost');
			$this->txtServer->Required = TRUE;

			$this->txtPort = new QTextBox($this);
			$this->txtPort->Name = t('Port');
			$this->txtPort->Instructions = t('Database Port Number.');
			$this->txtPort->Text = ($this->objSetting['port']?$this->objSetting['port']:'3306');
			$this->txtPort->Required = TRUE;

			$this->txtDbName = new QTextBox($this);
			$this->txtDbName->Name = t('Database Name');
			$this->txtDbName->Instructions = t('The name of your database');
			$this->txtDbName->Text = $this->objSetting['dbname'];
			$this->txtDbName->Required = TRUE;

			$this->txtUsername = new QTextBox($this);
			$this->txtUsername->Name = t('Username');
			$this->txtUsername->Instructions = t('Username with access to this database');
			$this->txtUsername->Text = $this->objSetting['username'];
			$this->txtUsername->Required = TRUE;

			$this->txtPassword = new QTextBox($this);
			$this->txtPassword->Name = t('Password');
			$this->txtPassword->Instructions = t('Password for database user');
			$this->txtPassword->Text = $this->objSetting['password'];
			$this->txtPassword->Required = TRUE;

			$this->lstProfiling = new QListBox($this);
			$this->lstProfiling->Name = "Profiling Enabled";
			$this->lstProfiling->Required = TRUE;
			$this->lstProfiling->AddItem('No',FALSE,$this->objSetting['profiling']?FALSE:TRUE);
			$this->lstProfiling->AddItem('Yes',TRUE,$this->objSetting['profiling']?TRUE:FALSE);

			$this->txtClassNamePrefix = new QTextBox($this);
			$this->txtClassNamePrefix->Name = t('Class Name Prefix');
			$this->txtClassNamePrefix->Text = $this->objSetting['classNamePrefix'];

			$this->txtClassNameSuffix = new QTextBox($this);
			$this->txtClassNameSuffix->Name = t('Class Name Suffix');
			$this->txtClassNameSuffix->Text = $this->objSetting['classNameSuffix'];

			$this->txtAssociatedObjectNamePrefix = new QTextBox($this);
			$this->txtAssociatedObjectNamePrefix->Name = t('Associated Object Name Prefix');
			$this->txtAssociatedObjectNamePrefix->Text = $this->objSetting['associatedObjectNamePrefix'];

			$this->txtAssociatedObjectNameSuffix = new QTextBox($this);
			$this->txtAssociatedObjectNameSuffix->Name = t('Associated Object Name Suffix');
			$this->txtAssociatedObjectNameSuffix->Text = $this->objSetting['associatedObjectNameSuffix'];

			$this->txtTypeTableIdentifierSuffix = new QTextBox($this);
			$this->txtTypeTableIdentifierSuffix->Name = t('Type Table Identifier Suffix');
			$this->txtTypeTableIdentifierSuffix->Text = $this->objSetting['typeTableIdentifierSuffix']?$this->objSetting['typeTableIdentifierSuffix']:'_type';

			$this->txtAssociationTableIdentifierSuffix = new QTextBox($this);
			$this->txtAssociationTableIdentifierSuffix->Name = t('Association Table Identifier Suffix');
			$this->txtAssociationTableIdentifierSuffix->Text = $this->objSetting['associationTableIdentifierSuffix']?$this->objSetting['associationTableIdentifierSuffix']:'_assoc';

			$this->txtExcludeTableList = new QTextBox($this);
			$this->txtExcludeTableList->Name = t('Exclude Table List');
			$this->txtExcludeTableList->Text = $this->objSetting['excludeTablesList'];

			$this->txtExcludeTablePattern = new QTextBox($this);
			$this->txtExcludeTablePattern->Name = t('Exclude Table Pattern');
			$this->txtExcludeTablePattern->Text = $this->objSetting['excludeTablesPattern'];

			$this->txtIncludeTableList = new QTextBox($this);
			$this->txtIncludeTableList->Name = t('Include Table List');
			$this->txtIncludeTableList->Text = $this->objSetting['includeTablesList'];

			$this->txtIncludeTablePattern = new QTextBox($this);
			$this->txtIncludeTablePattern->Name = t('Include Table Pattern');
			$this->txtIncludeTablePattern->Text = $this->objSetting['includeTablesPattern'];

			$this->txtRelationships = new QTextBox($this);
			$this->txtRelationships->Name = t('Relationships');
			$this->txtRelationships->Text = $this->objSetting['relationships'];

			$this->btnSubmit = new QButton($this);
			$this->btnSubmit->Text = "Save";
			$this->btnSubmit->AddAction(new QClickEvent(), new QServerAction('btnSubmit_Click'));

			$this->btnCancel = new QButton($this);
			$this->btnCancel->Text = "Cancel";
			$this->btnCancel->ActionParameter = $qdrupal_node->nid;
			$this->btnCancel->AddAction(new QClickEvent(), new QServerAction('btnCancel_Click'));
		}

		protected function btnCancel_Click($strFormId, $strControlId, $strParameter) {
			drupal_goto('node/'.$strParameter);
		}

		protected function Form_Validate() {
			// TODO - Verify we can connect to the database
		}

		protected function btnSubmit_Click($strFormId, $strControlId, $strParameter) {
			$this->objSetting['name'] = $this->txtProfile->Text;

			$this->objSetting['adapter'] = $this->lstAdapter->SelectedName;
			$this->objSetting['server'] = $this->txtServer->Text;
			$this->objSetting['port'] = $this->txtPort->Text;
			$this->objSetting['dbname'] = $this->txtDbName->Text;
			$this->objSetting['username'] = $this->txtUsername->Text;
			$this->objSetting['password'] = $this->txtPassword->Text;
			$this->objSetting['profiling'] = ($this->lstProfiling->SelectedName == 'No'?FALSE:TRUE);

			$this->objSetting['classNamePrefix'] = $this->txtClassNamePrefix->Text;
			$this->objSetting['classNameSuffix'] = $this->txtClassNameSuffix->Text;
			$this->objSetting['associatedObjectNamePrefix'] = $this->txtAssociatedObjectNamePrefix->Text;
			$this->objSetting['associatedObjectNameSuffix'] = $this->txtAssociatedObjectNameSuffix->Text;
			$this->objSetting['typeTableIdentifierSuffix'] = $this->txtTypeTableIdentifierSuffix->Text;
			$this->objSetting['associationTableIdentifierSuffix'] = $this->txtAssociationTableIdentifierSuffix->Text;
			$this->objSetting['excludeTablesList'] = $this->txtExcludeTableList->Text;
			$this->objSetting['excludeTablesPattern'] = $this->txtExcludeTablePattern->Text;
			$this->objSetting['includeTablesList'] = $this->txtIncludeTableList->Text;
			$this->objSetting['includeTablesPattern'] = $this->txtIncludeTablePattern->Text;
			$this->objSetting['relationships'] = $this->txtRelationships->Text;

			global $qdrupal_node;
			qdrupal_settings_save($qdrupal_node->nid,$this->objSetting);

			drupal_goto('node/'.$qdrupal_node->nid.'/databases');
		}
	}
