<?php
// Require ODR API demo class
require_once 'Odr_commands.php';
require_once 'Odr_exception.php';

// Configuration array, with user API Keys

function ODR_getConfigArray() {
	$configarray = array
	(
		"Debug" => array( "FriendlyName" => "Debug", "Type" => "yesno"),
		"APIKey" => array( "FriendlyName" => "API Key", "Type" => "text", "Size" => "50"),
		"APISecret" => array( "FriendlyName" => "API Secret", "Type" => "password", "Size" => "50"),
		"Adminuser" => array( "FriendlyName" => "Admin user", "Type" => "text", "Default" => "admin", "Size" => "50"),
		"Synccontact" => array( "FriendlyName" => "Sync Contact", "Type" => "yesno"),
		"Syncdomain" => array( "FriendlyName" => "Sync Domain", "Type" => "yesno"),
		"Primairydomain" => array( "FriendlyName" => "Primairy domain", "Type" => "text", "Size" => "50"),
	);
	return $configarray;
}

function ODR_Login($module)
{
	$module->login();

	if ($result['status'] === Api_Odr::STATUS_ERROR) {
		return 'Can\'t login, reason - '. $loginResult['response'];
	}
	
	return "OK";
}

function ODR_Config($params)
{
	$module = array
	(
		'api_key'    => $params["APIKey"],
		'api_secret' => $params["APISecret"],
	);

	return new Api_Odr($module);
}

function ODR_GetNameservers($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	} 
	//Check if domain is active
	$values["domainid"] = $params['domainid'];
	$result = localAPI("getclientsdomains",$values,$params["Adminuser"]);
	logModuleCall("ODR", "GetNameservers", $params, $result, "", "");
	
	if ($result['domains']['domain'][0]['status'] == 'Active')
	{
		$raw = $module->custom('/domain/info/'. $params["domainname"] .'/', Api_Odr::METHOD_GET);
		$result = $raw->getResult();
		logModuleCall("ODR", "GetNameservers | Retrieve domain details", $params["domainname"], $result, "", "");
		
 		if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		{
			$values["error"] = 'Following error occurred: '. $result['response']['message'];
			return $values;
		}

		$values["ns1"] = $result['response']['nameservers'][0]['host'];
		$values["ns2"] = $result['response']['nameservers'][1]['host'];
		$values["ns3"] = $result['response']['nameservers'][2]['host'];
		$values["ns4"] = $result['response']['nameservers'][3]['host'];
		$values["ns5"] = $result['response']['nameservers'][4]['host'];
	}
	return $values;
}

function ODR_SaveNameservers($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	}
	
	//Get current domain data
	$raw = $module->custom('/domain/info/'. $params["domainname"] .'/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();
	logModuleCall("ODR", "SaveNameservers | Get orginal value", $params["domainname"], $result, "", "");
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
	}

	$data["number_months"] = $params["regperiod"] * 12;
	$data["handle_registrant"] = $result['response']['handle_registrant'];
	$data["handle_onsite"] = $result['response']['handle_tech']; //onsite is not aviable in the result
	$data["handle_tech"] = $result['response']['handle_tech'];
	$data["ns1"] = $params["ns1"];
	$data["ns2"] = $params["ns2"];
	$data["ns3"] = $params["ns3"];
	$data["ns4"] = $params["ns4"];
	$data["ns5"] = $params["ns5"];
	
	//Save new domain data
	$raw = $module->custom('/domain/'. $params["domainname"] .'/', Api_Odr::METHOD_PUT, $data);
	$result = $raw->getResult();
	logModuleCall("ODR", "SaveNameservers | Save new value" , $data, $result, "", "");
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
	}
	
	return $values;
}

