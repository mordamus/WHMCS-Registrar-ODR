<?php
// Require ODR API demo class
require_once 'Odr_commands.php';

// Configuration array, with user API Keys

function ODR_getConfigArray() {
	$configarray = array(
	 "APIKey" => array( "Type" => "text", "Size" => "50"),
	 "APISecret" => array( "Type" => "text", "Size" => "50"),
	 "Debug" => array( "Type" => "yesno", "Default" => "no", ),
	);
	return $configarray;
}

function ODR_GetNameservers($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get the nameservers here and return the values below
	$values["ns1"] = $nameserver1;
	$values["ns2"] = $nameserver2;
    $values["ns3"] = $nameserver3;
    $values["ns4"] = $nameserver4;
	# If error, return the error message in the value below
	$values["error"] = $error;
	return $values;
}

function ODR_SaveNameservers($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver1 = $params["ns1"];
	$nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
	$nameserver4 = $params["ns4"];
	# Put your code to save the nameservers here
	# If error, return the error message in the value below
	$values["error"] = $error;
	return $values;
}

function ODR_GetRegistrarLock($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get the lock status here
	if ($lock=="1") {
		$lockstatus="locked";
	} else {
		$lockstatus="unlocked";
	}
	return $lockstatus;
}

function ODR_SaveRegistrarLock($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	if ($params["lockenabled"]) {
		$lockstatus="locked";
	} else {
		$lockstatus="unlocked";
	}
	# Put your code to save the registrar lock here
	# If error, return the error message in the value below
	$values["error"] = $Enom->Values["Err1"];
	return $values;
}

function ODR_GetEmailForwarding($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get email forwarding here - the result should be an array of prefixes and forward to emails (max 10)
	foreach ($result AS $value) {
		$values[$counter]["prefix"] = $value["prefix"];
		$values[$counter]["forwardto"] = $value["forwardto"];
	}
	return $values;
}

function ODR_SaveEmailForwarding($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	foreach ($params["prefix"] AS $key=>$value) {
		$forwardarray[$key]["prefix"] =  $params["prefix"][$key];
		$forwardarray[$key]["forwardto"] =  $params["forwardto"][$key];
	}
	# Put your code to save email forwarders here
}

function ODR_GetDNS($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    # Put your code here to get the current DNS settings - the result should be an array of hostname, record type, and address
    $hostrecords = array();
    $hostrecords[] = array( "hostname" => "ns1", "type" => "A", "address" => "192.168.0.1", );
    $hostrecords[] = array( "hostname" => "ns2", "type" => "A", "address" => "192.168.0.2", );
	return $hostrecords;
}

function ODR_SaveDNS($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    # Loop through the submitted records
	foreach ($params["dnsrecords"] AS $key=>$values) {
		$hostname = $values["hostname"];
		$type = $values["type"];
		$address = $values["address"];
		# Add your code to update the record here
	}
    # If error, return the error message in the value below
	$values["error"] = $Enom->Values["Err1"];
	return $values;
}

function ODR_TransferDomain($params) {

	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);

	$demo->login();

	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		echo 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}
	$data_contact[first_name] = trim($params["firstname"]);
	$data_contact[middle_name] = "-";
	$data_contact[last_name] = trim($params["lastname"]);
	$data_contact[full_name] = trim($params["firstname"]) . ' ' .trim($params["lastname"]);
	$data_contact[initials] = trim($params["firstname"][0]) . trim($params["lastname"][0]);
	$data_contact[birthday] = "1970-01-01";
	$data_contact[gender] = "NA";
	$data_contact[language] = $params["country"];
	$data_contact[email] = $params["email"];
	$data_contact[phone] = format_phone($params["phonenumber"],$params["country"]);
	$data_contact[postal_code] = $params["postcode"];
	$data_contact[country] = $params["country"];
	$data_contact[state] = $params["state"];
	$data_contact[city] = $params["city"];
	$data_contact[street] = trim(format_street($params["address1"],1));
	$data_contact[house_number] = format_street($params["address1"],2);	
	$data_contact[organization_legal_form] = "ANDERS";
	$data_contact[company_name] = format_company_name($params);
	$data_contact[company_email] = $params["email"];	
	$data_contact[company_phone] = format_phone($params["phonenumber"],$params["country"]);	
	$data_contact[company_postal_code] = $params["postcode"];
	$data_contact[company_city] = $params["city"];	
	$data_contact[company_street] = trim(format_street($params["address1"],1));
	$data_contact[company_house_number] = format_street($params["address1"],2);	
	$data_contact[url] = "http:\/\/".$params["sld"].".".$params["tld"];
	$data_contact[company_url] = "http:\/\/".$params["sld"].".".$params["tld"];
	$data_contact[company_vatin] = "TAX54677422";

	$demo->createContact($data_contact);
	$result_contact = $demo->getResult();

	if ($result_contact['status'] !== 'success') 
	{
		echo 'Following error occured by creating contact: '. $result_contact['response'];
		print_r($data_contact);
		exit(1);
		
	}

	$data_domain["auth_code"] = $params["transfersecret"];
	$data_domain["number_months"] = $params["regperiod"]*12;
	
	$data_domain["handle_registrant"] = $result_contact['response']['created_contact_id'];
    $data_domain["handle_onsite"] = $result_contact['response']['created_contact_id'];
    $data_domain["handle_tech"] = $result_contact['response']['created_contact_id'];

	$data_domain["handle_ns1"]["host"] = $params["ns1"];
	$data_domain["handle_ns2"]["host"] = $params["ns2"];

	$demo->registerDomain($params['domainname'], $data_domain);
	$result_domain = $demo->getResult();
	
	if ($result_domain['status'] !== 'success') 
	{
		echo 'Following error occured by transfering domain: '. $result_domain['response'];
        if ($params["Debug"])
		{			
			print_r($result_contact);
			print_r($data_domain);
		}
		exit(1);
	}

    $values["error"] = $error;
    return $values;
}

