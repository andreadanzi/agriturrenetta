<?php
include_once( '../zurmolib/zurmolib.php' );
include_once( '../zurmolib/zurmo_functions.php' );
echo "Test Zurmo Api\n";
$crm_url = "http://localhost/zurmo";
$crm_username = "super";
$crm_password = "admin";
$test_email = "ale.menapace20@gmail.com";
$add_five_minutes = strtotime("+5 minutes");
$startStamp = date("Y-m-d H:i:s");
$endStamp =  date("Y-m-d H:i:s",$add_five_minutes);             

$contactInputData = Array
    (
        'firstName' => 'Michael C',
        'lastName' => 'Smith C',
        'jobTitle' => 'President',
        'department' => 'Sales',
        'officePhone' => '653-235-7824',
        'mobilePhone' => '653-235-7821',
        'officeFax' => '653-235-7834',
        'description' => 'Some desc.',
        'companyName' => 'Michael Co CC',
        'website' => 'http://sample.com',
        'industry' => Array
            (
                'value' => 'Financial Services'
            ),

        'source' => Array
            (
                'value' => 'Outbound'
            ),

        'title' => Array
            (
                'value' => 'Dr.'
            ),

        'state' => Array
            (
                'id' => 5
            ),

        'primaryEmail' => Array
            (
                'emailAddress' => $test_email,
                'optOut' => 1,
            ),

        'secondaryEmail' => Array
            (
                'emailAddress' => 'b@example.com',
                'optOut' => 0,
                'isInvalid' => 1,
            ),

        'primaryAddress' => Array
            (
                'street1' => '129 Noodle Boulevard',
                'street2' => 'Apartment 6000A',
                'city' => 'Noodleville',
                'postalCode' => '23453',
                'country' => 'The Good Old US of A',
            ),

        'secondaryAddress' => Array
            (
                'street1' => '25 de Agosto 2543',
                'street2' => 'Local 3',
                'city' => 'Ciudad de Los Fideos',
                'postalCode' => '5123-4',
                'country' => 'Latinoland',
            ),
    );








$zcl = new ZurmoClient($crm_url."/app/index.php",$crm_username, $crm_password,"contact");

