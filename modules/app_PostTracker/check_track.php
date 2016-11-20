<?php

// change dir to root folder
chdir(dirname(__FILE__) . '/../..');

// load config & libs
include_once("./config.php");
include_once("./lib/loader.php");

// load postoffice class
require_once(DIR_MODULES . '/app_PostTracker/app_PostTracker.class.php');

// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME); 

try 
{
   $PostTracker = new app_PostTracker();
   $result = $PostTracker->updateStatuses();
   if (!$result)
      throw new Exception("Check track error");
}
catch(Exception $e)
{
   echo "PostTracker Error: " . $e->getMessage();
}

?>