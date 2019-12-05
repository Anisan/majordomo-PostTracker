<?php
require_once("IProvider.php");

class Track24 implements IProvider
{
    public $debug;
    
    private $url_api = "https://api.track24.ru/tracking.json.php?";
    private $apikey;
    private $domain;
    
    function __construct($apikey, $domain) {
        $this->apikey = $apikey;
        $this->domain = $domain;
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
        
        $url = $this->url_api."apiKey=".$this->apikey."&domain=".$this->domain."&code=".$track;
        $json = file_get_contents($url);
        if ($this->debug)
            echo 'Track24:'.$json."<br>";
        
        $data = json_decode($json,true);
        
        $events = $data['data']['groupedEvents'][0];
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
        $result['statuses'] = $res;
        return $result;
    }
}

?>