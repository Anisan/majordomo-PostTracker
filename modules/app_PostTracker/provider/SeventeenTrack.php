<?php
require_once("IProvider.php");

class SeventeenTrack implements IProvider
{
    private $tracker_url = 'http://www.17track.net/restapi/handlertrack.ashx';
    private $headers = [
        'Accept'=>  '*/*',
        'Content-Type'=>  'application/x-www-form-urlencoded; charset=UTF-8',
        'Origin'=>  'http://www.17track.net',
        'Referer'=>  'http://www.17track.net/pt/track?nums=&fc=0',
        'User-Agent'=>  'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.87 Safari/537.36',
        'X-Requested-With'=>  'XMLHttpRequest'
        ];
    
    function __construct($apikey, $domain) {

    }
    
    public function getStatus($track)
    {
        $val = json_encode(
            ['guid' => '',
             'data' => [['num' => trim($track), 'fc' => 100000],],
            ]); 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$this->tracker_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $val);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $server_output = curl_exec ($ch);
        curl_close ($ch);
        $res = array();
        
        $data = json_decode($server_output,true);
        //print_r($data['dat'][0]);
        $events = $data['dat'][0]['track']['z1'];
        //print_r($events);
        foreach($events as $event) {
            $status = array();
            $status['DATE_STATUS'] = strtotime($event['a']);
            $status['STATUS_INFO'] = $event['z'];
            $status['LOCATION'] = $event['c'];
            array_push($res,$status);
        }
        return $res;
    }
}

?>