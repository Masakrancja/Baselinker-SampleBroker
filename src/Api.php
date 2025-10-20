<?php

declare(strict_types=1);

namespace Baselinker\Samplebroker;

class Api
{
    public function __construct(private string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function getServices(string $apiKey): array
    {
        $command = 'GetServices';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
        ]);
        $response = $this->runCommand($body);

        return $response;
    }

    public function getServiceInfo(string $apiKey, string $service): array
    {
        $command = 'GetServiceInfo';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
            'Service' => $service,
        ]);
        $response = $this->runCommand($body);

        return $response;
    }

    public function createShipment(string $apiKey, array $body): array
    {
        $command = 'OrderShipment';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
            'Shipment' => $body,
        ]);
        $response = $this->runCommand($body);

        return $response['response']['Shipment'];
    }

    public function getShipmentLabel(
        string $apiKey, 
        array $body
    ): array
    {
        $command = 'GetShipmentLabel';
        $body = json_encode([
            'Apikey' => $apiKey,
            'Command' => $command,
            'Shipment' => $body,
        ]);
        $response = $this->runCommand($body);

        return $response;
    }

    private function runCommand(?string $body = null, string $type = 'POST'): array
    {
        $ch = $this->getCurl($body, $type);
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
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $type,
        ));
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        return $ch;
    }

}
