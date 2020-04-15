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
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(202);
			}
		}
		else {
			$this->SetStatus(104);
		}	
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	    
	// Beginn der Funktionen
	public function CallMonitor(string $Data)
	{
		$VoIP_InstanceID = $this->ReadPropertyInteger("VoIP_InstanceID");

		if($_IPS["SENDER"] == "VoIP") {
			$_IPS = unserialize($Data);
		    	// Wir wollen nur eingehende Anrufe verarbeiten
		    	if(VoIP_GetConnection($VoIP_InstanceID, $_IPS["CONNECTION"])["Direction"] == 1 /* Ausgehend */) {
				$this->SendDebug("CallMonitor", "Ausgehender Anruf", 0);
			    	return;
		    	}

		    	switch($_IPS["EVENT"]) {
				case "Incoming":
					$this->SendDebug("CallMonitor", "Eingehender Anruf", 0);
			    		break;

				case "Connect":
					$this->SendDebug("CallMonitor", "Es wurde eine Verbindung aufgebaut", 0);
			    		break;

				case "Disconnect":
					$this->SendDebug("CallMonitor", "Es wurde eine Verbindung beendet", 0);
			    		break;

				case "DTMF":
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
