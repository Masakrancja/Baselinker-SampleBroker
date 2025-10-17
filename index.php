<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL); 

require_once __DIR__ . '/vendor/autoload.php';

use Baselinker\Samplebroker\Courier;

$baseUrl = 'https://developers.baselinker.com/recruitment/api';

//Sample data for create Package. Sender and delivery details
$order = [
    'sender_company' => 'BaseLinker',
    'sender_fullname' => 'Jan Kowalski',
    'sender_address' => 'Kopernika 10',
    'sender_city' => 'Gdansk',
    'sender_postalcode' => '80208',
    'sender_email' => '',
    'sender_phone' => '666666666',

    'delivery_company' => 'Spring GDS',
    'delivery_fullname' => 'Maud Driant',
    'delivery_address' => 'Strada Foisorului, Nr. 16, Bl. F11C, Sc. 1, Ap. 10',
    'delivery_city' => 'Bucuresti, Sector 3',
    'delivery_postalcode' => '031179',
    'delivery_country' => 'RO',
    'delivery_email' => 'john@doe.com',
    'delivery_phone' => '555555555',
];

//Sample data for create Package - service parameters
$params = [
    'api_key' => 'GsQgkJIpazAu6xXmwfiL',
    'label_format' => 'PDF',
    'service' => 'PPTT',
];

// 1. Create courier object
$courier = new Courier($baseUrl);

// 2. Create shipment
$response = $courier->newPackage($order, $params);
print_r($response);


// 3. Get shipping label and force a download dialog



