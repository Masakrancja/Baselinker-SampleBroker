<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker\Tests;

use Baselinker\Samplebroker\Validate;
use PHPUnit\Framework\TestCase;

class ValidateConsignorAddressTest extends TestCase
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
                        'FullName' => 50,
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
    public function testConsignorAddressWithMinimalValidData(): void
    {
        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertEquals('ul. Testowa 123', $result['AddressLine1']);
        $this->assertEquals('Warszawa', $result['City']);
        $this->assertEquals('00-001', $result['Zip']);
    }

    /**
     * Test z kompletnymi poprawnymi danymi
     */
    public function testConsignorAddressWithCompleteValidData(): void
    {
        $consignor = [
            'sender_fullname' => 'Jan Kowalski',
            'sender_company' => 'Test Company Sp. z o.o.',
            'sender_address' => 'ul. Główna 123',
            'sender_address2' => 'mieszkanie 45',
            'sender_address3' => 'budynek A',
            'sender_city' => 'Kraków',
            'sender_state' => 'małopolskie',
            'sender_postalcode' => '30-001',
            'sender_country' => 'PL',
            'sender_phone' => '123456789',
            'sender_email' => 'test@example.com'
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

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

        $this->assertEquals('Jan Kowalski', $result['FullName']);
        $this->assertEquals('Test Company Sp. z o.o.', $result['Company']);
        $this->assertEquals('PL', $result['Country']); // Sprawdzenie konwersji na wielkie litery
        $this->assertEquals('test@example.com', $result['Email']);
        $this->assertEquals('123456789', $result['Phone']);
    }

    /**
     * Test brakującego wymaganego pola AddressLine1
     */
    public function testConsignorAddressMissingRequiredAddressLine1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_address' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola City
     */
    public function testConsignorAddressMissingRequiredCity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_city' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Zip
     */
    public function testConsignorAddressMissingRequiredZip(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_postalcode' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test pustego pola wymaganego (puste string)
     */
    public function testConsignorAddressEmptyRequiredField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_address' cannot be empty.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => '',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego pola FullName
     */
    public function testConsignorAddressTooLongFullName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_fullname' exceeds maximum length of 50 characters.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_fullname' => str_repeat('a', 51), // 51 znaków
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego pola Company
     */
    public function testConsignorAddressTooLongCompany(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_company' exceeds maximum length of 60 characters.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_company' => str_repeat('a', 61), // 61 znaków
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego adresu z dodatkową informacją
     */
    public function testConsignorAddressTooLongAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_address' exceeds maximum length of 50 characters. Consider splitting the address into multiple lines.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => str_repeat('a', 51), // 51 znaków
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego formatu email
     */
    public function testConsignorAddressInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_email' must be a valid email address.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_email' => 'niepoprawny-email'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego formatu telefonu
     */
    public function testConsignorAddressInvalidPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_phone' must be a valid phone number: (up to 15 digits).");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_phone' => 'abc123'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego telefonu
     */
    public function testConsignorAddressTooLongPhone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_phone' exceeds maximum length of 15 characters.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_phone' => '1234567890123456' // 16 cyfr
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego kodu kraju (zbyt krótki)
     */
    public function testConsignorAddressInvalidCountryCodeTooShort(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' must be a valid 2-letter country code.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_country' => 'P'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test niepoprawnego kodu kraju (zbyt długi)
     */
    public function testConsignorAddressInvalidCountryCodeTooLong(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' exceeds maximum length of 2 characters.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_country' => 'POL'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test dla kodu kraju o poprawnej długości ale błędnego formatu
     */
    public function testConsignorAddressInvalidCountryCodeFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_country' must be a valid 2-letter country code.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_country' => '1' // Tylko jedna cyfra
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test nieobsługiwanego kodu kraju
     */
    public function testConsignorAddressUnsupportedCountryCode(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'sender_country' country code 'XX' is not supported for TEST_SERVICE service.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_country' => 'XX'
        ];

        $this->validate->consignorAddress($consignor, $this->validServiceInfo);
    }

    /**
     * Test konwersji kodu kraju na wielkie litery
     */
    public function testConsignorAddressCountryCodeConversion(): void
    {
        $consignor = [
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_country' => 'pl' // małe litery
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

        $this->assertEquals('PL', $result['Country']);
    }

    /**
     * Test z domyślnymi limitami gdy brakuje fieldLimits w serviceInfo
     */
    public function testConsignorAddressWithDefaultLimits(): void
    {
        $serviceInfoWithoutLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE'
                ]
            ]
        ];

        $consignor = [
            'sender_fullname' => 'Jan Kowalski',
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $result = $this->validate->consignorAddress($consignor, $serviceInfoWithoutLimits);

        $this->assertArrayHasKey('FullName', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
    }

    /**
     * Test z limitami zdefiniowanymi w serviceInfo
     */
    public function testConsignorAddressWithServiceLimits(): void
    {
        $serviceInfoWithCustomLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE',
                    'fieldLimits' => [
                        'FullName' => 30, // Niższy limit niż domyślny
                        'SupportedCountries' => ['PL', 'US']
                    ]
                ]
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'sender_fullname' exceeds maximum length of 30 characters.");
        $this->expectExceptionCode(400);

        $consignor = [
            'sender_fullname' => str_repeat('a', 31), // 31 znaków, przekracza limit 30
            'sender_address' => 'ul. Testowa 123',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001'
        ];

        $this->validate->consignorAddress($consignor, $serviceInfoWithCustomLimits);
    }

    /**
     * Test z białymi znakami w polach - sprawdzenie trim()
     */
    public function testConsignorAddressWithWhitespace(): void
    {
        $consignor = [
            'sender_fullname' => '  Jan Kowalski  ',
            'sender_address' => '  ul. Testowa 123  ',
            'sender_city' => '  Warszawa  ',
            'sender_postalcode' => '  00-001  '
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

        $this->assertEquals('Jan Kowalski', $result['FullName']);
        $this->assertEquals('ul. Testowa 123', $result['AddressLine1']);
        $this->assertEquals('Warszawa', $result['City']);
        $this->assertEquals('00-001', $result['Zip']);
    }

    /**
     * Test z pustymi opcjonalnymi polami - nie powinny być dodane do wyniku
     */
    public function testConsignorAddressEmptyOptionalFields(): void
    {
        $consignor = [
            'sender_fullname' => '',
            'sender_company' => '',
            'sender_address' => 'ul. Testowa 123',
            'sender_address2' => '',
            'sender_city' => 'Warszawa',
            'sender_postalcode' => '00-001',
            'sender_email' => '',
            'sender_phone' => ''
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

        // Sprawdzamy, że puste opcjonalne pola nie są w wyniku
        $this->assertArrayNotHasKey('FullName', $result);
        $this->assertArrayNotHasKey('Company', $result);
        $this->assertArrayNotHasKey('AddressLine2', $result);
        $this->assertArrayNotHasKey('Email', $result);
        $this->assertArrayNotHasKey('Phone', $result);

        // Sprawdzamy, że wymagane pola są w wyniku
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
    }

    /**
     * Test graniczny - maksymalna długość pól
     */
    public function testConsignorAddressMaxLengthFields(): void
    {
        $consignor = [
            'sender_fullname' => str_repeat('a', 50), // Dokładnie 50 znaków
            'sender_company' => str_repeat('b', 60), // Dokładnie 60 znaków
            'sender_address' => str_repeat('c', 50), // Dokładnie 50 znaków
            'sender_city' => str_repeat('d', 50), // Dokładnie 50 znaków
            'sender_postalcode' => str_repeat('1', 20), // Dokładnie 20 znaków
            'sender_phone' => str_repeat('1', 15) // Dokładnie 15 znaków
        ];

        $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);

        $this->assertArrayHasKey('FullName', $result);
        $this->assertArrayHasKey('Company', $result);
        $this->assertArrayHasKey('AddressLine1', $result);
        $this->assertArrayHasKey('City', $result);
        $this->assertArrayHasKey('Zip', $result);
        $this->assertArrayHasKey('Phone', $result);
    }

    /**
     * Test z prawidłowymi emailami granicznymi
     */
    public function testConsignorAddressValidEmailFormats(): void
    {
        $validEmails = [
            'test@example.com',
            'user.name@domain.co.uk', 
            'test+tag@example.org',
            'simple@test.io'
        ];

        foreach ($validEmails as $email) {
            $consignor = [
                'sender_address' => 'ul. Testowa 123',
                'sender_city' => 'Warszawa',
                'sender_postalcode' => '00-001',
                'sender_email' => $email
            ];

            $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);
            $this->assertEquals($email, $result['Email']);
        }
    }

    /**
     * Test z prawidłowymi telefonami granicznymi
     */
    public function testConsignorAddressValidPhoneFormats(): void
    {
        $validPhones = [
            '123456789',
            '48123456789',
            '1', // Minimum 1 cyfra
            str_repeat('1', 15) // Maksymalnie 15 cyfr
        ];

        foreach ($validPhones as $phone) {
            $consignor = [
                'sender_address' => 'ul. Testowa 123',
                'sender_city' => 'Warszawa',
                'sender_postalcode' => '00-001',
                'sender_phone' => $phone
            ];

            $result = $this->validate->consignorAddress($consignor, $this->validServiceInfo);
            $this->assertEquals($phone, $result['Phone']);
        }
    }
}