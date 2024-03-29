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
    $out['MP_APIKEY'] = $this->config['MP_APIKEY'];
    $out['TR24_APIKEY'] = $this->config['TR24_APIKEY'];
    $out['TR24_DOMAIN'] = $this->config['TR24_DOMAIN'];
    $out['RP_LOGIN'] = $this->config['RP_LOGIN'];
    $out['RP_PASSWORD'] = $this->config['RP_PASSWORD'];
    $out['TRRU_APIKEY'] = $this->config['TRRU_APIKEY'];
    $out['PROVIDER'] = $this->config['PROVIDER'];
    $out['SCRIPT_NEWSTATUS_ID'] = $this->config['SCRIPT_NEWSTATUS_ID'];
    $out['SCRIPT_DISPUTE_ID'] = $this->config['SCRIPT_DISPUTE_ID'];
    $out['SCRIPT_ADD_DEL_ARCH_ID'] = $this->config['SCRIPT_ADD_DEL_ARCH_ID'];
    $out['SCRIPTS']=SQLSelect("SELECT ID, TITLE FROM scripts ORDER BY TITLE");
    $out['POST_DEBUG'] = $this->config['POST_DEBUG'];
    if($this->data_source == 'app_PostTracker' || $this->data_source == '') {
        if($this->view_mode == 'update_settings') {
            global $provider;
            $this->config['PROVIDER'] = $provider;
            global $gp_apikey;
            $this->config['GP_APIKEY'] = $gp_apikey;
            global $mp_apikey;
            $this->config['MP_APIKEY'] = $mp_apikey;
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
            global $trru_apikey;
            $this->config['TRRU_APIKEY'] = $trru_apikey;
            global $script_dispute_id;
            $this->config['SCRIPT_DISPUTE_ID'] = $script_dispute_id;
            global $script_add_del_arch_id;
            $this->config['SCRIPT_ADD_DEL_ARCH_ID'] = $script_add_del_arch_id;
            global $post_debug;
            $this->config['POST_DEBUG'] = $post_debug;
            $this->saveConfig();
            $this->redirect("?");
        }
        
        
    }
}

