<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker\Tests;

use Baselinker\Samplebroker\Validate;
use PHPUnit\Framework\TestCase;

class ValidateShipmentTest extends TestCase
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
                    'fieldLimits' => [
                        'SupportedCountries' => ['PL', 'DE', 'FR', 'GB', 'US']
                    ]
                ]
            ]
        ];
    }

    /**
     * Test podstawowych danych przesyłki
     */
    public function testShipmentWithMinimalValidData(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertIsArray($result);
        $this->assertEquals('REF123', $result['ShipperReference']);
        $this->assertEquals(1.5, $result['Weight']);
        $this->assertEquals(100.0, $result['Value']);
    }

    /**
     * Test kompletnych danych przesyłki
     */
    public function testShipmentWithCompleteValidData(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'order_reference' => 'ORDER456',
            'order_date' => '2025-10-20',
            'display_id' => 'DISP123',
            'invoice_number' => 'INV789',
            'weight' => 2.5,
            'weight_unit' => 'kg',
            'length' => 30.0,
            'width' => 20.0,
            'height' => 15.0,
            'dim_unit' => 'cm',
            'value' => 150.0,
            'shipping_value' => 25.0,
            'currency' => 'EUR',
            'customs_duty' => 'DDP',
            'description' => 'Test shipment',
            'declaration_type' => 'Commercial',
            'dangerous_goods' => 'N',
            'export_carriername' => 'Test Carrier',
            'export_awb' => 'AWB123456',
            'ni_vat' => 'XI123456789',
            'eu_eori' => 'PL1234567890',
            'ioss' => 'IMPL123456789012',
            'label_format' => 'PDF'
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertIsArray($result);
        $this->assertCount(24, $result);
        $this->assertEquals('REF123', $result['ShipperReference']);
        $this->assertEquals('kg', $result['WeightUnit']);
        $this->assertEquals('cm', $result['DimUnit']);
        $this->assertEquals('EUR', $result['Currency']);
        $this->assertEquals('DDP', $result['CustomsDuty']);
        $this->assertEquals('N', $result['DangerousGoods']);
        $this->assertEquals('PDF', $result['LabelFormat']);
    }

    /**
     * Test braku wymaganego pola shipper_reference
     */
    public function testShipmentMissingRequiredShipperReference(): void
    {
        $shipment = [
            'weight' => 1.5,
            'value' => 100.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'shipper_reference' cannot be empty.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test braku wymaganego pola weight
     */
    public function testShipmentMissingRequiredWeight(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'value' => 100.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'weight' cannot be empty.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test braku wymaganych pól value i shipping_value
     */
    public function testShipmentMissingBothValueFields(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("At least one of the shipment fields 'shipping_value' or 'value' must be provided.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowej daty
     */
    public function testShipmentInvalidOrderDate(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'order_date' => '2025-13-45'  // Nieprawidłowa data
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'order_date' must be in 'YYYY-MM-DD' format.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowej jednostki wagi
     */
    public function testShipmentInvalidWeightUnit(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'weight_unit' => 'invalid'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'weight_unit' exceeds maximum length of 2 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowej jednostki wymiarów
     */
    public function testShipmentInvalidDimUnit(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 30.0,
            'width' => 20.0,
            'height' => 15.0,
            'dim_unit' => 'invalid'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'dim_unit' exceeds maximum length of 2 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowej waluty
     */
    public function testShipmentInvalidCurrency(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'currency' => 'INVALID'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'currency' exceeds maximum length of 3 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego customs_duty
     */
    public function testShipmentInvalidCustomsDuty(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'customs_duty' => 'INVALID'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'customs_duty' exceeds maximum length of 3 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego dangerous_goods
     */
    public function testShipmentInvalidDangerousGoods(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'dangerous_goods' => 'INVALID'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Field 'dangerous_goods' exceeds maximum length of 1 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego formatu etykiety
     */
    public function testShipmentInvalidLabelFormat(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'label_format' => 'INVALID'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'label_format' has invalid value 'INVALID'. Allowed values: PDF, PNG, ZPL300, ZPL600, ZPL200, ZPL, EPL");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego formatu NI VAT
     */
    public function testShipmentInvalidNiVatFormat(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'ni_vat' => 'INVALID123'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'ni_vat' must be in the format 'XI123456789'.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego formatu EU EORI
     */
    public function testShipmentInvalidEuEoriFormat(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'eu_eori' => '123INVALID'  // Nie zaczyna się od kodu kraju
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'eu_eori' must start with a 2-letter country code followed by alphanumeric characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test nieprawidłowego formatu IOSS
     */
    public function testShipmentInvalidIossFormat(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'ioss' => 'INVALID'
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'ioss' must be in the format 'IMXX123456789012' where XX is the 2-letter country code.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test niekompletnych wymiarów (tylko 1 wymiar)
     */
    public function testShipmentIncompleteDimensionsOne(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 30.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Three dimensions (length, width, height) must be provided together. Or none of them.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test niekompletnych wymiarów (tylko 2 wymiary)
     */
    public function testShipmentIncompleteDimensionsTwo(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 30.0,
            'width' => 20.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Three dimensions (length, width, height) must be provided together. Or none of them.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test przekroczenia maksymalnej długości
     */
    public function testShipmentExceedsMaxLength(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 150.0,  // Przekracza MAX_SHIPMENT_LENGTH (120)
            'width' => 20.0,
            'height' => 15.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment length '150 cm'  must be between 0.01 cm and 120 cm.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test przekroczenia maksymalnej szerokości
     */
    public function testShipmentExceedsMaxWidth(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 30.0,
            'width' => 80.0,  // Przekracza MAX_SHIPMENT_WIDTH (60)
            'height' => 15.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment width '80 cm'  must be between 0.01 cm and 60 cm.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test przekroczenia maksymalnej wysokości
     */
    public function testShipmentExceedsMaxHeight(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 30.0,
            'width' => 20.0,
            'height' => 80.0  // Przekracza MAX_SHIPMENT_HEIGHT (60)
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment height '80 cm'  must be between 0.01 cm and 60 cm.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test przekroczenia sumy wymiarów
     */
    public function testShipmentExceedsMaxDimensionSum(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 120.0,  // L + 2*(W+H) = 120 + 2*(50+50) = 320 > 300
            'width' => 50.0,
            'height' => 50.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sum of shipment dimensions [L + 2 * (W + H)] = '320 cm'  exceeds maximum allowed of 300 cm.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test konwersji wymiarów z cali na cm
     */
    public function testShipmentDimensionConversionFromInches(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'length' => 11.81,  // ~30cm
            'width' => 7.87,    // ~20cm
            'height' => 5.91,   // ~15cm
            'dim_unit' => 'in'
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('in', $result['DimUnit']);
        $this->assertEquals(11.81, $result['Length']);
    }

    /**
     * Test przekroczenia maksymalnej wagi
     */
    public function testShipmentExceedsMaxWeight(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 35.0,  // Przekracza MAX_SHIPMENT_WEIGHT (30)
            'value' => 100.0
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment weight '35 kg'  must be between 0.01 kg and 30 kg.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test konwersji wagi z funtów na kg
     */
    public function testShipmentWeightConversionFromPounds(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 22.05,  // ~10kg
            'weight_unit' => 'lb',
            'value' => 100.0
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('lb', $result['WeightUnit']);
        $this->assertEquals(22.05, $result['Weight']);
    }

    /**
     * Test z maksymalnymi granicznymi wartościami
     */
    public function testShipmentWithMaxBoundaryValues(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 30.0,     // MAX_SHIPMENT_WEIGHT
            'length' => 120.0,    // MAX_SHIPMENT_LENGTH
            'width' => 60.0,      // MAX_SHIPMENT_WIDTH
            'height' => 20.0,     // Height that keeps sum under 300
            'value' => 5000.0     // MAX_VALUE
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals(30.0, $result['Weight']);
        $this->assertEquals(120.0, $result['Length']);
        $this->assertEquals(5000.0, $result['Value']);
    }

    /**
     * Test konwersji wartości na wielkie litery
     */
    public function testShipmentValueConversionToUpperCase(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'currency' => 'eur',           // Małe litery
            'customs_duty' => 'ddp',       // Małe litery
            'dangerous_goods' => 'n',      // Małe litery
            'label_format' => 'pdf'        // Małe litery
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('EUR', $result['Currency']);
        $this->assertEquals('DDP', $result['CustomsDuty']);
        $this->assertEquals('N', $result['DangerousGoods']);
        $this->assertEquals('PDF', $result['LabelFormat']);
    }

    /**
     * Test za długiego display_id
     */
    public function testShipmentDisplayIdTooLong(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'display_id' => '1234567890123456'  // 16 znaków - powinno być za długie
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Shipment field 'display_id' exceeds maximum length of 15 characters.");
        
        $this->validate->shipment($shipment, $this->validServiceInfo);
    }

    /**
     * Test prawidłowego display_id
     */
    public function testShipmentValidDisplayId(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'display_id' => '123456789012345'  // Dokładnie 15 znaków
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('123456789012345', $result['DisplayId']);
    }
    public function testShipmentWithEmptyOptionalFields(): void
    {
        $shipment = [
            'shipper_reference' => 'REF123',
            'weight' => 1.5,
            'value' => 100.0,
            'order_reference' => '',
            'description' => '',
            'invoice_number' => ''
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('REF123', $result['ShipperReference']);
        $this->assertEquals(1.5, $result['Weight']);
        $this->assertEquals(100.0, $result['Value']);
        // Puste pola nie powinny być w wyniku
        $this->assertArrayNotHasKey('OrderReference', $result);
        $this->assertArrayNotHasKey('Description', $result);
        $this->assertArrayNotHasKey('InvoiceNumber', $result);
    }

    /**
     * Test z białymi znakami w polach
     */
    public function testShipmentWithWhitespace(): void
    {
        $shipment = [
            'shipper_reference' => '  REF123  ',
            'weight' => 1.5,
            'value' => 100.0,
            'description' => '  Test description  '
        ];

        $result = $this->validate->shipment($shipment, $this->validServiceInfo);
        
        $this->assertEquals('REF123', $result['ShipperReference']);
        $this->assertEquals('Test description', $result['Description']);
    }
}