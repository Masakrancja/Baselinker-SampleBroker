<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

use Baselinker\Samplebroker\Validate;

class ValidateProduct extends Validate
{
    public function products(
        array $products,
        string $consignorCountry,
        string $consigneeCountry,
        array $serviceInfo
    ): array {
        $result = [];
        $serviceInfo = $serviceInfo['response']['ServiceInfo'];
        $maxWeight = $serviceInfo['maxWeight'] ?? self::MAX_SHIPMENT_WEIGHT; // from service is only in kg unit
        $service = $serviceInfo['service'] ?? '';
        if (empty($products)) {
            throw new \InvalidArgumentException('Products array cannot be empty.', 400);
        }

        $requiredHSCode = false;
        if ($consigneeCountry !== $consignorCountry) {
            $requiredHSCode = true;
        }

        $fields = [
            'Description' => [
                'name' => 'name',
                'required' => true,
                'type' => 'string',
                'default_length' => self::PRODUCT_DESC_MAX_LENGTH,
            ],
            'Quantity' => [
                'name' => 'quantity',
                'required' => true,
                'type' => 'int',
                'min' => 1,
                'max' => self::MAX_PRODUCT_COUNT,
            ],
            'Weight' => [
                'name' => 'weight',
                'required' => true,
                'type' => 'number',
                'min' => 0,
                'max' => self::MAX_SHIPMENT_WEIGHT,
            ],
            'Value' => [
                'name' => 'value',
                'required' => false,
                'type' => 'number',
                'min' => 0,
                'max' => self::MAX_VALUE,
            ],
            'HsCode' => [
                'name' => 'hs_code',
                'required' => $requiredHSCode,
                'type' => 'string',
                'default_length' => 255,
            ],
            'OriginCountry' => [
                'name' => 'origin_country',
                'required' => false,
                'type' => 'string',
                'default_length' => 2,
            ],
        ];

        $productIndex = 0;
        $amountOfProducts = 0;
        $weightOfProducts = 0.0;
        $valueOfProducts = 0.0;
        foreach ($products as $product) {
            $result[] = $this->validateProduct($fields, $product, $serviceInfo, $productIndex);
            $amountOfProducts += (int) ($result[$productIndex]['Quantity']);
            if ($this->weightUnit === 'lb') {
                $result[$productIndex]['Weight'] = (
                    round((float) ($result[$productIndex]['Weight']) * self::LB_TO_KG, 2)
                );
            }
            $weightOfProducts += (float) (
                $result[$productIndex]['Weight']) * (int) ($result[$productIndex]['Quantity']
            );
            $valueOfProducts += ((float) (
                $result[$productIndex]['Value'] ?? 0.0)) * (int) ($result[$productIndex]['Quantity']
            );
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
        $msgCalculated = '';
        if (strtolower($this->weightUnit) === 'lb') {
            $msgCalculated = "(Weight converted from {$this->weightUnit} to " . self::DEFAULT_WEIGHT_UNIT . ")";
        }

        if ($weightOfProducts > $maxWeight) {
            throw new \InvalidArgumentException(
                "Total weight '{$weightOfProducts} " . self::DEFAULT_WEIGHT_UNIT . "'" .
                $msgCalculated . " of products exceeds maximum allowed weight " .
                "{$maxWeight} " . self::DEFAULT_WEIGHT_UNIT . " for the " . $service . " service.",
                400
            );
        }

        return $result;
    }

    private function validateProduct(
        array $fields, 
        array $product, 
        array $serviceInfo, 
        int $productIndex
    ): array
    {
        $result = [];
        // $serviceInfo is already transformed in products() method, no need to access ['response']['ServiceInfo']
        $serviceLimits = $serviceInfo['fieldLimits'] ?? [];
        $availableCountries = $serviceLimits['SupportedCountries'] ?? [];
        $service = $serviceInfo['service'] ?? '';
        $maxProductWeight = $serviceLimits['Weight'] ?? $fields['Weight']['max'];

        foreach ($fields as $key => $field) {
            $value = trim((string) ($product[$field['name']] ?? null));
            if ($field['required'] && $value === '') {
                throw new \InvalidArgumentException(
                    "Product no: {$productIndex} field: '{$field['name']}' cannot be empty.",
                    400
                );
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

                        if ($key === 'Weight') {
                            $field['max'] = $maxProductWeight ?? $field['max'];
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
}