function api($params) {
        if ($params['op']=='add' || $params['request'][0]=='add') {
            if ($params['name'] == '' or $params['track']=='')
                return "wrong request (need name and track)";
            $this->addTrack($params['name'],$params['track'],$params['track_url'],$params['waitday'],$params['description']);
            return "ok";
        }
        if ($params['op']=='edit' || $params['request'][0]=='edit') {
            if ($params['name'] == '' and $params['track']=='')
                return "wrong request (need name and track)";
            $this->addTrack($params['name'],$params['track'],$params['track_url'],$params['waitday'],$params['description']);
            return "ok";
        }
        if ($params['request'][0]=='archive') {
            if (!array_key_exists('id',$params))
                $params['id'] = '';
            if ($params['request'][1])
                $params['id'] = $params['request'][1];
            if ($params['id'] == '' and $params['track']=='')
                return "wrong request (need id or track)";
            $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $params['track'] . "' OR ID=" . $params['id']);
            if ($rec['ID']) {
                $archive = $rec['ARCHIVE'];
                if ($archive == "1") $archive = "0"; else $archive = "1";
                $this->archive($rec,$archive);
                return "ok";
            }
            else
                return "not found";
        }         
        if ($params['request'][0]=='del') {
            if (!array_key_exists('id',$params))
                $params['id'] = 0;
            if ($params['request'][1])
                $params['id'] = $params['request'][1];
            $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $params['track'] . "' OR NAME='" . $params['name'] . "' OR ID=" . $params['id']);
            if ($rec['ID']) {
                $this->delTrack($rec["ID"]);
                return "ok";
            }
            else
                return "not found";
        } 
        if ($params['request'][0]=='list') {
            $sql = "SELECT * FROM pt_track";
            if($params['request'][1] != "all")
            {
                if($params['request'][1] == "archive")
                    $sql .= " where ARCHIVE=1";
                else
                    $sql .= " where ARCHIVE=0";
            }
            $res=SQLSelect($sql);
            return $res;
        }
        if ($params['request'][0]=='statuses') {
            if (!array_key_exists('id',$params))
                $params['id'] = 0;
            if ($params['request'][1])
                $params['id'] = $params['request'][1];
            $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $params['track'] . "' OR NAME='" . $params['name'] . "' OR ID='" . $params['id']."'");
            if ($rec['ID']) {
                $sql = "SELECT * FROM pt_status WHERE TRACK_ID='" . $rec['ID'] ."'";
                $res=SQLSelect($sql);
                return $res;
            }
            else
                return "not found";
        }
        if ($params['request'][0]=='update') {
            $this->updateStatuses(false);
            if (!array_key_exists('id',$params))
                $params['id'] = 0;
            if ($params['request'][1])
                $params['id'] = $params['request'][1];
            if ($params['id']!=0)
            {
                $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $params['id'] . "'");
                $this->updateStatusInit($rec);
            }
            
            else
                $this->updateStatuses(false);
            return "ok";
        }
        return "not support command";
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
        global $name;
        global $track;
        global $track_url;
        global $waitday;
        global $description;
        $this->addTrack($name,$track,$track_url,$waitday,$description);
        $this->redirect("?");
    }else if ($this->mode=='del_track') {
        $this->delTrack($this->id);
        $this->redirect("?");
    }else if ($this->mode=='del_track_info') {
        $recStatus = SQLSelectOne("SELECT * FROM pt_status WHERE ID=".$this->id);
        if ($recStatus['ID'])
        {
            $trackId = $recStatus['TRACK_ID'];
            SQLExec("DELETE FROM pt_status WHERE ID='" . $this->id . "'");
            $res_info=SQLSelect("SELECT * FROM pt_status WHERE TRACK_ID='" . $trackId . "' ORDER BY DATE_STATUS DESC");
            $recTrack = SQLSelectOne("SELECT * FROM pt_track WHERE ID=".$trackId);
            if ($res_info[0]['ID'])
            {
                $recTrack['LAST_DATE'] = $res_info[0]['DATE_STATUS'];
                $recTrack['LAST_STATUS'] = $res_info[0]['STATUS_INFO'];
            }
            else
            {
                $recTrack['LAST_DATE'] = "";
                $recTrack['LAST_STATUS'] = "";
            }
            SQLUpdate('pt_track', $recTrack);
        }
        $this->redirect("?");
    }else if ($this->mode=='switch_archive') {
        $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $this->id . "'");
        $this->archive($rec, !$rec["ARCHIVE"]);
        $this->redirect("?view_mode=".$this->view_mode);
    }else if ($this->mode=='update_statuses') {
        if ($this->id)
        {
            $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $this->id . "'");
            $this->updateStatusInit($rec);
        }
        else
            $this->updateStatuses(false);
        $this->redirect("?");
    }else{
        // SEARCH RESULTS
        global $session;        
        $sql = "SELECT *, '' as STATUSES FROM pt_track where ";
        global $archive_switch;
        if (!isset($archive_switch)) {
            $archive_switch=$session->data['post_archive'];
        } else {
            $session->data['post_archive']=$archive_switch;
        }
        if (!$archive_switch) $archive_switch=0;
        if($archive_switch == 1)
            $sql .= "ARCHIVE=1";
        else
            $sql .= "ARCHIVE=0";
        $out['ARCHIVE']=$archive_switch;
        // FIELDS ORDER
        global $sortby_post;
        if (!$sortby_post) {
            $sortby_post=$session->data['post_sort'];
        } else {
            if ($session->data['post_sort']==$sortby_post) {
                if (Is_Integer(strpos($sortby_post, ' DESC'))) {
                    $sortby_post=str_replace(' DESC', '', $sortby_post);
                } else {
                    $sortby_post=$sortby_post." DESC";
                }
            }
            $session->data['post_sort']=$sortby_post;
        }
        if (!$sortby_post) $sortby_post="ID";
        $out['SORTBY']=$sortby_post;
        $sql .= " ORDER BY ".$sortby_post;
        $res=SQLSelect($sql);
        if ($res[0]['ID']) {  
            paging($res, 20, $out); // search result paging
            $total=count($res);
            for($i=0;$i<$total;$i++) {
                // some action for every record if required
                $res_info=SQLSelect("SELECT * FROM pt_status WHERE TRACK_ID='" . $res[$i]['ID'] . "' ORDER BY DATE_STATUS");
                $total_st=count($res_info);
                for($j=0;$j<$total_st;$j++) {
                    $dt = strtotime($res_info[$j]['DATE_STATUS']);
                    $res_info[$j]['DATE_STATUS'] = date ("d-m-Y", $dt);
                    $res_info[$j]['TIME_STATUS'] = date ("H:i", $dt);
                }
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
                $dt = strtotime($res[$i]['LAST_DATE']);
                $res[$i]['LAST_DATE'] = date ("d-m-Y", $dt);
                $res[$i]['LAST_TIME'] = date ("H:i", $dt);
            }
            $out['RESULT']=$res;
        }
        $this->getConfig();
        $out['LAST_UPDATE']=$this->config['LAST_UPDATE'];
        $out['PROVIDER']=$this->config['PROVIDER'];
    }
}
//////////////////////////////////////////////
function addTrack($name, $track, $track_url, $waitday, $description)
{
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $track . "'");
        
    $rec['NAME']=$name;
    $rec['TRACK']=$track;
    $rec['TRACK_URL']=$track_url;
    $rec['WAIT_DAY']= 50;
    $rec['DESCRIPTION'] = '';
    if ($waitday != null)
        $rec['WAIT_DAY']=$waitday;
    if ($description != null)
        $rec['DESCRIPTION']=$description;

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
        $status['PROVIDER_ID'] = 0;
        SQLInsert("pt_status", $status);
        $this->addTrackToProvider($rec);
        $this->exec_script_newstatus($rec,"");
        $this->updateStatusInit($rec);
                
        $this->exec_script_add_del_arc($rec, "ADD");
    }
}