function ODR_RenewDomain($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	$regperiod = $params["regperiod"];
	# Put your code to renew domain here
	# If error, return the error message in the value below
	$values["error"] = $error;
	return $values;
}

function ODR_GetContactDetails($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Put your code to get WHOIS data here
	# Data should be returned in an array as follows
	$values["Registrant"]["First Name"] = $firstname;
	$values["Registrant"]["Last Name"] = $lastname;
	$values["Admin"]["First Name"] = $adminfirstname;
	$values["Admin"]["Last Name"] = $adminlastname;
	$values["Tech"]["First Name"] = $techfirstname;
	$values["Tech"]["Last Name"] = $techlastname;
	return $values;
}

function ODR_SaveContactDetails($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	# Data is returned as specified in the GetContactDetails() function
	$firstname = $params["contactdetails"]["Registrant"]["First Name"];
	$lastname = $params["contactdetails"]["Registrant"]["Last Name"];
	$adminfirstname = $params["contactdetails"]["Admin"]["First Name"];
	$adminlastname = $params["contactdetails"]["Admin"]["Last Name"];
	$techfirstname = $params["contactdetails"]["Tech"]["First Name"];
	$techlastname = $params["contactdetails"]["Tech"]["Last Name"];
	# Put your code to save new WHOIS data here
	# If error, return the error message in the value below
	$values["error"] = $error;
	return $values;
}

function ODR_GetEPPCode($params) 
{
	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);
	$demo->login();
	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		$values["error"] = 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}

	$result = $demo->custom('/domain/auth-code/'. $params["domainname"] .'/', Api_Odr::METHOD_GET);

	if ($result['is_error'] === true || $result['data']['status'] === 'error') {
		if ($result['is_error'] === true) {
			$values["error"] = 'Following error occured: '. $result['error_msg'];
		} else {
			$values["error"] = 'Following error occured: '. $result['data']['response'];
		}
		exit();
	}
	
	if (!empty($result['data']['response']['auth_code'])) 
	{
		$values["eppcode"] = $result['data']['response']['auth_code'];
    } else {
		$values["error"] = "Voor dit domein bestaat geen EPP Code.";
	}
    return $values;
}

function ODR_TransferSync($params) {
	
	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);
	$demo->login();
	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		$values["error"] = 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}

	$result = $demo->custom('/domain/'. $params['domainname'] .'/', Api_Odr::METHOD_GET);

	if ($result['data']['status'] != 'success')
	{
		$values["error"] = 'Following error occured: '. $result['response'];
		exit(1);
	}
	
	if ($result['data']['response']['domain_status'] == "REGISTERED")
	{
		$values['completed'] = true;
		$values['expirydate'] = $result['data']['response']['domain_expiration_date'];
	}
	elseif ($result['data']['response']['domain_status'] == "PENDING")
	{
		$values['error'] = "Domain " . $domain_id . " is still in the following status: " . $result['data']['response']['domain_status'];
	}
	else
	{
		$values['failed'] = true;
		$values['reason'] = "Domain " . $domain_id . " is currently in the following status: " . $result['data']['response']['domain_status'];
	}
	
    return $values;
}

function ODR_Sync($params) {

	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);
	$demo->login();
	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		$values["error"] = 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}

	$result = $demo->custom('/domain/'. $params['domainname'] .'/', Api_Odr::METHOD_GET);

	if ($result['data']['status'] != 'success')
	{
		
		$values["error"] = 'Following error occured: '. $result['response'];
		echo $values["error"];
		exit(1);
	}
	
	if ($result['data']['response']['domain_status'] == "REGISTERED")
	{
		$values['active'] = true;
	}
	elseif ($result['data']['response']['domain_status'] == "DELETED")
	{
		$values['expired'] = true;
	}
	
	$values['expirydate'] = $result['data']['response']['domain_expiration_date'];
	
    return $values;
	
}