function ODR_RegisterDomain($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	}

	//check if contacts exist
	$uniquename = format_uniquename($params["firstname"], $params["lastname"], $params["companyname"]);
	$contact_id = ODR_search_contact($uniquename, $module);
	logModuleCall("ODR", "TransferDomain | List contacts" , $uniquename , $contact_id, "", "");
	
	//contact doesn't exist, create a new
	if (!$contact_id > 0)
	{		
		$data_contact = ODR_format_contact_whmcs($params);
	
		$raw = $module->custom('/contact/', Api_Odr::METHOD_POST, $data_contact);
		$result = $raw->getResult();
		logModuleCall("ODR", "RegisterDomain | Create contact" , $data_contact, $result, "", "");
		
		if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		{
			$values["error"] = 'Following error occurred: '. $result['response']['message'];
			return $values;
		}
	
		$contact_id = $result_contact['response']['created_contact_id'];
	}

	$data_domain["number_months"] = $params["regperiod"]*12;	
	$data_domain["contact_registrant"] = $contact_id;
	$data_domain["contact_onsite"] = $contact_id;
	$data_domain["contact_tech"] = $contact_id;
	$data_domain["handle_ns1"]["host"] = $params["ns1"];
	$data_domain["handle_ns2"]["host"] = $params["ns2"];

	$raw = $module->custom('/domain/'. trim($params['domainname']) .'/', Api_Odr::METHOD_POST, $data_domain);
	$result = $raw->getResult();
	logModuleCall("ODR", "RegisterDomain | Register domain" , $data_domain, $result, "", "");
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
	}	

    $values["error"] = $error;
    return $values;
}

function ODR_TransferDomain($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	}

	//check if contacts exist
	$uniquename = format_uniquename($params["firstname"], $params["lastname"], $params["companyname"]);
	$contact_id = ODR_search_contact($uniquename, $module);
	logModuleCall("ODR", "TransferDomain | List contacts" , $uniquename , $contact_id, "", "");

	//contact doesn't exist, create a new
	if ($contact_id == 0)
	{		
		$data_contact = ODR_format_contact_whmcs($params);
	
		$raw = $module->custom('/contact/', Api_Odr::METHOD_POST, $data_contact);
		$result = $raw->getResult();
		logModuleCall("ODR", "TransferDomain | Create contact" , $data_contact, $result, "", "");
		
		if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		{
			$values["error"] = 'Following error occurred: '. $result['response']['message'];
			return $values;
		}
		
		$contact_id = $result['response']['data']['id'];
	}
	
	$data_domain["auth_code"] = $params["transfersecret"];
	$data_domain["number_months"] = $params["regperiod"]*12;	
	$data_domain["contact_registrant"] = $contact_id;
	$data_domain["contact_onsite"] = $contact_id;
	$data_domain["contact_tech"] = $contact_id;
	$data_domain["ns1"] = $params["ns1"];
	$data_domain["ns2"] = $params["ns2"];

	$raw = $module->custom('/domain/'. trim($params['domainname']) .'/transfer/', Api_Odr::METHOD_PUT, $data_domain);
	$result = $raw->getResult();
	logModuleCall("ODR", "TransferDomain | Transfer domain" , $data_domain, $result, "", "");
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
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

function ODR_GetEPPCode($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	} 

	$raw = $module->custom('/domain/auth-code/'. $params["domainname"] .'/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
	}

	if (!empty($result['response']['auth_code'])) 
	{
		$values["eppcode"] = $result['response']['auth_code'];
	} 
	else 
	{
		$values["error"] = "Voor dit domein bestaat geen EPP Code.";
	}

    return $values;
}

function ODR_TransferSync($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	} 

	$raw = $module->custom('/domain/'. $params['domainname'] .'/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();
	logModuleCall("ODR", "TransferSync | Retrieve domain status", $params, $result, "", "");

	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		$values["error"] = 'Following error occurred: '. $result['response']['message'];
		return $values;
	}
	
	if ($result['response']['status'] == "REGISTERED")
	{
		$values['completed'] = true;
		$values['expirydate'] = $result['response']['domain_expiration_date'];
	}
	elseif ($result['response']['status'] == "PENDING")
	{
		$values['error'] = "Domain " . $domain_id . " is still in the following status: " . $result['response']['domain_status'];
	}
	else
	{
		$values['failed'] = true;
		$values['reason'] = "Domain " . $domain_id . " is currently in the following status: " . $result['response']['domain_status'];
	}
	
    return $values;
}

