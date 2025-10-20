<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

class Validate
{   
    private const MAX_PRODUCT_COUNT = 50;

    private const MAX_SHIPMENT_WEIGHT = 30.0; // in kg

    private const MAX_VALUE = 5000.0; // in EUR

    private const MAX_SHIPMENT_LENGTH = 120.0; // in cm

    private const MAX_SHIPMENT_WIDTH = 60.0; // in cm

    private const MAX_SHIPMENT_HEIGHT = 60.0; // in cm

    private const MAX_SHIPMENT_DIMENSION = 300.0; // in cm (sum of length + 2 * (width + height))

    private const LB_TO_KG = 0.453592;

    private const INCH_TO_CM = 2.54;

    private const PRODUCT_DESC_MAX_LENGTH = 105;

    private const ALLOWED_LABEL_FORMATS = [
        'PDF', 'PNG', 'ZPL300', 'ZPL600', 'ZPL200', 'ZPL', 'EPL'
    ];

    private const ALLOWED_WEIGHT_UNITS = [
        'kg', 'lb'
    ];

    private const DEFAULT_WEIGHT_UNIT = 'kg';


    private const ALLOWED_DIM_UNITS = [
        'cm', 'in'
    ];
    
    private const DEFAULT_DIM_UNIT = 'cm';

    private const CURRENCIES = [
        'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
        'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
        'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
        'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
        'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS',
        'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF',
        'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD',
        'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT',
        'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD',
        'MMK', 'MNT', 'MOP', 'MRU', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN',
        'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK',
        'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR',
        'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL', 'SRD',
        'STN', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY',
        'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VES',
        'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR',
        'ZMW', 'ZWL'
    ];

    private const CUSTOM_DUTY_TYPES = [
        'DDP', 'DDU'
    ];

    private const DANGEROUS_GOODS_TYPES = [
        'Y', 'N'
    ];

    public function apiKey(string $apiKey): string
    {
        if (empty($apiKey) || !is_string($apiKey)) {
            throw new \InvalidArgumentException('Invalid API key provided.', 400);
        }

        return $apiKey;
    }

    public function service(string $service, array $services): string
    {
        $allowedServices = $services['response']['Services']['AllowedServices'] ?? [];
        if (!in_array(strtoupper($service), $allowedServices, true)) {
            throw new \InvalidArgumentException(
                'Invalid service. Allowed services: ' . implode(', ', $allowedServices),
                400
            );
        }
        return $service;
    }

