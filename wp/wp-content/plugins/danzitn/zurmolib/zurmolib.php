<?php

class ApiRestHelper
{
    public static function createApiCall($url, $method, $headers, $data = array())
    {
        if ($method == 'PUT')
        {
            $headers[] = 'X-HTTP-Method-Override: PUT';
        }

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        switch($method)
        {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($handle, CURLOPT_POST, true);
                curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'PUT':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($data));
                break;
            case 'DELETE':
                curl_setopt($handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }
        $response = curl_exec($handle);
        return $response;
    }
        
    
}

class ZurmoClient
{
    
    function __construct($zurmo_url,$username, $password,$entitytype) {
        $this->zurmo_base_url = $zurmo_url; // http://localhost/zurmo/app/index.php
        $this->entitytypes = array('account','contact','lead','meeting','task');
        if(in_array($entitytype,$this->entitytypes)) { 
            $this->entitytype = $entitytype;
            $this->zurmo_url = $zurmo_url;
            $this->setEntityType($entitytype);
        }
        $this->username = $username;
        $this->password = $password;
        $this->headers = array();
        $this->loggedin = false;
    }
    
    
    
    public function setEntityType($entitytype)
    {
        if( in_array($entitytype,$this->entitytypes) ) { 
            $this->entitytype = $entitytype;
            if($entitytype=='lead') $this->zurmo_api_url = $this->zurmo_url."/leads/contact/api/";
            else $this->zurmo_api_url = $this->zurmo_url."/".$entitytype."s/".$entitytype."/api/";
        }
    }
    
    public function setLoginHeaders($headers)
    {
        $this->headers = $headers;
    }
    
    public function getLoginHeaders()
    {
        return $this->headers;
    }
    
    public function login()
    {
        $headers = array(
            'Accept: application/json',
            'ZURMO_AUTH_USERNAME: ' . $this->username,
            'ZURMO_AUTH_PASSWORD: ' . $this->password,
            'ZURMO_API_REQUEST_TYPE: REST',
        );
        $loginUrl = $this->zurmo_base_url."/zurmo/api/login";
        $response = ApiRestHelper::createApiCall($loginUrl, 'POST', $headers);
        $response = json_decode($response, true);
        if ($response['status'] == 'SUCCESS')
        {
            $authenticationData = $response["data"];
            $headers = array(
                'Accept: application/json',
                'ZURMO_SESSION_ID: ' . $authenticationData['sessionId'],
                'ZURMO_TOKEN: ' . $authenticationData['token'],
                'ZURMO_API_REQUEST_TYPE: REST',
            );
            $this->headers = $headers;
            $this->loggedin = true;
            return $response;
        }
        else
        {
            return false;
        }
    }
    
    protected function createApiCallWithRelativeUrl($relativeUrl, $method, $data = array())
    {
        $url = $this->zurmo_api_url . $relativeUrl;
        return ApiRestHelper::createApiCall($url, $method, $this->headers, $data);
    }
    
    public function listFiltered($filterData) {
        $response = $this->createApiCallWithRelativeUrl('list/filter/', 'POST', array('data' => $filterData));
        // print_r($response );
        $response = json_decode($response, true);    
        $response_data = array();
        if ($response['status'] == 'SUCCESS') {
            $response_data = $response['data'];
        } else {
            $this->last_error = $response['errors'];
        }
        return $response;
    }
    
    public function getById($entityId)  {
        $response = $this->createApiCallWithRelativeUrl('read/' . $entityId, 'GET');
        // print_r($response );
        $response = json_decode($response, true);    
        $response_data = array();
        if ($response['status'] == 'SUCCESS') {
            $response_data = $response['data'];
        } else {
            $this->last_error = $response['errors'];
        }
        return $response;
    }
    
    public function create($entityData) {
        $response = $this->createApiCallWithRelativeUrl('create/', 'POST', array('data' => $entityData));
        $response = json_decode($response, true); 
        $response_data = array();
        if ($response['status'] == 'SUCCESS') {
            $response_data = $response['data'];
        } else {
            $this->last_error = $response['errors'];
        }
        return $response;
    }
}


function create_new_zurmo_lead($zcl,$channel, $subject, $record) {  
    $contactInputData = Array
    (
        'firstName' => $record['first_name'],
        'lastName' => $record['last_name'],      
        'officePhone' => $record['phone1'],
        'description' => $subject,
        'companyName' => "Sig./Sig.ra ".$record['last_name'] . " " .$record['first_name'],
        'source' => Array
            (
                'value' => $channel
            ),
        'primaryEmail' => Array
            (
                'emailAddress' => $record['user_email'],
                'optOut' => 1,
            ),
        'state' => Array
            (
                'id' => 1
            ),
    );
    $zcl->setEntityType("lead");
    return $zcl->create($contactInputData);
}

