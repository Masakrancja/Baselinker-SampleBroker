<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Baselinker\Samplebroker\ValidateAddress;
use InvalidArgumentException;

class ValidateAddressTest extends TestCase
{
    private ValidateAddress $validator;
    private array $defaultServiceInfo;

    protected function setUp(): void
    {
        $this->validator = new ValidateAddress();
        
        $this->defaultServiceInfo = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'DE', 'FR', 'US', 'GB', 'NL'],
                        'FullName' => 40,
                        'Company' => 50,
                        'AddressLine1' => 45,
                        'City' => 30,
                        'Phone' => 12,
                        'Email' => 100
                    ]
                ]
            ]
        ];
    }

    /**
     * Test consignor address validation with minimal required data
     */
    public function testConsignorAddressWithMinimalData(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
        $this->assertEquals('Main Street 123', $result['AddressLine1']);
        $this->assertEquals('Warsaw', $result['City']);
        $this->assertEquals('00-001', $result['Zip']);
        $this->assertEquals('PL', $result['Country']);
    }

    /**
     * Test consignor address validation with all fields
     */
    public function testConsignorAddressWithAllFields(): void
    {
        $consignorData = [
            'sender_fullname' => 'John Doe',
            'sender_company' => 'Test Company Ltd',
            'sender_address' => 'Main Street 123',
            'sender_address2' => 'Building A',
            'sender_address3' => 'Floor 2',
            'sender_city' => 'Warsaw',
            'sender_state' => 'Mazowieckie',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_phone' => '123456789',
            'sender_email' => 'john@example.com'
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('FullName', $result);
        $this->assertArrayHasKey('Company', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('AddressLine2', $result);
        $this->assertArrayHasKey('AddressLine3', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('State', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
        $this->assertArrayHasKey('Phone', $result);
        $this->assertArrayHasKey('Email', $result);
        $this->assertEquals('john@example.com', $result['Email']);
    }

    /**
     * Test consignee address validation with minimal required data
     */
    public function testConsigneeAddressWithMinimalData(): void
    {
        $consigneeData = [
            'delivery_fullname' => 'Jane Smith',
            'delivery_address' => 'Oak Avenue 456',
            'delivery_city' => 'Berlin',
            'delivery_postalcode' => '10115',
            'delivery_country' => 'DE'
        ];

        $result = $this->validator->consigneeAddress($consigneeData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('FullName', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
        $this->assertEquals('Jane Smith', $result['FullName']);
        $this->assertEquals('Oak Avenue 456', $result['AddressLine1']);
        $this->assertEquals('Berlin', $result['City']);
        $this->assertEquals('10115', $result['Zip']);
        $this->assertEquals('DE', $result['Country']);
    }

    /**
     * Test consignor address - missing required address field
     */
    public function testConsignorAddressMissingRequiredAddress(): void
    {
        $consignorData = [
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_address' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test consignor address - missing required city field
     */
    public function testConsignorAddressMissingRequiredCity(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_city' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test consignor address - missing required postal code field
     */
    public function testConsignorAddressMissingRequiredPostalCode(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_postalcode' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test consignor address - missing required country field
     */
    public function testConsignorAddressMissingRequiredCountry(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test consignee address - missing required fullname field
     */
    public function testConsigneeAddressMissingRequiredFullName(): void
    {
        $consigneeData = [
            'delivery_address' => 'Oak Avenue 456',
            'delivery_city' => 'Berlin',
            'delivery_postalcode' => '10115',
            'delivery_country' => 'DE'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_fullname' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->consigneeAddress($consigneeData, $this->defaultServiceInfo);
    }

    /**
     * Test field length validation - exceeds service limit
     */
    public function testAddressFieldExceedsServiceLimit(): void
    {
        $consignorData = [
            'sender_fullname' => str_repeat('A', 45), // Exceeds service limit (40)
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_fullname' exceeds maximum length of 40 characters.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test field length validation - exceeds default limit when no service limit
     */
    public function testAddressFieldExceedsDefaultLimit(): void
    {
        $serviceInfoWithoutLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL']
                        // No field limits specified
                    ]
                ]
            ]
        ];

        $consignorData = [
            'sender_fullname' => str_repeat('A', 55), // Exceeds default limit (50)
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_fullname' exceeds maximum length of 50 characters.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $serviceInfoWithoutLimits);
    }

    /**
     * Test address field length validation with special message
     */
    public function testAddressFieldExceedsLimitWithSpecialMessage(): void
    {
        $consignorData = [
            'sender_address' => str_repeat('A', 50), // Exceeds service limit (45)
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_address' exceeds maximum length of 45 characters. Consider splitting the address into multiple lines.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test email validation - invalid email format
     */
    public function testAddressInvalidEmailFormat(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_email' => 'invalid-email-format'
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_email' must be a valid email address.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test phone validation - invalid phone format
     */
    public function testAddressInvalidPhoneFormat(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_phone' => '123-456-789abc' // Contains letters and exceeds length
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_phone' exceeds maximum length of 12 characters.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test phone validation - invalid characters but valid length
     */
    public function testAddressInvalidPhoneCharacters(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_phone' => '123-456-789' // Contains hyphens but within length limit
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_phone' must be a valid phone number:  (up to 12 digits).");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test phone validation - exceeds maximum digits
     */
    public function testAddressPhoneExceedsMaxLength(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_phone' => '1234567890123' // 13 digits, exceeds service limit (12)
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_phone' exceeds maximum length of 12 characters.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test country code validation - invalid length
     */
    public function testAddressInvalidCountryCodeLength(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'POLAND' // Too long
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' exceeds maximum length of 2 characters.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test country code validation - single character
     */
    public function testAddressCountryCodeSingleChar(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'P' // Only 1 character
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' must be a valid 2-letter country code.");
        $this->expectExceptionCode(400);

        $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
    }

    /**
     * Test country code validation - not supported by service
     */
    public function testAddressCountryNotSupportedByService(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'XX' // Not in supported countries
        ];

        try {
            $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString("country code 'XX", $e->getMessage());
            $this->assertStringContainsString("is not supported for EXPRESS service", $e->getMessage());
        }
    }

    /**
     * Test country code case conversion
     */
    public function testAddressCountryCodeCaseConversion(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'pl' // Lowercase
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertEquals('PL', $result['Country']); // Should be uppercase
    }

    /**
     * Test string trimming for all fields
     */
    public function testAddressStringTrimming(): void
    {
        $consignorData = [
            'sender_fullname' => '  John Doe  ',
            'sender_address' => '  Main Street 123  ',
            'sender_city' => '  Warsaw  ',
            'sender_postalcode' => '  00-001  ',
            'sender_country' => '  PL  '
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertEquals('John Doe', $result['FullName']);
        $this->assertEquals('Main Street 123', $result['AddressLine1']);
        $this->assertEquals('Warsaw', $result['City']);
        $this->assertEquals('00-001', $result['Zip']);
        $this->assertEquals('PL', $result['Country']);
    }

    /**
     * Test empty optional fields are not included in result
     */
    public function testAddressEmptyOptionalFieldsNotIncluded(): void
    {
        $consignorData = [
            'sender_fullname' => '', // Empty optional field
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL',
            'sender_phone' => '' // Empty optional field
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertArrayNotHasKey('FullName', $result);
        $this->assertArrayNotHasKey('Phone', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
    }

    /**
     * Test valid email formats
     */
    public function testAddressValidEmailFormats(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk',
            'user+tag@example.org',
            'user123@test-domain.com'
        ];

        foreach ($validEmails as $email) {
            $consignorData = [
                'sender_address' => 'Main Street 123',
                'sender_city' => 'Warsaw',
                'sender_postalcode' => '00-001',
                'sender_country' => 'PL',
                'sender_email' => $email
            ];

            $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
            $this->assertEquals($email, $result['Email']);
        }
    }

    /**
     * Test valid phone number formats
     */
    public function testAddressValidPhoneFormats(): void
    {
        $validPhones = [
            '123456789',
            '48123456789',
            '0123456789',
            '999888777666' // Exactly 12 digits (service limit)
        ];

        foreach ($validPhones as $phone) {
            $consignorData = [
                'sender_address' => 'Main Street 123',
                'sender_city' => 'Warsaw',
                'sender_postalcode' => '00-001',
                'sender_country' => 'PL',
                'sender_phone' => $phone
            ];

            $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);
            $this->assertEquals($phone, $result['Phone']);
        }
    }

    /**
     * Test service without supported countries validation
     */
    public function testAddressServiceWithoutSupportedCountries(): void
    {
        $serviceInfoWithoutCountries = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'fieldLimits' => [
                        'SupportedCountries' => [] // Empty array instead of missing
                    ]
                ]
            ]
        ];

        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        // Should throw exception when supported countries is empty array
        try {
            $this->validator->consignorAddress($consignorData, $serviceInfoWithoutCountries);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString("country code 'PL", $e->getMessage());
            $this->assertStringContainsString("is not supported for EXPRESS service", $e->getMessage());
        }
    }

    /**
     * Test service with missing supported countries key
     */
    public function testAddressServiceWithMissingSupportedCountries(): void
    {
        $serviceInfoWithoutCountriesKey = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'fieldLimits' => [
                        // SupportedCountries key missing entirely - will be null
                    ]
                ]
            ]
        ];

        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        // Should throw exception when supported countries key is missing 
        // because it defaults to [] (empty array), and is_array([]) returns true
        try {
            $this->validator->consignorAddress($consignorData, $serviceInfoWithoutCountriesKey);
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString("country code 'PL", $e->getMessage());
            $this->assertStringContainsString("is not supported for EXPRESS service", $e->getMessage());
        }
    }

    /**
     * Test consignee address with company field
     */
    public function testConsigneeAddressWithCompany(): void
    {
        $consigneeData = [
            'delivery_fullname' => 'Jane Smith',
            'delivery_company' => 'Customer Corp',
            'delivery_address' => 'Oak Avenue 456',
            'delivery_city' => 'Berlin',
            'delivery_postalcode' => '10115',
            'delivery_country' => 'DE'
        ];

        $result = $this->validator->consigneeAddress($consigneeData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('FullName', $result);
        $this->assertArrayHasKey('Company', $result);
        $this->assertEquals('Jane Smith', $result['FullName']);
        $this->assertEquals('Customer Corp', $result['Company']);
    }

    /**
     * Test address with multiple address lines
     */
    public function testAddressWithMultipleLines(): void
    {
        $consignorData = [
            'sender_address' => 'Main Street 123',
            'sender_address2' => 'Apartment 4B',
            'sender_address3' => 'Building C',
            'sender_city' => 'Warsaw',
            'sender_postalcode' => '00-001',
            'sender_country' => 'PL'
        ];

        $result = $this->validator->consignorAddress($consignorData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('AddressLine2', $result);
        $this->assertArrayHasKey('AddressLine3', $result);
        $this->assertEquals('Main Street 123', $result['AddressLine1']);
        $this->assertEquals('Apartment 4B', $result['AddressLine2']);
        $this->assertEquals('Building C', $result['AddressLine3']);
    }

    /**
     * Test address with state field
     */
    public function testAddressWithState(): void
    {
        $consigneeData = [
            'delivery_fullname' => 'John Smith',
            'delivery_address' => '123 Main St',
            'delivery_city' => 'New York',
            'delivery_state' => 'NY',
            'delivery_postalcode' => '10001',
            'delivery_country' => 'US'
        ];

        $result = $this->validator->consigneeAddress($consigneeData, $this->defaultServiceInfo);

        $this->assertArrayHasKey('State', $result);
        $this->assertEquals('NY', $result['State']);
    }
}