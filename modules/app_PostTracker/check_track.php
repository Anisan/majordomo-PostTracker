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
set_time_limit(6000);
header("HTTP/1.0: 200 OK\n");
header('Content-Type: text/html; charset=utf-8');
echo "<html>";
echo "<body>"; 
try 
{
   $PostTracker = new app_PostTracker();
   $result = $PostTracker->updateStatuses(true);
}
catch(Exception $e)
{
   echo "PostTracker Error: " . $e->getMessage();
}
echo "</body>";
echo "</html>";
//echo str_repeat(' ', 1024);
flush();
flush();  
?>