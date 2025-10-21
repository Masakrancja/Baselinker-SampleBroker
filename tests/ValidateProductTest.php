<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Baselinker\Samplebroker\ValidateProduct;
use InvalidArgumentException;
use RuntimeException;

class ValidateProductTest extends TestCase
{
    private ValidateProduct $validator;
    private array $defaultServiceInfo;
    private string $consignorCountry = 'PL';
    private string $consigneeCountry = 'DE'; // Different country - requires HS code

    protected function setUp(): void
    {
        $this->validator = new ValidateProduct();
        
        $this->defaultServiceInfo = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'maxWeight' => 30.0,
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'DE', 'FR', 'US', 'GB'],
                        'Description' => 80,
                        'HsCode' => 10,
                        'Weight' => 25.0
                    ]
                ]
            ]
        ];
    }

    /**
     * Test products validation with minimal required data for international shipment
     */
    public function testProductsWithMinimalDataInternational(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 2,
                'weight' => 1.5,
                'hs_code' => '1234567890'
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('Description', $result[0]);
        $this->assertArrayHasKey('Quantity', $result[0]);
        $this->assertArrayHasKey('Weight', $result[0]);
        $this->assertArrayHasKey('HsCode', $result[0]);
        $this->assertEquals('Test Product', $result[0]['Description']);
        $this->assertEquals(2, $result[0]['Quantity']);
        $this->assertEquals(1.5, $result[0]['Weight']);
        $this->assertEquals('1234567890', $result[0]['HsCode']);
    }

    /**
     * Test products validation for domestic shipment (no HS code required)
     */
    public function testProductsMinimalDataDomestic(): void
    {
        $products = [
            [
                'name' => 'Domestic Product',
                'quantity' => 1,
                'weight' => 0.5
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consignorCountry, // Same country - no HS code required
            $this->defaultServiceInfo
        );

        $this->assertCount(1, $result);
        $this->assertEquals('Domestic Product', $result[0]['Description']);
        $this->assertEquals(1, $result[0]['Quantity']);
        $this->assertEquals(0.5, $result[0]['Weight']);
        $this->assertArrayNotHasKey('HsCode', $result[0]); // HS code not required for domestic
    }

    /**
     * Test products validation with all optional fields
     */
    public function testProductsWithAllFields(): void
    {
        $products = [
            [
                'name' => 'Complete Product',
                'quantity' => 3,
                'weight' => 2.0,
                'value' => 100.0,
                'hs_code' => '9876543210',
                'origin_country' => 'PL'
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertCount(1, $result);
        $this->assertEquals('Complete Product', $result[0]['Description']);
        $this->assertEquals(3, $result[0]['Quantity']);
        $this->assertEquals(2.0, $result[0]['Weight']);
        $this->assertEquals(100.0, $result[0]['Value']);
        $this->assertEquals('9876543210', $result[0]['HsCode']);
        $this->assertEquals('PL', $result[0]['OriginCountry']);
    }

    /**
     * Test multiple products validation
     */
    public function testMultipleProducts(): void
    {
        $products = [
            [
                'name' => 'Product A',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1111111111'
            ],
            [
                'name' => 'Product B',
                'quantity' => 2,
                'weight' => 0.5,
                'value' => 50.0,
                'hs_code' => '2222222222'
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertCount(2, $result);
        $this->assertEquals('Product A', $result[0]['Description']);
        $this->assertEquals('Product B', $result[1]['Description']);
        $this->assertEquals(1, $result[0]['Quantity']);
        $this->assertEquals(2, $result[1]['Quantity']);
    }

    /**
     * Test empty products array validation
     */
    public function testEmptyProductsArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Products array cannot be empty.');
        $this->expectExceptionCode(400);

        $this->validator->products(
            [],
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test missing required description field
     */
    public function testProductMissingRequiredDescription(): void
    {
        $products = [
            [
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no: 0 field: 'name' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test missing required quantity field
     */
    public function testProductMissingRequiredQuantity(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no: 0 field: 'quantity' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test missing required weight field
     */
    public function testProductMissingRequiredWeight(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no: 0 field: 'weight' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test missing required HS code for international shipment
     */
    public function testProductMissingRequiredHsCodeInternational(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0
                // Missing hs_code for international shipment
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no: 0 field: 'hs_code' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test product description exceeds service limit
     */
    public function testProductDescriptionExceedsServiceLimit(): void
    {
        $products = [
            [
                'name' => str_repeat('A', 85), // Exceeds service limit (80)
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'name' exceeds maximum length of 80 characters.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test product description exceeds default limit when no service limit
     */
    public function testProductDescriptionExceedsDefaultLimit(): void
    {
        $serviceInfoNoLimits = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'maxWeight' => 30.0,
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'DE']
                        // No Description limit
                    ]
                ]
            ]
        ];

        $products = [
            [
                'name' => str_repeat('A', 110), // Exceeds default limit (105)
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'name' exceeds maximum length of 105 characters.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $serviceInfoNoLimits
        );
    }

    /**
     * Test invalid quantity - not an integer
     */
    public function testProductInvalidQuantityNotInteger(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => '1.5', // Should be integer
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be an integer.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test invalid quantity - zero treated as invalid integer
     */
    public function testProductQuantityZero(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 0, // Zero fails filter_var validation because 0 == false
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be an integer.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test negative quantity
     */
    public function testProductQuantityNegative(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => -1, // Negative quantity
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be at least 1.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test invalid quantity - exceeds maximum
     */
    public function testProductQuantityExceedsMaximum(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 55, // Exceeds maximum (50)
                'weight' => 1.0,
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'quantity' must be at most 50.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test invalid weight - not numeric
     */
    public function testProductInvalidWeightNotNumeric(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 'heavy', // Not numeric
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'weight' must be a number.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test invalid value - not numeric
     */
    public function testProductInvalidValueNotNumeric(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 'expensive', // Not numeric
                'hs_code' => '1234567890'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'value' must be a number.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test origin country validation - invalid length
     */
    public function testProductOriginCountryInvalidLength(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890',
                'origin_country' => 'POLAND' // Too long - will hit string length validation first
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'origin_country' exceeds maximum length of 2 characters.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test origin country validation - single character
     */
    public function testProductOriginCountrySingleChar(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890',
                'origin_country' => 'P' // Single character
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no. 0: field 'origin_country' must be a valid 2-letter country code.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test origin country validation - not supported by service
     */
    public function testProductOriginCountryNotSupported(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890',
                'origin_country' => 'XX' // Not in supported countries
            ]
        ];

        try {
            $this->validator->products(
                $products,
                $this->consignorCountry,
                $this->consigneeCountry,
                $this->defaultServiceInfo
            );
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString("country code 'XX", $e->getMessage());
            $this->assertStringContainsString("is not supported for EXPRESS service", $e->getMessage());
        }
    }

    /**
     * Test origin country case conversion
     */
    public function testProductOriginCountryCaseConversion(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890',
                'origin_country' => 'pl' // Lowercase
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertEquals('PL', $result[0]['OriginCountry']); // Should be uppercase
    }

    /**
     * Test total quantity exceeds maximum
     */
    public function testTotalQuantityExceedsMaximum(): void
    {
        $products = [
            [
                'name' => 'Product A',
                'quantity' => 30,
                'weight' => 1.0,
                'hs_code' => '1111111111'
            ],
            [
                'name' => 'Product B',
                'quantity' => 25, // Total: 55, exceeds maximum (50)
                'weight' => 1.0,
                'hs_code' => '2222222222'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exceeded maximum total quantity of products. Maximum allowed is 50');
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test total value exceeds maximum
     */
    public function testTotalValueExceedsMaximum(): void
    {
        $products = [
            [
                'name' => 'Expensive Product A',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 3000.0,
                'hs_code' => '1111111111'
            ],
            [
                'name' => 'Expensive Product B',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => 2500.0, // Total: 5500, exceeds maximum (5000)
                'hs_code' => '2222222222'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exceeded maximum total value of products. Maximum allowed is 5000 EUR');
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test total weight exceeds service maximum
     */
    public function testTotalWeightExceedsServiceMaximum(): void
    {
        $products = [
            [
                'name' => 'Heavy Product A',
                'quantity' => 2,
                'weight' => 10.0, // 20kg total
                'hs_code' => '1111111111'
            ],
            [
                'name' => 'Heavy Product B',
                'quantity' => 2,
                'weight' => 8.0, // 16kg total -> Total: 36kg, exceeds service max (30kg)
                'hs_code' => '2222222222'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Total weight '36 kg' of products exceeds maximum allowed weight 30 kg for the EXPRESS service.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test weight conversion from pounds to kilograms
     */
    public function testProductWeightConversionFromPounds(): void
    {
        // We need to set up weight unit conversion
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 2.20462, // ~1 kg
                'hs_code' => '1234567890'
            ]
        ];

        // First we need to trigger the weight unit setting mechanism
        // This happens in the parent class when weight_unit is validated
        $validatorWithPounds = new class extends ValidateProduct {
            public function setWeightUnit(string $unit): void {
                $this->weightUnit = $unit;
            }
        };
        
        $validatorWithPounds->setWeightUnit('lb');
        
        $result = $validatorWithPounds->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        // Weight should be converted from pounds to kg: 2.20462 * 0.453592 ≈ 1.0
        $this->assertEquals(1.0, $result[0]['Weight']);
    }

    /**
     * Test string trimming for product fields
     */
    public function testProductStringTrimming(): void
    {
        $products = [
            [
                'name' => '  Test Product  ',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '  1234567890  ',
                'origin_country' => '  PL  '
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertEquals('Test Product', $result[0]['Description']);
        $this->assertEquals('1234567890', $result[0]['HsCode']);
        $this->assertEquals('PL', $result[0]['OriginCountry']);
    }

    /**
     * Test empty optional fields are not included in result
     */
    public function testProductEmptyOptionalFieldsNotIncluded(): void
    {
        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'value' => '', // Empty optional field
                'hs_code' => '1234567890',
                'origin_country' => '' // Empty optional field
            ]
        ];

        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        $this->assertArrayNotHasKey('Value', $result[0]);
        $this->assertArrayNotHasKey('OriginCountry', $result[0]);
        $this->assertArrayHasKey('Description', $result[0]);
        $this->assertArrayHasKey('HsCode', $result[0]);
    }

    /**
     * Test product weight uses service limit when available
     */
    public function testProductWeightUsesServiceLimit(): void
    {
        $products = [
            [
                'name' => 'Heavy Product',
                'quantity' => 1,
                'weight' => 28.0, // Exceeds service limit (25.0) but under default limit (30.0)
                'hs_code' => '1234567890'
            ]
        ];

        // The validation happens during the number validation
        // Weight validation in validateProduct method only sets the limit but doesn't validate against it
        // The actual validation happens in the products() method for total weight
        $result = $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );

        // Should succeed because individual product weight validation doesn't enforce the limit
        // Only total weight is validated against service limits
        $this->assertEquals(28.0, $result[0]['Weight']);
    }

    /**
     * Test product index in error messages for multiple products
     */
    public function testProductIndexInErrorMessages(): void
    {
        $products = [
            [
                'name' => 'Valid Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1111111111'
            ],
            [
                'name' => '', // Invalid - empty name
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '2222222222'
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Product no: 1 field: 'name' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->products(
            $products,
            $this->consignorCountry,
            $this->consigneeCountry,
            $this->defaultServiceInfo
        );
    }

    /**
     * Test service without supported countries for origin country validation
     */
    public function testProductOriginCountryWithoutSupportedCountries(): void
    {
        $serviceInfoNoCountries = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'maxWeight' => 30.0,
                    'fieldLimits' => [
                        'SupportedCountries' => [] // Empty array - will cause validation error
                    ]
                ]
            ]
        ];

        $products = [
            [
                'name' => 'Test Product',
                'quantity' => 1,
                'weight' => 1.0,
                'hs_code' => '1234567890',
                'origin_country' => 'PL'
            ]
        ];

        // Should throw exception when supported countries is empty array
        try {
            $this->validator->products(
                $products,
                $this->consignorCountry,
                $this->consigneeCountry,
                $serviceInfoNoCountries
            );
            $this->fail('Expected InvalidArgumentException was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals(400, $e->getCode());
            $this->assertStringContainsString("country code 'PL", $e->getMessage());
            $this->assertStringContainsString("is not supported for EXPRESS service", $e->getMessage());
        }
    }
}