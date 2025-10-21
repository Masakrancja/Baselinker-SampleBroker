<?php

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';

use Baselinker\Samplebroker\Courier;

if (php_sapi_name() === 'cli') {
    // Running from command line
    $eol = PHP_EOL;
} else {
    // Running from web server
    $eol = '<br>';
}

// Sample data for create Package. Sender and delivery details
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
    'delivery_address' => 'StradaFoisorului, Nr. 16',
    'delivery_address2' => 'Bl. F11C, Sc. 1, Ap. 10',
    'delivery_address3' => 'Et. 2, Ap. 5',
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
    'weight' => 2,
    'weight_unit' => 'lb    ',
    'length' => 23,
    'width' => 22,
    'height' => 20,
    'dim_unit' => 'cm',
    'value' => 100.0,
    'shipment_value' => 20.0,
    'currency' => 'pln',
    'customs_duty' => 'DDu',
    'description' => 'Electronics items',
    'declaration_type' => 'gift',
    'dangerous_goods' => 'n',
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

// Sample data for create Package - service parameters
$params = [
    'api_key' => 'GsQgkJIpazAu6xXmwfiL',
    'label_format' => 'PDF',
    'service' => 'PPTT',
];

try {

    // Create courier object
    $courier = new Courier();

    // Create shipment
    $response = $courier->newPackage($order, $params);

    if ($response['status'] === 'ERROR') {
        throw new \RuntimeException(
            $response['error_message'] ?? 'Unknown error occurred during shipment creation.',
            $response['error_code'] ?? 500
        );
    }

    if ($response['status'] === 'SUCCESS') {
        if (!isset($response['data']['TrackingNumber'])) {
            throw new \RuntimeException('Tracking number not found in the response.', 500);
        }

        // Get shipping label and force a download dialog
        $result = $courier->packagePDF(
            $params['api_key'] ?? '',
            $response['data']['TrackingNumber'] ?? null,
            null,
            $params['label_format'] ?? 'PDF'
        );

        if ($result['status'] === 'ERROR') {
            throw new \RuntimeException(
                $result['error_message'] ?? 'Unknown error occurred during label retrieval.',
                $result['error_code'] ?? 500
            );
        }
        if ($result['status'] === 'SUCCESS') {

            if (php_sapi_name() === 'cli') {
                // Save the label to a file
                $fileName = 'shipping_label.' . strtolower($params['label_format'] ?? 'PDF');
                file_put_contents($fileName, $result['data']);
                echo "Shipping label saved to " . $fileName . $eol;
            } else {
                // Serve the label for download
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="shipping_label.' . strtolower($params['label_format'] ?? 'PDF') . '"');
                header('Content-Length: ' . strlen($result['data']));
                echo $result['data'];   
                exit();
            }
        }
    }
} catch (\ArgumentCountError $e) {
    echo 'Invalid Argument Count: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . $eol;
} catch (\RuntimeException $e) {
    echo 'Runtime Error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . $eol;
} catch (\InvalidArgumentException $e) {
    echo 'Invalid Argument: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . $eol;
} catch (\TypeError $e) {
    echo 'Type Error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . $eol;
} catch (Throwable $e) {
    echo 'General Error: ' . $e->getMessage() . ' (Code: ' . $e->getCode() . ')' . $eol;
}

