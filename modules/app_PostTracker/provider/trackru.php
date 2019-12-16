<?php
require_once("IProvider.php");
class Trackru implements IProvider
{
    public $debug;
    
    private $tracker_url = 'https://api.trackru.ru/v1/';
    private $apikey;
    private $headers;
    
    function __construct($apikey) {
        $this->apikey = $apikey;
        $this->headers = array();
    }
    
    public function query($url, $method, $params = null)
    {
        if ($this->debug)
            echo 'Trackru:'.$method." ".$url."<br>";
        if (!$params)
        $params = array(
            'countryCode' => 'RU'
        );

        $opts = array(
            'http' => array(
                'method' => $method,
                'header' => 'Content-Type: application/json' . "\n"  . 'Api-Key: '.$this->apikey,
                'content' => json_encode($params),
                'timeout' => 60,
                'ignore_errors' => false
            )
        );

        $context = stream_context_create($opts);
        $output = file_get_contents($url, false, $context);
        
        if ($this->debug)
        {
            //DebMes($output,"Posttracker");
            echo 'Trackru:'.$output."<br>";
        }
        return $output;
    }
    
    public function check_error($data)
    {
        return false;
    }
    
    public function getCarrier($track)
    {
        $url = $this->tracker_url."carriers/detect";
        $params = array(
            'tracking_number' => $track
        );
        $server_output = $this->query($url,"POST",$params);
        //if ($this->check_error($server_output)) return "";
        $carriers = json_decode($server_output,true);
        return $carriers["data"][0]["code"];
    }
    
    public function addTrack($rec)
    {
        $track = $rec['TRACK'];
        $carrier = $this->getCarrier($track);
        if ($carrier == "") return;
        
        $url = $this->tracker_url."trackings/post";
        $params = array(
            'tracking_number' => $track,
            'carrier_code' => $carrier,
            'lang' => 'RU',
            'title' => $rec['NAME'],
            'comment' => $rec['DESCRIPTION']
        );
        $output = $this->query($url,"POST",$params);
        if ($this->debug) 
            echo ($output);
    }
    public function delTrack($rec)
    {
        $track = $rec['TRACK'];
        $carrier = $this->getCarrier($track);
        if ($carrier == "") return;
        
        $url = $this->tracker_url."trackings/".$carrier."/".$track;
        $output = $this->query($url,"DELETE");
        if ($this->debug) 
            echo ($output);
        
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
        
        $carrier = $this->getCarrier($track);
        if ($carrier == "") return $res;
        
        $url = $this->tracker_url."trackings/".$carrier."/".$track;
        $output = $this->query($url,"GET");
        $data = json_decode($output,true);
        //if ($this->check_error($data)) return $res;
        $events = array();
        if ($data["data"][0]["origin_info"]["trackinfo"]!=null)
            $events = array_merge($events,$data["data"][0]["origin_info"]["trackinfo"]);
        if ($data["data"][0]["destination_info"]["trackinfo"]!= null)
            $events = array_merge($events,$data["data"][0]["destination_info"]["trackinfo"]);
        foreach($events as $event) {
                $status = array();
                $status['DATE_STATUS'] = strtotime($event["Date"]);
                $status['STATUS_INFO'] = $event["StatusDescription"];
                $status['LOCATION'] = $event["Details"];
                array_push($res,$status);
            }
        
        $result = array();
        $result['carrier'] = $data["data"][0]["carrier_code"];
        $result['originCountry'] = $data["data"][0]["original_country"];
        $result['destinationCountry'] = $data["data"][0]["destination_country"];

        $result['statuses'] = $res;
        //DebMes(json_encode($result),"Posttracker");
        return $result;
    }
    
    public function getList()
    {
    }
    
}

?>