function delTrack($id)
{
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE ID='" . $id . "'");
    $this->delTrackFromProvider($rec);
    SQLExec("DELETE FROM pt_track WHERE ID='" . $id . "'");
    SQLExec("DELETE FROM pt_status WHERE TRACK_ID='" . $id . "'");
    $this->exec_script_add_del_arc($rec, "DEL");
}

function archiveByTrack($track) {
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $track . "'");
    if ($rec)
        $this->archive($rec,TRUE);
}
function archiveByName($name) {
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE NAME='" . $name . "'");
    if ($rec)
        $this->archive($rec,TRUE);
}
function unarchiveByTrack($track) {
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE TRACK='" . $track . "'");
    if ($rec)
        $this->archive($rec,FALSE);
}
function unarchiveByName($name) {
    $rec = SQLSelectOne("SELECT * FROM pt_track WHERE NAME='" . $name . "'");
    if ($rec)
        $this->archive($rec,FALSE);
}
function archive($rec,$acrhive) {
    if ($rec['ARCHIVE']==$acrhive) 
        return;
    $status = array();
    $status['DATE_STATUS'] = date ("Y-m-d H:i:s");;
    $status['TRACK_ID'] = $rec['ID'];
    $status['PROVIDER'] = -1;
    $status['PROVIDER_ID'] = 0;
    $rec['ARCHIVE']=$acrhive;
    $provider = $this->getProvider();
    if (!$rec['ARCHIVE'])
    {
        $status['STATUS_INFO'] = "Unarchived (enable monitoring)";
        $provider->unarchiveTrack($rec);
    }
    else
    {
        $status['STATUS_INFO'] = "Archived (disable monitoring)";
        $provider->archiveTrack($rec);
    }
    SQLUpdate("pt_track", $rec);
    SQLInsert("pt_status", $status);
    $this->exec_script_add_del_arc($rec, "ARCHIVE");
}
//////////////////////////////////////////////
function updateStatuses($log=false) {
    $this->getConfig();

    $res=SQLSelect("SELECT * FROM pt_track where ARCHIVE=0");
    if ($res[0]['ID']) {  
        // init provider
        $provider = $this->getProvider();
        if ($log)
            $provider->debug = $this->config['POST_DEBUG'];
        
        $total=count($res);
        
        for($i=0;$i<$total;$i++) {
            $this->updateStatus($provider,$res[$i],$log);
        }
    }
    $this->config['LAST_UPDATE'] = date ("Y-m-d H:i:s");
    $this->saveConfig();
}

function getProvider() {
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
            case 4: // Moyaposylka
                require_once("./modules/app_PostTracker/provider/Moyaposylka.php");
                $provider = new Moyaposylka($this->config['MP_APIKEY']);
                break;
            case 5: // Moyaposylka
                require_once("./modules/app_PostTracker/provider/trackru.php");
                $provider = new Trackru($this->config['TRRU_APIKEY']);
                break;
    }
    $provider->debug = $this->config['POST_DEBUG'];
    return $provider;
}

function addTrackToProvider($rec) {
    $this->getConfig();
    $provider = $this->getProvider();
    $provider->addTrack($rec);
}

function delTrackFromProvider($rec) {
    $this->getConfig();
    $provider = $this->getProvider();
    $provider->delTrack($rec);
}


function updateStatusInit($rec) {
    $this->getConfig();
    $provider = $this->getProvider();
    $this->updateStatus($provider,$rec,false);
}