function ODR_Sync_contact($module, $params, $handle_whmcs, $handle_odr)
{	
	//This function retrieves the contact details of the domain by WHMCS and ODR and compares them.
	//If there are differences a new contact is created

	//retrieve whmcs contact details
	$values["clientid"] = $handle_whmcs;
	$result = localAPI("getclientsdetails",$values,$params['Adminuser']);

	if ($result['result'] !== 'success')
	{
		logModuleCall("ODR Sync contact", "Retrieve contact details WHMCS |" . $params['domain'], $values["clientid"], $result, "", "");
		return array("status" => "error", "error" => 'Error occured while retrieving contact details in WHMCS for ' . $params['domain']);
	}
	
	$contact_whmcs = ODR_format_contact_whmcs($result);

	if ($handle_odr > 1000)
	{
		//retrieve odr contact details
		$raw = $module->custom('/contact/'. $handle_odr .'/', Api_Odr::METHOD_GET);
		$result = $raw->getResult();
		
		if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		{
			logModuleCall("ODR Sync contact", "Retrieve contact details ODR |" . $params['domain'], $handle_odr, $result, "", "");	
			return array("status" => "error", "error" => 'Error occurred while retrieving ODR contact for '. $params['domain']);
		}
		
		$contact_odr = ODR_format_contact_odr($result['response']['contact']);
	}
	
	//Check if the contact info is valid and differs between ODR and WHMCS
	if ($contact_odr['full_name'] !== $contact_whmcs['full_name'] || $contact_odr['company_name'] !== $contact_whmcs['company_name'] || $contact_odr['organization_legal_form'] !== $contact_whmcs['organization_legal_form'] || !$handle_odr > 1000)
	{
		logModuleCall("ODR Sync contact", "Check contact |" . $params['domain'], $contact_whmcs, $contact_odr, "", "");
		
		if (!$params['Synccontact'])
		{		
			//Create a new contact
			$raw = $module->custom('/contact/', Api_Odr::METHOD_POST, $contact_whmcs);
			$result = $raw->getResult();
			
			if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
			{
				logModuleCall("ODR Sync contact", "Create ODR contact |" . $params['domain'], $contact_whmcs, $result, "", "");
				return array("status" => "error", "error" => 'Error occured while creating ODR contact for  '. $params['domain']);
			}
			
			$values["description"] = "[INFO] Sync contact | Created contact for " . $params['domain'];
			localAPI("logactivity",$values,$params['Adminuser']);
		
			return array("status" => "success", "handle" => $result['response']['created_contact_id']);
		}
		else
		{
			$values["description"] = "[INFO] Sync contact | Domain contact differs from WHMCS for " . $params['domain'];

			localAPI("logactivity",$values,$params['Adminuser']);			
			
			$values["description"] .= '<table>';
			$values["description"] .= '<tr><td>-</td><td>ODR</td><td>WHMCS</td></tr>';			
			$values["description"] .= '<tr><td>Full name</td><td>' . $contact_odr['full_name'] . '</td><td>' . $contact_whmcs['full_name'] . '</td></tr>';
			$values["description"] .= '<tr><td>Company_name</td><td>' . $contact_odr['company_name'] . '</td><td>' . $contact_whmcs['company_name'] . '</td></tr>';
			$values["description"] .= '<tr><td>Organization legal form</td><td>' . $contact_odr['organization_legal_form'] . '</td><td>' . $contact_whmcs['organization_legal_form'] . '</td></tr>';
			$values["description"] .= '</table>';
			
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

			mail("admin@mordamus.nl", "Domain Contact Sync", $values["description"], $headers);
			
			//Were debugging, no contact is created
			return array("status" => "success", "handle" => $handle_odr);
		}
	}
	else
	{
		//compare whmcs and odr contact details
		$result = array_diff($contact_whmcs, $contact_odr);
		
		if (count($result) > 0)
		{
			logModuleCall("ODR Sync contact", "Compare WHMCS ODR contact |" . $params['domain'], $contact_whmcs, $contact_odr, "", "");
					
			$raw = $module->custom('/contact/'. $handle_odr .'/', Api_Odr::METHOD_PUT, $contact_whmcs);
			$result = $raw->getResult();
			
			if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
			{
				logModuleCall("ODR Sync contact", "Update ODR contact |" . $params['domain'], $handle_odr, $contact_whmcs, "", "");
				return array("status" => "error", "error" => 'Error occurred while updating contact for '. $params['domain']);
			}
				$values["description"] = "[INFO] Sync contact | Updated contact for " . $params['domain'];
				localAPI("logactivity",$values,$params['Adminuser']);
		}

		return array("status" => "success", "handle" => $handle_odr);
	}
}

