<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL); 

require_once __DIR__ . '/vendor/autoload.php';

use Baselinker\Samplebroker\Courier;
use Baselinker\Samplebroker\Api;
use Baselinker\Samplebroker\Validate;

$baseUrl = 'https://developers.baselinker.com/recruitment/api';

//Sample data for create Package. Sender and delivery details
$order = [
    'sender_company' => 'BaseLinker',
    'sender_fullname' => 'Jan Kowalski',
    'sender_address' => 34,
    'sender_city' => 'Denver',
    'sender_postalcode' => 8020,
    'sender_email' => 'hak@ptak.cc',
    'sender_phone' => '68888888866',
    'sender_country' => null,

    'delivery_company' => 'Spring GDS',
    'delivery_fullname' => 'Maud Driant',
    'delivery_address' => 'StradaFoisorului, Nr. 16,` ',
    'delivery_address2' => '  Bl. F11C, Sc. 1, Ap. 10',
    'delivery_address3' => '  Et. 2, Ap. 5',
    'delivery_address4' => 'coś jeszcze',  
    'delivery_city' => 'Bucuresti, Sector 3',
    'delivery_postalcode' => '031179',
    'delivery_country' => 'RO',
    'delivery_email' => 'john@doe.com',
    'delivery_phone' => '555555555',

    'shipper_reference' => '123',
    'order_reference' => 'no: 222',

    'products' => [
        [
            'name' => 'Product 1',
            'quantity' => 1,
            'weight' => 0.5,
            'value' => 10.0,
            'hs_code' => '123456',
            'origin_country' => 'CN',
            
        ],
        [
            'name' => 'Product 2 lorem ipsum dolor sit amet consectetur adipisicing elit lorem ipsum dolor sit amet consectetur adipisicing elit lorem ipsum dolor sit amet consectetur adipisicing elit',
            'quantity' => 1,
            'weight' => 1.3,
            'value' => 20.0,
            'origin_country' => 'US',
        ],
    ],
];

//Sample data for create Package - service parameters
$params = [
    'api_key' => 'GsQgkJIpazAu6xXmwfiL',
    'label_format' => 'PDF',
    'service' => 'PPTT',
];

try {

    // 1. Create courier object
    $courier = new Courier(new Api($baseUrl), new Validate());


    // 2. Create shipment
    $response = $courier->newPackage($order, $params);
    print_r($response);


    // 3. Get shipping label and force a download dialog




} catch(\ArgumentCountError $e) {
    echo 'Invalid Argument Count: ' . PHP_EOL;
    exit();
} catch (\TypeError $e) {
    echo 'Type Error: ' . PHP_EOL;
    exit();
} catch (Throwable $e) {
    echo 'General Error: ' . $e->getMessage();
    exit();
}

