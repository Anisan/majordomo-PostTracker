<?php
require_once("IProvider.php");

class Track24 implements IProvider
{
    private $url_api = "https://track24.ru/api/tracking.json.php?";
    private $apikey;
    private $domain;
    
    function __construct($apikey, $domain) {
        $this->apikey = $apikey;
        $this->domain = $domain;
    }
    
    public function getStatus($track)
    {
        $res = array();
        
        $url = $this->url_api."apiKey=".$this->apikey."&domain=".$this->domain."&code=".$track;
        $json = file_get_contents($url);
        $data = json_decode($json,true);
        
        $events = $data['data']['events'];
        //print_r($events);
        foreach($events as $event) {
            $status = array();
            $status['PROVIDER_ID'] = $event['id'];
            $status['DATE_STATUS'] = strtotime($event['operationDateTime']);
            $status['STATUS_INFO'] = $event['operationAttribute'];
            $status['LOCATION_ZIP'] = $event['operationPlacePostalCode'];
            $status['LOCATION'] = $event['operationPlaceName'];
            array_push($res,$status);
        }
        return $res;
    }
}

?>