<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker\Tests;

use Baselinker\Samplebroker\Validate;
use PHPUnit\Framework\TestCase;

class ValidateConsigneeAddressTest extends TestCase
{
    private Validate $validate;
    private array $validServiceInfo;

    protected function setUp(): void
    {
        $this->validate = new Validate();
        
        // Przykładowe dane serviceInfo z obsługiwanymi krajami
        $this->validServiceInfo = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE',
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'US', 'DE', 'FR', 'GB'],
                        'Name' => 50,
                        'Company' => 60,
                        'AddressLine1' => 50,
                        'AddressLine2' => 50,
                        'AddressLine3' => 50,
                        'City' => 50,
                        'State' => 50,
                        'Zip' => 20,
                        'Country' => 2,
                        'Phone' => 15,
                        'Email' => 255
                    ]
                ]
            ]
        ];
    }

    /**
     * Test z poprawnymi minimalnymi danymi (tylko wymagane pola)
     */
    public function testConsigneeAddressWithMinimalValidData(): void
    {
        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
        $this->assertEquals('Jan Kowalski', $result['Name']);
        $this->assertEquals('ul. Testowa 123', $result['AddressLine1']);
        $this->assertEquals('Warszawa', $result['City']);
        $this->assertEquals('00-001', $result['Zip']);
        $this->assertEquals('PL', $result['Country']);
    }

    /**
     * Test z kompletnymi poprawnymi danymi
     */
    public function testConsigneeAddressWithCompleteValidData(): void
    {
        $consignee = [
            'delivery_fullname' => 'Anna Nowak',
            'delivery_company' => 'Test Company Sp. z o.o.',
            'delivery_address' => 'ul. Główna 123',
            'delivery_address2' => 'mieszkanie 45',
            'delivery_address3' => 'budynek A',
            'delivery_city' => 'Kraków',
            'delivery_state' => 'małopolskie',
            'delivery_postalcode' => '30-001',
            'delivery_country' => 'PL',
            'delivery_phone' => '123456789',
            'delivery_email' => 'anna@example.com'
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        $this->assertArrayHasKey('Name', $result);
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

        $this->assertEquals('Anna Nowak', $result['Name']);
        $this->assertEquals('Test Company Sp. z o.o.', $result['Company']);
        $this->assertEquals('PL', $result['Country']); // Sprawdzenie konwersji na wielkie litery
        $this->assertEquals('anna@example.com', $result['Email']);
        $this->assertEquals('123456789', $result['Phone']);
    }

    /**
     * Test brakującego wymaganego pola Name
     */
    public function testConsigneeAddressMissingRequiredName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_fullname' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola AddressLine1
     */
    public function testConsigneeAddressMissingRequiredAddressLine1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_address' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola City
     */
    public function testConsigneeAddressMissingRequiredCity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_city' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Zip
     */
    public function testConsigneeAddressMissingRequiredZip(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_postalcode' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Country
     */
    public function testConsigneeAddressMissingRequiredCountry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_country' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test pustego pola wymaganego (puste string)
     */
    public function testConsigneeAddressEmptyRequiredField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_fullname' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => '',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego pola Name
     */
    public function testConsigneeAddressTooLongName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_fullname' exceeds maximum length of 50 characters.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => str_repeat('a', 51), // 51 znaków
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego pola Company
     */
    public function testConsigneeAddressTooLongCompany(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_company' exceeds maximum length of 60 characters.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_company' => str_repeat('a', 61), // 61 znaków
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego adresu z dodatkową informacją
     */
    public function testConsigneeAddressTooLongAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_address' exceeds maximum length of 50 characters. Consider splitting the address into multiple lines.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => str_repeat('a', 51), // 51 znaków
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego formatu email
     */
    public function testConsigneeAddressInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_email' must be a valid email address.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL',
            'delivery_email' => 'niepoprawny-email'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego formatu telefonu
     */
    public function testConsigneeAddressInvalidPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_phone' must be a valid phone number: (up to 15 digits).");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL',
            'delivery_phone' => 'abc123'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego telefonu
     */
    public function testConsigneeAddressTooLongPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_phone' exceeds maximum length of 15 characters.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL',
            'delivery_phone' => '1234567890123456' // 16 cyfr
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego kodu kraju (zbyt krótki)
     */
    public function testConsigneeAddressInvalidCountryCodeTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_country' must be a valid 2-letter country code.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'P'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego kodu kraju (zbyt długi)
     */
    public function testConsigneeAddressInvalidCountryCodeTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_country' exceeds maximum length of 2 characters.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'POL'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test nieobsługiwanego kodu kraju
     */
    public function testConsigneeAddressUnsupportedCountryCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'delivery_country' country code 'XX' is not supported for TEST_SERVICE service.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'XX'
        ];

        $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
    }

    /**
     * Test konwersji kodu kraju na wielkie litery
     */
    public function testConsigneeAddressCountryCodeConversion(): void
    {
        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'pl' // małe litery
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        $this->assertEquals('PL', $result['Country']);
    }

    /**
     * Test z domyślnymi limitami gdy brakuje fieldLimits w serviceInfo
     */
    public function testConsigneeAddressWithDefaultLimits(): void
    {
        $serviceInfoWithoutLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE',
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL'] // Musi być lista krajów, inaczej walidacja się nie powiedzie
                    ]
                ]
            ]
        ];

        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $result = $this->validate->consigneeAddress($consignee, $serviceInfoWithoutLimits);

        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
    }

    /**
     * Test z limitami zdefiniowanymi w serviceInfo
     */
    public function testConsigneeAddressWithServiceLimits(): void
    {
        $serviceInfoWithCustomLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE',
                    'fieldLimits' => [
                        'Name' => 30, // Niższy limit niż domyślny
                        'SupportedCountries' => ['PL', 'US']
                    ]
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'delivery_fullname' exceeds maximum length of 30 characters.");
        $this->expectExceptionCode(400);

        $consignee = [
            'delivery_fullname' => str_repeat('a', 31), // 31 znaków, przekracza limit 30
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $this->validate->consigneeAddress($consignee, $serviceInfoWithCustomLimits);
    }

    /**
     * Test z białymi znakami w polach - sprawdzenie trim()
     */
    public function testConsigneeAddressWithWhitespace(): void
    {
        $consignee = [
            'delivery_fullname' => '  Anna Nowak  ',
            'delivery_address' => '  ul. Testowa 123  ',
            'delivery_city' => '  Kraków  ',
            'delivery_postalcode' => '  30-001  ',
            'delivery_country' => '  pl  '
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        $this->assertEquals('Anna Nowak', $result['Name']);
        $this->assertEquals('ul. Testowa 123', $result['AddressLine1']);
        $this->assertEquals('Kraków', $result['City']);
        $this->assertEquals('30-001', $result['Zip']);
        $this->assertEquals('PL', $result['Country']); // Również trim + uppercase
    }

    /**
     * Test z pustymi opcjonalnymi polami - nie powinny być dodane do wyniku
     */
    public function testConsigneeAddressEmptyOptionalFields(): void
    {
        $consignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_company' => '',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_address2' => '',
            'delivery_address3' => '',
            'delivery_city' => 'Warszawa',
            'delivery_state' => '',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL',
            'delivery_email' => '',
            'delivery_phone' => ''
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        // Sprawdzamy, że puste opcjonalne pola nie są w wyniku
        $this->assertArrayNotHasKey('Company', $result);
        $this->assertArrayNotHasKey('AddressLine2', $result);
        $this->assertArrayNotHasKey('AddressLine3', $result);
        $this->assertArrayNotHasKey('State', $result);
        $this->assertArrayNotHasKey('Email', $result);
        $this->assertArrayNotHasKey('Phone', $result);

        // Sprawdzamy, że wymagane pola są w wyniku
        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
    }

    /**
     * Test graniczny - maksymalna długość pól
     */
    public function testConsigneeAddressMaxLengthFields(): void
    {
        $consignee = [
            'delivery_fullname' => str_repeat('a', 50), // Dokładnie 50 znaków
            'delivery_company' => str_repeat('b', 60), // Dokładnie 60 znaków
            'delivery_address' => str_repeat('c', 50), // Dokładnie 50 znaków
            'delivery_city' => str_repeat('d', 50), // Dokładnie 50 znaków
            'delivery_postalcode' => str_repeat('1', 20), // Dokładnie 20 znaków
            'delivery_country' => 'PL',
            'delivery_phone' => str_repeat('1', 15) // Dokładnie 15 znaków
        ];

        $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);

        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('Company', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Country', $result);
        $this->assertArrayHasKey('Phone', $result);
    }

    /**
     * Test z prawidłowymi emailami granicznymi
     */
    public function testConsigneeAddressValidEmailFormats(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk', 
            'test+tag@example.org',
            'simple@test.io'
        ];

        foreach ($validEmails as $email) {
            $consignee = [
                'delivery_fullname' => 'Jan Kowalski',
                'delivery_address' => 'ul. Testowa 123',
                'delivery_city' => 'Warszawa',
                'delivery_postalcode' => '00-001',
                'delivery_country' => 'PL',
                'delivery_email' => $email
            ];

            $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
            $this->assertEquals($email, $result['Email']);
        }
    }

    /**
     * Test z prawidłowymi telefonami granicznymi
     */
    public function testConsigneeAddressValidPhoneFormats(): void
    {
        $validPhones = [
            '123456789',
            '48123456789',
            '1', // Minimum 1 cyfra
            str_repeat('1', 15) // Maksymalnie 15 cyfr
        ];

        foreach ($validPhones as $phone) {
            $consignee = [
                'delivery_fullname' => 'Jan Kowalski',
                'delivery_address' => 'ul. Testowa 123',
                'delivery_city' => 'Warszawa',
                'delivery_postalcode' => '00-001',
                'delivery_country' => 'PL',
                'delivery_phone' => $phone
            ];

            $result = $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
            $this->assertEquals($phone, $result['Phone']);
        }
    }

    /**
     * Test specyficzny dla consigneeAddress - sprawdzenie wszystkich wymaganych pól
     */
    public function testConsigneeAddressAllRequiredFields(): void
    {
        // Test, że rzeczywiście wszystkie 5 pól jest wymaganych: Name, AddressLine1, City, Zip, Country
        $baseConsignee = [
            'delivery_fullname' => 'Jan Kowalski',
            'delivery_address' => 'ul. Testowa 123',
            'delivery_city' => 'Warszawa',
            'delivery_postalcode' => '00-001',
            'delivery_country' => 'PL'
        ];

        $requiredFields = [
            'delivery_fullname' => 'Name',
            'delivery_address' => 'AddressLine1',
            'delivery_city' => 'City',
            'delivery_postalcode' => 'Zip',
            'delivery_country' => 'Country'
        ];

        foreach ($requiredFields as $fieldName => $expectedKey) {
            $consignee = $baseConsignee;
            unset($consignee[$fieldName]); // Usuń jedno pole

            try {
                $this->validate->consigneeAddress($consignee, $this->validServiceInfo);
                // Jeśli nie został rzucony wyjątek, test nie powiódł się
                $this->fail("Expected InvalidArgumentException for missing field '{$fieldName}' was not thrown.");
            } catch (\InvalidArgumentException $e) {
                // Sprawdzamy czy to oczekiwany błąd
                $this->assertEquals(400, $e->getCode());
                $this->assertStringContainsString("Field '{$fieldName}' cannot be empty.", $e->getMessage());
            }
        }
        
        // Test udany jeśli wszystkie wymagane pola zostały sprawdzone
        $this->assertTrue(true);
    }
}