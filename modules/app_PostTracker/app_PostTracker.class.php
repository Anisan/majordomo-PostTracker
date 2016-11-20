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
function app_PostTracker() {
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
    $out['PROVIDER'] = $this->config['PROVIDER'];
    
    if($this->data_source == 'app_PostTracker' || $this->data_source == '') {
        if($this->view_mode == 'update_settings') {
            global $provider;
            $this->config['PROVIDER'] = $provider;
            global $gp_apikey;
            $this->config['GP_APIKEY'] = $gp_apikey;
            global $tr24_apikey;
            $this->config['TR24_APIKEY'] = $tr24_apikey;
            global $tr24_domain;
            $this->config['TR24_DOMAIN'] = $tr24_domain;
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
    if ($this->mode=='add_track') { 
        $rec = array();
        global $name;
        $rec['NAME']=$name;
        global $track;
        $rec['TRACK']=$track;
        global $track_url;
        $rec['TRACK_URL']=$track_url;
        
        $rec['ID']=SQLInsert("pt_track", $rec); // adding new record
        $this->redirect("?");
    }
    else if ($this->mode=='del_track') {
        $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $this->id . "'");
        // some action for related tables
        SQLExec("DELETE FROM pt_track WHERE ID='" . $rec['ID'] . "'");
        SQLExec("DELETE FROM pt_status WHERE TRACK_ID='" . $rec['ID'] . "'");
        $this->redirect("?");
    }else if ($this->mode=='switch_archive') {
        SQLExec("UPDATE pt_track set ARCHIVE = 1 - ARCHIVE WHERE ID='" . $this->id . "'");
        $this->redirect("?");
    }else if ($this->mode=='update_statuses') {
        $this->updateStatuses();
        $this->redirect("?");
    }else{
        // SEARCH RESULTS  
        $res=SQLSelect("SELECT *, 'No status' AS INFO FROM pt_track where ARCHIVE=0");
        if ($res[0]['ID']) {  
            paging($res, 20, $out); // search result paging
            $total=count($res);
            for($i=0;$i<$total;$i++) {
                // some action for every record if required
                $res_info=SQLSelect("SELECT * FROM pt_status WHERE TRACK_ID='" . $res[$i]['ID'] . "'");
                $total_info=count($res_info);
                if ($total_info>0)
                {
                    $res[$i]['INFO'] = "";
                    foreach($res_info as $info) {
                        $res[$i]['INFO'] .= $info['DATE_STATUS']." - ".$info['STATUS_INFO']."<br>";
                    }
                }
                else
                    $res[$i]['INFO'] = "No status";
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
            case 2: // Other
                return;
                break;
        }
        
        
        $total=count($res);
        for($i=0;$i<$total;$i++) {
            echo $res[$i]['TRACK']."\n";
            $statuses = $provider->getStatus($res[$i]['TRACK']);
            // proc statuses
            //print_r($statuses);
            foreach($statuses as $status) {
                $status['TRACK_ID'] = $res[$i]['ID'];
                $status['PROVIDER'] = $this->config['PROVIDER'];
                $status['DATE_STATUS'] = date ("Y-m-d H:i:s", $status['DATE_STATUS']);
                // find old
                if (isset($status['PROVIDER_ID']))
                    $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$res[$i]['ID']." and PROVIDER_ID = '" . $status['PROVIDER_ID'] . "';");
                else
                    $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$res[$i]['ID']." and DATE_STATUS = '" . DBSafe($status['DATE_STATUS']) . "';");
                if (!$find)
                {
                    echo 'Add new status '.$status['STATUS_INFO']."\n";
                    //add new
                    SQLInsert("pt_status", $status);
                }
            }
            
        }
    
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
pt_track: LAST_CHECKED datetime
pt_track: LAST_STATUS text
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