    public function shipment(array $shipment, array $serviceInfo): array
    {
        $result = [];
        $fields = [
            'ShipperReference' => [
                'name' => 'shipper_reference', 
                'required' => true, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'OrderReference' => [
                'name' => 'order_reference', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'OrderDate' => [
                'name' => 'order_date', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 10
            ],
            'DisplayId' => [
                'name' => 'display_id', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'InvoiceNumber' => [
                'name' => 'invoice_number', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'Weight' => [
                'name' => 'weight', 
                'required' => true, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_SHIPMENT_WEIGHT
            ],
            'WeightUnit' => [
                'name' => 'weight_unit', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 2
            ],
            'Length' => [
                'name' => 'length', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_SHIPMENT_LENGTH
            ],
            'Width' => [
                'name' => 'width', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_SHIPMENT_WIDTH
            ],
            'Height' => [
                'name' => 'height', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_SHIPMENT_HEIGHT
            ],
            'DimUnit' => [
                'name' => 'dim_unit', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 2
            ],
            'Value' => [
                'name' => 'value', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_VALUE
            ],
            'ShippingValue' => [
                'name' => 'shipping_value', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0.01,
                'max' => self::MAX_VALUE
            ],
            'Currency' => [
                'name' => 'currency', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 3
            ],
            'CustomsDuty' => [
                'name' => 'customs_duty',   
                'required' => false, 
                'type' => 'string',
                'default_length' => 3
            ],
            'Description' => [
                'name' => 'description', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'DeclarationType' => [
                'name' => 'declaration_type', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'DangerousGoods' => [
                'name' => 'dangerous_goods', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 1
            ],
            'ExportCarrierName' => [
                'name' => 'export_carriername', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'ExportAWB' => [
                'name' => 'export_awb', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'NIVat' => [
                'name' => 'ni_vat', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'EuEori' => [
                'name' => 'eu_eori', 
                'required' => false,
                'type' => 'string', 
                'default_length' => 255
            ],
            'Ioss' => [
                'name' => 'ioss', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'LabelFormat' => [
                'name' => 'label_format', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 10
            ],
        ];


        $result = $this->validateShipment($fields, $shipment, $serviceInfo);

        print_r($result);

        $dim = (int)isset($result['Length']) + (int)isset($result['Width']) + (int)isset($result['Height']);

        if ($dim === 1 || $dim === 2) {
            throw new \InvalidArgumentException(
                "Three dimensions (length, width, height) must be provided together. " .
                "Or none of them.",
                400
            );
        }

        $dimUnit = $result['DimUnit'] ?? self::DEFAULT_DIM_UNIT;

        if ($dim === 3) {
            $length = (float) $result['Length'];
            $width = (float) $result['Width'];
            $height = (float) $result['Height'];

            if ($dimUnit === 'in') {
                $length = round($length * self::INCH_TO_CM, 2);
                $width = round($width * self::INCH_TO_CM, 2);
                $height = round($height * self::INCH_TO_CM, 2);
            }

            $msgCalculated = '';
            if ($dimUnit !== self::DEFAULT_DIM_UNIT) {
                $msgCalculated = "(Dimension converted from {$dimUnit} to " . 
                self::DEFAULT_DIM_UNIT . ")";
            }

            if ($length < $fields['Length']['min'] || $length > $fields['Length']['max']) {
                throw new \InvalidArgumentException(
                    "Shipment length '{$length} " . self::DEFAULT_DIM_UNIT . "' " .
                    $msgCalculated . " must be between " .
                    "{$fields['Length']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Length']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            if ($width < $fields['Width']['min'] || $width > $fields['Width']['max']) {
                throw new \InvalidArgumentException(
                    "Shipment width '{$width} " . self::DEFAULT_DIM_UNIT . "' " . 
                    $msgCalculated . " must be between " .
                    "{$fields['Width']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Width']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            if ($height < $fields['Height']['min'] || $height > $fields['Height']['max']) {
                throw new \InvalidArgumentException(
                    "Shipment height '{$height} " . self::DEFAULT_DIM_UNIT . "' " . 
                    $msgCalculated . " must be between " .
                    "{$fields['Height']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Height']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            $dimensionSum = $length + 2 * ($width + $height);
            if ($dimensionSum > self::MAX_SHIPMENT_DIMENSION) {
                throw new \InvalidArgumentException(
                    "Sum of shipment dimensions [L + 2 * (W + H)] = '{$dimensionSum} " . 
                    self::DEFAULT_DIM_UNIT . "' " . $msgCalculated . 
                    " exceeds maximum allowed of " .
                    self::MAX_SHIPMENT_DIMENSION . " " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }
        }
        
        $weightUnit = $result['WeightUnit'] ?? self::DEFAULT_WEIGHT_UNIT;
        $weight = (float) $result['Weight'];
        $msgCalculated = '';
        if ($weightUnit === 'lb') {
            $weight = round($weight * self::LB_TO_KG, 2);
            $msgCalculated = "(Weight converted from {$weightUnit} to " . 
            self::DEFAULT_WEIGHT_UNIT . ")";
        }
        if ($weight < $fields['Weight']['min'] || $weight > $fields['Weight']['max']) {
            throw new \InvalidArgumentException(
                "Shipment weight '{$weight} " . self::DEFAULT_WEIGHT_UNIT . "' " .
                $msgCalculated . " must be between " .
                "{$fields['Weight']['min']} " . self::DEFAULT_WEIGHT_UNIT . " and " .
                "{$fields['Weight']['max']} " . self::DEFAULT_WEIGHT_UNIT . ".",
                400
            );
        }

        return $result;

    }

    public function consignorAddress(array $consignor, array $serviceInfo): array
    {
        $fields = [
            'FullName' => [
                'name' => 'sender_fullname', 
                'required' => false, 
                'default_length' => 50
            ],
            'Company' => [
                'name' => 'sender_company', 
                'required' => false, 
                'default_length' => 60
            ],
            'AddressLine1' => [
                'name' => 'sender_address', 
                'required' => true, 
                'default_length' => 50
            ],
            'AddressLine2' => [
                'name' => 'sender_address2', 
                'required' => false, 
                'default_length' => 50
            ],
            'AddressLine3' => [
                'name' => 'sender_address3', 
                'required' => false, 
                'default_length' => 50
            ],
            'City' => [
                'name' => 'sender_city', 
                'required' => true, 
                'default_length' => 50
            ],
            'State' => [
                'name' => 'sender_state', 
                'required' => false, 
                'default_length' => 50
            ],
            'Zip' => [
                'name' => 'sender_postalcode', 
                'required' => true, 
                'default_length' => 20
            ],
            'Country' => [
                'name' => 'sender_country', 
                'required' => false, 
                'default_length' => 2
            ],
            'Phone' => [
                'name' => 'sender_phone', 
                'required' => false, 
                'default_length' => 15
            ],
            'Email' => [
                'name' => 'sender_email', 
                'required' => false, 
                'default_length' => 255
            ],
        ];
        return $this->validateAddress($fields, $consignor, $serviceInfo);
    }

    public function consigneeAddress(array $consignee, array $serviceInfo): array
    {
        $fields = [
            'Name' => [
                'name' => 'delivery_fullname', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 50
            ],
            'Company' => [
                'name' => 'delivery_company', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 60
            ],
            'AddressLine1' => [
                'name' => 'delivery_address', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 50
            ],
            'AddressLine2' => [
                'name' => 'delivery_address2', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'AddressLine3' => [
                'name' => 'delivery_address3', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'City' => [
                'name' => 'delivery_city', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 50
            ],
            'State' => [
                'name' => 'delivery_state', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'Zip' => [
                'name' => 'delivery_postalcode', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 20
            ],
            'Country' => [
                'name' => 'delivery_country', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 2
            ],
            'Phone' => [
                'name' => 'delivery_phone', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 15
            ],
            'Email' => [
                'name' => 'delivery_email', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 255
            ],
        ];
        return $this->validateAddress($fields, $consignee, $serviceInfo);
    }

    public function products(
        array $products, 
        array $serviceInfo, 
        string $weightUnit
    ): array
    {
        $result = [];
        $serviceInfo = $serviceInfo['response']['ServiceInfo'];
        $maxWeight = $serviceInfo['maxWeight'] ?? self::MAX_SHIPMENT_WEIGHT; //from service is only in kg unit
        $service = $serviceInfo['service'] ?? '';
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.', 400);
        }

        $fields = [
            'Description' => [
                'name' => 'name', 
                'required' => true, 
                'type' => 'string', 
                'default_length' => self::PRODUCT_DESC_MAX_LENGTH
            ],
            'Quantity' => [
                'name' => 'quantity', 
                'required' => true, 
                'type' => 'int', 
                'min' => 1, 
                'max' => self::MAX_PRODUCT_COUNT
            ],
            'Weight' => [
                'name' => 'weight', 
                'required' => true, 
                'type' => 'number', 
                'min' => 0, 
                'max' => self::MAX_SHIPMENT_WEIGHT
            ],
            'Value' => [
                'name' => 'value', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0, 
                'max' => self::MAX_VALUE
            ],
            'HsCode' => [
                'name' => 'hs_code', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 255
            ],
            'OriginCountry' => [
                'name' => 'origin_country', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 2
            ],
        ];

        $productIndex = 0;
        $amountOfProducts = 0;
        $weightOfProducts = 0.0;
        $valueOfProducts = 0.0;
        foreach ($products as $product) {
            $result[] = $this->validateProduct($fields, $product, $serviceInfo, $productIndex);
            $amountOfProducts += (int) ($product['quantity']);
            $weightOfProducts += (float) ($product['weight']) * (int) ($product['quantity']);
            $valueOfProducts += (float) ($product['value']) ?? 0.0 * (int) ($product['quantity']);
            $productIndex++;
        }

        if ($amountOfProducts > self::MAX_PRODUCT_COUNT) {
            throw new \InvalidArgumentException(
                'Exceeded maximum total quantity of products. Maximum allowed is ' . 
                self::MAX_PRODUCT_COUNT,
                400
            );
        }

        if ($valueOfProducts > self::MAX_VALUE) {
            throw new \InvalidArgumentException(
                'Exceeded maximum total value of products. Maximum allowed is ' . 
                self::MAX_VALUE . ' EUR',
                400
            );
        }

        if (strtolower($weightUnit) === 'lb') {
            $msgCalculated = '';
            if ($weightUnit !== self::DEFAULT_WEIGHT_UNIT) {
                $msgCalculated = "(Weight converted from {$weightUnit} to " . 
                self::DEFAULT_WEIGHT_UNIT . ")";
            }

            $weightOfProducts = round($weightOfProducts * self::LB_TO_KG, 2);

            if ($weightOfProducts > $maxWeight) {
                throw new \InvalidArgumentException(
                    "Total weight '{$weightOfProducts} " . self::DEFAULT_WEIGHT_UNIT . "'" . 
                    $msgCalculated . " of products exceeds maximum allowed weight " .
                    "{$maxWeight} " . self::DEFAULT_WEIGHT_UNIT . " for the " . $service . " service.",
                    400
                );
            }           
        } elseif (strtolower($weightUnit) === self::DEFAULT_WEIGHT_UNIT) {
            if ($weightOfProducts > $maxWeight) {
                throw new \InvalidArgumentException(
                    "Total weight '{$weightOfProducts} " . self::DEFAULT_WEIGHT_UNIT . "' " .
                    "of products exceeds maximum allowed weight ." . 
                    "{$maxWeight} " . self::DEFAULT_WEIGHT_UNIT . " for the " . $service . " service.",
                    400
                );
            }
        }

        return $result;
    }

    private function validateAddress(
        array $fields, 
        array $address, 
        array $serviceInfo
    ): array
    {
        $result = [];
        $serviceInfo = $serviceInfo['response']['ServiceInfo'];
        $serviceLimits = $serviceInfo['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['service'] ?? '';

        foreach ($fields as $key => $field) {
            $value = trim((string) ($address[$field['name']] ?? null));
            if ($field['required'] && empty($value)) {
                throw new \InvalidArgumentException("Field '{$field['name']}' cannot be empty.", 400);
            }
            if (!empty($value)) {
                $maxLength = $serviceLimits[$key] ?? $field['default_length'];
                if (!is_numeric($maxLength)) {
                    $maxLength = $field['default_length'];
                }
                $valueLength = mb_strlen($value, 'UTF-8');
                if ($valueLength > $maxLength && $maxLength > 0) {
                    $message = "Field '{$field['name']}' exceeds maximum length of {$maxLength} characters.";
                    if (strpos($field['name'], '_address') !== false && $valueLength > $maxLength) {
                        $message .= " Consider splitting the address into multiple lines.";
                    }
                    throw new \InvalidArgumentException(
                        $message,
                        400
                    );
                }
                if (strpos($key, 'Email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException(
                        "Field '{$field['name']}' must be a valid email address.",
                        400
                    );
                }
                if (strpos($key, 'Phone') !== false && !preg_match('/^\d{0,' . $maxLength . '}$/', $value)) {
                    throw new \InvalidArgumentException(
                        "Field '{$field['name']}' must be a valid phone number: (up to {$maxLength} digits).",
                        400
                    );
                }
                if (strpos($key, 'Country') !== false) {
                    $value = strtoupper($value);
                    if (strlen($value) === 2) {
                        if (is_array($availableCountries)) {
                            $this->validateCountryCode(
                                $value, 
                                $availableCountries, 
                                $service,
                                $field['name']
                            );
                        }
                    } else {
                        throw new \InvalidArgumentException(
                            "Field '{$field['name']}' must be a valid 2-letter country code.",
                            400
                        );
                    }
                }
            }
            if (strlen($value) > 0) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function validateProduct(
        array $fields, array $product, array $serviceInfo, int $productIndex
    ): array
    {
        $result = [];
        $serviceInfo = $serviceInfo['response']['ServiceInfo'];
        $serviceLimits = $serviceInfo['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['service'] ?? '';
        $maxProductWeight = $serviceLimits['Weight'] ?? $fields['Weight']['max'];

        foreach ($fields as $key => $field) {
            $value = trim((string) ($product[$field['name']] ?? null));
            if ($field['required'] && $value === '') {
                throw new \InvalidArgumentException("Product field '{$field['name']}' cannot be empty.", 400);
            }
            if ($value !== '') {
                switch ($field['type']) {
                    case 'string':
                        $maxLength = $serviceLimits[$key] ?? $field['default_length'];
                        $valueLength = mb_strlen($value, 'UTF-8');
                        if ($valueLength > $maxLength && $maxLength > 0) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' exceeds maximum length of {$maxLength} characters.",
                                400
                            );
                        }
                        break;
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be an integer.",
                                400
                            );
                        }
                        $value = (int) $value;
                        if ($value < $field['min']) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be at least {$field['min']}.",
                                400
                            );
                        }
                        if ($value > $field['max']) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be at most {$field['max']}.",
                                400
                            );
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be a number.",
                                400
                            );
                        }
                        $value = (float) $value;
                        if ($value < $field['min']) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be at least {$field['min']}.",
                                400
                            );
                        }
                        if ($key === 'Weight') {
                            $field['max'] = $maxProductWeight ?? $field['max'];
                        }
                        if ($value > $field['max']) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be at most {$field['max']}.",
                                400
                            );
                        }
                        break;
                    default:
                        throw new \RuntimeException(
                            "Application error. Contact support.",
                            500
                        );
                }

                if ($key === 'OriginCountry') {
                    if (strlen($value) === 2) {
                        $value = strtoupper($value);
                        if (is_array($availableCountries)) {
                            $this->validateCountryCode(
                                $value,
                                $availableCountries,
                                $service,
                                $field['name']
                            );
                        }
                    } else {
                        throw new \InvalidArgumentException(
                            "Product no. {$productIndex}: field '{$field['name']}' must be a valid 2-letter country code.",
                            400
                        );
                    }
                }
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function validateShipment(
        array $fields, array $shipment, array $serviceInfo
    ): array {
        $result = [];
        $serviceInfo = $serviceInfo['response']['ServiceInfo'];
        $serviceLimits = $serviceInfo['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['service'] ?? '';

        foreach ($fields as $key => $field) {
            $value = trim((string) ($shipment[$field['name']] ?? null));
            if ($field['required'] && $value === '') {
                throw new \InvalidArgumentException(
                    "Shipment field '{$field['name']}' cannot be empty.",
                    400
                );
            }
            if ($value !== '') {
                switch ($field['type']) {
                    case 'string':
                        $maxLength = $serviceLimits[$key] ?? $field['default_length'];
                        if (!is_numeric($maxLength)) {
                            $maxLength = $field['default_length'];
                        }
                        $valueLength = mb_strlen($value, 'UTF-8');
                        if ($valueLength > $maxLength && $maxLength > 0) {
                            throw new \InvalidArgumentException(
                                "Field '{$field['name']}' exceeds maximum length of {$maxLength} characters.",
                                400
                            );
                        }
                        break;
                    case 'number':
                        if (!is_numeric($value)) {
                            throw new \InvalidArgumentException(
                                "Shipment field '{$field['name']}' must be a number.",
                                400
                            );
                        }
                        $value = (float) $value;
                        break;
                    default:
                        throw new \RuntimeException(
                            "Application error. Contact support.",
                            500
                        );
                }
                if ($key === 'OrderDate') {
                    $date = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' must be in 'YYYY-MM-DD' format.",
                            400
                        );
                    }
                }
                if ($key === 'DisplayId') {
                    $valueLength = mb_strlen($value, 'UTF-8');
                    if ($valueLength > $serviceLimits[$key] ?? 15) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' exceeds maximum length of " . ($serviceLimits[$key] ?? 15) . " characters.",
                            400
                        );
                    }
                }
                if ($key === 'WeightUnit') {
                    $lowerValue = strtolower($value);
                    if (!in_array($lowerValue, self::ALLOWED_WEIGHT_UNITS, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Allowed values: " . implode(', ', self::ALLOWED_WEIGHT_UNITS),
                            400
                        );
                    }
                    $value = $lowerValue;
                }
                if ($key === 'DimUnit') {
                    $lowerValue = strtolower($value);
                    if (!in_array($lowerValue, self::ALLOWED_DIM_UNITS, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Allowed values: " . implode(', ', self::ALLOWED_DIM_UNITS),
                            400
                        );
                    }
                    $value = $lowerValue;
                }
                if ($key === 'Currency') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::CURRENCIES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Must be a valid ISO 4217 currency code.",
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'CustomsDuty') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::CUSTOM_DUTY_TYPES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Allowed values: " . implode(', ', self::CUSTOM_DUTY_TYPES),
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'DangerousGoods') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::DANGEROUS_GOODS_TYPES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Allowed values: " . implode(', ', self::DANGEROUS_GOODS_TYPES),
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'NIVat') {
                    $upperValue = strtoupper(preg_replace('/\s+/', '', $value));
                    if (!preg_match('/^XI\d{9}$/', $upperValue)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' must be in the format 'XI123456789'.",
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'EuEori') {
                    $upperValue = strtoupper(preg_replace('/\s+/', '', $value));
                    if (!preg_match('/^[A-Z]{2}\S+$/', $upperValue)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' must start with a 2-letter country code followed by alphanumeric characters.",
                            400
                        );
                    }
                    $countryCode = substr($upperValue, 0, 2);
                    if (is_array($availableCountries)) {
                        $this->validateCountryCode(
                            $countryCode, 
                            $availableCountries, 
                            $service,
                            $field['name']
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'Ioss') {
                    $upperValue = strtoupper(preg_replace('/\s+/', '', $value));
                    if (!preg_match('/^IM[A-Z]{2}\d{12}$/', $upperValue)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' must be in the format 'IMXX123456789012' where XX is the 2-letter country code.",
                            400
                        );
                    }
                    $countryCode = substr($upperValue, 2, 2);
                    if (is_array($availableCountries)) {
                        $this->validateCountryCode(
                            $countryCode, 
                            $availableCountries, 
                            $service,
                            $field['name']
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'LabelFormat') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::ALLOWED_LABEL_FORMATS, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. Allowed values: " . implode(', ', self::ALLOWED_LABEL_FORMATS),
                            400
                        );
                    }
                    $value = $upperValue;
                }
                $result[$key] = $value;
            }
        }
        if (!isset($result['ShippingValue']) && !isset($result['Value'])) {
            throw new \InvalidArgumentException(
                "At least one of the shipment fields 'shipping_value' or 'value' must be provided.",
                400
            );
        }

        return $result;
    }

    private function validateCountryCode(
        string $countryCode, 
        array $availableCountries, 
        string $service,
        string $fieldName
    ): string
    {
        $countryCode = strtoupper($countryCode);
        if (!in_array($countryCode, $availableCountries, true)) {
            throw new \InvalidArgumentException(
                "Shipment field '{$fieldName}' country code '{$countryCode}' is not supported for {$service} service.",
                400
            );
        }
        return $countryCode;
    }

}