function ODR_RequestDelete($params)
{	
	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);
	$demo->login();
	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		$values["error"] = 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}

	$result = $demo->custom('/domain/renew-off/'. $params["domainname"] .'/', Api_Odr::METHOD_PUT);
	
		if ($result['data']['status'] != 'success')
	{
		$values["error"] = 'Following error occured: '. $result['data']['response'];
		exit(1);
	}
	
	return $values;
}



function ODR_RegisterNameserver($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    # Put your code to register the nameserver here
    # If error, return the error message in the value below
    $values["error"] = $error;
    return $values;
}

function ODR_ModifyNameserver($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    # Put your code to update the nameserver here
    # If error, return the error message in the value below
    $values["error"] = $error;
    return $values;
}

function ODR_DeleteNameserver($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$testmode = $params["TestMode"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    $nameserver = $params["nameserver"];
    # Put your code to delete the nameserver here
    # If error, return the error message in the value below
    $values["error"] = $error;
    return $values;
}

function format_phone($number, $country)
{
  	//remove starting zero
	$number = ltrim($number, '0');
		
	switch (strtolower ($country))
	{
		case 'be':
		{
			if (substr($number,0,3) == "+32.")
			{
				return $number;
			} else {
				return '+32.' . $number;
			}
			break;
		}
		default:
		{
			if (substr($number,0,3) == "+31.")
			{
				return $number;
			} else {
				return '+31.' . $number;
			}
			break;
		}
	}
}
	
function format_language($country)
{
  switch (strtolower ($country))
	{
		case 'be':
		{
			return 'nl';
			break;
		}
		default:
		{
			return  strtolower($country);
			
			break;
		}
	}
}
	
function format_street($adres, $part) {
	preg_match("/([A-Za-z ]+)(\d+)\s?(\w*)/",$adres, $return_adres);
	return $return_adres[$part];
}
	
function format_company_name($params) {
	if ($params["companyname"] != "")
	{
		return $params["companyname"];
	}
	else
	{
		return trim($params["firstname"]) . ' ' .trim($params["lastname"]);
	}
}

function ODR_RegisterDomain($params, $config) {
	
	$config = array(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	$demo = new Api_Odr($config);

	$demo->login();

	$result = $demo->getResult();

	if ($result['status'] === 'error') {
		echo 'Can\'t login, reason - '. $result['response'];
		exit(1);
	}
	$data_contact[first_name] = trim($params["firstname"]);
	$data_contact[middle_name] = "-";
	$data_contact[last_name] = trim($params["lastname"]);
	$data_contact[full_name] = trim($params["firstname"]) . ' ' .trim($params["lastname"]);
	$data_contact[initials] = trim($params["firstname"][0]) . trim($params["lastname"][0]);
	$data_contact[birthday] = "1970-01-01";
	$data_contact[gender] = "NA";
	$data_contact[language] = $params["country"];
	$data_contact[email] = $params["email"];
	$data_contact[phone] = format_phone($params["phonenumber"],$params["country"]);
	$data_contact[postal_code] = $params["postcode"];
	$data_contact[country] = $params["country"];
	$data_contact[state] = $params["state"];
	$data_contact[city] = $params["city"];
	$data_contact[street] = trim(format_street($params["address1"],1));
	$data_contact[house_number] = format_street($params["address1"],2);	
	$data_contact[organization_legal_form] = "ANDERS";
	$data_contact[company_name] = format_company_name($params);
	$data_contact[company_email] = $params["email"];	
	$data_contact[company_phone] = format_phone($params["phonenumber"],$params["country"]);	
	$data_contact[company_postal_code] = $params["postcode"];
	$data_contact[company_city] = $params["city"];	
	$data_contact[company_street] = trim(format_street($params["address1"],1));
	$data_contact[company_house_number] = format_street($params["address1"],2);	
	$data_contact[url] = "http:\/\/".$params['domainname'];
	$data_contact[company_url] = "http:\/\/".$params['domainname'];
	$data_contact[company_vatin] = "TAX54677422";

	$demo->createContact($data_contact);
	$result_contact = $demo->getResult();

	if ($result_contact['status'] !== 'success') 
	{
		echo 'Following error occured: '. $result_contact['response'];
		exit(1);
	}

	$data_domain["domain_period"] = $params["regperiod"];
	$data_domain["number_months"] = $params["regperiod"]*12;

	$data_domain["handle_registrant"] = $result_contact['response']['contact_id'];
    $data_domain["handle_onsite"] = $result_contact['response']['contact_id'];
    $data_domain["handle_tech"] = $result_contact['response']['contact_id'];
	$data_domain["handle_ns1"]["host"] = $params["ns1"];
	$data_domain["handle_ns2"]["host"] = $params["ns2"];

	print_r($data_domain);
	$demo->registerDomain($params['domainname'], $data_domain);
	$result_domain = $demo->getResult();
	
	if ($result_domain['status'] !== 'success') 
	{
		echo 'Following error occured: '. $result_domain['response'];
		exit(1);
	}

    $values["error"] = $error;
    return $values;
}