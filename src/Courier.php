<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

use Baselinker\Samplebroker\Api;
use Baselinker\Samplebroker\ValidateShipment;

class Courier
{
    public function __construct(
        private Api $api = new Api(),
        private ValidateShipment $validateShipment = new ValidateShipment(),
        private ValidateAddress $validateAddress = new ValidateAddress(),
        private ValidateProduct $validateProduct = new ValidateProduct()
    ) {
    }

    /**
     * Creates a new shipping package/shipment with validation and API integration.
     * 
     * This method validates order data and service parameters, then creates a shipment
     * using the BaseLinker recruitment API. It handles complete order validation including
     * addresses, products, shipment details, and service compatibility.
     *
     * @param array $order Complete order data containing sender/delivery information and products
     *                     Required keys:
     *                     - sender_address: string - Sender's street address
     *                     - sender_city: string - Sender's city  
     *                     - sender_postalcode: string - Sender's postal code
     *                     - sender_country: string - Sender's 2-letter country code
     *                     - delivery_fullname: string - Recipient's full name
     *                     - delivery_address: string - Delivery street address
     *                     - delivery_city: string - Delivery city
     *                     - delivery_postalcode: string - Delivery postal code
     *                     - delivery_country: string - Delivery 2-letter country code
     *                     - shipper_reference: string - Unique shipment reference
     *                     - weight: float - Package weight in kg or lb (with weight_unit)
     *                     - products: array - Array of product objects with name, quantity, weight, etc.
     *                     
     *                     Optional keys:
     *                     - sender_fullname: string - Sender's full name
     *                     - sender_company: string - Sender's company name
     *                     - sender_phone: string - Sender's phone number (up to 15 digits)
     *                     - sender_email: string - Sender's email address
     *                     - delivery_company: string - Delivery company name
     *                     - delivery_phone: string - Delivery phone number
     *                     - delivery_email: string - Delivery email address
     *                     - order_reference: string - Order reference number
     *                     - order_date: string - Order date in YYYY-MM-DD format
     *                     - display_id: string - Display ID (max 15 chars for most services)
     *                     - invoice_number: string - Invoice number
     *                     - weight_unit: string - 'kg' or 'lb' (default: 'kg')
     *                     - length: float - Package length in cm or in (with dim_unit)
     *                     - width: float - Package width in cm or in (with dim_unit)  
     *                     - height: float - Package height in cm or in (with dim_unit)
     *                     - dim_unit: string - 'cm' or 'in' (default: 'cm')
     *                     - value: float - Package value (max 5000 EUR)
     *                     - shipment_value: float - Shipment declared value
     *                     - currency: string - 3-letter ISO currency code
     *                     - customs_duty: string - 'DDP' or 'DDU'
     *                     - description: string - Package description
     *                     - declaration_type: string - Declaration type (e.g., 'gift')
     *                     - dangerous_goods: string - 'Y' or 'N'
     *                     - export_carriername: string - Export carrier name
     *                     - export_awb: string - Export AWB number
     *                     - ni_vat: string - NI VAT number (format: XI123456789)
     *                     - eu_eori: string - EU EORI number (format: CC123456789...)
     *                     - ioss: string - IOSS number (format: IMCC123456789012)
     *                     - label_format: string - Label format: PDF, PNG, ZPL300, ZPL600, ZPL200, ZPL, EPL
     *
     * @param array $params Service configuration parameters
     *                      Required keys:
     *                      - api_key: string - Valid BaseLinker API key
     *                      - service: string - Service name (e.g., 'EXPRESS', 'STANDARD')
     *                      
     *                      Optional keys:
     *                      - label_format: string - Override label format from order
     *
     * @return array Response array with status and data or error information
     *               Success response:
     *               [
     *                   'status' => 'SUCCESS',
     *                   'data' => [
     *                       'TrackingNumber' => string,
     *                       'ShipperReference' => string,
     *                       // ... other shipment data from API
     *                   ]
     *               ]
     *               
     *               Error response:
     *               [
     *                   'status' => 'ERROR', 
     *                   'error_code' => int,
     *                   'error_message' => string
     *               ]
     *
     * @throws \InvalidArgumentException When validation fails (caught and returned in error response)
     * @throws \RuntimeException When API communication fails (caught and returned in error response)  
     * @throws \TypeError When invalid data types are provided (caught and returned in error response)
     *
     * @example
     * ```php
     * $courier = new Courier();
     * 
     * $order = [
     *     'sender_address' => 'Main St 123',
     *     'sender_city' => 'Warsaw', 
     *     'sender_postalcode' => '00-001',
     *     'sender_country' => 'PL',
     *     'delivery_fullname' => 'John Doe',
     *     'delivery_address' => 'Oak Ave 456',
     *     'delivery_city' => 'Berlin',
     *     'delivery_postalcode' => '10115', 
     *     'delivery_country' => 'DE',
     *     'shipper_reference' => 'REF123',
     *     'weight' => 2.5,
     *     'products' => [
     *         [
     *             'name' => 'Test Product',
     *             'quantity' => 1,
     *             'weight' => 2.5,
     *             'hs_code' => '1234567890'
     *         ]
     *     ]
     * ];
     * 
     * $params = [
     *     'api_key' => 'your-api-key',
     *     'service' => 'EXPRESS'
     * ];
     * 
     * $result = $courier->newPackage($order, $params);
     * 
     * if ($result['status'] === 'SUCCESS') {
     *     echo "Tracking number: " . $result['data']['TrackingNumber'];
     * } else {
     *     echo "Error: " . $result['error_message'];
     * }
     * ```
     * 
     * @see Api::createShipment() For the underlying API call
     * @see ValidateShipment::shipment() For shipment data validation
     * @see ValidateAddress::consignorAddress() For sender address validation  
     * @see ValidateAddress::consigneeAddress() For recipient address validation
     * @see ValidateProduct::products() For products validation
     * 
     * @since 1.0.0
     */
    public function newPackage(array $order, array $params): array
    {
        $shipment = [];
        try {

            // Validate API key
            $apiKey = $this->validateShipment->apiKey($params['api_key'] ?? '');

            // Get available services from API
            $services = $this->api->getServices($apiKey);
            if ($services['http_code'] !== 200) {
                throw new \InvalidArgumentException(
                    $services['response']['Error'],
                    $services['response']['ErrorLevel'],
                );
            }

            // Validate selected service
            //$service = $this->validateShipment->service($params['service'] ?? '', $services);

            $service = strtoupper($params['service'] ?? '');

            // Get additional information about the service from API
            $serviceInfo = $this->api->getServiceInfo($apiKey, $service);

            if ($serviceInfo['http_code'] !== 200) {
                throw new \InvalidArgumentException(
                    $serviceInfo['response']['Error'],
                    $serviceInfo['response']['ErrorLevel'],
                );
            }

            // Validate shipment details
            $shipment = $this->validateShipment->shipment(
                array_filter($order, fn ($key) => in_array(strtolower($key), [
                    'shipper_reference', 'order_reference', 'order_date',
                    'display_id', 'invoice_number', 'weight', 'weight_unit',
                    'length', 'width', 'height', 'dim_unit', 'value',
                    'shipment_value', 'currency', 'customs_duty', 'description',
                    'declaration_type', 'dangerous_goods', 'export_carriername',
                    'export_awb', 'ni_vat', 'eu_eori', 'ioss', 'label_format',
                ]), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );


            // Validate consignor address
            $consignor = $this->validateAddress->consignorAddress(
                array_filter($order, fn ($key) => str_starts_with($key, 'sender_'), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );

            // Validate consignee address
            $consignee = $this->validateAddress->consigneeAddress(
                array_filter($order, fn ($key) => str_starts_with($key, 'delivery_'), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );

            // Validate products data
            $products = $this->validateProduct->products(
                $order['products'] ?? [],
                $consignor['Country'],
                $consignee['Country'],
                $serviceInfo
            );

            $shipment['Service'] = $service;
            $shipment['ConsignorAddress'] = $consignor;
            $shipment['ConsigneeAddress'] = $consignee;
            $shipment['Products'] = $products;

            $response = $this->api->createShipment($apiKey, $shipment);
            if ($response['http_code'] !== 200) {
                throw new \InvalidArgumentException(
                    $response['response']['Error'],
                    $response['response']['ErrorLevel'],
                );
            }
            return [
                'status' => 'SUCCESS',
                'data' => $response['response']['Shipment'],
            ];
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return [
                'status' => 'ERROR',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ];
        } catch (\TypeError $e) {
            return [
                'status' => 'ERROR',
                'error_code' => 500,
                'error_message' => 'Invalid input data types.',
            ];
        }
    }

    /**
     * Retrieves and decodes a shipping label PDF for an existing shipment.
     * 
     * This method fetches the shipping label from the BaseLinker API using either
     * a tracking number or shipper reference. The label is returned as decoded
     * binary data ready for file output or HTTP response.
     *
     * @param string $apiKey Valid BaseLinker API key for authentication
     * @param string|null $trackingNumber Optional tracking number to identify the shipment
     *                                   Either this or $shipperReference must be provided
     * @param string|null $shipperReference Optional shipper reference to identify the shipment
     *                                     Either this or $trackingNumber must be provided
     * @param string|null $labelFormat Optional label format override
     *                                Supported formats: PDF, PNG, ZPL300, ZPL600, ZPL200, ZPL, EPL
     *                                If not provided, uses the format from shipment creation
     *
     * @return array Response array with status and label data or error information
     *               Success response:
     *               [
     *                   'status' => 'SUCCESS',
     *                   'data' => string (binary label data, decoded from base64)
     *               ]
     *               
     *               Error response:
     *               [
     *                   'status' => 'ERROR',
     *                   'error_code' => int,
     *                   'error_message' => string
     *               ]
     *
     * @throws \InvalidArgumentException When neither tracking number nor shipper reference provided,
     *                                  or when API returns validation errors (caught and returned in error response)
     * @throws \RuntimeException When label not found in response, base64 decode fails,
     *                          or API communication issues (caught and returned in error response)
     * @throws \TypeError When invalid data types are provided (caught and returned in error response)
     *
     * @example
     * ```php
     * $courier = new Courier();
     * 
     * // Using tracking number
     * $result = $courier->packagePDF(
     *     'your-api-key',
     *     'TRK123456789',
     *     null,
     *     'PDF'
     * );
     * 
     * if ($result['status'] === 'SUCCESS') {
     *     // Save to file
     *     file_put_contents('label.pdf', $result['data']);
     *     
     *     // Or serve via HTTP
     *     header('Content-Type: application/pdf');
     *     header('Content-Disposition: attachment; filename="label.pdf"');
     *     echo $result['data'];
     * } else {
     *     echo "Error: " . $result['error_message'];
     * }
     * 
     * // Using shipper reference instead
     * $result = $courier->packagePDF(
     *     'your-api-key',
     *     null,
     *     'REF123456',
     *     'PNG'
     * );
     * ```
     *
     * @see Api::getShipmentLabel() For the underlying API call
     * @see newPackage() For creating shipments that generate trackable labels
     * 
     * @since 1.0.0
     */
    public function packagePDF(
        string $apiKey,
        ?string $trackingNumber = null,
        ?string $shipperReference = null,
        ?string $labelFormat = null
    ): array {
        $body = [];
        try {
            if (!empty($trackingNumber)) {
                $body['TrackingNumber'] = $trackingNumber;
            } elseif (!empty($shipperReference)) {
                $body['ShipperReference'] = $shipperReference;
            } else {
                throw new \InvalidArgumentException(
                    'Either trackingNumber or shipperReference must be provided.',
                    400
                );
            }
            if ($labelFormat !== null) {
                $body['LabelFormat'] = $labelFormat;
            } 

            $labelResponse = $this->api->getShipmentLabel($apiKey, $body);
            if ($labelResponse['response']['ErrorLevel'] > 0) {
                throw new \InvalidArgumentException(
                    $labelResponse['response']['Error'],
                    $labelResponse['response']['ErrorLevel'],
                );
            }

            $label = $labelResponse['response']['Shipment']['LabelImage'] ?? null;

            if ($label === null) {
                throw new \RuntimeException(
                    'Label not found in the response.',
                    500
                );
            }

            $labelDecoded = base64_decode($label);
            if ($labelDecoded === false) {
                throw new \RuntimeException(
                    'Failed to decode the label content.',
                    500
                );
            }

            return [
                'status' => 'SUCCESS',
                'data' => $labelDecoded,
            ];
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            return [
                'status' => 'ERROR',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ];
        } catch (\TypeError $e) {
            return [
                'status' => 'ERROR',
                'error_code' => 500,
                'error_message' => 'Invalid input data types.',
            ];
        }

    }
}