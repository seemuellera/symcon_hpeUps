<?php

	// Include the snmp library
	require ('libs/snmp.php');

    // Klassendefinition
    class HpeUps extends IPSModule {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            
		// Diese Zeile nicht löschen.
            	parent::Create();

		// Properties
		$this->RegisterPropertyString("Sender","SymconHpeUps");
		$this->RegisterPropertyString("Hostname","");
		$this->RegisterPropertyString("Community","");
		$this->RegisterPropertyInteger("RefreshInterval",5);

		// Variables
		$this->RegisterVariableString("HpeUpsMgmtFW", "Management Module Firmware Version");
		$this->RegisterVariableString("HpeUpsMgmtHW", "Management Module Hardware Version");
		$this->RegisterVariableString("HpeUpsMgmgPartNr", "Management Module Part Number");
		$this->RegisterVariableString("HpeUpsMgmgSerialNr", "Management Module Serial Number");
		$this->RegisterVariableString("HpeUpsSW", "UPS Software Version");
		$this->RegisterVariableString("HpeUpsModel", "UPS Model");
		$this->RegisterVariableInteger("HpeUpsBatTimeRemaining", "Remaining battery time");
		$this->RegisterVariableInteger("HpeUpsBatVoltage", "Battery voltage");
		$this->RegisterVariableInteger("HpeUpsBatCapacity", "Battery capacity");
		$this->RegisterVariableInteger("HpeUpsBatAbmStatus", "Battery status");
		$this->RegisterVariableFloat("HpeUpsInputFrequency", "Input frequency");
		$this->RegisterVariableInteger("HpeUpsInputLineBads", "Number of times input power was out of tolerance");
		$this->RegisterVariableInteger("HpeUpsInputVoltage", "Input voltage");
		$this->RegisterVariableInteger("HpeUpsInputCurrent", "Input current");
		$this->RegisterVariableInteger("HpeUpsOutputLoad", "Output Load");
		$this->RegisterVariableFloat("HpeUpsOutputFrequency", "Output frequency");
		$this->RegisterVariableInteger("HpeUpsOutputVoltage", "Output voltage");
		$this->RegisterVariableInteger("HpeUpsOutputCurrent", "Output current");
		$this->RegisterVariableInteger("HpeUpsOutputPower", "Output power");
		$this->RegisterVariableInteger("HpeUpsOutputSource", "Output source");
		$this->RegisterVariableInteger("HpeUpsAmbientTemperature", "Ambient temperature");
		$this->RegisterVariableInteger("HpeUpsBatteryTestStatus", "Battery test status");

		// Timer
		$this->RegisterTimer("RefreshInformation", 0, 'HPEUPS_RefreshInformation($_IPS[\'TARGET\']);');
 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {

		$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
	        $this->SetTimerInterval("RefreshInformation", $newInterval);


            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }


	public function GetConfigurationForm() {

        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
			"actions" => Array()
        		);

		// Add the Elements
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Refresh Interval");
		$form['elements'][] = Array("type" => "ValidationTextBox", "name" => "Hostname", "caption" => "Hostname");
		$form['elements'][] = Array("type" => "PasswordTextBox", "name" => "Community", "caption" => "Community");

		// Add the buttons for the test center
                $form['actions'][] = Array("type" => "Button", "label" => "Refresh Overall Status", "onClick" => 'HPEUPS_RefreshInformation($id);');

		// Return the completed form
		return json_encode($form);

	}


        /**
	* Get the list of robots linked to this profile and modifies the Select list to allow the user to select them.
        *
        */
        public function RefreshInformation() {

		$oid_mapping_table['HpeUpsMgmtFW'] = '.1.3.6.1.4.1.232.165.1.2.3';
		$oid_mapping_table['HpeUpsMgmtHW'] = '.1.3.6.1.4.1.232.165.1.2.4';

		foreach (array_keys($oid_mapping_table) as $currentIdent) {
		
			$this->UpdateVariable($currentIdent, $oid_mapping_table[$currentIdent]);
		}
	}

	protected function UpdateVariable($varIdent, $oid) {
	
		$oldValue = GetValue($this->GetIDForIdent($varIdent));
		$newValue = $this->SnmpGet($oid);

		if ($newValue != $oldValue) {
		
			SetValue($this->GetIdForIdent($varIdent), $newValue);
		}
	}

	protected function SnmpGet($oid) {
	
		$snmp = new snmp();
		$snmp->version = SNMP_VERSION_2;

		$result = $snmp->bulk_get($this->ReadPropertyString("Hostname"), $oid, ['community' => $this->ReadPropertyString("Community") ] );

		$resultValue = reset($result);

		return $resultValue;
	}

    }
?>
