<?php
// Require ODR API demo class
require_once 'Odr_commands.php';

$secret = 'secret$dadf4d0dca024b41bd3cddf8c13187e8bef5ce6f';

// Configuration array, with user API Keys
	$config = array(
		'api_key'    => 'public$66b0f0aca2a200ee5565a6d44c25e6aeebf7a18b',
		'api_secret' => $secret
	);

// Domain name you want to know how to register
$domainName = 'test.nl';

// Create new instance of API demo class
$demo = new Api_Odr($config);

// Login into API
$demo->login();

$loginResult = $demo->getResult();

if ($loginResult['status'] === 'error') {
    echo 'Can\'t login, reason - '. $loginResult['response'];

    exit(1);
}

// Request information about domain registration
$demo->info('domain');

// Get result of request
$result = $demo->getResult();

if ($result['status'] !== 'success') {
    echo 'Following error occured: '. $result['response'];

    exit(1);
}

$result = $result['response'];

// Output what we get
print_r($result);