function find_zurmo_entity_by_email($zcl, $email) {
    $entity_types = array('lead', 'contact','account');
    $status = 'SUCCESS'; 
    $arr_res = array();
    $arr_errors = array();
    $filterdata = array(
        'dynamicSearch' => array(
            'dynamicClauses' => array(
                array(
                    'attributeIndexOrDerivedType' => 'primaryEmail',
                    'structurePosition' => 1,
                    'primaryEmail' =>  array('emailAddress' => $email),
                ),
            ),
            'dynamicStructure' => '1',
        ),
        'pagination' => array(
            'page'     => 1,
            'pageSize' => 2,
        ),
        'sort' => 'id.asc',
    );
    foreach($entity_types as $entity_type) {
        $zcl->setEntityType($entity_type);
        $response = $zcl->listFiltered($filterdata);
        if ($response['status'] == 'SUCCESS')
        {
            // Do something with results
            if ($response['data']['totalCount'] > 0)
            {
                foreach ($response['data']['items'] as $item)
                {
                    if($entity_type=="account") {
                        $arr_res[$entity_type][] = array(
                              'id'=>$item["id"],
                              'industry'=>$item['industry']['value'],
                              'billingAddress'=>$item['billingAddress'],
                              'account_id'=>$item['id'],
                              'companyName'=>$item['name'],
                              'primaryEmail'=>$item['primaryEmail']['emailAddress'],
                              'officePhone'=>$item['officePhone']
                              );

                    } else {
                        $arr_res[$entity_type][] = array(
                                                      'id'=>$item["id"],
                                                      'firstName'=>$item['firstName'],
                                                      'lastName'=>$item['lastName'],
                                                      'primaryAddress'=>$item['primaryAddress'],
                                                      'account_id'=>$item['account']['id'],
                                                      'companyName'=>$item['companyName'],
                                                      'primaryEmail'=>$item['primaryEmail']['emailAddress'],
                                                      'officePhone'=>$item['officePhone']
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

// $event['subject'] $event['description'] $event['type'] $event['location']
function create_zurmo_event_for_entity($zcl,$type,$record,$event) {
    $remove_five_minutes = strtotime("-5 minutes");
    $add_five_minutes = strtotime("+115 minutes");
    $startStamp = date("Y-m-d H:i:s", $remove_five_minutes);
    $endStamp =  date("Y-m-d H:i:s",$add_five_minutes);
    $event_name = $event['subject'] . " - " . ( $type=='account' ? $record['companyName'] . "(".$type.")" : $record['lastName']. " " . $record['firstName'] . " (".$type.")");
    $event_name = substr($event_name,0,64);
    $meetingInputData = Array
    (
        'name' => $event_name,
        'startDateTime' => $startStamp, // '2012-05-08 11:21:36',
        'endDateTime' => $endStamp,
        'location' =>  $event['location'], 
        'description' => $event['description'] ,
        'category' => Array
            (
                'value' => $event['type'] // discriminare la categoria sualla base di $event
            ),
        'modelRelations' => array(
            'activityItems' => array(
                array(
                    'action' => 'add', 
                    'modelId' =>$record["id"], 
                    'modelClassName' => ucfirst( ( $type=='lead' ?'contact':$type) )
                ),
            ),
        ),
    );
    $zcl->setEntityType('meeting');
    return $zcl->create($meetingInputData);  
}


// $event['subject'] $event['description'] $event['type'] $event['location']
function create_zurmo_task_for_entity($zcl,$type,$record,$event) {
    $remove_five_minutes = strtotime("+5 minutes");
    $add_sixtyfive_minutes = strtotime("+1 day");
    $startStamp = date("Y-m-d H:i:s", $remove_five_minutes);
    $endStamp =  date("Y-m-d H:i:s",$add_sixtyfive_minutes);
    $event_name = $event['subject'] . " - " . ( $type=='account' ? $record['companyName'] . "(".$type.")" : $record['lastName']. " " . $record['firstName'] . " (".$type.")");
    $description = $event_name . "\n";
    $description .= $event['description'];
    $event_name = substr($event_name,0,64);
    $meetingInputData = Array
    (
        'name' => $event_name,
        'dueDateTime' => $endStamp, // '2012-05-08 11:21:36',
        'description' => $description ,
        'status' => 1,
        'modelRelations' => array(
            'activityItems' => array(
                array(
                    'action' => 'add', 
                    'modelId' =>$record["id"], 
                    'modelClassName' => ucfirst( ( $type=='lead' ?'contact':$type) )
                ),
            ),
        ),
    );
    $zcl->setEntityType('task');
    return $zcl->create($meetingInputData);  
}




?>
