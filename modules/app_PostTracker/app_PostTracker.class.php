<?php
/**
* PostTracker 
* @package project
* @author Eraser <eraser1981@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 18:11:05 [Nov 18, 2016])
*/
//
//
class app_PostTracker extends module {
/**
* app_PostTracker
*
* Module class constructor
*
* @access private
*/
function __construct() {
  $this->name="app_PostTracker";
  $this->title="PostTracker";
  $this->module_category="<#LANG_SECTION_APPLICATIONS#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
    $this->getConfig();
    $out['GP_APIKEY'] = $this->config['GP_APIKEY'];
    $out['TR24_APIKEY'] = $this->config['TR24_APIKEY'];
    $out['TR24_DOMAIN'] = $this->config['TR24_DOMAIN'];
    $out['RP_LOGIN'] = $this->config['RP_LOGIN'];
    $out['RP_PASSWORD'] = $this->config['RP_PASSWORD'];
    $out['PROVIDER'] = $this->config['PROVIDER'];
    $out['SCRIPT_NEWSTATUS_ID'] = $this->config['SCRIPT_NEWSTATUS_ID'];
    $out['SCRIPT_DISPUTE_ID'] = $this->config['SCRIPT_DISPUTE_ID'];
    $out['SCRIPTS']=SQLSelect("SELECT ID, TITLE FROM scripts ORDER BY TITLE");
    if($this->data_source == 'app_PostTracker' || $this->data_source == '') {
        if($this->view_mode == 'update_settings') {
            global $provider;
            $this->config['PROVIDER'] = $provider;
            global $gp_apikey;
            $this->config['GP_APIKEY'] = $gp_apikey;
            global $rp_login;
            $this->config['RP_LOGIN'] = $rp_login;
            global $rp_password;
            $this->config['RP_PASSWORD'] = $rp_password;
            global $tr24_apikey;
            $this->config['TR24_APIKEY'] = $tr24_apikey;
            global $tr24_domain;
            $this->config['TR24_DOMAIN'] = $tr24_domain;
            global $script_newstatus_id;
            $this->config['SCRIPT_NEWSTATUS_ID'] = $script_newstatus_id;
            global $script_dispute_id;
            $this->config['SCRIPT_DISPUTE_ID'] = $script_dispute_id;
            $this->saveConfig();
            $this->redirect("?");
        }
        
        
    }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
    global $track_info;
    if ($track_info)
    {
        $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $track_info . "'");
        header("HTTP/1.0: 200 OK\n");
        header('Content-Type: text/html; charset=utf-8');
        echo json_encode($rec);
        exit;
    }
    if ($this->mode=='archive') {$out['VIEW_MODE']="archive";$this->view_mode = "archive";}
    if ($this->mode=='active') $out['VIEW_MODE']="";
    if ($this->mode=='add_track' || $this->mode=='edit_track') { 
        $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $this->id . "'");
        
        global $name;
        $rec['NAME']=$name;
        global $track;
        $rec['TRACK']=$track;
        global $track_url;
        $rec['TRACK_URL']=$track_url;
        global $waitday;
        $rec['WAIT_DAY']=$waitday;
        
