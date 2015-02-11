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
        $this->entitytypes = array('account','contact','lead');
        if(in_array($entitytype,$this->entitytypes)) { 
            $this->entitytype = $entitytype;
            $this->zurmo_api_url = $zurmo_url."/".$entitytype."s/".$entitytype."/api/";
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
            $this->zurmo_api_url = $zurmo_url."/".$entitytype."s/".$entitytype."/api/";
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


function zurmo_login($zurmo_url,$username, $password)
{
    $headers = array(
        'Accept: application/json',
        'ZURMO_AUTH_USERNAME: ' . $username,
        'ZURMO_AUTH_PASSWORD: ' . $password,
        'ZURMO_API_REQUEST_TYPE: REST',
    );
    $response = ApiRestHelper::createApiCall($zurmo_url, 'POST', $headers);
    $response = json_decode($response, true);

    if ($response['status'] == 'SUCCESS')
    {
        return $response;
    }
    else
    {
        return false;
    }
}

?>