function ODR_Sync_handle($module, $params, $domain_odr, $handle)
{
	//This function wil check if there are differences in the ODR handles. If there any the domain will be updated
	
	if ($handle > 1000 && (($handle != $domain_odr['handle_tech'] && $domain_odr['handle_tech'] > 1000 ) || ($handle!= $domain_odr['handle_admin'] && $domain_odr['handle_admin'] > 1000) || ($handle != $domain_odr['handle_onsite'] && $domain_odr['handle_onsite'] > 1000) || ($handle != $domain_odr['handle_registrant'] && $domain_odr['handle_registrant'] > 1000 )))
	{	
		$domain_whmcs['number_months'] = $domain_odr['number_months'];
		//!!Debug purpose!!
		//$domain_whmcs['handle_registrant'] = $handle;
		$domain_whmcs['handle_registrant'] = $domain_odr['handle_registrant'];
		$domain_whmcs['handle_onsite'] = $handle;
		$domain_whmcs['handle_tech'] = $handle;
		$domain_whmcs['handle_ns1'] = $domain_odr['handle_ns1'];
		$domain_whmcs['handle_ns2'] = $domain_odr['handle_ns2'];
		$domain_whmcs['handle_ns3'] = $domain_odr['handle_ns3'];
		
		$raw = $module->custom('/domain/'. $params['domain'] .'/', Api_Odr::METHOD_PUT, $domain_whmcs);
		$result = $raw->getResult();

		if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		{
			logModuleCall("ODR Sync handle", "| Update domain details ODR |" . $params['domain'], $domain_whmcs, $result, "", "");
			return array("status" => "error", "error" => 'Error occurred while updating ODR domain: '. $params['domain']);
		}

		$values["description"] = "Sync handle" . $params['domain'] . "| Changed handle for domain " . $params['domain'];
		localAPI("logactivity",$values,$params['Adminuser']);
	}
	return array("status" => "success");
}

