<?php

declare(strict_types=1);

namespace Venny\Cartridges\GooglePlaces;

use Venny\Cartridges\GooglePlaces\Exceptions\ProviderException;

final class Client
{
    public function __construct(private readonly Config $config) {}

    public function get(string $path, array $query = [], ?string $fieldMask = null): array
    {
        $url = Config::BASE_URL . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->request('GET', $url, null, $fieldMask);
    }

    public function post(string $path, array $body, ?string $fieldMask = null): array
    {
        $url = Config::BASE_URL . '/' . ltrim($path, '/');

        return $this->request(
            'POST',
            $url,
            json_encode($body, JSON_THROW_ON_ERROR),
            $fieldMask
        );
    }

    private function request(
        string $method,
        string $url,
        ?string $body,
        ?string $fieldMask
    ): array {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $this->config->serverKey(),
            'User-Agent: VennyIO/int_google_places/1.0.0',
        ];

        if ($fieldMask !== null && $fieldMask !== '') {
            $headers[] = 'X-Goog-FieldMask: ' . $fieldMask;
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new ProviderException('Unable to initialize cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($responseBody === false) {
            throw new ProviderException(
                'Google Places API transport failed: ' . $error
            );
        }

        try {
            $decoded = $responseBody === ''
                ? []
                : json_decode($responseBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ProviderException(
                'Google Places API returned invalid JSON.',
                $status,
                $e
            );
        }

        if (!is_array($decoded)) {
            $decoded = [];
        }

        if ($status < 200 || $status >= 300) {
            throw ProviderException::fromHttpResponse($status, $decoded);
        }

        return $decoded;
    }
}
