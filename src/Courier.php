<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

class Courier
{

    private const ALLOWED_LABEL_FORMATS = ['PDF', 'PNG', 'ZPL'];
    private string $labelFormat;
    private string $service;
    private array $serviceInfo;


    public function __construct(private string $baseUrl)
    {
    }


    /**
     * Create a new package (shipment)
     *
     * @param array $order  Sender and delivery details
     * @param array $params Service parameters
     *
     * @return array Response with shipment details or error information
     * 
     * 
     * 
     */

    public function newPackage(array $order, array $params): array
    {
        try {
            $apiKey = $this->validateApiKey($params['api_key'] ?? null);
            $this->labelFormat = $this->validateLabelFormat($params['label_format'] ?? null);
            $this->service = $this->validateService($params['service'] ?? null, $apiKey);
            $this->serviceInfo = $this->getServiceInfo($apiKey);
            $consignor = $this->validateConsignorAddress(
                array_filter($order, fn($key) => str_starts_with($key, 'sender_'), ARRAY_FILTER_USE_KEY)
            );

            print_r($consignor);
            exit();

        } catch (\InvalidArgumentException $e) {
            return [
                'status' => 'ERROR',
                'error_code' => $e->getCode(),
                'error_message' => $e->getMessage(),
            ];
        }

        // Logic to create shipment
        return [];
    }

    public function packagePDF(): string
    {
        // Logic to get shipping label
        return '';
    }


    private function validateApiKey(?string $apiKey): string
    {
        if (empty($apiKey)) {
            throw new \InvalidArgumentException('API key cannot be empty.', 400);
        }
        return $apiKey;
    }

    private function validateLabelFormat(?string $labelFormat): string
    {
        if (!in_array(strtoupper($labelFormat), self::ALLOWED_LABEL_FORMATS, true)) {
            throw new \InvalidArgumentException(
                'Invalid label format. Allowed formats: ' . implode(', ', self::ALLOWED_LABEL_FORMATS),
                400
            );
        }
        return $labelFormat;
    }

    private function validateService(?string $service, string $apiKey): string
    {
        $response = $this->getServices($apiKey);
        $allowedServices = $response['response']['Services']['AllowedServices'] ?? [];
        if (!in_array(strtoupper($service), $allowedServices, true)) {
            throw new \InvalidArgumentException(
                'Invalid service. Allowed services: ' . implode(', ', $allowedServices),
                400
            );
        }
        return $service;
    }

    private function validateConsignorAddress(array $consignor): array
    {
        $fields = [
            'FullName' => ['name' => 'sender_fullname', 'required' => false, 'default_length' => 50],
            'Company' => ['name' => 'sender_company', 'required' => false, 'default_length' => 60],
            'AddressLine1' => ['name' => 'sender_address', 'required' => true, 'default_length' => 50],
            'AddressLine2' => ['name' => 'sender_address2', 'required' => false, 'default_length' => 50],
            'AddressLine3' => ['name' => 'sender_address3', 'required' => false, 'default_length' => 50],
            'City' => ['name' => 'sender_city', 'required' => true, 'default_length' => 50],
            'State' => ['name' => 'sender_state', 'required' => false, 'default_length' => 50],
            'Zip' => ['name' => 'sender_postalcode', 'required' => true, 'default_length' => 20],
            'Country' => ['name' => 'sender_country', 'required' => false, 'default_length' => 2],
            'Phone' => ['name' => 'sender_phone', 'required' => false, 'default_length' => 15],
            'Email' => ['name' => 'sender_email', 'required' => false, 'default_length' => -1],
        ];
        return $this->validateAddress($fields, $consignor);
    }

    private function validateConsigneeAddress(array $consignee): array
    {
        $fields = [
            'Name' => ['name' => 'delivery_fullname', 'required' => true, 'default_length' => 50],
            'Company' => ['name' => 'delivery_company', 'required' => false, 'default_length' => 60],
            'AddressLine1' => ['name' => 'delivery_address', 'required' => true, 'default_length' => 50],
            'AddressLine2' => ['name' => 'delivery_address2', 'required' => false, 'default_length' => 50],
            'AddressLine3' => ['name' => 'delivery_address3', 'required' => false, 'default_length' => 50],
            'City' => ['name' => 'delivery_city', 'required' => true, 'default_length' => 50],
            'State' => ['name' => 'delivery_state', 'required' => false, 'default_length' => 50],
            'Zip' => ['name' => 'delivery_postalcode', 'required' => true, 'default_length' => 20],
            'Country' => ['name' => 'delivery_country', 'required' => true, 'default_length' => 2],
            'Phone' => ['name' => 'delivery_phone', 'required' => false, 'default_length' => 15],
            'Email' => ['name' => 'delivery_email', 'required' => false, 'default_length' => -1],
        ];
        return $this->validateAddress($fields, $consignee);
    }

    private function validateAddress(array $fields, array $address): array
    {
        $result = [];
        $serviceLimits = $this->serviceInfo['response']['ServiceInfo']['fieldLimits'] ?? [];
        foreach ($fields as $key => $field) {
            if ($field['required'] && empty($address[$field['name']])) {
                throw new \InvalidArgumentException("Field '{$field['name']}' cannot be empty.", 400);
            }
            if (isset($address[$field['name']])) {
                $maxLength = $serviceLimits[$key] ?? $field['default_length'];
                if (!is_numeric($maxLength)) {
                    $maxLength = $field['default_length'];
                }
                if (mb_strlen(trim($address[$field['name']]), 'UTF-8') > $maxLength && $maxLength > 0) {
                    throw new \InvalidArgumentException(
                        "Field '{$field['name']}' exceeds maximum length of {$maxLength} characters.",
                        400
                    );
                }
            }
            if (strlen($address[$field['name']]) > 0) {
                $result[$key] = $address[$field['name']];
            }
        }
        return $result;
    }

    private function getServices(string $apiKey): array
    {
        $command = 'GetServices';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
        ]);
        $response = $this->runCommand($body);
        if ($response['http_code'] !== 200) {
            $this->throwError($response['response'], $response['http_code']);
        }
        return $response;
    }

    private function getServiceInfo(string $apiKey): array
    {
        $command = 'GetServiceInfo';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
            'Service' => $this->service,
        ]);
        $response = $this->runCommand($body);
        if ($response['http_code'] !== 200) {
            $this->throwError($response['response'], $response['http_code']);
        }
        return $response;
    }


    private function runCommand(?string $body): array
    {
        $ch = $this->getCurl($body);
        $response = curl_exec($ch);
        if ($response === false) {
            throw new \RuntimeException('cURL error: ' . curl_error($ch), 500);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('JSON decode error: ' . json_last_error_msg(), 500);
        }

        return [
            'http_code' => $httpCode,
            'response' => $decodedResponse,
        ];
    }

    private function getHeaders(): array
    {
        return [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
    }

    private function getCurl(
        ?string $body = null,
        string $type = 'POST',
    ): \CurlHandle {
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_HTTPHEADER => $this->getHeaders(),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $type,
        ));
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        return $ch;
    }

    private function throwError(array $response, int $httpCode): void
    {
        if (isset($response['ErrorLevel']) && isset($response['Error'])) {
            throw new \InvalidArgumentException(
                $response['Error'],
                $httpCode
            );
        }
        throw new \InvalidArgumentException(
            'Unknown error occurred.',
            $httpCode
        );
    }


}



