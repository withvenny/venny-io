<?php

declare(strict_types=1);

namespace Venny\Cartridges\GoogleLocations;

use Venny\Cartridges\GoogleLocations\Exceptions\ProviderException;

final class Client
{
    public function __construct(private readonly Config $config) {}

    public function get(array $params): array
    {
        $params['key'] = $this->config->serverKey();

        $url = Config::ENDPOINT . '?' . http_build_query(
            $params,
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        $curl = curl_init($url);

        if ($curl === false) {
            throw new ProviderException('Unable to initialize cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: VennyIO/int_google_locations/1.0.0',
            ],
        ]);

        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            throw new ProviderException(
                'Google Geocoding API transport failed: ' . $error
            );
        }

        if ($status < 200 || $status >= 300) {
            throw new ProviderException(
                sprintf('Google Geocoding API returned HTTP %d.', $status),
                $status
            );
        }

        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ProviderException(
                'Google Geocoding API returned invalid JSON.',
                0,
                $e
            );
        }

        if (!is_array($decoded)) {
            throw new ProviderException('Google Geocoding API response was not an object.');
        }

        return $decoded;
    }
}
