<?php
include_once( '../vtwsclib/vtwsclib.php' );
echo "Test VTIGER Api\n";
$crm_url = "http://localhost/vtiger62";
$crm_username = "admin";
$crm_password = "bQU6yRXm1bOfGVPj";
$test_email = "andrea.dnz1983@gmail.com";
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


$event = array('type'=>'Custom Channel',
	       'subject'=>'subject text',
	       'location'=>'location text',
	       'description'=>'extended description');
$vt_url = $crm_url;
$client = new VtigerClient($vt_url,$crm_username, $crm_password);
$checkLogin = $client->login();
$out_result = "";
if($checkLogin) {
  // converts like this {"type":"Custom Channel","subject":"subject text","location":"location text","description":"extended description"}
  $evt_parmstr = $client->toParameterString($event);
  print_r($evt_parmstr );
  echo "\nConnessione sembra OK per ".$client->_userid."\n";
  $opParms = array(
                    "email"=>$test_email,
                    "element"=>$evt_parmstr ,
                    );
  // $retOp = $client->doOperation("process_email",$opParms);
  // print_r($retOp);
  $found_res = find_vtiger_entity_by_email($client, $test_email);
    if($found_res["status"] == 'SUCCESS') {
	    $bFound = false;
	    foreach($found_res["data"] as $module_key=>$entities) {
		    $out_result .= "\n<strong>for " .$module_key."</strong>";
		    foreach($entities as $entity) {
			    foreach($entity as $key=>$val) {
				    $out_result .= "\n".$key." = " .$val;
			    }
		        $bFound = true;
                $evnt_ret = create_vtiger_event_for_entity($client,$module_key,$entity,$event);
                print_r($evnt_ret);
		    }
	    }
	    if( !$bFound  ) {
		    $out_result .= " but nothing found!\n";
		    $record = array(
		                    'first_name'=>'First Name ABCDE3',
                            'last_name'=>'Last Name ABCDE3',
                            'company_name'=>'Company ABCDE3',
                            'user_email'=>$test_email ,
                            'addr1'=>'via sommadossi',
                            'city'=>'CittA',
                            'thestate'=>'TN',
                            'country'=>'IT',
                            'zip'=>'38010',
                            'phone1'=>'+39 046356565',
                            'user_url'=>'www.cicciosilava.it',
                            'business_type'=>'Engineering',
                            'description'=>'Descrizione Estesa'
		    );
            $response = create_new_vtiger_lead($client,'Custom Channel', 'subject text', $record);
            print_r($response);

            if ($response["status"] == 'SUCCESS')
            {
                $record_id = $response["data"]["id"];
                $record = array();
                $record['companyName'] = $response["data"]["companyName"];
                $record['firstName'] = $response["data"]["firstName"];
                $record['lastName'] = $response["data"]["lastName"];
                $record['id'] = $response["data"]["id"];
                $evnt_ret = create_vtiger_event_for_entity($client,"Leads",$entity,$event);
                print_r($evnt_ret);
            } 


	    }
    } else {
	    $out_result .= " but searching for ".$test_email." failed, with message ".$found_res["errors"];
    }
    echo $out_result;

} else {
  echo "Connessione fallita!\n";
  print_r($client);
}




?>
