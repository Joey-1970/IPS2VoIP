<?
    // Klassendefinition
    class IPS2VoIPSplitter extends IPSModule 
    {  
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		// {A4224A63-49EA-445F-8422-22EF99D8F624}
		//$this->ConnectParent("{A4224A63-49EA-445F-8422-22EF99D8F624}");
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("VoIP_InstanceID", 0);
		
		$this->RegisterVariableString("State", "Status", "", 10);
		
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
		$arrayElements[] = array("type" => "Label", "label" => "IP-Symcon VoIP-Instanz"); 
		$arrayElements[] = array("type" => "SelectInstance", "name" => "VoIP_InstanceID", "caption" => "VoIP-Instanz");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Prüfen des ausgeählten Parents
			$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
			$CheckParentModuleID = $this->CheckParentModuleID($VoIP_InstanceID);
			
			If ($CheckParentModuleID == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}	
	}
	
	public function ForwardData($JSONString) 
	{
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = false;
	 	switch ($data->Function) {
			case "Connect":
				$Result = $this->Connect($data->DeviceNumber);
				break;
			case "Disconnect":
				$this->Disconnect($data->ConnectionID);
				break;
			
		}
	return $Result;
	}
	    
	// Beginn der Funktionen
	public function CallMonitor(string $Data)
	{
		$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");

		if($_IPS["SENDER"] == "VoIP") {
			$_IPS = unserialize($Data);
		    	
			$Number = preg_replace('/[^0-9*]/', '', VoIP_GetConnection($VoIP_InstanceID, $_IPS["CONNECTION"])["Number"]); 
			If ($Number == "") {
				$Number = "unbekannt";
			}
			// Ausgehender Anruf
		    	if(VoIP_GetConnection($VoIP_InstanceID, $_IPS["CONNECTION"])["Direction"] == 1 /* Ausgehend */) {
				SetValueString($this->GetIDForIdent("State"), "Ausgehender Anruf: ".$Number);
				$this->SendDebug("CallMonitor", "Ausgehender Anruf: ".$Number, 0);
			   	switch($_IPS["EVENT"]) {
					case "Connect":
						SetValueString($this->GetIDForIdent("State"), "Es wurde eine Verbindung zu ".$Number." aufgebaut");
						$this->SendDebug("CallMonitor", "Es wurde eine Verbindung zu ".$Number." aufgebaut", 0);
						break;

					case "Disconnect":
						SetValueString($this->GetIDForIdent("State"), "Es wurde eine Verbindung beendet");
						$this->SendDebug("CallMonitor", "Es wurde eine Verbindung beendet", 0);
						break;

					default:
						$this->SendDebug("CallMonitor", "Ein unbekanntes Event ".$_IPS["EVENT"]." wurde ausgelöst", 0);
						break;
				}
		    	}
			else {
				// Eingehender Anruf
				switch($_IPS["EVENT"]) {
					case "Incoming":
						SetValueString($this->GetIDForIdent("State"), "Eingehender Anruf von: ".$Number);
						$this->SendDebug("CallMonitor", "Eingehender Anruf von: ".$Number, 0);
						break;

					case "Connect":
						SetValueString($this->GetIDForIdent("State"), "Es wurde eine Verbindung zu ".$Number." aufgebaut");
						$this->SendDebug("CallMonitor", "Es wurde eine Verbindung zu ".$Number." aufgebaut", 0);
						break;

					case "Disconnect":
						SetValueString($this->GetIDForIdent("State"), "Es wurde eine Verbindung beendet");
						$this->SendDebug("CallMonitor", "Es wurde eine Verbindung beendet", 0);
						break;

					case "DTMF":
						SetValueString($this->GetIDForIdent("State"), "Es wurde ein DTMF Signal empfangen");
						$this->SendDebug("CallMonitor", "Es wurde ein DTMF Signal empfangen", 0);

						switch($_IPS["DATA"]) {
							case '1':
							case '2':
							case '3':
							case '4':
							case '5':
							case '6':
							$this->SendDebug("CallMonitor", "Es wurde eine der Tasten 1 bis 6 gedrückt", 0);
							break;

							case '#':
								$this->SendDebug("CallMonitor", "Es wurde die Taste # gedrückt", 0);
								break;

							default:
								$this->SendDebug("CallMonitor", "Es wurde die Taste ". $_IPS["DATA"] ." gedrückt", 0);
								break;
						}
						break;

					case "PlayFinish":
						$this->SendDebug("CallMonitor", "Es wurde eine Sounddatei abgespielt", 0);
						break;

					default:
						$this->SendDebug("CallMonitor", "Ein unbekanntes Event ".$_IPS["EVENT"]." wurde ausgelöst", 0);
						break;
				}
			}
		}
	}
	
	private function Connect(string $DeviceNumber)
	{
		$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
		$ConnectionID = VoIP_Connect($VoIP_InstanceID, $DeviceNumber);
	return $ConnectionID;
	}
	    
	private function Disconnect(int $ConnectionID)
	{
		$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");
		VoIP_Disconnect($VoIP_InstanceID, $ConnectionID);
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
				Echo "Fehlerhafte VoIP-Schnittstelle! \n(keine korrekte VoIP-Instanz)\n";
			}
		}
		else {
			Echo "Fehlende VoIP-Schnittstelle! \n";
		}
	return $Result;
	}
}
?>