        if ($rec['ID']) {
            SQLUpdate(pt_track, $rec); // update
        }
        else{
            $rec['CREATED'] = date ("Y-m-d H:i:s");
            $rec['LAST_DATE'] = date ("Y-m-d H:i:s");
            $rec['LAST_STATUS'] = "Start monitoring";
            $rec['ID']=SQLInsert("pt_track", $rec); // adding new record
            $status = array();
            $status['DATE_STATUS'] = date ("Y-m-d H:i:s");;
            $status['STATUS_INFO'] = "Add track code to module";
            $status['TRACK_ID'] = $rec['ID'];
            $status['PROVIDER'] = -1;
            SQLInsert("pt_status", $status);
            $this->exec_script_newstatus($rec,"");
            $this->updateStatusInit($rec);
        }
        $this->redirect("?");
    }
    else if ($this->mode=='del_track') {
        SQLExec("DELETE FROM pt_track WHERE ID='" . $this->id . "'");
        SQLExec("DELETE FROM pt_status WHERE TRACK_ID='" . $this->id . "'");
        $this->redirect("?");
    }else if ($this->mode=='switch_archive') {
        $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $this->id . "'");
        $status = array();
        $status['DATE_STATUS'] = date ("Y-m-d H:i:s");;
        $status['TRACK_ID'] = $rec['ID'];
        $status['PROVIDER'] = -1;
        if ($rec['ARCHIVE']==1)
        {
            $status['STATUS_INFO'] = "Unarchived (enable monitoring)";
            $rec['ARCHIVE']=0;
        }
        else
        {
            $status['STATUS_INFO'] = "Archived (disable monitoring)";
            $rec['ARCHIVE']=1;
        }
        SQLUpdate("pt_track", $rec);
        SQLInsert("pt_status", $status);
        $this->redirect("?view_mode=".$this->view_mode);
    }else if ($this->mode=='update_statuses') {
        $this->updateStatuses();
        $this->redirect("?");
    }else{
        // SEARCH RESULTS  
        if($this->view_mode == 'archive')
            $res=SQLSelect("SELECT *, '' as STATUSES FROM pt_track where ARCHIVE=1");
        else
            $res=SQLSelect("SELECT *, '' as STATUSES FROM pt_track where ARCHIVE=0");
        if ($res[0]['ID']) {  
            paging($res, 20, $out); // search result paging
            $total=count($res);
            for($i=0;$i<$total;$i++) {
                // some action for every record if required
                $res_info=SQLSelect("SELECT * FROM pt_status WHERE TRACK_ID='" . $res[$i]['ID'] . "' ORDER BY DATE_STATUS");
                $res[$i]['STATUSES'] = $res_info;
                $dayDispite = $res[$i]['CREATED'];
                if ($res_info[0]['ID'])
                {
                    $dayDispite = $res_info[0]['DATE_STATUS'];
                    $diff = time()- strtotime($res_info[0]['DATE_STATUS']);
                    $days = floor($diff / (24*60*60));
                    $res[$i]['SENDING_DAY'] = $days;
                }
                $res[$i]['DISPUTE_STATE'] = "success";
                if ($res[$i]['WAIT_DAY'])
                {
                    $diff = time()- strtotime($dayDispite);
                    $days = floor($diff / (24*60*60));
                    $disp = (int)$res[$i]['WAIT_DAY'] - $days;
                    if ($disp <= 20) $res[$i]['DISPUTE_STATE'] = "warning";
                    if ($disp <= 10) $res[$i]['DISPUTE_STATE'] = "danger";
                    $res[$i]['DISPUTE_DAY'] = $disp;
                }
            }
            $out['RESULT']=$res;
        }    
    }
}
//////////////////////////////////////////////
function updateStatuses() {
    $this->getConfig();

    $res=SQLSelect("SELECT * FROM pt_track where ARCHIVE=0");
    if ($res[0]['ID']) {  
        // init provider
        switch ($this->config['PROVIDER']) {
            case 0: // track24
                require_once("./modules/app_PostTracker/provider/track24.php");
                $provider = new Track24($this->config['TR24_APIKEY'],$this->config['TR24_DOMAIN']);
                break;
            case 1: // Gdeposylka
                require_once("./modules/app_PostTracker/provider/gdeposylka.php");
                $provider = new Gdeposylka($this->config['GP_APIKEY']);
                break;
            case 2: // RussianPost
                require_once("./modules/app_PostTracker/provider/russianpost.php");
                $provider = new RussianPost($this->config['RP_LOGIN'],$this->config['RP_PASSWORD']);
                break;
            case 3: // 17Track
                require_once("./modules/app_PostTracker/provider/SeventeenTrack.php");
                $provider = new SeventeenTrack();
                break;
        }
        
        $total=count($res);
        
        for($i=0;$i<$total;$i++) {
            $this->updateStatus($provider,$res[$i]);
        }
    }
}

function updateStatusInit($rec) {
    $this->getConfig();
    switch ($this->config['PROVIDER']) {
        case 0: // track24
                require_once("./modules/app_PostTracker/provider/track24.php");
                $provider = new Track24($this->config['TR24_APIKEY'],$this->config['TR24_DOMAIN']);
                break;
        case 1: // Gdeposylka
                require_once("./modules/app_PostTracker/provider/gdeposylka.php");
                $provider = new Gdeposylka($this->config['GP_APIKEY']);
                break;
        case 2: // RussianPost
                require_once("./modules/app_PostTracker/provider/russianpost.php");
                $provider = new RussianPost($this->config['RP_LOGIN'],$this->config['RP_PASSWORD']);
                break;
        case 3: // 17Track
                require_once("./modules/app_PostTracker/provider/SeventeenTrack.php");
                $provider = new SeventeenTrack();
                break;
    }
    $this->updateStatus($provider,$rec);
}

