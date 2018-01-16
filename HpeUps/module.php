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
		$this->RegisterVariableString("BotvacSerial", "Robot Serial Number");
		$this->RegisterVariableString("BotvacModel", "Robot Model");
		$this->RegisterVariableString("BotvacName", "Robot Name");
		$this->RegisterVariableString("BotvacFirmware", "Robot Firmware Version");
		$this->RegisterVariableString("BotvacExtStatus", "Robot Extended Status");
		$this->RegisterVariableBoolean("BotvacStatus", "Robot Power Status","~Switch");
		$this->RegisterVariableBoolean("BotvacEcoMode", "Robot Eco Mode", "~Switch");
		$this->RegisterVariableBoolean("BotvacDocked", "Robot is Docked");
		$this->RegisterVariableInteger("BotvacCharge", "Battery Level", "~Intensity.100");

		// Timer
		$this->RegisterTimer("UpdateRobotStatus", 0, 'BOTVAC_RefreshInformation($_IPS[\'TARGET\']);');
 
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {

		// Check if there is a Botvac Secret. If not we need to handle the API authentication
		if (! $this->ReadPropertyString("BotvacSecret") ) {
		
			// If there is no Authentication token we need to generate one first
			if (! $this->ReadPropertyString("AuthToken") ) {
			
				$NeatoClient = new NeatoBotvacClient(false, $this->ReadPropertyString("BotvacVendor") );
	                        $AuthToken = $NeatoClient->authorize($this->ReadPropertyString("Username"), $this->ReadPropertyString("Password") );

				if (! $AuthToken) {
				
					IPS_LogMessage($_IPS['SELF'],"BOTVAC - Authentication with username and password was not possible");
				}
				else {
				
					IPS_SetProperty($_IPS['SELF'],"AuthToken",$AuthToken);
				}
			}
		}
		

            // Diese Zeile nicht löschen
            parent::ApplyChanges();
        }


	public function GetConfigurationForm() {

        	
		// Initialize the form
		$form = Array(
            		"elements" => Array(),
			"actions" => Array()
        		);

		// If we do not have a secret we also have no robot information. So we need to display formst to ask for that first.
		if ( ! $this->ReadPropertyString("BotvacSecret") ) {

			// If there is no username and password stored we need to ask for it
			if ( ! ( ($this->ReadPropertyString("Username") ) && (! $this->ReadProperty("Password") ) ) ) {
		
				$form['elements'][] = Array("type" => "Label", "label" => "No robot API secret is stored in the module. Please provide your account details to retrieve them:");
				$form['elements'][] = Array("type" => "ValidationTextBox", "name" => "Username", "caption" => "Username");
				$form['elements'][] = Array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
			
				// Now we need an array of vendors
				$BotvacVendorOptions = Array();
				$BotvacVendorOptions[] = Array("label" => "Neato", "value" => "neato");
				$BotvacVendorOptions[] = Array("label" => "Vorwerk", "value" => "vorwerk");
				$form['elements'][] = Array("type" => "Select", "name" => "BotvacVendor", "caption" => "Select Vendor", "options" => $BotvacVendorOptions);

				$form['elements'][] = Array("type" => "Label", "label" => "Please click on Apply to retrieve a list of robots");

				// Stop processing for now and return the form
				return json_encode($form);
			}
		}

		if (! $this->GetBuffer('AuthToken') ) {


			$NeatoClient = new NeatoBotvacClient(false, $this->ReadPropertyString("BotvacVendor") );
			$AuthToken = $NeatoClient->authorize($this->ReadPropertyString("Username"), $this->ReadPropertyString("Password") );

                        if ($AuthToken) {

                                $this->SetBuffer('AuthToken', $AuthToken);
                        }

		}
		else {

			$NeatoClient = new NeatoBotvacClient($this->GetBuffer('AuthToken'), $this->ReadPropertyString("BotvacVendor") );

			$form['elements'][] = Array("type" => "Label", "label" => "An Authentication Token was found");

			// Now we need a list of robots
			$robots = Array();
			$robotSelectOptions = Array();

			$result = $NeatoClient->getRobots();

			$robots = $result['robots'];

			foreach ($robots as $robot) {
				
				$robotSelectOptions[] = Array("label" => $robot['name'], "value" => $robot['serial']);
			}

			$form['elements'][] = Array("type" => "Select", "name" => "Robot", "caption" => "Select Robot", "options" => $robotSelectOptions);


			// Fill the variables when a Robot is selcted
			if ($this->ReadPropertyString("Robot") ) {

				SetValue($this->GetIDForIdent("BotvacSerial"), $this->ReadPropertyString("Robot") );

				foreach ($robots as $currentRobot) {

					if ($currentRobot['serial'] == $this->ReadPropertyString("Robot")) {

						SetValue($this->GetIDForIdent("BotvacSecret"), $currentRobot["secret_key"]);
						SetValue($this->GetIDForIdent("BotvacName"), $currentRobot["name"]);
						SetValue($this->GetIDForIdent("BotvacModel"), $currentRobot["model"]);
					}
				}
			}

		}

		if (GetValue($this->GetIDForIdent("BotvacSecret") ) != "" ) {
	
			$form['elements'][] = Array("type" => "Label", "label" => "A Robot with name " . GetValue($this->GetIDForIdent("BotvacName")) .  " and serial number " . GetValue($this->GetIDForIdent("BotvacSerial")) . " was successfully registered");
		}
	
		// Add a number spinner to select the refresh cycle
		$form['elements'][] = Array("type" => "NumberSpinner", "name" => "RefreshInterval", "caption" => "Select Refresh Interval");

		// Add the buttons for the test center
		$form['actions'][] = Array("type" => "Button", "label" => "Refresh Robot Data", "onClick" => 'BOTVAC_RefreshInformation($id);');
		$form['actions'][] = Array("type" => "Button", "label" => "Start Cleaning", "onClick" => 'BOTVAC_StartCleaning($id);');
		$form['actions'][] = Array("type" => "Button", "label" => "Pause Cleaning", "onClick" => 'BOTVAC_PauseCleaning($id);');
		$form['actions'][] = Array("type" => "Button", "label" => "Resume Cleaning", "onClick" => 'BOTVAC_ResumeCleaning($id);');
		$form['actions'][] = Array("type" => "Button", "label" => "Send to Base", "onClick" => 'BOTVAC_SendToBase($id);');

		// Return the completed form
		return json_encode($form);

	}


        /**
	* Get the list of robots linked to this profile and modifies the Select list to allow the user to select them.
        *
        */
        public function RefreshInformation() {

		$NeatoRobot = new NeatoBotvacRobot(GetValue($this->GetIDForIdent("BotvacSerial")), GetValue($this->GetIDForIdent("BotvacSecret")), GetValue($this->GetIDForIdent("BotvacModel")) );	

		$robotInformation = $NeatoRobot->getState();

		SetValue($this->GetIDForIdent("BotvacFirmware"), $robotInformation["meta"]["firmware"]);
		SetValue($this->GetIDForIdent("BotvacDocked"), $robotInformation["details"]["isDocked"]);
		SetValue($this->GetIDForIdent("BotvacCharge"), $robotInformation["details"]["charge"]);

		if ($robotInformation["details"]["isDocked"] == 1) {

			SetValue($this->GetIDForIdent("BotvacExtStatus"), "In Docking Station");
		}
        }

	public function StartCleaning() {

		$NeatoRobot = new NeatoBotvacRobot(GetValue($this->GetIDForIdent("BotvacSerial")), GetValue($this->GetIDForIdent("BotvacSecret")), GetValue($this->GetIDForIdent("BotvacModel")) );	

		// Check if EcoMode is activated
		if (GetValue($this->GetIDForIdent("BotvacEcoMode") ) ) {

			$NeatoRobot->startEcoCleaning();
			SetValue($this->GetIDForIdent("BotvacExtStatus"), "Cleaning in Eco-Mode");
		}
		else {
	
			$NeatoRobot->startCleaning();
			SetValue($this->GetIDForIdent("BotvacExtStatus"), "Cleaning");
		}
	}

	public function PauseCleaning() {

		$NeatoRobot = new NeatoBotvacRobot(GetValue($this->GetIDForIdent("BotvacSerial")), GetValue($this->GetIDForIdent("BotvacSecret")), GetValue($this->GetIDForIdent("BotvacModel")) );	
		$NeatoRobot->pauseCleaning();
		SetValue($this->GetIDForIdent("BotvacExtStatus"), "Paused");
	}

	public function ResumeCleaning() {

		$NeatoRobot = new NeatoBotvacRobot(GetValue($this->GetIDForIdent("BotvacSerial")), GetValue($this->GetIDForIdent("BotvacSecret")), GetValue($this->GetIDForIdent("BotvacModel")) );	

		// Check if EcoMode is activated
		if (GetValue($this->GetIDForIdent("BotvacEcoMode") ) ) {

			$NeatoRobot->resumeCleaning();
			SetValue($this->GetIDForIdent("BotvacExtStatus"), "Cleaning in Eco-Mode");
		}
		else {
	
			$NeatoRobot->resumeCleaning();
			SetValue($this->GetIDForIdent("BotvacExtStatus"), "Cleaning");
		}
	}

	public function SendToBase() {

		$NeatoRobot = new NeatoBotvacRobot(GetValue($this->GetIDForIdent("BotvacSerial")), GetValue($this->GetIDForIdent("BotvacSecret")), GetValue($this->GetIDForIdent("BotvacModel")) );	
		$NeatoRobot->sendToBase();
		SetValue($this->GetIDForIdent("BotvacExtStatus"), "Back to Base");
	}

    }
?>
