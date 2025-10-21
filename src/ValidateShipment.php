<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

use Baselinker\Samplebroker\Validate;

class ValidateShipment extends Validate
{

    /**
     * Validates API key format and presence.
     *
     * @param string $apiKey API key to validate
     * @return string Validated API key
     * @throws \InvalidArgumentException When API key is empty or invalid
     */
    public function apiKey(string $apiKey): string
    {
        if (empty($apiKey) || !is_string($apiKey)) {
            throw new \InvalidArgumentException('Invalid API key provided.', 400);
        }

        return $apiKey;
    }

    /**
     * Validates service name against available services from API.
     *
     * @param string $service Service name to validate
     * @param array $services Available services response from API
     * @return string Validated service name
     * @throws \InvalidArgumentException When service is not in allowed services list
     */
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

    /**
     * Validates complete shipment data with dimensions, weight, and customs information.
     * 
     * Validates all shipment fields, handles unit conversions (weight/dimensions), checks
     * dimension constraints, and applies service-specific limits. Ensures at least one
     * value field is provided.
     *
     * @param array $shipment Shipment data to validate
     * @param array $serviceInfo Service configuration from API containing limits
     * @return array Validated shipment data with converted units
     * @throws \InvalidArgumentException When validation fails or constraints violated
     */
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
            'ShipmentValue' => [
                'name' => 'shipment_value', 
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
        $dim = (int)isset($result['Length']) + 
        (int)isset($result['Width']) + 
        (int)isset($result['Height']);

        if ($dim === 1 || $dim === 2) {
            throw new \InvalidArgumentException(
                "Three dimensions (length, width, height) must be provided together. " .
                "Or none of them.",
                400
            );
        }

        $dimUnit = $this->dimUnit ?? self::DEFAULT_DIM_UNIT;

        if ($dim === 3) {
            $result['Length'] = (float) $result['Length'];
            $result['Width'] = (float) $result['Width'];
            $result['Height'] = (float) $result['Height'];

            if ($dimUnit === 'in') {
                $result['Length'] = round($result['Length'] * self::INCH_TO_CM, 2);
                $result['Width'] = round($result['Width'] * self::INCH_TO_CM, 2);
                $result['Height'] = round($result['Height'] * self::INCH_TO_CM, 2);
            }

            $msgCalculated = '';
            if ($dimUnit !== self::DEFAULT_DIM_UNIT) {
                $msgCalculated = "(Dimension converted from {$dimUnit} to " . 
                self::DEFAULT_DIM_UNIT . ")";
            }

            if (
                $result['Length'] < $fields['Length']['min'] || 
                $result['Length'] > $fields['Length']['max']
            ) {
                throw new \InvalidArgumentException(
                    "Shipment length '{$result['Length']} " . self::DEFAULT_DIM_UNIT . "' " .
                    $msgCalculated . " must be between " .
                    "{$fields['Length']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Length']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            if (
                $result['Width'] < $fields['Width']['min'] || 
                $result['Width'] > $fields['Width']['max']
            ) {
                throw new \InvalidArgumentException(
                    "Shipment width '{$result['Width']} " . self::DEFAULT_DIM_UNIT . "' " . 
                    $msgCalculated . " must be between " .
                    "{$fields['Width']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Width']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            if (
                $result['Height'] < $fields['Height']['min'] || 
                $result['Height'] > $fields['Height']['max']    
            ) {
                throw new \InvalidArgumentException(
                    "Shipment height '{$result['Height']} " . self::DEFAULT_DIM_UNIT . "' " . 
                    $msgCalculated . " must be between " .
                    "{$fields['Height']['min']} " . self::DEFAULT_DIM_UNIT . " and " .
                    "{$fields['Height']['max']} " . self::DEFAULT_DIM_UNIT . ".",
                    400
                );
            }

            $dimensionSum = $result['Length'] + 2 * ($result['Width'] + $result['Height']);
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

        $result['Weight'] = (float) $result['Weight'];
        $maxWeight = $serviceInfo['response']['ServiceInfo']['maxWeight'] ?? 
        self::MAX_SHIPMENT_WEIGHT; //from service is only in kg unit

        $msgCalculated = '';
        if ($this->weightUnit === 'lb') {
            $result['Weight'] = round($result['Weight'] * self::LB_TO_KG, 2);
            $msgCalculated = "(Weight converted from {$this->weightUnit} to " . 
            self::DEFAULT_WEIGHT_UNIT . ")";
        }

        if ($result['Weight'] > $maxWeight || $result['Weight'] < $fields['Weight']['min']) {
            throw new \InvalidArgumentException(
                "Shipment weight '{$result['Weight']} " . self::DEFAULT_WEIGHT_UNIT . "' " .
                $msgCalculated . " must be between " .
                "{$fields['Weight']['min']} " . self::DEFAULT_WEIGHT_UNIT . " and " .
                "{$maxWeight} " . self::DEFAULT_WEIGHT_UNIT . ".",
                400
            );
        }

        return $result;
    }

    /**
     * Core field-by-field validation logic for shipment data.
     * 
     * Validates individual fields including data types, length limits, date formats,
     * special field validations (currencies, customs, VAT numbers), and country codes.
     * Applies service-specific field limits when available.
     *
     * @param array $fields Field configuration array defining validation rules
     * @param array $shipment Raw shipment data to validate
     * @param array $serviceInfo Service configuration containing field limits
     * @return array Validated and normalized shipment data
     * @throws \InvalidArgumentException When field validation fails
     * @throws \RuntimeException When unsupported field type encountered
     */
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
                                "Field '{$field['name']}' exceeds maximum length of {$maxLength} ".
                                " characters.",
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
                    if ($valueLength > ($serviceLimits[$key] ?? 15)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' exceeds maximum length of " . 
                            ($serviceLimits[$key] ?? 15) . " characters.",
                            400
                        );
                    }
                }
                if ($key === 'WeightUnit') {
                    $lowerValue = strtolower($value);
                    if (!in_array($lowerValue, self::ALLOWED_WEIGHT_UNITS, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Allowed values: " . implode(', ', self::ALLOWED_WEIGHT_UNITS),
                            400
                        );
                    }
                    $this->weightUnit = $lowerValue;
                    $value = self::DEFAULT_WEIGHT_UNIT; // always convert to kg internally
                }
                if ($key === 'DimUnit') {
                    $lowerValue = strtolower($value);
                    if (!in_array($lowerValue, self::ALLOWED_DIM_UNITS, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Allowed values: " . implode(', ', self::ALLOWED_DIM_UNITS),
                            400
                        );
                    }
                    $this->dimUnit = $lowerValue;
                    $value = self::DEFAULT_DIM_UNIT; // always convert to cm internally
                }
                if ($key === 'Currency') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::CURRENCIES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Must be a valid ISO 4217 currency code.",
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'CustomsDuty') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::CUSTOM_DUTY_TYPES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Allowed values: " . implode(', ', self::CUSTOM_DUTY_TYPES),
                            400
                        );
                    }
                    $value = $upperValue;
                }
                if ($key === 'DangerousGoods') {
                    $upperValue = strtoupper($value);
                    if (!in_array($upperValue, self::DANGEROUS_GOODS_TYPES, true)) {
                        throw new \InvalidArgumentException(
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Allowed values: " . implode(', ', self::DANGEROUS_GOODS_TYPES),
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
                            "Shipment field '{$field['name']}' must start with a 2-letter ".
                            "country code followed by alphanumeric characters.",
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
                            "Shipment field '{$field['name']}' has invalid value '{$value}'. ".
                            "Allowed values: " . implode(', ', self::ALLOWED_LABEL_FORMATS),
                            400
                        );
                    }
                    $value = $upperValue;
                }
                $result[$key] = $value;
            }
        }

        if (!isset($result['ShipmentValue']) && !isset($result['Value'])) {
            throw new \InvalidArgumentException(
                "At least one of the shipment fields 'shipment_value' or 'value' must be provided.",
                400
            );
        }

        return $result;
    }
}
