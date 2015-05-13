<?php
include_once( 'Vtiger/WSClient.php' );
class VtigerClient
{
    
    function __construct($vtiger_url,$username, $password) {
        $this->vtiger_base_url = $vtiger_url; // http://localhost/vtiger61
        $this->entitytypes = array('Accounts','Contacts','Leads');
        $this->client = new Vtiger_WSClient($this->vtiger_base_url);
        $this->username = $username;
        $this->password = $password;
        $this->loggedin = false;
        $this->_userid = "0x0";
    }
    
    public function login()
    {
        $ret_login = array();
        $checkLogin = $this->client->doLogin($this->username, $this->password);
        $wasError = $this->client->lastError();
        if($wasError) {
            $ret_login['status'] = 'FAIL';
            $ret_login['errors'] = $wasError;
        } elseif ($checkLogin)  {
            $ret_login['status'] = 'SUCCESS';
            $this->loggedin = true;
            $this->_userid = $this->client->_userid;
            return $ret_login;
        }
        else
        {
            return false;
        }
    }
    
    public function doQuery($query) {
        return $this->client->doQuery($query);
    }
    
    public function doOperation($method, $params) {    
        return $this->client->doInvoke($method, $params);
    }
    
    public function toParameterString($valuemap) {
        return $this->client->toJSONString($valuemap);
    }
    
    
    public function listFiltered($query) {
        $response = array();
        $records = $this->client->doQuery($query);
        $wasError = $this->client->lastError();
        if($wasError) {
            $response['status'] = 'FAIL';
            $response['errors'] = $wasError;
        } else {
            $response['status'] = 'SUCCESS';
            $response['data']['totalCount'] = count($records);
            $response['data']['items'] = $records;
        }
        return $response;
    }
    
    public function create($entityType, $entityData) {
        $response = array();
        $records = $this->client->doCreate($entityType, $entityData);
        $wasError = $this->client->lastError();
        if($wasError) {
            $response['status'] = 'FAIL';
            $response['errors'] = $wasError;
        } else {
            $response['status'] = 'SUCCESS';
            $response['data']['totalCount'] = count($records);
            $response['data']['items'] = $records;
        }
        return $response;
    }
}


function create_new_vtiger_lead($vcl,$channel, $subject, $record) {  
    $recordid = 0;
    $status = 'FAIL'; 
    $arr_res = array();
    $arr_errors = array();
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
                            'status'=>'leadstatus',
                            'business_type'=>'industry',
                            'description'=>'description'
                            );
	if( $record['last_name']=='' && $record['nickname']!= '' )  $record['last_name'] = $record['nickname'];
	if( $record['last_name']=='' && $record['user_email']!= '' )  $record['last_name'] = $record['user_email'];
	if( $record['company_name']=='' )  $record['company_name'] = "Sig./Sig.ra " . $record['last_name'];
    $vt_record = array();
    foreach($map_vt_lead_array as $key=>$value)
    {
        if(array_key_exists($key,$record))    $vt_record[$value] = $record[$key];
    }
    $vt_record['description'] .= "\n".$subject;
    $vt_record['leadsource'] = $channel;
    $vt_record['rating'] = 'Acquired';
    $vt_record['leadstatus'] = 'Pre Qualified';
    $response = $vcl->create('Leads',  $vt_record  );
    
    if($response['status'] == 'FAIL') {
        $arr_errors['error']= $response['errors'];
    } elseif ($response['status'] == 'SUCCESS') {
        $recordid = $response['data']['items']['id'];
        $arr_res["id"] = $recordid;
        $arr_res["companyName"] = $record["company_name"];
        $arr_res["firstName"] = $record["first_name"];
        $arr_res["lastName"] = $record["last_name"];
        $status = "SUCCESS"; 
    }
    return array("status"=>$status,"data"=>$arr_res,"errors"=>$arr_errors);
}

