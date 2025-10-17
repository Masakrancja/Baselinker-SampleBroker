<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

class Courier
{

    private const ALLOWED_LABEL_FORMATS = ['PDF', 'PNG', 'ZPL'];


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
            $labelFormat = $this->validateLabelFormat($params['label_format'] ?? null);
            $service = $this->validateService($params['service'] ?? null, $apiKey);

            

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
        if ($response['http_code'] !== 200) {
            $this->throwError($response['response'], $response['http_code']);
        }

        // print_r($response); // For debugging purposes
        $allowedServices = $response['response']['Services']['AllowedServices'] ?? [];

        print_r($allowedServices); // For debugging purposes    

        if (!in_array(strtoupper($service), $allowedServices, true)) {
            throw new \InvalidArgumentException(
                'Invalid service. Allowed services: ' . implode(', ', $allowedServices),
                400
            );
        }
        return $service;
    }

    private function getServices(string $apiKey): array
    {
        $command = 'GetServices';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
        ]);
        return $this->runCommand($body);
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