function updateStatus($provider,$rec,$log=true) {
    if ($log)
        $this->echonow("Track: ".$rec['NAME'].' ('.$rec['TRACK'].")<br>", 'green');
    $info = $provider->getStatus($rec['TRACK']);
    $statuses = $info['statuses'];
    if ($log)
        $this->echonow("Count statuses:".count($statuses)."<br>",'blue');
    // proc statuses
    if ($log)
        $this->debug(json_encode($statuses,JSON_UNESCAPED_UNICODE).'<br>');
    $last_status_info = "";
    $last_status_date = "";
    $location = "";
    $new_statuses = 0;
    foreach($statuses as $status) {
        $status['TRACK_ID'] = $rec['ID'];
        $status['PROVIDER'] = $this->config['PROVIDER'];
        $status['DATE_STATUS'] = date ("Y-m-d H:i:s", $status['DATE_STATUS']);
        // find old
        if (isset($status['PROVIDER_ID']))
            $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$rec['ID']." and PROVIDER_ID = '" . $status['PROVIDER_ID'] . "';");
        else
        {
            $find = SQLSelectOne("SELECT * FROM pt_status WHERE TRACK_ID=".$rec['ID']." and DATE_STATUS = '" . DBSafe($status['DATE_STATUS']) . "';");
            $status['PROVIDER_ID'] = 0;
        }
        if (!$find)
        {
            ++$new_statuses;
            if ($log)
                $this->echonow('Add new status '.$status['STATUS_INFO']." (".$status['DATE_STATUS'].")<br>",'orange');
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
    if ($log)
        $this->echonow("New statuses:".$new_statuses."<br>",'red');
    $rec['LAST_CHECKED'] = date ("Y-m-d H:i:s");
    
    if (array_key_exists('carrier',$info))
        $rec['CARRIER'] = $info['carrier'];
    if (array_key_exists('weight',$info))
        $rec['WEIGTH'] = $info['weight'];
    if (array_key_exists('originCountry',$info))
        $rec['ORIGINCOUNTRY'] = $info['originCountry'];
    if (array_key_exists('destinationCountry',$info))
        $rec['DESTINATIONCOUNTRY'] = $info['destinationCountry'];
    
    if (array_key_exists('item',$info))
        $rec['ITEM'] = $info['item'];
    if (array_key_exists('sender',$info))
        $rec['SENDER'] = $info['sender'];
    if (array_key_exists('recipient',$info))
        $rec['RECIPIENT'] = $info['recipient'];
            
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
        if ($log)
            $this->echonow("Days on the way:".$days.". To dispute:".$disp.".<br>");
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
        $params['DESCRIPTION']=$rec['DESCRIPTION'];
        $params['DATE']=$rec['LAST_DATE'];
        $params['STATUS']=$rec['LAST_STATUS'];
        $params['LOCATION']=$location;
        runScript($this->config['SCRIPT_NEWSTATUS_ID'], $params);
    } 
}
function exec_script_add_del_arc($rec, $operation)
{
    $this->getConfig();
    if ($this->config['SCRIPT_ADD_DEL_ARCH_ID']) {
        $params=array();
        $params['OPERATION']=$operation;
        $params['NAME']=$rec['NAME'];
        $params['TRACK']=$rec['TRACK'];
        $params['TRACK_URL']=$rec['TRACK_URL'];
        $params['DESCRIPTION']=$rec['DESCRIPTION'];
        $params['ARCHIVE']=$rec['ARCHIVE'];
    
        $res=SQLSelect("SELECT * FROM pt_track where ARCHIVE=0");
        $params['COUNT_WORK']=count($res);
        $res=SQLSelect("SELECT * FROM pt_track where ARCHIVE=1");
        $params['COUNT_ARCHIVE']=count($res);
        
        runScript($this->config['SCRIPT_ADD_DEL_ARCH_ID'], $params);
    } 
}
/////////////////////////////////////////////
function debug($msg, $color='') {
    if (!$this->config['POST_DEBUG']) return;
    if ($color == '')
        $this->echonow($msg, 'gray');
    else
        $this->echonow($msg, $color);
}
function echonow($msg, $color='') {
  if ($color) {
   echo '<font color="'.$color.'">';
  }
  echo $msg;
  if ($color) {
   echo '</font>';
  }
  echo "<script language='javascript'>window.scrollTo(0,document.body.scrollHeight);</script>";
  echo str_repeat(' ', 16*1024);
  flush();
  ob_flush();
 }

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
pt_track: ID int(10) NOT NULL auto_increment
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
pt_track: DESCRIPTION text
pt_track: CARRIER varchar(255)
pt_track: WEIGTH varchar(255)
pt_track: ORIGINCOUNTRY varchar(255)
pt_track: DESTINATIONCOUNTRY varchar(255)
pt_track: ITEM varchar(255)
pt_track: SENDER varchar(255)
pt_track: RECIPIENT varchar(255)
        
pt_status: ID int(10) NOT NULL auto_increment
pt_status: PROVIDER int(3) NOT NULL
pt_status: PROVIDER_ID int(10) NOT NULL DEFAULT 0
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
