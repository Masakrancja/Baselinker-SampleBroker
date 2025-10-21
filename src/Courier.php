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

    public function newPackage(array $order, array $params): array
    {
        $shipment = [];
        try {

            // Validate API key
            $apiKey = $this->validateShipment->apiKey($params['api_key'] ?? '');

            // Get available services from API
            $services = $this->api->getServices($apiKey);

            // Validate selected service
            $service = $this->validateShipment->service($params['service'] ?? '', $services);

            // Get additional information about the service from API
            $serviceInfo = $this->api->getServiceInfo($apiKey, $service);

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