function Sync_domain ($module, $params)
{
	$send_email = false;
	$values['limitnum'] = 9999;
	$values["custommessage"] = "<h3>ODR Domain Synchronisation Report 2</h3>";
	
	//retrieves all domains in WHMCS
	$domain_whmcs = localAPI("getclientsdomains", $values, $params['Adminuser']);
	if ($domain_whmcs['result'] !== 'success')
	{
		logModuleCall("ODR Sync contact", "Retrieve domains WHMCS |" . "", $values, $domain_whmcs, "", "");
		return array("status" => "error", "error" => 'Error occured while retrieving WHMCS domains');
	}
	
	//retrieves all domains in ODR
	$raw = $module->custom('/domain/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();
			logModuleCall("ODR Sync domain", "| Retrieve domains ODR2 |" . "", "", $result, "", "");
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		logModuleCall("ODR Sync domain", "| Retrieve domains ODR |" . "", "", $result, "", "");
		return array("status" => "error", "error" => 'Error occurred with retrieving ODR domains');
	}

	//Only checking the active ODR domains
	foreach ($result['response'] as $i => $row)
	{
		logModuleCall("ODR Sync domain", "| Retrieve domains ODR 4 |" . "", "", $row, "", "");
		if ($row['status'] == 'REGISTERED')
		{
			$domain_odr[] = $row['name'] + '.' + $row['tld'];
		}	
	}
	logModuleCall("ODR Sync domain", "| Retrieve domains ODR 33 |" . "", "", $domain_odr, "", "");

	//Loop through the WHMCS domains
	$values["custommessage"] .= "<h3>Active domains not in ODR</h3>";
	$values["custommessage"] .= "<ul>";

	foreach ($domain_whmcs['domains']['domain'] as $i => $row)
	{
		if ($row['registrar'] == "ODR" && ($row['status'] == "Active" || $row['status'] == "Expired"))
		{	
			if(($key = array_search($row['domainname'], $domain_odr)) !== false) 
			{
				unset($domain_odr[$key]);
			} 
			else
			{		
				$values["custommessage"] .= "<li>" . $row['domainname'] . "</li>";
				$send_email = true;
			}
		}
	}
	$values["custommessage"] .= "</ul>";

	//Loop through the ODR domains
	$values["custommessage"] .= "<h3>Active domains not in WHMCS</h3>";
	$values["custommessage"] .= "<ul>";

	foreach ($domain_odr as $i => $row)
	{
		// $raw = $module->custom('/domain/' . $row . '/' , Api_Odr::METHOD_GET);
		// $result = $raw->getResult();
		
		// if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
		// {
			// logModuleCall("ODR Sync domain", "| Filter active domains |" . $row, $row, $result, "", "");
			// $values["custommessage"] .= "<li>Error checking " . $result['response']['domain_name'] . "." . $result['response']['tld'] . "</li>";
		// }
		// else if ($result['response']['domain_status'] == "REGISTERED")
		// {	
				$values["custommessage"] .= "<li>" . $result['response']['domain_name'] . "." . $result['response']['tld'] . "</li>";
				$send_email = true;
		//}
	}
	
	if ($send_email)
	{	
		$values["customtype"] = "domain";
		$values["customsubject"] = "WHMCS  ODR Sync Job Activity";
		$values["id"] = $params['domainid'];
		$result = localAPI("sendemail", $values, $params['Adminuser']);

		if ($result['result'] !== 'success')
		{
			logModuleCall("ODR Sync domains", "Send email |" . "", $params, $result, "", "");
			return array("status" => "error", "error" => 'Error occured while sending the email rapport.');
		}
	}
	
	return array("status" => "success");
}

