<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

use Baselinker\Samplebroker\Validate;

class ValidateAddress extends Validate
{
    /**
     * Validates sender/consignor address data.
     * 
     * Validates all sender address fields including required fields (address, city, postal code, country)
     * and optional fields (name, company, phone, email). Applies service-specific length limits and
     * format validation.
     *
     * @param array $consignor Sender address data with 'sender_' prefixed keys
     * @param array $serviceInfo Service configuration from API containing field limits
     * 
     * @return array Validated address data with standardized keys
     * 
     * @throws \InvalidArgumentException When required fields are missing or validation fails
     */
    public function consignorAddress(array $consignor, array $serviceInfo): array
    {
        $fields = [
            'FullName' => [
                'name' => 'sender_fullname', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'Company' => [
                'name' => 'sender_company', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 60
            ],
            'AddressLine1' => [
                'name' => 'sender_address', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 50
            ],
            'AddressLine2' => [
                'name' => 'sender_address2', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'AddressLine3' => [
                'name' => 'sender_address3', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'City' => [
                'name' => 'sender_city', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 50
            ],
            'State' => [
                'name' => 'sender_state', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 50
            ],
            'Zip' => [
                'name' => 'sender_postalcode', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 20
            ],
            'Country' => [
                'name' => 'sender_country', 
                'required' => true, 
                'type' => 'string',
                'default_length' => 2
            ],
            'Phone' => [
                'name' => 'sender_phone', 
                'required' => false,
                'type' => 'string', 
                'default_length' => 15
            ],
            'Email' => [
                'name' => 'sender_email', 
                'required' => false, 
                'type' => 'string',
                'default_length' => 255
            ],
        ];

        return $this->validateAddress($fields, $consignor, $serviceInfo);
    }

    /**
     * Validates recipient/consignee address data.
     * 
     * Validates all delivery address fields including required fields (fullname, address, city, 
     * postal code, country) and optional fields (company, phone, email). Applies service-specific 
     * length limits and format validation.
     *
     * @param array $consignee Delivery address data with 'delivery_' prefixed keys
     * @param array $serviceInfo Service configuration from API containing field limits
     * 
     * @return array Validated address data with standardized keys
     * 
     * @throws \InvalidArgumentException When required fields are missing or validation fails
     */
    public function consigneeAddress(array $consignee, array $serviceInfo): array
    {
        $fields = [
            'FullName' => [
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

    /**
     * Core address validation logic shared by consignor and consignee validation.
     * 
     * Performs field-by-field validation including length limits, format validation for emails
     * and phone numbers, country code validation, and service compatibility checks.
     *
     * @param array $fields Field configuration array defining validation rules
     * @param array $address Raw address data to validate
     * @param array $serviceInfo Service configuration from API containing field limits
     * 
     * @return array Validated and normalized address data
     * 
     * @throws \InvalidArgumentException When validation fails for any field
     */
    private function validateAddress(
        array $fields,
        array $address,
        array $serviceInfo
    ): array {
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
                if (
                    strpos($key, 'Phone') !== false && 
                    !preg_match('/^\d{0,' . $maxLength . '}$/', $value)
                ) {
                    throw new \InvalidArgumentException(
                        "Field '{$field['name']}' must be a valid phone number: ".
                        " (up to {$maxLength} digits).",
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
}