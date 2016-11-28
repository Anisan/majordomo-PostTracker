<?php
require_once("IProvider.php");
class Gdeposylka implements IProvider
{
    private $tracker_url = 'https://gdeposylka.ru';
    private $apikey;
    private $headers;
    
    function Gdeposylka($apikey) {
        $this->apikey = $apikey;
        $this->headers = array();
        $this->headers[] = "X-Authorization-Token: $apikey";
    }
    
    public function query($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        $output = curl_exec ($ch);
        curl_close ($ch);
        return $output;
    }
    
    public function getTrackers($track)
    {
        $url = $this->tracker_url."/api/v4/tracker/detect/".$track;
        $server_output = $this->query($url);
        return json_decode($server_output,true);
    }
    
    public function getStatus($track)
    {
        $res = array();
        
        $trackers = $this->getTrackers($track);
        if ($trackers['result']!= 'success') return $res;
        
        $tu = $trackers['data'][0]['tracker_url'];
        
        $url = $this->tracker_url.$tu;
        $output = $this->query($url);
        $data = json_decode($output,true);
        
        if ($data['result']!= 'success') return $res;
        
        $events = $data['data']['checkpoints'];
        foreach($events as $event) {
                $status = array();
                $status['PROVIDER_ID'] = $event['id'];
                $status['DATE_STATUS'] = strtotime($event['time']);
                $status['STATUS_INFO'] = $event['status_name'];
                $status['LOCATION_ZIP'] = $event['location_zip_code'];
                $status['LOCATION'] = $event['location_translated'];
                array_push($res,$status);
            }
        
        return $res;
    }
}

?>