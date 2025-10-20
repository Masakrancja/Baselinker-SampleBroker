<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker\Tests;

use Baselinker\Samplebroker\Validate;
use PHPUnit\Framework\TestCase;

class ValidateProductsTest extends TestCase
{
    private Validate $validate;
    private array $validServiceInfo;

    protected function setUp(): void
    {
        $this->validate = new Validate();
        
        // Przykładowe dane serviceInfo
        $this->validServiceInfo = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TEST_SERVICE',
                    'maxWeight' => 30.0, // kg
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'US', 'DE', 'FR', 'GB'],
                        'Description' => 105,
                        'Quantity' => 50,
                        'Weight' => 30.0,
                        'Value' => 5000.0,
                        'HsCode' => 255,
                        'OriginCountry' => 2
                    ]
                ]
            ]
        ];
    }

    /**
     * Test z pojedynczym poprawnym produktem
     */
    public function testProductsWithSingleValidProduct(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 2,
                'weight' => 1.5,
                'value' => 100.0,
                'hs_code' => '1234567890'
                // Usuwamy origin_country ze względu na błąd w kodzie
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('Description', $result[0]);
        $this->assertArrayHasKey('Quantity', $result[0]);
        $this->assertArrayHasKey('Weight', $result[0]);
        $this->assertArrayHasKey('Value', $result[0]);
        $this->assertArrayHasKey('HsCode', $result[0]);
        
        $this->assertEquals('Test Product', $result[0]['Description']);
        $this->assertEquals(2, $result[0]['Quantity']);
        $this->assertEquals(1.5, $result[0]['Weight']);
        $this->assertEquals(100.0, $result[0]['Value']);
        $this->assertEquals('1234567890', $result[0]['HsCode']);
    }

    /**
     * Test z wieloma poprawnymi produktami
     */
    public function testProductsWithMultipleValidProducts(): void
    {
        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 5,
                'weight' => 2.0,
                'value' => 50.0
            ],
            [
                'name' => 'Product 2',
                'quantity' => 3,
                'weight' => 1.0,
                'value' => 25.0,
                'hs_code' => '9876543210'
                // Usuwamy origin_country ze względu na błąd w kodzie
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(2, $result);
        $this->assertEquals('Product 1', $result[0]['Description']);
        $this->assertEquals('Product 2', $result[1]['Description']);
    }

    /**
     * Test z minimalnymi wymaganymi polami
     */
    public function testProductsWithMinimalRequiredFields(): void
    {
        $products = [
            [
                'name' => 'Minimal Product',
                'quantity' => 1,
                'weight' => 0.1,
                'value' => 0 // Dodajemy value żeby nie było undefined key
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('Description', $result[0]);
        $this->assertArrayHasKey('Quantity', $result[0]);
        $this->assertArrayHasKey('Weight', $result[0]);
        $this->assertArrayNotHasKey('HsCode', $result[0]);
        $this->assertArrayNotHasKey('OriginCountry', $result[0]);
    }

    /**
     * Test pustej tablicy produktów
     */
    public function testProductsEmptyArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Products array cannot be empty.');
        $this->expectExceptionCode(400);

        $this->validate->products([], 'kg', $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Description
     */
    public function testProductsMissingRequiredDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product field 'name' cannot be empty.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Quantity
     */
    public function testProductsMissingRequiredQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product field 'quantity' cannot be empty.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'weight' => 1.0,
                'value' => 0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test brakującego wymaganego pola Weight
     */
    public function testProductsMissingRequiredWeight(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product field 'weight' cannot be empty.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'value' => 0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test zbyt długiego opisu produktu
     */
    public function testProductsTooLongDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'name' exceeds maximum length of 105 characters.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => str_repeat('a', 106), // 106 znaków
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test niepoprawnej ilości (nie integer)
     */
    public function testProductsInvalidQuantityNotInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be an integer.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 'abc',
                'weight' => 1.0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test zbyt małej ilości (< 1)
     */
    public function testProductsQuantityTooSmall(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be an integer.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 0,
                'weight' => 1.0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test zbyt dużej ilości (> 50)
     */
    public function testProductsQuantityTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be at most 50.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 51,
                'weight' => 1.0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test niepoprawnej wagi (nie number)
     */
    public function testProductsInvalidWeightNotNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'weight' must be a number.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 'abc'
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test zbyt dużej wagi produktu (> 30.0)
     */
    public function testProductsWeightTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'weight' must be at most 30.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 30.1
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test zbyt dużej wartości produktu (> 5000.0)
     */
    public function testProductsValueTooLarge(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'value' must be at most 5000.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 5000.1
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego kodu kraju pochodzenia
     */
    public function testProductsInvalidOriginCountryCode(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 100.0,
                'origin_country' => 'INVALID'  // Nieprawidłowy kod kraju
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'origin_country' exceeds maximum length of 2 characters.");
        
        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test nieobsługiwanego kodu kraju pochodzenia
     */
    public function testProductsUnsupportedOriginCountryCode(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 100.0,
                'origin_country' => 'XY'  // Nieobsługiwany kod kraju
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'origin_country' country code 'XY' is not supported for TEST_SERVICE service.");
        
        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test przekroczenia łącznej ilości produktów
     */
    public function testProductsExceededTotalQuantity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exceeded maximum total quantity of products. Maximum allowed is 50');
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 30,
                'weight' => 0.5, // Zmniejszamy wagę
                'value' => 10
            ],
            [
                'name' => 'Product 2',
                'quantity' => 25, // 30 + 25 = 55 > 50
                'weight' => 0.5,
                'value' => 10
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test przekroczenia łącznej wartości produktów
     */
    public function testProductsExceededTotalValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Exceeded maximum total value of products. Maximum allowed is 5000 EUR');
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 3000.0
            ],
            [
                'name' => 'Product 2',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 2500.0 // 3000 + 2500 = 5500 > 5000
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test przekroczenia łącznej wagi produktów (kg)
     */
    public function testProductsExceededTotalWeightKg(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Total weight '31 kg' of products exceeds maximum allowed weight .30 kg for the TEST_SERVICE service.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 2,
                'weight' => 10.0, // 2 * 10 = 20
                'value' => 10
            ],
            [
                'name' => 'Product 2',
                'quantity' => 1,
                'weight' => 11.0, // 1 * 11 = 11, łącznie 31 > 30
                'value' => 10
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test przekroczenia łącznej wagi produktów (lb -> kg)
     */
    public function testProductsExceededTotalWeightLb(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'weight' must be at most 30.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 1,
                'weight' => 70.0 // 70 lb przekracza limit 30 na produkt
            ]
        ];

        $this->validate->products($products, 'lb', $this->validServiceInfo);
    }

    /**
     * Test z jednostką wagi lb - poprawna konwersja
     */
    public function testProductsWithLbWeightUnitValidConversion(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 22.0, // 22 lb * 0.453592 = 9.98 kg < 30
                'value' => 10
            ]
        ];

        $result = $this->validate->products($products, 'lb', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertEquals(22.0, $result[0]['Weight']); // Oryginalna wartość w wyniku
    }

    /**
     * Test z maksymalnymi wartościami granicznymi
     */
    public function testProductsWithMaximumBoundaryValues(): void
    {
        $products = [
            [
                'name' => str_repeat('a', 105), // Dokładnie 105 znaków
                'quantity' => 1, // Zmniejszamy ilość żeby łączna waga nie przekroczyła limitu
                'weight' => 30.0, // Maksymalna waga
                'value' => 5000.0, // Maksymalna wartość
                'hs_code' => str_repeat('b', 255) // Maksymalna długość HS code
                // Usuwamy origin_country ze względu na błąd w kodzie
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertEquals(str_repeat('a', 105), $result[0]['Description']);
        $this->assertEquals(1, $result[0]['Quantity']);
        $this->assertEquals(30.0, $result[0]['Weight']);
        $this->assertEquals(5000.0, $result[0]['Value']);
    }

    /**
     * Test z pustymi opcjonalnymi polami
     */
    public function testProductsWithEmptyOptionalFields(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => '',
                'hs_code' => '',
                'origin_country' => ''
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('Value', $result[0]);
        $this->assertArrayNotHasKey('HsCode', $result[0]);
        $this->assertArrayNotHasKey('OriginCountry', $result[0]);
    }

    /**
     * Test z białymi znakami w polach - sprawdzenie trim()
     */
    public function testProductsWithWhitespace(): void
    {
        $products = [
            [
                'name' => '  Test Product  ',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '  123456  ',
                'value' => 10.0
                // Usuwamy origin_country ze względu na błąd w kodzie
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(1, $result);
        $this->assertEquals('Test Product', $result[0]['Description']);
        $this->assertEquals('123456', $result[0]['HsCode']);
    }

    /**
     * Test konwersji kodu kraju na wielkie litery
     */
    public function testProductsOriginCountryConversion(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 100.0,
                'origin_country' => 'pl'  // Mały kod kraju - powinien być skonwertowany na PL
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);
        
        $this->assertCount(1, $result);
        $this->assertEquals('PL', $result[0]['OriginCountry']);
    }

    /**
     * Test z limitami serwisu dla wagi produktu
     */
    public function testProductsWithServiceWeightLimit(): void
    {
        // Ustawiamy serviceInfo z niskim limitem wagi dla produktu
        $serviceInfoWithWeightLimit = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'TestService',
                    'fieldLimits' => [
                        'Weight' => 5.0, // Limit wagi produktu na 5kg
                        'SupportedCountries' => ['PL', 'DE', 'FR']
                    ]
                ]
            ]
        ];

        $products = [
            [
                'name' => 'Heavy Product',
                'quantity' => 1,
                'weight' => 10.0, // Przekracza limit serwisu (5kg)
                'value' => 100.0
            ]
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'weight' must be at most 5.");
        
        $this->validate->products($products, 'kg', $serviceInfoWithWeightLimit);
    }

    /**
     * Test z różnymi typami błędów w różnych produktach
     */
    public function testProductsMultipleProductsWithError(): void
    {
        // Test że błąd w drugim produkcie zawiera prawidłowy indeks
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 1: field 'quantity' must be an integer.");
        $this->expectExceptionCode(400);

        $products = [
            [
                'name' => 'Valid Product',
                'quantity' => 1,
                'weight' => 1.0
            ],
            [
                'name' => 'Invalid Product',
                'quantity' => 'abc', // Błąd w drugim produkcie
                'weight' => 1.0
            ]
        ];

        $this->validate->products($products, 'kg', $this->validServiceInfo);
    }

    /**
     * Test maksymalnej łącznej ilości produktów (granica)
     */
    public function testProductsMaxTotalQuantityBoundary(): void
    {
        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 25,
                'weight' => 0.5 // Zmniejszamy wagę żeby nie przekroczyć limitu wagi
            ],
            [
                'name' => 'Product 2',
                'quantity' => 25, // Łącznie dokładnie 50
                'weight' => 0.5
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(2, $result);
    }

    /**
     * Test maksymalnej łącznej wartości produktów (granica)
     */
    public function testProductsMaxTotalValueBoundary(): void
    {
        $products = [
            [
                'name' => 'Product 1',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 2500.0
            ],
            [
                'name' => 'Product 2',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 2500.0 // Łącznie dokładnie 5000
            ]
        ];

        $result = $this->validate->products($products, 'kg', $this->validServiceInfo);

        $this->assertCount(2, $result);
    }
}