function updateStatus($provider,$rec) {
    echo $rec['TRACK']."\n";
    $statuses = $provider->getStatus($rec['TRACK']);
    // proc statuses
    //print_r($statuses);
    $last_status_info = "";
    $last_status_date = "";
    $location = "";
    foreach($statuses as $status) {
        $status['TRACK_ID'] = $rec['ID'];
        $status['PROVIDER'] = $this->config['PROVIDER'];
        $status['DATE_STATUS'] = date ("Y-m-d H:i:s", $status['DATE_STATUS']);
        // find old
        if (isset($status['PROVIDER_ID']))
            $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$rec['ID']." and PROVIDER_ID = '" . $status['PROVIDER_ID'] . "';");
        else
            $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$rec['ID']." and DATE_STATUS = '" . DBSafe($status['DATE_STATUS']) . "';");
        if (!$find)
        {
            echo 'Add new status '.$status['STATUS_INFO']."\n";
            //add new
            SQLInsert("pt_status", $status);
            // check date
            if ($status['DATE_STATUS']>$last_status_date)
            {
                $last_status_info = $status['STATUS_INFO'];
                $last_status_date = $status['DATE_STATUS'];
                $location = $status['LOCATION'];
            }
        }
    }
    $rec['LAST_CHECKED'] = date ("Y-m-d H:i:s");
            
    //exec last new state
    if ($last_status_info!="")
    {
        $rec['LAST_DATE'] = $last_status_date;
        $rec['LAST_STATUS'] = $last_status_info;
        //run script
        $this->exec_script_newstatus($rec,$location);
    }
    SQLUpdate('pt_track', $rec);
            
    //check to dispute 
    if ($rec['WAIT_DAY'] && $rec['LAST_DATE'])
    {
        $dayDispite = $rec['LAST_DATE'];
        $diff = time()- strtotime($dayDispite);
        $days = floor($diff / (24*60*60));
        $disp = (int)$rec['WAIT_DAY'] - $days;
        $start_day = date("Y-m-d");
        if ($disp < 7 && (strtotime($start_day)>strtotime($rec['LAST_SEND_WARNING'])))
        {                    
            // LAST_SEND_WARNING
            // exec script (one time on day)
            if ($this->config['SCRIPT_DISPUTE_ID']) {
                $params=array();
                $params['NAME']=$rec['NAME'];
                $params['TRACK']=$rec['TRACK'];
                $params['TRACK_URL']=$rec['TRACK_URL'];
                $params['DATE']=$last_status_date;
                $params['STATUS']=$last_status_info;
                $params['DISPUTE']=$disp;
                runScript($this->config['SCRIPT_DISPUTE_ID'], $params);
            } 
            $rec['LAST_SEND_WARNING'] = date ("Y-m-d H:i:s");
            SQLUpdate('pt_track', $rec);
        }
    }
}

function exec_script_newstatus($rec,$location)
{
    $this->getConfig();
    if ($this->config['SCRIPT_NEWSTATUS_ID']) {
        $params=array();
        $params['NAME']=$rec['NAME'];
        $params['TRACK']=$rec['TRACK'];
        $params['TRACK_URL']=$rec['TRACK_URL'];
        $params['DATE']=$rec['LAST_DATE'];
        $params['STATUS']=$rec['LAST_STATUS'];
        $params['LOCATION']=$location;
        runScript($this->config['SCRIPT_NEWSTATUS_ID'], $params);
    } 
}
/////////////////////////////////////////////


/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
// --------------------------------------------------------------------
    /**
     * dbInstall
        *
     * Database installation routine
        *
     * @access private
     */
function dbInstall($data) {
    $data = <<<EOD
pt_track: ID int(10) unsigned NOT NULL auto_increment
pt_track: NAME varchar(255) NOT NULL DEFAULT ''
pt_track: TRACK varchar(255) NOT NULL DEFAULT ''
pt_track: TRACK_URL varchar(255) DEFAULT ''
pt_track: CREATED datetime
pt_track: WAIT_DAY int(3) DEFAULT '0'
pt_track: LAST_SEND_WARNING datetime
pt_track: LAST_CHECKED datetime
pt_track: LAST_STATUS text
pt_track: LAST_DATE datetime
pt_track: ARCHIVE boolean NOT NULL DEFAULT FALSE
        
pt_status: ID int(10) unsigned NOT NULL auto_increment
pt_status: PROVIDER int(3) unsigned NOT NULL
pt_status: PROVIDER_ID int(10) unsigned NOT NULL
pt_status: TRACK_ID int(10) NOT NULL
pt_status: STATUS_CODE text
pt_status: STATUS_INFO text
pt_status: LOCATION_ZIP text
pt_status: LOCATION text
pt_status: DATE_STATUS datetime
        
EOD;
    parent::dbInstall($data);
    }

        // --------------------------------------------------------------------
        
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTm92IDE4LCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
