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
    'sender_country' => 'PL',

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
    'order_date' => '2024-06-01',
    'display_id' => '',
    'invoice_number' => 'FV/06/2024/01',
    'weight' => "2",
    'weight_unit' => 'KG',
    'length' => 23,
    'width' => 22,
    'height' => 20,
    'dim_unit' => 'cm',
    'value' => 100.0,
    'shipment_value' => 20.0,
    'currency' => 'pln',
    'customs_duty' => 'DDu',
    'description' => 'Electron"i"cs items',
    'declaration_type' => 'gift',
    // 'dangerous_goods' => 'no',
    'export_carriername' => 'DHL',
    'export_awb' => '1234567890',
    'ni_vat' => 'xi123456789',
    'eu_eori' => 'PL123456789012',
    'ioss' => 'IMRO123456789055',
    'label_format' => 'ZPL',

    'products' => [
        [
            'name' => 'Product 1',
            'quantity' => 1,
            'weight' => 0.03,
            'value' => 10.0,
            'hs_code' => '123456',
            'origin_country' => 'CN',
            
        ],
        [
            'name' => 'Product 2 lorem',
            'quantity' => 12,
            'weight' => 0.05,
            'value' => 20.0,
            'origin_country' => 'US',
            'hs_code' => '654321',
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

    // 3. Get shipping label and force a download dialog
    $courier->packagePDF(
        $params['api_key'], 
        $response['TrackingNumber']
    );

} catch(\ArgumentCountError $e) {
    echo 'Invalid Argument Count: ' . PHP_EOL;
} catch (\TypeError $e) {
    echo 'Type Error: ' . PHP_EOL;
} catch (Throwable $e) {
    echo 'General Error: ' . $e->getMessage();
}

