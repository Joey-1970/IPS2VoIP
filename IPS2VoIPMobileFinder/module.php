<?
    // Klassendefinition
    class IPS2VoIPMobileFinder extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}  
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		// {A4224A63-49EA-445F-8422-22EF99D8F624}
		//$this->ConnectParent("{A4224A63-49EA-445F-8422-22EF99D8F624}");
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("DeviceNumber", "");
		$this->RegisterPropertyInteger("VoIP_InstanceID", 0);
		$this->RegisterPropertyInteger("Timer_1", 3);
		$this->RegisterTimer("Timer_1", 0, 'IPS2VoIPMobileFinder_Disconnect($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterProfileInteger("IPS2VoIP.StartStop", "Telephone", "", "", 0, 1, 0);
		IPS_SetVariableProfileAssociation("IPS2VoIP.StartStop", 0, "Start", "Telephone", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2VoIP.StartStop", 1, "Stop", "Telephone", 0xFF0000);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("State", "Ruf", "IPS2VoIP.StartStop", 10);
		$this->EnableAction("State");
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Fehlerhafte Schnittstelle!");
				
		$arrayElements = array(); 
		
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceNumber", "caption" => "Telefonnummer");
		$arrayElements[] = array("type" => "SelectInstance", "name" => "VoIP_InstanceID", "caption" => "VoIP-Instanz");
		$arrayElements[] = array("type" => "Label", "label" => "Laufzeit des Klingelsignals"); 
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Timer_1", "caption" => "s");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayElements[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		SetValueInteger($this->GetIDForIdent("State"), 1);
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Prüfen des ausgeählten Parents
			$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
			$CheckParentModuleID = $this->CheckParentModuleID($VoIP_InstanceID);
			$DeviceNumber = $this->ReadPropertyString("DeviceNumber");
			$CheckDeviceNumber = $this->CheckDeviceNumber($DeviceNumber);
			
			If (($CheckParentModuleID == true) AND ($CheckDeviceNumber == true)) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(202);
			}
			$this->SetTimerInterval("Timer_1", 0);
		}
		else {
			$this->SetStatus(104);
			$this->SetTimerInterval("Timer_1", 0);
		}	
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "State":
			SetValueInteger($this->GetIDForIdent("State"), $Value);
	            	If ($Value == 0) {
				$this->Connect();
			}
			elseif ($Value == 1) {
				$this->Disconnect();
			}
		break;
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	    
	// Beginn der Funktionen
	private function Connect()
	{
  		$CurrentStatus = $this->GetStatus();
		If (($this->ReadPropertyBoolean("Open") == true) AND ($CurrentStatus == 102)) {
			$DeviceNumber = $this->ReadPropertyString("DeviceNumber");
			$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
			$Timer_1 = $this->ReadPropertyInteger("Timer_1");
			
			$ConnectionID = VoIP_Connect($VoIP_InstanceID, $DeviceNumber);
			$this->SetBuffer("ConnectionID", $ConnectionID);
			$this->SetTimerInterval("Timer_1", $Timer_1 * 1000);
		}
	}
	
	public function Disconnect()
	{
  		$CurrentStatus = $this->GetStatus();
		If (($this->ReadPropertyBoolean("Open") == true) AND ($CurrentStatus == 102)) {
			$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
			$ConnectionID = intval($this->GetBuffer("ConnectionID"));
			
			VoIP_Disconnect($VoIP_InstanceID, $ConnectionID);
			$this->SetTimerInterval("Timer_1", 0);
			$this->SetBuffer("ConnectionID", 0);
		}
	}
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	} 
	
	private function CheckParentModuleID(int $InstanceID)
	{
		$Result = false;
		If ($InstanceID >= 10000) {
			$ModuleID = (IPS_GetInstance($InstanceID)['ModuleInfo']['ModuleID']); 
			If ($ModuleID == "{A4224A63-49EA-445F-8422-22EF99D8F624}") {
				$Result = true;
			}
			else {
				Echo "Fehlerhafte Schnittstellen! \n(keine korrekte VoIP-Instanz\n)";
			}
		}
	return $Result;
	}
	    
	private function CheckDeviceNumber(string $DeviceNumber)
	{
		$Result = false;
		If (strlen($DeviceNumber > 0)) {
			if (preg_match("#^[0-9*]+$#", $DeviceNumber)) {
				$Result = true;
			}
			else {
				Echo "Fehlerhafte Telefonnummer! \n(zulässige Zeichen: 0-9 *)";
			}
		}
		else {
			Echo "Fehlende Telefonnummer! \n(zulässige Zeichen: 0-9 *)";
		}
	return $Result;
	}
}
?>
