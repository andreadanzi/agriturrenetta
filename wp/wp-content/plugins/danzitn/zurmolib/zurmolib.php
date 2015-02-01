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

public function zurmo_login($zurmo_url,$username, $password)
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
