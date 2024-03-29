<?php

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
		$this->RegisterPropertyInteger("RefreshInterval",5);
		$this->RegisterPropertyInteger("SnmpInstance",0);

		// Variables
		$this->RegisterVariableBoolean("HpeUpsReachable","Management Module reachable", "~Alert.Reversed");
		$this->RegisterVariableString("HpeUpsMgmtFW", "Management Module Firmware Version");
		$this->RegisterVariableString("HpeUpsMgmtHW", "Management Module Hardware Version");
		$this->RegisterVariableString("HpeUpsMgmgPartNr", "Management Module Part Number");
		$this->RegisterVariableString("HpeUpsMgmgSerialNr", "Management Module Serial Number");
		$this->RegisterVariableString("HpeUpsSW", "UPS Software Version");
		$this->RegisterVariableString("HpeUpsModel", "UPS Model");
		$this->RegisterVariableInteger("HpeUpsBatTimeRemaining", "Remaining battery time");
		$this->RegisterVariableInteger("HpeUpsBatVoltage", "Battery voltage");
		$this->RegisterVariableInteger("HpeUpsBatCapacity", "Battery capacity","~Intensity.100");
		$this->RegisterVariableInteger("HpeUpsBatAbmStatus", "Battery status");
		$this->RegisterVariableFloat("HpeUpsInputFrequency", "Input frequency", "~Hertz");
		$this->RegisterVariableInteger("HpeUpsInputLineBads", "Number of times input power was out of tolerance");
		$this->RegisterVariableInteger("HpeUpsInputVoltage", "Input voltage");
		$this->RegisterVariableInteger("HpeUpsInputCurrent", "Input current");
		$this->RegisterVariableInteger("HpeUpsOutputLoad", "Output Load", "~Intensity.100");
		$this->RegisterVariableFloat("HpeUpsOutputFrequency", "Output frequency", "~Hertz");
		$this->RegisterVariableInteger("HpeUpsOutputVoltage", "Output voltage");
		$this->RegisterVariableInteger("HpeUpsOutputCurrent", "Output current");
		$this->RegisterVariableInteger("HpeUpsOutputPower", "Output power");
		$this->RegisterVariableInteger("HpeUpsOutputSource", "Output source");
		$this->RegisterVariableInteger("HpeUpsAmbientTemperature", "Ambient temperature","~Temperature.ZWave");
		$this->RegisterVariableInteger("HpeUpsBatteryTestStatus", "Battery test status");

		// Timer
		$this->RegisterTimer("RefreshInformation", 0, 'HPEUPS_RefreshInformation($_IPS[\'TARGET\']);');
 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {

			$newInterval = $this->ReadPropertyInteger("RefreshInterval") * 1000;
	        $this->SetTimerInterval("RefreshInformation", $newInterval);

			$this->RegisterReference($this->ReadPropertyInteger("SnmpInstance"));

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
		$form['elements'][] = Array("type" => "SelectInstance", "name" => "SnmpInstance", "caption" => "SNMP instance");

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

		$oid_mapping_table['HpeUpsMgmtFW'] = '1.3.6.1.4.1.232.165.1.2.3.0';
		$oid_mapping_table['HpeUpsMgmtHW'] = '1.3.6.1.4.1.232.165.1.2.4.0';
		$oid_mapping_table['HpeUpsMgmgPartNr'] = '1.3.6.1.4.1.232.165.1.2.5.0';
		$oid_mapping_table['HpeUpsMgmgSerialNr'] = '1.3.6.1.4.1.232.165.1.2.7.0';
		$oid_mapping_table['HpeUpsSW'] = '1.3.6.1.4.1.232.165.3.1.3.0';
		$oid_mapping_table['HpeUpsModel'] = '1.3.6.1.4.1.232.165.3.1.2.0';
		$oid_mapping_table['HpeUpsBatTimeRemaining'] = '1.3.6.1.4.1.232.165.3.2.1.0';
		$oid_mapping_table['HpeUpsBatVoltage'] = '1.3.6.1.4.1.232.165.3.2.2.0';
		$oid_mapping_table['HpeUpsBatCapacity'] = '1.3.6.1.4.1.232.165.3.2.4.0';
		$oid_mapping_table['HpeUpsBatAbmStatus'] = '1.3.6.1.4.1.232.165.3.2.5.0';
		$oid_mapping_table['HpeUpsInputFrequency'] = '1.3.6.1.4.1.232.165.3.3.1.0';
		$oid_mapping_table['HpeUpsInputLineBads'] = '1.3.6.1.4.1.232.165.3.3.2.0';
		$oid_mapping_table['HpeUpsInputVoltage'] = '1.3.6.1.4.1.232.165.3.3.4.1.2.1';
		$oid_mapping_table['HpeUpsInputCurrent'] = '1.3.6.1.4.1.232.165.3.3.4.1.3.1';
		$oid_mapping_table['HpeUpsOutputLoad'] = '1.3.6.1.4.1.232.165.3.4.1.0';
		$oid_mapping_table['HpeUpsOutputFrequency'] = '1.3.6.1.4.1.232.165.3.4.2.0';
		$oid_mapping_table['HpeUpsOutputVoltage'] = '1.3.6.1.4.1.232.165.3.4.4.1.2.1';
		$oid_mapping_table['HpeUpsOutputCurrent'] = '1.3.6.1.4.1.232.165.3.4.4.1.3.1';
		$oid_mapping_table['HpeUpsOutputPower'] = '1.3.6.1.4.1.232.165.3.4.4.1.4.1';
		$oid_mapping_table['HpeUpsOutputSource'] = '1.3.6.1.4.1.232.165.3.4.5.0';
		$oid_mapping_table['HpeUpsAmbientTemperature'] = '1.3.6.1.4.1.232.165.3.6.1.0';
		$oid_mapping_table['HpeUpsBatteryTestStatus'] = '1.3.6.1.4.1.232.165.3.7.2.0';
		
		$this->BulkUpdateVariables($oid_mapping_table);
	}

	protected function BulkUpdateVariables($mappingTable) {
	
		$allResults = $this->SnmpBulkGet($mappingTable);
		
		if (! $allResults) {
			
			$this->LogMessage("ERROR - Unable to fetch data via SNMP", KL_ERROR);
			return false;
		}
		
		$identLookupTable = array_flip ($mappingTable);

		foreach ($allResults as $resultOid => $resultValue) {
			
			$varIdent = $identLookupTable[$resultOid];
			
			if ( ($varIdent == "HpeUpsInputFrequency") || ($varIdent == "HpeUpsOutputFrequency") ){
			
				$newValue = $resultValue / 10;
			}
			else {
				
				$newValue = $resultValue;
			}

			SetValue($this->GetIdForIdent($varIdent), $newValue);
		}
	}
	
	protected function SnmpBulkGet($oids) {
	
		$result = IPSSNMP_ReadSNMP($this->ReadPropertyInteger("SnmpInstance"), $oids);
		
		if (count($result) == 0) {
			
			SetValue($this->GetIDForIdent("HpeUpsReachable"), false);
			return false;
		}
		
		SetValue($this->GetIDForIdent("HpeUpsReachable"), true);
		return $result;
	}

}