function ODR_Sync($params) 
{
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
		$values["error"] = $login;
		return $values;
	}

	//retrieve odr domain details
	$raw = $module->custom('/domain/info/'. $params['domain'] .'/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) 
	{
		$values["description"] = "[ERROR] Sync contact | Error retrieving domain details ODR for " . $params['domain'];
		localAPI("logactivity",$values,$params['Adminuser']);
				
		logModuleCall("ODR Sync", "Retrieve domain details ODR |" . $params['domain'], $params["domain"], $result, "", "");
		$values["error"] = $values["description"];
		return $values;
	}
	
	$domain_odr = $result['response'];
	
	//Sync domain status
	if ($domain_odr['domain_status'] == "REGISTERED")
	{
		$values['active'] = true;
	}
	elseif ($domain_odr['domain_status'] == "DELETED")
	{
		$values['expired'] = true;
	}
	
	//Sync domain expiry data
	$values['expirydate'] = $domain_odr['domain_expiration_date'];
	
	if ($params['Synccontact'])
	{
		//retrieve whmcs domain details
		$result = localAPI("getclientsdomains",$params,$params['Adminuser']);

		if ($result['result'] !== 'success')
		{
			$values["description"] = "[ERROR] Sync contact | Error retrieving domain details WHMCS for " . $params['domain'];
			localAPI("logactivity",$values,$params['Adminuser']);
			
			logModuleCall("ODR Sync", "Retrieve domain details WHMCS |" . $params['domain'], $params["domain"], $result, "", "");
			
			$values["error"] = $values["description"];
			return $values;
		}
		
		$domain_whmcs = $result['domains']['domain'][0];

		//Sync registrant contact
		$result = ODR_Sync_contact($module, $params, $domain_whmcs['userid'], $domain_odr['handle_registrant']);
		
		if ($result['status'] != "success")
		{
			$values["description"] = "[ERROR] Sync contact |" . $result["error"];
			localAPI("logactivity",$values,$params['Adminuser']);
			
			$values["error"] = $result["error"];
			return $values;
		}

		$handle = $result['handle'];
	
	
		//Sync odr handles
		$result = ODR_Sync_handle($module, $params, $domain_odr, $handle);
		
		if ($result['status'] != "success")
		{
			$values["description"] = "[ERROR] Sync handle |" . $result["error"];
			localAPI("logactivity",$values,$params['Adminuser']);
			
			$values["error"] = $result["error"];
			return $values;
		}
	}
	
	//Sync odr domains
	if ($params['Syncdomain'] && $params['Primairydomain'] == $params['domain'])
	{		
		$result = Sync_domain($module, $params);
		
		if ($result['status'] != "success")
		{
			$values["description"] = "[ERROR] Sync domain |" . $result["error"];
			localAPI("logactivity",$values,$params['Adminuser']);
			
			$values["error"] = $result["error"];
			return $values;
		}
	}
	
    return $values;
}

function ODR_RequestDelete($params)
{	
	$module = ODR_Config($params);
	
	$login = ODR_Login($module);
	if ($login != "OK")
	{
	   $values["error"] = $login;
	   return $values;
	} 

	$raw = $module->custom('/domain/renew-off/'. $params["domainname"] .'/', Api_Odr::METHOD_PUT);
	$result = $raw->getResult();
	
	logModuleCall("ODR", "RequestDelete | Cancel domain in ODR", $params["domainname"], $result, "", "");
	
	if ($result['status'] !== Api_Odr::STATUS_SUCCESS) {
		$values["error"] = 'Following error occurred: '. $result['response']['message'] ;
		return $values;
	}
	
	$values["domainid"] = $params["domainid"];
	$values["status"] = "Cancelled";
	$result = localAPI("updateclientdomain", $values, $params['Adminuser']);
	logModuleCall("ODR", "RequestDelete | Cancel domain in WHMCS", $values, $result, "", "");
	
	return $values;
}

function ODR_format_country($country)
{
	$array = array
	(
		'nl' => array('Netherlands','NL',"+31"),
        'be' => array('Belgium', 'BE','+32')
	);
	
	if (array_key_exists($country, $array)) 
	{
		return $array[$country];
	}
	else
	{
		return $array['nl'];
	}
}

function ODR_split_street($street) 
{
	preg_match("/([A-Za-z ]+)(\d+)\s?(\w*)/", ucwords(strtolower(trim($street))), $array);
	return $array;
}
	
function ODR_format_company_name($companyname, $fullname) 
{
	if ($companyname != "")
	{
		return $companyname;
	}
	else
	{
		return $fullname;
	}
}

function ODR_format_phone($phone)
{
	$phone = str_replace("+31.","", $phone);
	$phone = str_replace("-","", $phone);
	
	return $phone;
}

function ODR_format_initials($name)
{
	$nword = explode(" ",$name);
	foreach($nword as $letter)
	{
		$new_name .= $letter{0};
	}
    return strtoupper($new_name);
}

function ODR_format_postcode($postcode)
{
	return strtoupper(str_replace(" ","",$postcode));
}

function ODR_format_legal_form($company_name)
{
	if (!empty($company_name))
	{
		return "PERSOON";
	}
	else
	{
		return "ANDERS";
	}
}

