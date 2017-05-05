<?php
require_once("IProvider.php");
class Moyaposylka implements IProvider
{
    public $debug;
    
    private $tracker_url = 'https://moyaposylka.ru/api/v1/';
    private $apikey;
    private $headers;
    
    function __construct($apikey) {
        $this->apikey = $apikey;
        $this->headers = array();
        $this->headers[] = "X-Authorization-Token: $apikey";
    }
    
    public function query($url, $method)
    {
        if ($this->debug)
            echo 'Moyaposylka:'.$method." ".$url."<br>";
        $params = array(
            'countryCode' => 'RU'
        );

        $opts = array(
            'http' => array(
                'method' => $method,
                'header' => 'Content-Type: application/json' . "\n"
                    . 'X-Api-Key: '.$this->apikey,
                'content' => json_encode($params),
                'timeout' => 60,
                'ignore_errors' => false
            )
        );

        $context = stream_context_create($opts);
        $output = file_get_contents($url, false, $context);
        if ($this->debug)
            echo 'Moyaposylka:'.$output."<br>";
        return $output;
    }
    
    public function check_error($data)
    {
        if ($data['result']== 'error')
            echo $data['error']."\n";
        if ($data['result']!= 'success') return true;
        return false;
    }
    
    public function getCarrier($track)
    {
        $url = $this->tracker_url."carriers/".$track;
        $server_output = $this->query($url,"GET");
        //if ($this->check_error($server_output)) return "";
        $carriers = json_decode($server_output,true);
        return $carriers[0]["code"];
    }
    
    public function getStatus($track)
    {
        $res = array();
        
        $carrier = $this->getCarrier($track);
        if ($carrier == "") return $res;
        
        $url = $this->tracker_url."trackers/".$carrier."/".$track."/realtime";
        $output = $this->query($url,"POST");
        $output = $this->query($url,"GET");
        $data = json_decode($output,true);
        //if ($this->check_error($data)) return $res;
        
        $events = $data['events'];
        foreach($events as $event) {
                $status = array();
                $status['DATE_STATUS'] = $event['eventDate']/1000;
                $status['STATUS_INFO'] = $event['operation'];
                $status['LOCATION'] = $event['location'];
                $status['LOCATION_ZIP'] = $event['zipCode'];
                array_push($res,$status);
            }
        
        return $res;
    }
}

?>