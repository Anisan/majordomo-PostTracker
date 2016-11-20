<?php
require_once("IProvider.php");
class Gdeposylka implements IProvider
{
    private $apikey;
    function Gdeposylka($apikey) {
        $this->apikey = $apikey;
    }
    
    public function getStatus($track)
    {
        return "";
    }
}

?>