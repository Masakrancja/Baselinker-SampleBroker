<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

abstract class Validate
{
    protected const MAX_PRODUCT_COUNT = 50;

    protected const MAX_SHIPMENT_WEIGHT = 30.0; // in kg

    protected const MAX_VALUE = 5000.0; // in EUR

    protected const MAX_SHIPMENT_LENGTH = 120.0; // in cm

    protected const MAX_SHIPMENT_WIDTH = 60.0; // in cm

    protected const MAX_SHIPMENT_HEIGHT = 60.0; // in cm

    protected const MAX_SHIPMENT_DIMENSION = 300.0; // in cm (sum of length + 2 * (width + height))

    protected const LB_TO_KG = 0.453592;

    protected const INCH_TO_CM = 2.54;

     const PRODUCT_DESC_MAX_LENGTH = 105;

    protected const ALLOWED_LABEL_FORMATS = [
        'PDF', 'PNG', 'ZPL300', 'ZPL600', 'ZPL200', 'ZPL', 'EPL'
    ];

    protected const ALLOWED_WEIGHT_UNITS = [
        'kg', 'lb'
    ];

    protected const DEFAULT_WEIGHT_UNIT = 'kg';

    protected const ALLOWED_DIM_UNITS = [
        'cm', 'in',
    ];

    protected const DEFAULT_DIM_UNIT = 'cm';

    protected const CURRENCIES = [
        'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
        'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
        'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY',
        'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD',
        'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS',
        'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF',
        'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD',
        'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT',
        'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD',
        'MMK', 'MNT', 'MOP', 'MRU', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN',
        'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK',
        'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR',
        'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL', 'SRD',
        'STN', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY',
        'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VES',
        'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR',
        'ZMW', 'ZWL'
    ];

    protected const CUSTOM_DUTY_TYPES = [
        'DDP', 'DDU'
    ];

    protected const DANGEROUS_GOODS_TYPES = [
        'Y', 'N'
    ];

    protected string $dimUnit = self::DEFAULT_DIM_UNIT;

    protected string $weightUnit = self::DEFAULT_WEIGHT_UNIT;

    protected function validateCountryCode(
        string $countryCode,
        array $availableCountries,
        string $service,
        string $fieldName
    ): string {
        $countryCode = strtoupper($countryCode);
        if (!in_array($countryCode, $availableCountries, true)) {
            throw new \InvalidArgumentException(
                "Shipment field '{$fieldName}' country code '{$countryCode} . 
                ' is not supported for {$service} service.",
                400
            );
        }
        return $countryCode;
    }

}