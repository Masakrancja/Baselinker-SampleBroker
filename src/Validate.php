<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

class Validate
{   

    private const ALLOWED_LABEL_FORMATS = [
        'PDF', 'PNG', 'ZPL300', 'ZPL600', 'ZPL200', 'ZPL', 'EPL'
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

    public function shipment($shipment): array
    {
        $result = [];
        foreach ($shipment as $key => $value) {
            echo 'key: ' . $key . ', value: ' . $value . PHP_EOL;
        }

        return $result;
    }

    public function labelFormat(?string $labelFormat): string
    {
        if (!in_array(strtoupper($labelFormat), self::ALLOWED_LABEL_FORMATS, true)) {
            throw new \InvalidArgumentException(
                'Invalid label format. Allowed formats: ' . implode(', ', self::ALLOWED_LABEL_FORMATS),
                400
            );
        }

        return $labelFormat;
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
                'default_length' => -1
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
                'default_length' => -1
            ],
        ];
        return $this->validateAddress($fields, $consignee, $serviceInfo);
    }

    public function products(array $products, array $serviceInfo): array
    {
        $result = [];
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.', 400);
        }

        $fields = [
            'Description' => [
                'name' => 'name', 
                'required' => true, 
                'type' => 'string', 
                'default_length' => 105
            ],
            'Quantity' => [
                'name' => 'quantity', 
                'required' => true, 
                'type' => 'int', 
                'min' => 1, 
                'max' => -1
            ],
            'Weight' => [
                'name' => 'weight', 
                'required' => true, 
                'type' => 'number', 
                'min' => 0, 
                'max' => -1
            ],
            'Value' => [
                'name' => 'value', 
                'required' => false, 
                'type' => 'number', 
                'min' => 0, 
                'max' => -1
            ],
            'HsCode' => [
                'name' => 'hs_code', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 25
            ],
            'OriginCountry' => [
                'name' => 'origin_country', 
                'required' => false, 
                'type' => 'string', 
                'default_length' => 2
            ],
        ];

        $maxWeight = $serviceInfo['response']['ServiceInfo']['maxWeight'] ?? 2.0;
        $service = $serviceInfo['response']['ServiceInfo']['service'] ?? '';
        $productsWeight = 0.0;
        $productIndex = 0;
        foreach ($products as $product) {
            $result[] = $this->validateProduct($fields, $product, $serviceInfo, $productIndex);
            $productsWeight += $product['weight'] * $product['quantity'];
            $productIndex++;
        }
        if ($productsWeight > $maxWeight) {
            throw new \InvalidArgumentException(
                "Total products weight ({$productsWeight} kg) exceeds the maximum allowed weight of {$maxWeight} kg for the {$service} service.",
                400
            );
        }

        return $result;
    }

    private function validateAddress(array $fields, array $address, array $serviceInfo): array
    {
        $result = [];
        $serviceLimits = $serviceInfo['response']['ServiceInfo']['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['response']['ServiceInfo']['service'] ?? '';

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
                    if (strlen($value) === 2) {
                        if (is_array($availableCountries)) {
                            if (!in_array(strtoupper($value), $availableCountries, true)) {
                                throw new \InvalidArgumentException(
                                    "Country code '{$value}' is not supported for {$service} service.",
                                    400
                                );
                            }
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
        $serviceLimits = $serviceInfo['response']['ServiceInfo']['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['response']['ServiceInfo']['service'] ?? '';

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
                        if ($field['max'] !== -1 && $value > $field['max']) {
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
                        if ($field['max'] !== -1 && $value > $field['max']) {
                            throw new \InvalidArgumentException(
                                "Product no. {$productIndex}: field '{$field['name']}' must be at most {$field['max']}.",
                                400
                            );
                        }
                        break;
                    default:
                        throw new \InvalidArgumentException(
                            "Product no. {$productIndex}: field '{$field['name']}' has an unknown type '{$field['type']}'.",
                            400
                        );
                }

                if (strpos($key, 'OriginCountry') !== false) {
                    if (strlen($value) === 2) {
                        if (is_array($availableCountries)) {
                            if (!in_array(strtoupper($value), $availableCountries, true)) {
                                throw new \InvalidArgumentException(
                                    "Product no. {$productIndex}: Origin country code '{$value}' is not supported for {$service} service.",
                                    400
                                );
                            }
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

}