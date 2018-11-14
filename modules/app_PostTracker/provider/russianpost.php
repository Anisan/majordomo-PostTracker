<?php
require_once("IProvider.php");

class RussianPost implements IProvider
{
	public $debug;
    
	function __construct($login, $password, $lang = "RUS")
	{
		$this->login = $login;
		$this->password = $password;
		$this->lang = $lang;
		$this->client = new SoapClient("https://tracking.russianpost.ru/rtm34?wsdl",  array('trace' => 1, 'soap_version' => SOAP_1_2));
	}
	
    public function addTrack($rec)
    {
    }
    public function delTrack($rec)
    {
    }
    public function archiveTrack($rec)
    {
    }
    public function unarchiveTrack($rec)
    {
    }


	public function getStatus($track)
    {
        $res = array();
        
		$response = $this->client->__doRequest(
			'<?xml version="1.0" encoding="UTF-8"?>
                <soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:oper="http://russianpost.org/operationhistory" xmlns:data="http://russianpost.org/operationhistory/data" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Header/>
                <soap:Body>
                   <oper:getOperationHistory>
                      <data:OperationHistoryRequest>
                         <data:Barcode>'.$track.'</data:Barcode>  
                         <data:MessageType>0</data:MessageType>
                         <data:Language>'.$this->lang.'</data:Language>
                      </data:OperationHistoryRequest>
                      <data:AuthorizationHeader soapenv:mustUnderstand="1">
                         <data:login>'.$this->login.'</data:login>
                         <data:password>'.$this->password.'</data:password>
                      </data:AuthorizationHeader>
                   </oper:getOperationHistory>
                </soap:Body>
             </soap:Envelope>',
			 "https://tracking.russianpost.ru/rtm34",
			 "getOperationHistory",
			 SOAP_1_2
		);
		
		
        if ($this->debug)
            echo 'RussianPost:'.$response."<br>";
		
		$xml = simplexml_load_string($response);
        $error =  $xml->children('S', true)->Body->Fault;
		if($error)
		{
			$error_title = $error->Reason->Text;
			
			$error_text = false;
			$error = $error->Detail->children('ns3', true);
			$error_text = $error->OperationHistoryFaultReason ? $error->OperationHistoryFaultReason : $error_text;
			$error_text = $error->AuthorizationFaultReason ? $error->AuthorizationFaultReason : $error_text;
			$error_text = $error->LanguageFaultReason ? $error->LanguageFaultReason : $error_text;
			
			$error_text = $error_text ? $error_text : $response;
			$error_title = $error_title ? $error_title : "Unknown error";
            if ($this->debug)
                echo 'RussianPost:'.$error_title.": ".$error_text."<br>";
			return $res;
		}
		
		$rows = $xml->children('S', true)->Body->children('ns7', true)->getOperationHistoryResponse->children('ns3', true)->OperationHistoryData->historyRecord;
		foreach($rows as $rec) {
            $status = array();
            $status['DATE_STATUS'] = strtotime((string) $rec->OperationParameters->OperDate);
            $status['STATUS_INFO'] = (string) $rec->OperationParameters->OperAttr->Name;
            $status['LOCATION_ZIP'] = (string) $rec->AddressParameters->OperationAddress->Index;
            $status['LOCATION'] = (string) $rec->AddressParameters->OperationAddress->Description;
            array_push($res,$status);
        }
        return $res;
	}
	

}