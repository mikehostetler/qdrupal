<?php

	class QDrupalCodegen extends QForm {

		protected $btnRunCodegen;
		protected $lblCodegenOutput;

		protected function Form_Create() {
			$this->btnRunCodegen = new QButton($this);
			$this->btnRunCodegen->Text = "Run Codegen";
			$this->btnRunCodegen->AddAction(new QClickEvent(), new QServerAction('btnRunCodegen_Click'));

			$this->lblCodegenOutput = new QLabel($this);
			$this->lblCodegenOutput->HtmlEntities = FALSE;
		}

		protected function btnRunCodegen_Click($strFormId,$strControlId,$strPrepend) {
			$this->lblCodegenOutput->Text = qdrupal_run_codegen();
		}
	}