function ODR_format_contact_odr($data)
{
		unset($data['id']);
		unset($data['customer_id']);
		unset($data['comment']);
		unset($data['created']);
		unset($data['updated']);
		unset($data['is_filled']);
		unset($data['birthday']);
		unset($data['url']);
		unset($data['company_url']);
		unset($data['company_vatin']);
		unset($data['company_kvk']);
		unset($data['company_address']);
		ksort($data);
		
		return $data;
}

function format_fullname($firstname, $lastname)
{
	return $firstname . " " . $lastname;
}

function format_uniquename($firstname, $lastname, $companyname)
{
	if (empty($companyname))
	{
		return $firstname . " " . $lastname;
	} 
	else
	{	
		return $firstname . " " . $lastname . " (" . $companyname . ")";
	}
}

function ODR_format_contact_whmcs($params)
{
	$data[first_name] = $params["firstname"];
	$data[middle_name] = "";
	$data[last_name] = $params["lastname"];
	$data[full_name] = format_fullname($data[first_name], $data[last_name]);
	$data[initials] = ODR_format_initials($params["firstname"]);
	$data[gender] = "NA";
	$data[language] = ODR_format_country($country)[1];
	$data[email] = $params["email"];
	$data[phone] = 	ODR_format_country($params["country"])[2] . '.' . ODR_format_phone($params["phonenumber"]);
	$data[fax] = $data[phone];
	$data[postal_code] = ODR_format_postcode($params["postcode"]);
	$data[country] = ODR_format_country($country)[1];
	$data[state] = $params["state"];
	$data[city] = $params["city"];
	$data[street] = ODR_split_street($params["address1"])[1];
	$data[house_number] = ODR_split_street($params["address1"])[2];
	$data[organization_legal_form] = ODR_format_legal_form($params['company_name']);
	$data[company_name] = ODR_format_company_name($params['companyname'], $data[full_name]);
	$data[company_email] = $data[email];	
	$data[company_phone] = $data[phone];
	$data[company_fax] = $data[phone];
	$data[company_postal_code] = $data[postal_code];
	$data[company_city] = $data[city];	
	$data[company_street] = $data[street];
	$data[company_house_number] = $data[house_number];
	ksort($data);
	
	return $data;	
}

function ODR_search_contact($name, $module) 
{
	$raw = $module->custom('/contact/', Api_Odr::METHOD_GET);
	$result = $raw->getResult();

   foreach ($result['response'] as $key => $val) 
   {	 
       if ($val['name'] === $name) 
	   {
			logModuleCall("ODR", "ODR_search_contact | Found match" , $name , $val['name'], "", "");  
			return $val['id'];
       }
   }
   return 0;
}


/* function ODR_RegisterNameserver($params) {
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
 */
/* function ODR_ModifyNameserver($params) {
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
 */
/* function ODR_DeleteNameserver($params) {
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
} */

/* function ODR_GetRegistrarLock($params) {
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
} */

/* function ODR_SaveRegistrarLock($params) {
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
} */

/* function ODR_GetEmailForwarding($params) {
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
} */

/* function ODR_SaveEmailForwarding($params) {
	$username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
	foreach ($params["prefix"] AS $key=>$value) {
		$forwardarray[$key]["prefix"] =  $params["prefix"][$key];
		$forwardarray[$key]["forwardto"] =  $params["forwardto"][$key];
	}
	# Put your code to save email forwarders here
} */

/* function ODR_GetDNS($params) {
    $username = $params["Username"];
	$password = $params["Password"];
	$tld = $params["tld"];
	$sld = $params["sld"];
    # Put your code here to get the current DNS settings - the result should be an array of hostname, record type, and address
    $hostrecords = array();
    $hostrecords[] = array( "hostname" => "ns1", "type" => "A", "address" => "192.168.0.1", );
    $hostrecords[] = array( "hostname" => "ns2", "type" => "A", "address" => "192.168.0.2", );
	return $hostrecords;
} */

/* function ODR_SaveDNS($params) {
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
} */

/* function ODR_GetContactDetails($params) {
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
} */

/* function ODR_SaveContactDetails($params) {
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
} */