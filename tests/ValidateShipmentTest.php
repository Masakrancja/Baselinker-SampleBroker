<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Baselinker\Samplebroker\ValidateShipment;
use InvalidArgumentException;
use RuntimeException;

class ValidateShipmentTest extends TestCase
{
    private ValidateShipment $validator;
    private array $defaultServiceInfo;
    private array $defaultShipmentData;

    protected function setUp(): void
    {
        $this->validator = new ValidateShipment();
        
        $this->defaultServiceInfo = [
            'response' => [
                'ServiceInfo' => [
                    'service' => 'EXPRESS',
                    'maxWeight' => 30.0,
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'DE', 'FR', 'US', 'GB'],
                        'ShipperReference' => 50,
                        'DisplayId' => 15
                    ]
                ]
            ]
        ];
        
        $this->defaultShipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'shipment_value' => 100.0
        ];
    }

    /**
     * Test successful validation with minimal required data
     */
    public function testValidateShipmentWithMinimalData(): void
    {
        $result = $this->validator->shipment($this->defaultShipmentData, $this->defaultServiceInfo);
        
        $this->assertArrayHasKey('ShipperReference', $result);
        $this->assertArrayHasKey('Weight', $result);
        $this->assertArrayHasKey('ShipmentValue', $result);
        $this->assertEquals('REF123456', $result['ShipperReference']);
        $this->assertEquals(5.0, $result['Weight']);
        $this->assertEquals(100.0, $result['ShipmentValue']);
    }

    /**
     * Test validation with all optional fields
     */
    public function testValidateShipmentWithAllFields(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'order_reference' => 'ORDER001',
            'order_date' => '2025-10-21',
            'display_id' => 'DISP001',
            'invoice_number' => 'INV001',
            'weight' => 5.0,
            'weight_unit' => 'kg',
            'length' => 30.0,
            'width' => 20.0,
            'height' => 15.0,
            'dim_unit' => 'cm',
            'value' => 150.0,
            'shipment_value' => 100.0,
            'currency' => 'EUR',
            'customs_duty' => 'DDP',
            'description' => 'Test package',
            'declaration_type' => 'GIFT',
            'dangerous_goods' => 'N',
            'export_carriername' => 'DHL',
            'export_awb' => 'AWB123',
            'ni_vat' => 'XI123456789',
            'eu_eori' => 'PL1234567890',
            'ioss' => 'IMPL123456789012',
            'label_format' => 'PDF'
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        $this->assertArrayHasKey('ShipperReference', $result);
        $this->assertArrayHasKey('OrderReference', $result);
        $this->assertArrayHasKey('OrderDate', $result);
        $this->assertArrayHasKey('Currency', $result);
        $this->assertEquals('EUR', $result['Currency']);
        $this->assertEquals('DDP', $result['CustomsDuty']);
        $this->assertEquals('N', $result['DangerousGoods']);
    }

    /**
     * Test required field validation - missing shipper_reference
     */
    public function testValidateShipmentMissingRequiredShipperReference(): void
    {
        $shipmentData = [
            'weight' => 5.0,
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'shipper_reference' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test required field validation - missing weight
     */
    public function testValidateShipmentMissingRequiredWeight(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'weight' cannot be empty.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test validation requires at least one value field
     */
    public function testValidateShipmentMissingValueFields(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("At least one of the shipment fields 'shipment_value' or 'value' must be provided.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test string field length validation
     */
    public function testValidateShipmentStringFieldTooLong(): void
    {
        $shipmentData = [
            'shipper_reference' => str_repeat('A', 256), // Too long (service limit is 50)
            'weight' => 5.0,
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'shipper_reference' exceeds maximum length of 50  characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test service limits override default limits
     */
    public function testValidateShipmentServiceLimitsOverride(): void
    {
        $shipmentData = [
            'shipper_reference' => str_repeat('A', 45), // Within service limit (50)
            'weight' => 5.0,
            'shipment_value' => 100.0
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        $this->assertEquals(str_repeat('A', 45), $result['ShipperReference']);
    }

    /**
     * Test numeric field validation - invalid number
     */
    public function testValidateShipmentInvalidNumericField(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 'not_a_number',
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'weight' must be a number.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test date format validation - invalid date
     */
    public function testValidateShipmentInvalidDateFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'order_date' => '2025-13-32', // Invalid date
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'order_date' must be in 'YYYY-MM-DD' format.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test date format validation - wrong format
     */
    public function testValidateShipmentWrongDateFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'order_date' => '21-10-2025', // Wrong format
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'order_date' must be in 'YYYY-MM-DD' format.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test weight unit validation - invalid unit
     */
    public function testValidateShipmentInvalidWeightUnit(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'weight_unit' => 'grams', // Invalid unit
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'weight_unit' exceeds maximum length of 2  characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test weight unit validation - valid short invalid unit
     */
    public function testValidateShipmentValidLengthButInvalidWeightUnit(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'weight_unit' => 'oz', // Valid length but invalid unit
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'weight_unit' has invalid value 'oz'. Allowed values: kg, lb");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test customs duty validation - valid length but invalid value
     */
    public function testValidateShipmentValidLengthButInvalidCustomsDuty(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'customs_duty' => 'ABC', // Valid length but invalid value
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'customs_duty' has invalid value 'ABC'. Allowed values: DDP, DDU");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dangerous goods validation - valid length but invalid value
     */
    public function testValidateShipmentValidLengthButInvalidDangerousGoods(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'dangerous_goods' => 'X', // Valid length but invalid value
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'dangerous_goods' has invalid value 'X'. Allowed values: Y, N");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension unit validation - invalid unit
     */
    public function testValidateShipmentInvalidDimUnit(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 30.0,
            'width' => 20.0,
            'height' => 15.0,
            'dim_unit' => 'mm', // Invalid unit
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'dim_unit' has invalid value 'mm'. Allowed values: cm, in");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test currency validation - invalid currency
     */
    public function testValidateShipmentInvalidCurrency(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'currency' => 'XXX', // Invalid currency
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'currency' has invalid value 'XXX'. Must be a valid ISO 4217 currency code.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test customs duty validation - invalid value
     */
    public function testValidateShipmentInvalidCustomsDuty(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'customs_duty' => 'INVALID', // Invalid value - too long for 3 char limit
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'customs_duty' exceeds maximum length of 3  characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dangerous goods validation - invalid value
     */
    public function testValidateShipmentInvalidDangerousGoods(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'dangerous_goods' => 'MAYBE', // Invalid value - too long for 1 char limit
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'dangerous_goods' exceeds maximum length of 1  characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test NI VAT format validation - invalid format
     */
    public function testValidateShipmentInvalidNIVatFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'ni_vat' => 'ABC123456789', // Invalid format (should start with XI)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'ni_vat' must be in the format 'XI123456789'.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test EU EORI format validation - invalid format
     */
    public function testValidateShipmentInvalidEuEoriFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'eu_eori' => '123PL456789', // Invalid format (should start with country code)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'eu_eori' must start with a 2-letter country code followed by alphanumeric characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test IOSS format validation - invalid format
     */
    public function testValidateShipmentInvalidIossFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'ioss' => 'ABCD123456789012', // Invalid format (should be IMXX...)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'ioss' must be in the format 'IMXX123456789012' where XX is the 2-letter country code.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test label format validation - invalid format
     */
    public function testValidateShipmentInvalidLabelFormat(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'label_format' => 'JPEG', // Invalid format
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'label_format' has invalid value 'JPEG'. Allowed values: PDF, PNG, ZPL300, ZPL600, ZPL200, ZPL, EPL");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension validation - incomplete dimensions (only length)
     */
    public function testValidateShipmentIncompleteDimensions(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 30.0, // Only length provided
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Three dimensions (length, width, height) must be provided together. Or none of them.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension validation - two dimensions provided
     */
    public function testValidateShipmentTwoDimensions(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 30.0,
            'width' => 20.0, // Only two dimensions
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Three dimensions (length, width, height) must be provided together. Or none of them.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension validation - length exceeds maximum
     */
    public function testValidateShipmentLengthExceedsMaximum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 150.0, // Exceeds MAX_SHIPMENT_LENGTH (120.0)
            'width' => 20.0,
            'height' => 15.0,
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment length '150 cm'  must be between 0.01 cm and 120 cm.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension validation - width exceeds maximum
     */
    public function testValidateShipmentWidthExceedsMaximum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 30.0,
            'width' => 70.0, // Exceeds MAX_SHIPMENT_WIDTH (60.0)
            'height' => 15.0,
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment width '70 cm'  must be between 0.01 cm and 60 cm.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension validation - height exceeds maximum
     */
    public function testValidateShipmentHeightExceedsMaximum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 30.0,
            'width' => 20.0,
            'height' => 70.0, // Exceeds MAX_SHIPMENT_HEIGHT (60.0)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment height '70 cm'  must be between 0.01 cm and 60 cm.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test dimension sum validation - exceeds maximum
     */
    public function testValidateShipmentDimensionSumExceedsMaximum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 100.0, // L + 2*(W+H) = 100 + 2*(50+50) = 300, but we'll make it 301
            'width' => 50.0,
            'height' => 50.5, // This makes the sum 301
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Sum of shipment dimensions [L + 2 * (W + H)] = '301 cm'  exceeds maximum allowed of 300 cm.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test weight conversion from lb to kg
     */
    public function testValidateShipmentWeightConversionFromPounds(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 11.023, // ~5 kg
            'weight_unit' => 'lb',
            'shipment_value' => 100.0
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        // 11.023 lb * 0.453592 = ~5.0 kg
        $this->assertEquals(5.0, $result['Weight']);
        $this->assertEquals('kg', $result['WeightUnit']);
    }

    /**
     * Test dimension conversion from inches to cm
     */
    public function testValidateShipmentDimensionConversionFromInches(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'length' => 11.81, // ~30 cm
            'width' => 7.87,   // ~20 cm  
            'height' => 5.91,  // ~15 cm
            'dim_unit' => 'in',
            'shipment_value' => 100.0
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        // Conversions: 11.81 * 2.54 = 30.0, 7.87 * 2.54 = 19.99, 5.91 * 2.54 = 15.0
        $this->assertEquals(30.0, $result['Length']);
        $this->assertEquals(19.99, $result['Width']); // Corrected expected value
        $this->assertEquals(15.01, $result['Height']); // 5.91 * 2.54 = 15.0114
        $this->assertEquals('cm', $result['DimUnit']);
    }

    /**
     * Test weight validation - exceeds service maximum
     */
    public function testValidateShipmentWeightExceedsServiceMaximum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 35.0, // Exceeds service max (30.0)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment weight '35 kg'  must be between 0.01 kg and 30 kg.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test weight validation - below minimum
     */
    public function testValidateShipmentWeightBelowMinimum(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 0.005, // Below minimum (0.01)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment weight '0.005 kg'  must be between 0.01 kg and 30 kg.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test display ID length validation with service limits
     */
    public function testValidateShipmentDisplayIdExceedsServiceLimit(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'display_id' => str_repeat('A', 16), // Exceeds service limit (15)
            'shipment_value' => 100.0
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'display_id' exceeds maximum length of 15  characters.");
        $this->expectExceptionCode(400);

        $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
    }

    /**
     * Test validation with valid value field only (no shipment_value)
     */
    public function testValidateShipmentWithValueFieldOnly(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'value' => 150.0 // Only value, no shipment_value
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        $this->assertArrayHasKey('Value', $result);
        $this->assertEquals(150.0, $result['Value']);
        $this->assertArrayNotHasKey('ShipmentValue', $result);
    }

    /**
     * Test case sensitivity handling for various fields
     */
    public function testValidateShipmentCaseSensitivityHandling(): void
    {
        $shipmentData = [
            'shipper_reference' => 'REF123456',
            'weight' => 5.0,
            'weight_unit' => 'KG', // Uppercase
            'currency' => 'eur',   // Lowercase
            'customs_duty' => 'ddp', // Lowercase
            'dangerous_goods' => 'n', // Lowercase
            'label_format' => 'pdf', // Lowercase
            'shipment_value' => 100.0
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        $this->assertEquals('kg', $result['WeightUnit']);
        $this->assertEquals('EUR', $result['Currency']);
        $this->assertEquals('DDP', $result['CustomsDuty']);
        $this->assertEquals('N', $result['DangerousGoods']);
        $this->assertEquals('PDF', $result['LabelFormat']);
    }

    /**
     * Test trimming of string values
     */
    public function testValidateShipmentStringTrimming(): void
    {
        $shipmentData = [
            'shipper_reference' => '  REF123456  ', // With spaces
            'weight' => 5.0,
            'description' => '  Test package  ',
            'shipment_value' => 100.0
        ];

        $result = $this->validator->shipment($shipmentData, $this->defaultServiceInfo);
        
        $this->assertEquals('REF123456', $result['ShipperReference']);
        $this->assertEquals('Test package', $result['Description']);
    }

    /**
     * Test apiKey validation - valid key
     */
    public function testValidateApiKeyValid(): void
    {
        $apiKey = 'valid-api-key-123';
        $result = $this->validator->apiKey($apiKey);
        
        $this->assertEquals($apiKey, $result);
    }

    /**
     * Test apiKey validation - empty key
     */
    public function testValidateApiKeyEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid API key provided.');
        $this->expectExceptionCode(400);

        $this->validator->apiKey('');
    }

    /**
     * Test service validation - valid service
     */
    public function testValidateServiceValid(): void
    {
        $services = [
            'response' => [
                'Services' => [
                    'AllowedServices' => ['EXPRESS', 'STANDARD', 'ECONOMY']
                ]
            ]
        ];

        $result = $this->validator->service('EXPRESS', $services);
        
        $this->assertEquals('EXPRESS', $result);
    }

    /**
     * Test service validation - case insensitive matching
     */
    public function testValidateServiceCaseInsensitive(): void
    {
        $services = [
            'response' => [
                'Services' => [
                    'AllowedServices' => ['EXPRESS', 'STANDARD', 'ECONOMY']
                ]
            ]
        ];

        $result = $this->validator->service('express', $services);
        
        $this->assertEquals('express', $result);
    }

    /**
     * Test service validation - invalid service
     */
    public function testValidateServiceInvalid(): void
    {
        $services = [
            'response' => [
                'Services' => [
                    'AllowedServices' => ['EXPRESS', 'STANDARD', 'ECONOMY']
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service. Allowed services: EXPRESS, STANDARD, ECONOMY');
        $this->expectExceptionCode(400);

        $this->validator->service('PREMIUM', $services);
    }

    /**
     * Test service validation - empty allowed services
     */
    public function testValidateServiceEmptyAllowedServices(): void
    {
        $services = [
            'response' => [
                'Services' => [
                    'AllowedServices' => []
                ]
            ]
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service. Allowed services: ');
        $this->expectExceptionCode(400);

        $this->validator->service('EXPRESS', $services);
    }

    /**
     * Test service validation - missing services structure
     */
    public function testValidateServiceMissingStructure(): void
    {
        $services = ['response' => []];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid service. Allowed services: ');
        $this->expectExceptionCode(400);

        $this->validator->service('EXPRESS', $services);
    }
}