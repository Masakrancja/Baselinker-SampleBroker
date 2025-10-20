<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

use Baselinker\Samplebroker\Api;

class Courier
{

    private const DEFAULT_WEIGHT_UNIT = 'kg';

    public function __construct(
        private Api $api, 
        private Validate $validate
    ) { 
    }

    public function newPackage(array $order, array $params): array
    {
        $shipment = [];
        try {

            // Validate API key
            $apiKey = $this->validate->apiKey($params['api_key'] ?? '');

            // Get available services from API
            $services = $this->api->getServices($apiKey);

            // Validate selected service
            $service = $this->validate->service($params['service'] ?? '', $services);

            // Get additional information about the service from API
            $serviceInfo = $this->api->getServiceInfo($apiKey, $service);

            // Validate shipment details
            $shipment = $this->validate->shipment(
                array_filter($order, fn($key) => in_array(strtolower($key), [
                    'shipper_reference', 'order_reference', 'order_date', 
                    'display_id', 'invoice_number', 'weight', 'weight_unit', 
                    'length', 'width', 'height', 'dim_unit', 'value', 
                    'shipping_value', 'currency', 'customs_duty', 'description', 
                    'declaration_type', 'dangerous_goods', 'export_carriername', 
                    'export_awb', 'ni_vat', 'eu_eori', 'ioss', 'label_format'
                ]), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );


            // Validate consignor address
            $consignor = $this->validate->consignorAddress(
                array_filter($order, fn($key) => str_starts_with($key, 'sender_'), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );

            // Validate consignee address
            $consignee = $this->validate->consigneeAddress(
                array_filter($order, fn($key) => str_starts_with($key, 'delivery_'), ARRAY_FILTER_USE_KEY),
                $serviceInfo
            );

            // Validate products data
            $products = $this->validate->products(
                $order['products'] ?? [], 
                $shipment['weight_unit'] ?? self::DEFAULT_WEIGHT_UNIT,
                $serviceInfo
            );

            return [];
            


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

        // Logic to create shipment
        return [];
    }

    public function packagePDF(): string
    {
        // Logic to get shipping label
        return '';
    }







 

















}



