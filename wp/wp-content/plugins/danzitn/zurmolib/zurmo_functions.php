<?php
include_once('zurmolib.php');



function create_new_lead($client,$channel, $subject, $record) {
    $map_vt_lead_array = array(
                            'first_name'=>'firstname',
                            'last_name'=>'lastname',
                            'company_name'=>'company',
                            'user_email'=>'email',
                            'addr1'=>'lane',
                            'city'=>'city',
                            'thestate'=>'state',
                            'country'=>'country',
                            'zip'=>'code',
                            'phone1'=>'phone',
                            'user_url'=>'website',
                            'business_type'=>'cf_510',
                            'description'=>'description'
                            );
	if( $record['last_name']=='' && $record['nickname']!= '' )  $record['last_name'] = $record['nickname'];
	if( $record['last_name']=='' && $record['user_email']!= '' )  $record['last_name'] = $record['user_email'];
	if( $record['company_name']=='' )  $record['company_name'] = "Azienda di " . $record['last_name'];
    $vt_record = array();
    foreach($map_vt_lead_array as $key=>$value)
    {
        if(array_key_exists($key,$record))    $vt_record[$value] = $record[$key];
    }
    $vt_record['description'] = $subject;
    $vt_record['leadsource'] = $channel;
    $nrecord = $client->doCreate("Leads", $vt_record);
    $wasError = $client->lastError();
    if($nrecord) {
        $recordid = $nrecord['id'];
    }
    return $recordid;
}

function create_campaign_for_entity($client,$type,$record,$campaign) {
    $client->doInvoke("ws_entity_to_campaign",$type, $record['id'],"Campaigns",$campaign['id'], 'POST');
}

function create_event_for_entity($client,$type,$record,$event) {
    $assigned_user_id=$client->_userid;
    $date_start=date("Y-m-d");
    $due_date=date('Y-m-d',strtotime('+1 week', strtotime($date_start)));
    $duration_hours=1;
    $eventstatus='Held';
    $time_start=date('H:i');
    $time_end=date('H:i',strtotime('+1 hours', strtotime($time_start)));
    if( $type == "Contacts" ) {
        $contact_id= $record['id'];
        $parent_id = $record['account_id'];
    }
    if( $type == "Leads" ) {
        $parent_id = $record['id'];
    } 
    if( $type == "Accounts" ) {
        $parent_id = $record['id'];
    } 
    $nrecord = $client->doCreate('Events', array('time_start'=>$time_start,
                                                 'assigned_user_id'=>$assigned_user_id,
                                                 'duration_hours'=>$duration_hours,
                                                 'due_date'=>$due_date,
                                                 'date_start'=>$date_start,
                                                 'time_end'=>$time_end,
                                                 'activitytype'=> $event['type'],// 'Login',
                                                 'contact_id'=>$contact_id,
                                                 'parent_id'=>$parent_id,
                                                 'description'=>$event['description'],
                                                 'eventstatus'=>$eventstatus,
                                                 'subject'=>$event['subject'])
                                            );
    $wasError= $client->lastError();
    if($wasError) {
           
    }
    
    
}

function find_entity_by_email($client, $email) {
    $arr_res=array();
    $ret_val = array("success"=>true,"result"=>array());
    $query = "SELECT id, lastname, firstname, phone, contact_no, account_id FROM Contacts WHERE  email = '".$email."'";
    $records = $client->doQuery($query);
    $wasError = $client->lastError();
    if($wasError) {
        $arr_res['Contacts'][] = array('error'=>$wasError['message']);
    } else {
        if( $records ) {
            foreach($records as $record) {
                $arr_res['Contacts'][] = array(
                                               'id'=>$record['id'],
                                               'lastname'=>$record['lastname'],
                                               'firstname'=>$record['firstname'],
                                               'phone'=>$record['phone'],
                                               'contact_no'=>$record['contact_no'],
                                               'account_id'=>$record['account_id']
                                               );
            }
        }
    }
    $query = "SELECT id, accountname, website, account_no FROM Accounts WHERE email1 = '".$email."'";
    $records = $client->doQuery($query);
    $wasError = $client->lastError();
    if($wasError) {
        $arr_res['Accounts'][] = array('error'=>$wasError['message']);
    } else {
        if( $records ) {
            foreach($records as $record) {
                $arr_res['Accounts'][] = array(
                                               'id'=>$record['id'],
                                               'accountname'=>$record['accountname'],
                                               'website'=>$record['website'],
                                               'phone'=>$record['phone'],
                                               'account_no'=>$record['account_no']
                                               );
            }
        }
    }
    $query = "SELECT id, lastname, firstname,company, phone, lead_no FROM Leads WHERE email = '".$email."'";
    $records = $client->doQuery($query);
    $wasError = $client->lastError();
    if($wasError) {
        $arr_res['Leads'][] = array('error'=>$wasError['message']);
    } else {
        if( $records ) {
            foreach($records as $record) {
                $arr_res['Leads'][] = array(
                                               'id'=>$record['id'],
                                               'lastname'=>$record['lastname'],
                                               'firstname'=>$record['firstname'],
                                               'company'=>$record['company'],
                                               'phone'=>$record['phone'],
                                               'lead_no'=>$record['lead_no']
                                               );
            }
        }
    }
    $ret_val['result']=$arr_res;
    return $ret_val;
}


?>
