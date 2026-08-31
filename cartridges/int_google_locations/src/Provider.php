<?php

declare(strict_types=1);

namespace Venny\Cartridges\GoogleLocations;

use Venny\Cartridges\GoogleLocations\Exceptions\AuthenticationException;
use Venny\Cartridges\GoogleLocations\Exceptions\ProviderException;
use Venny\Cartridges\GoogleLocations\Exceptions\QuotaException;
use Venny\Cartridges\GoogleLocations\Exceptions\ValidationException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function geocode(string $address, array $options = []): ProviderResult
    {
        $address = trim($address);

        if ($address === '') {
            throw new ValidationException('Address is required.');
        }

        $params = ['address' => $address] + $this->commonOptions($options);

        foreach (['bounds', 'components'] as $option) {
            if (isset($options[$option])) {
                $params[$option] = $this->serializeFilter($options[$option]);
            }
        }

        return $this->request('geocode_address', $params);
    }

    public function reverseGeocode(
        float $latitude,
        float $longitude,
        array $options = []
    ): ProviderResult {
        $this->validateCoordinates($latitude, $longitude);

        $params = [
            'latlng' => $latitude . ',' . $longitude,
        ] + $this->commonOptions($options);

        foreach (['result_type', 'location_type'] as $option) {
            if (isset($options[$option])) {
                $params[$option] = $this->serializeFilter($options[$option]);
            }
        }

        return $this->request('reverse_geocode', $params);
    }

    public function getAddressFromPlaceId(
        string $placeId,
        array $options = []
    ): ProviderResult {
        $placeId = trim($placeId);

        if ($placeId === '') {
            throw new ValidationException('Google Place ID is required.');
        }

        $params = ['place_id' => $placeId] + $this->commonOptions($options);

        return $this->request('geocode_place_id', $params);
    }

    public function normalizeAddress(
        string $address,
        array $options = []
    ): ProviderResult {
        $result = $this->geocode($address, $options);
        $results = $result->data()['results'];

        if ($results === []) {
            return ProviderResult::ok(
                'normalize_address',
                null,
                ['location' => null, 'results' => []],
                $result->metadata()
            );
        }

        $location = $this->normalizeResult($results[0]);

        return ProviderResult::ok(
            'normalize_address',
            $location['place_id'],
            [
                'location' => $location,
                'results' => $results,
            ],
            $result->metadata()
        );
    }

    public function getCoordinates(
        string $address,
        array $options = []
    ): ProviderResult {
        $normalized = $this->normalizeAddress($address, $options);
        $location = $normalized->data()['location'];

        return ProviderResult::ok(
            'get_coordinates',
            $location['place_id'] ?? null,
            [
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'formatted_address' => $location['formatted_address'] ?? null,
                'place_id' => $location['place_id'] ?? null,
            ],
            $normalized->metadata()
        );
    }

    public function healthCheck(bool $remote = false): ProviderResult
    {
        if (!$remote) {
            return ProviderResult::ok(
                'health_check',
                null,
                [
                    'configured' => true,
                    'remote_test_performed' => false,
                    'curl_available' => extension_loaded('curl'),
                    'endpoint' => Config::ENDPOINT,
                    'language' => $this->config->language(),
                    'region' => $this->config->region(),
                ],
                [
                    'billing_note' => 'Remote Geocoding API requests may incur Google Maps Platform usage charges.',
                ]
            );
        }

        $result = $this->geocode(
            '1600 Amphitheatre Parkway, Mountain View, CA'
        );

        return ProviderResult::ok(
            'health_check',
            $result->providerId(),
            [
                'configured' => true,
                'remote_test_performed' => true,
                'provider_reachable' => true,
                'result_count' => count($result->data()['results']),
            ],
            [
                'billing_note' => 'A real Geocoding API request was performed.',
            ]
        );
    }

    private function request(string $operation, array $params): ProviderResult
    {
        $response = $this->client->get($params);
        $status = (string) ($response['status'] ?? '');
        $errorMessage = isset($response['error_message'])
            ? (string) $response['error_message']
            : null;

        if ($status === 'REQUEST_DENIED') {
            throw new AuthenticationException(
                $errorMessage ?: 'Google Geocoding API request was denied.',
                0,
                null,
                ['google_status' => $status]
            );
        }

        if ($status === 'OVER_QUERY_LIMIT') {
            throw new QuotaException(
                $errorMessage ?: 'Google Geocoding API quota was exceeded.',
                0,
                null,
                ['google_status' => $status]
            );
        }

        if ($status === 'INVALID_REQUEST') {
            throw new ValidationException(
                $errorMessage ?: 'Google Geocoding API request was invalid.',
                0,
                null,
                ['google_status' => $status]
            );
        }

        if (!in_array($status, ['OK', 'ZERO_RESULTS'], true)) {
            throw new ProviderException(
                $errorMessage ?: 'Google Geocoding API returned status ' . ($status ?: 'UNKNOWN') . '.',
                0,
                null,
                ['google_status' => $status]
            );
        }

        $results = is_array($response['results'] ?? null)
            ? $response['results']
            : [];

        $providerId = null;
        if ($results !== [] && isset($results[0]['place_id'])) {
            $providerId = (string) $results[0]['place_id'];
        }

        return ProviderResult::ok(
            $operation,
            $providerId,
            [
                'results' => $results,
            ],
            [
                'google_status' => $status,
                'result_count' => count($results),
            ]
        );
    }

    private function normalizeResult(array $result): array
    {
        $geometry = is_array($result['geometry'] ?? null)
            ? $result['geometry']
            : [];
        $coords = is_array($geometry['location'] ?? null)
            ? $geometry['location']
            : [];
        $components = is_array($result['address_components'] ?? null)
            ? $result['address_components']
            : [];

        return [
            'formatted_address' => $result['formatted_address'] ?? null,
            'place_id' => $result['place_id'] ?? null,
            'latitude' => isset($coords['lat']) ? (float) $coords['lat'] : null,
            'longitude' => isset($coords['lng']) ? (float) $coords['lng'] : null,
            'location_type' => $geometry['location_type'] ?? null,
            'partial_match' => (bool) ($result['partial_match'] ?? false),
            'types' => $result['types'] ?? [],
            'address_components' => $components,
            'components' => $this->componentMap($components),
            'viewport' => $geometry['viewport'] ?? null,
            'bounds' => $geometry['bounds'] ?? null,
        ];
    }

    private function componentMap(array $components): array
    {
        $map = [];

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            $types = $component['types'] ?? [];
            if (!is_array($types)) {
                continue;
            }

            foreach ($types as $type) {
                if (!is_string($type)) {
                    continue;
                }

                $map[$type] = [
                    'long_name' => $component['long_name'] ?? null,
                    'short_name' => $component['short_name'] ?? null,
                ];
            }
        }

        return $map;
    }

    private function commonOptions(array $options): array
    {
        $params = [];

        $language = $options['language'] ?? $this->config->language();
        if ($language !== null && trim((string) $language) !== '') {
            $params['language'] = trim((string) $language);
        }

        $region = $options['region'] ?? $this->config->region();
        if ($region !== null && trim((string) $region) !== '') {
            $params['region'] = trim((string) $region);
        }

        return $params;
    }

    private function serializeFilter(mixed $value): string
    {
        if (is_array($value)) {
            $value = implode('|', array_map('strval', $value));
        }

        $value = trim((string) $value);

        if ($value === '') {
            throw new ValidationException('Geocoding filter cannot be empty.');
        }

        return $value;
    }

    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new ValidationException('Latitude must be between -90 and 90.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new ValidationException('Longitude must be between -180 and 180.');
        }
    }
}