function find_vtiger_entity_by_email($vcl, $email) {
    $entity_types = array('Contacts'=>'email','Accounts'=>'email1','Leads'=>'email');
    $status = 'SUCCESS'; 
    $arr_res = array();
    $arr_errors = array();
    foreach($entity_types as $entity_type => $email_field) {
        $query = "SELECT * FROM " .$entity_type. " WHERE " . $email_field . " = '".$email."'";
        // echo "\n".$query."\n";
        $response = $vcl->listFiltered($query);
        if ($response['status'] == 'SUCCESS')
        {
            // Do something with results
            if ($response['data']['totalCount'] > 0)
            {
                foreach ($response['data']['items'] as $item)
                {
                    if($entity_type == "Accounts") {
                        $arr_res[$entity_type][] = array(
                                                      'id'=>$item["id"],
                                                      'industry'=>$item['industry'],
                                                      'primaryAddress'=>array("street"=>$item['bill_street'],
                                                                               "city"=>$item['bill_city'],
                                                                               "state"=>$item['bill_state'],
                                                                               "zip"=>$item['bill_code'],
                                                                               "country"=>$item['bill_country']),
                                                      'account_id'=>$item['id'],
                                                      'companyName'=>$item['accountname'],
                                                      'primaryEmail'=>$item['email1'],
                                                      'officePhone'=>$item['phone']
                                                      );
                    } else if($entity_type == "Contacts")  {
                        $arr_res[$entity_type][] = array(
                                                      'id'=>$item["id"],
                                                      'firstName'=>$item['firstname'],
                                                      'lastName'=>$item['lastname'],
                                                      'primaryAddress'=>array("street"=>$item['mailingstreet'],
                                                                               "city"=>$item['mailingcity'],
                                                                               "state"=>$item['mailingstate'],
                                                                               "zip"=>$item['mailingzip'],
                                                                               "country"=>$item['mailingcountry']),
                                                      'account_id'=>$item['account_id'],
                                                      'companyName'=>"",
                                                      'primaryEmail'=>$item['email'],
                                                      'officePhone'=>$item['phone']
                                                      );
                     } else if($entity_type == "Leads")  {
                        $arr_res[$entity_type][] = array(
                                                      'id'=>$item["id"],
                                                      'industry'=>$item['industry'],
                                                      'firstName'=>$item['firstname'],
                                                      'lastName'=>$item['lastname'],
                                                      'primaryAddress'=>array("street"=>$item['lane'],
                                                                               "city"=>$item['city'],
                                                                               "state"=>$item['state'],
                                                                               "zip"=>$item['code'],
                                                                               "country"=>$item['country']),
                                                      'account_id'=>'',
                                                      'companyName'=>$item['company'],
                                                      'primaryEmail'=>$item['email'],
                                                      'officePhone'=>$item['phone']
                                                      );
                     }
                }
            }
        } else  {
            $status = 'FAIL';        
            $arr_errors = $response['errors'];
        }
    }
    return array("status"=>$status,"data"=>$arr_res,"errors"=>$arr_errors);
}

function create_vtiger_event_for_entity($vcl,$type,$record,$event) {   
    $assigned_user_id = $vcl->_userid;
    $remove_five_minutes = strtotime("-5 minutes");
    $add_fiftyfive_minutes = strtotime("+115 minutes");
    $startStamp = gmdate("Y-m-d", $remove_five_minutes); // gmdate instead of gmdate
    $endStamp =  gmdate("Y-m-d",$add_fiftyfive_minutes);
    $duration_hours=1;
    $eventstatus='Planned';
    $time_start=gmdate("H:i", $remove_five_minutes);
    $time_end=gmdate("H:i", $add_fiftyfive_minutes);
    // ricordarsi di passare la data UTC+- ora solare
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
    $event_data =  array('time_start'=>$time_start.":00",
                                                 'assigned_user_id'=>$assigned_user_id,
                                                 'duration_hours'=>$duration_hours,
                                                 'due_date'=>$endStamp,
                                                 'date_start'=>$startStamp,
                                                 'time_end'=>$time_end.":00",
                                                 'activitytype'=> $event['type'],
                                                 'contact_id'=>$contact_id,
                                                 'parent_id'=>$parent_id,
                                                 'description'=>$event['description'],
                                                 'location'=>$event['location'],
                                                 'eventstatus'=>$eventstatus,
                                                 'visibility'=>'Public',
                                                 'subject'=>$event['subject']);
    // print_r($event_data);
    $nrecord = $vcl->create('Events',   $event_data  );
    return $nrecord;    
}





?>