$ret_login = $zcl->login();
// $ret_login = zurmo_login($crm_url."/app/index.php/zurmo/api/login",$crm_username, $crm_password);
if(!empty($ret_login) && $ret_login['status'] == 'SUCCESS') {
    echo "Sei connesso!\n";
    $headers = $zcl->getLoginHeaders();
    
    $found_res = find_entity_by_email($zcl,$test_email);
    
    if(  $found_res['status'] == 'SUCCESS' ) {
        echo "success\n";
        if(empty($found_res['data'])) {
            // nothing found
            echo "nothing found!\n";
        } else {
            // something's there
            foreach ($found_res['data'] as $entity_type => $items)
            {
                echo $entity_type."\n";
                foreach($items as $item) {
                    echo 'id = '. $item['id']. "\n";
                    echo 'primaryEmail = '. $item['primaryEmail']. "\n";
                    $event = array();
                    $event['subject']= "Subject Prova";
                    $event['description']= "Descr Prova";
                    $event['type']= "Web request";
                    $event['location']= "Web";
                    $newevent = create_event_for_entity($zcl,$entity_type,$item,$event);
                    echo "Evento creato!\n";
                }
            }
        }
    } else {
        echo "fail\n";
        print_r($found_res);
    }
    die("STOOOP!\n");
    $data = array(
        'dynamicSearch' => array(
            'dynamicClauses' => array(
                array(
                    'attributeIndexOrDerivedType' => 'primaryEmail',
                    'structurePosition' => 1,
                    'primaryEmail' =>  array('emailAddress' => $test_email),
                ),
            ),
            'dynamicStructure' => '1 AND 2',
        ),
        'pagination' => array(
            'page'     => 1,
            'pageSize' => 2,
        ),
        'sort' => 'firstName.asc',
    );
    $response = $zcl->listFiltered($data);
    // Get first page of results
    // $response = ApiRestHelper::createApiCall($crm_url."/app/index.php/contacts/contact/api/list/filter/", 'POST', $headers, array('data' => $data));
    // leads differenza , a aprte url , è che leads ha array["state"]["id"]=1 mentre contatto array["state"]["id"]=5 $response = ApiRestHelper::createApiCall($crm_url."/app/index.php/leads/contact/api/list/filter/", 'POST', $headers, array('data' => $data));
    
    if ($response['status'] == 'SUCCESS')
    {
        // Do something with results
        if ($response['data']['totalCount'] > 0)
        {
            foreach ($response['data']['items'] as $item)
            {
                echo 'id = '. $item['id']. "\n";
                echo 'primaryEmail = '. $item['primaryEmail']['emailAddress']. "\n";
                echo 'account_id = '. $item['account']['id']. "\n";
                echo 'firstName = '. $item['firstName']. "\n";
                echo 'lastName = '. $item['lastName']. "\n";
                echo 'companyName = '. $item['companyName']. "\n";
                echo 'officePhone = '. $item['officePhone']. "\n";
                echo "Contatto trovato!\n";
                $event = array();
                $event['subject']= "Subject Prova";
                $event['description']= "Descr Prova";
                $event['type']= "Web request";
                $event['location']= "Web";
                $newevent = create_event_for_entity($zcl,"contact",$item,$event);
                echo "Evento creato!\n";
            }
        }
        else
        {
            echo "Nessun contatto rispecchia i criteri!\n";
            $data = array(
                'dynamicSearch' => array(
                    'dynamicClauses' => array(
                        array(
                            'attributeIndexOrDerivedType' => 'name',
                            'structurePosition' => 1,
                            'name' =>  $contactInputData['companyName'],
                        ),
                    ),
                    'dynamicStructure' => '1',
                ),
                'pagination' => array(
                    'page'     => 1,
                    'pageSize' => 2,
                ),
                'sort' => 'name.asc',
            );
            $zcl->setEntityType("account");
            $response = $zcl->listFiltered($data);
            // Get first page of results
            // $response = ApiRestHelper::createApiCall($crm_url."/app/index.php/accounts/account/api/list/filter/", 'POST', $headers, array('data' => $data));
            // $response = json_decode($response, true);
            if ($response['status'] == 'SUCCESS')    {
                $account_id = -1;
                // Do something with results
                if ($response['data']['totalCount'] > 0) {
                    foreach ($response['data']['items'] as $item)
                    {
                        print_r($item);
                        $account_id = $item["id"];
                        echo "Azienda trovata!\n";
                    }
                } else {
                    $accdata = Array(
                        'name' => $contactInputData["companyName"],
                        'officePhone' =>  $contactInputData["officePhone"],
                        'officeFax' => $contactInputData["officeFax"],
                        'website' => $contactInputData["website"],
                        'description' => $contactInputData["description"],
                        'industry' => $contactInputData["industry"], 
                        'billingAddress' => $contactInputData["primaryAddress"]
                    );            
                    // $response = ApiRestHelper::createApiCall($crm_url.'/app/index.php/accounts/account/api/create/', 'POST', $headers, array('data' => $accdata));
                    // $response = json_decode($response, true);
                    
                    $response = $zcl->create($accdata);
                    if ($response['status'] == 'SUCCESS')
                    {
                        $account_data = $response['data'];
                        //Do something with contact data
                        print_r($account_data);
                        $account_id = $account_data["id"];
                        echo "Azienda creata\n";
                    }
                    else
                    {
                        // Error
                        $errors = $response['errors'];
                        // Do something with errors, show them to user
                         echo "Errori nella creazione di Azienda\n";
                    }
                    
                }
                
                $zcl->setEntityType("contact");
                $contactInputData['account']['id'] = $account_id;
                $data = $contactInputData;
                // $response = ApiRestHelper::createApiCall($crm_url.'/app/index.php/contacts/contact/api/create/', 'POST', $headers, array('data' => $data));
                // $response = json_decode($response, true);
                $response = $zcl->create($data);
                if ($response['status'] == 'SUCCESS')
                {
                    $contact = $response['data'];
                    //Do something with contact data
                    print_r($contact);
                    echo "Contatto creato\n";
                }
                else
                {
                    // Error
                    $errors = $response['errors'];
                    // Do something with errors, show them to user
                     echo "Errori nella creazione del Contatto\n";
                }
            }
            else
            {
                $errors = $response['errors'];
                // Do something with errors
                echo "Errori nella ricerca di Azienda\n";
            }
        }
    }
    else
    {
        $errors = $response['errors'];
        // Do something with errors
        echo "Errori nella ricerca di Contatto\n";
    }
    
} else {
    echo "Connessione fallita!\n";

}



?>
