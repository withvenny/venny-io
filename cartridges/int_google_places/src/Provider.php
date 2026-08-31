<?php

declare(strict_types=1);

namespace Venny\Cartridges\GooglePlaces;

use Venny\Cartridges\GooglePlaces\Exceptions\ValidationException;

final class Provider
{
    private const DEFAULT_DETAIL_FIELDS = [
        'id',
        'displayName',
        'formattedAddress',
        'location',
        'types',
        'businessStatus',
    ];

    private const DEFAULT_SEARCH_FIELDS = [
        'places.id',
        'places.displayName',
        'places.formattedAddress',
        'places.location',
        'places.types',
        'places.businessStatus',
    ];

    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function createSessionToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    public function autocomplete(string $input, array $options = []): ProviderResult
    {
        $input = trim($input);

        if ($input === '') {
            throw new ValidationException('Autocomplete input is required.');
        }

        $body = ['input' => $input];

        $map = [
            'session_token' => 'sessionToken',
            'language_code' => 'languageCode',
            'region_code' => 'regionCode',
            'included_primary_types' => 'includedPrimaryTypes',
            'included_region_codes' => 'includedRegionCodes',
            'location_bias' => 'locationBias',
            'location_restriction' => 'locationRestriction',
            'origin' => 'origin',
            'include_query_predictions' => 'includeQueryPredictions',
            'include_pure_service_area_businesses' => 'includePureServiceAreaBusinesses',
            'include_future_opening_businesses' => 'includeFutureOpeningBusinesses',
        ];

        foreach ($map as $inputKey => $googleKey) {
            if (array_key_exists($inputKey, $options)) {
                $body[$googleKey] = $options[$inputKey];
            }
        }

        if (!isset($body['languageCode']) && $this->config->language() !== null) {
            $body['languageCode'] = $this->config->language();
        }

        if (!isset($body['regionCode']) && $this->config->regionCode() !== null) {
            $body['regionCode'] = $this->config->regionCode();
        }

        $response = $this->client->post('places:autocomplete', $body);

        return ProviderResult::ok(
            'autocomplete',
            null,
            [
                'suggestions' => $response['suggestions'] ?? [],
                'raw' => $response,
            ],
            [
                'session_token' => $body['sessionToken'] ?? null,
            ]
        );
    }

    public function getPlace(string $placeId, array $options = []): ProviderResult
    {
        $placeId = trim($placeId);

        if ($placeId === '') {
            throw new ValidationException('Google Place ID is required.');
        }

        $fieldMask = $this->fieldMask(
            $options['field_mask'] ?? self::DEFAULT_DETAIL_FIELDS
        );

        $query = [];

        $language = $options['language_code'] ?? $this->config->language();
        if ($language !== null) {
            $query['languageCode'] = (string) $language;
        }

        $region = $options['region_code'] ?? $this->config->regionCode();
        if ($region !== null) {
            $query['regionCode'] = (string) $region;
        }

        if (isset($options['session_token'])) {
            $query['sessionToken'] = (string) $options['session_token'];
        }

        $response = $this->client->get(
            'places/' . rawurlencode($placeId),
            $query,
            $fieldMask
        );

        return ProviderResult::ok(
            'place_details',
            $response['id'] ?? $placeId,
            [
                'place' => $this->normalizePlace($response),
            ],
            [
                'field_mask' => $fieldMask,
            ]
        );
    }

    public function searchText(string $queryText, array $options = []): ProviderResult
    {
        $queryText = trim($queryText);

        if ($queryText === '') {
            throw new ValidationException('Text Search query is required.');
        }

        $body = ['textQuery' => $queryText];

        $map = [
            'included_type' => 'includedType',
            'strict_type_filtering' => 'strictTypeFiltering',
            'location_bias' => 'locationBias',
            'location_restriction' => 'locationRestriction',
            'language_code' => 'languageCode',
            'region_code' => 'regionCode',
            'rank_preference' => 'rankPreference',
            'open_now' => 'openNow',
            'min_rating' => 'minRating',
            'max_result_count' => 'maxResultCount',
            'price_levels' => 'priceLevels',
            'include_pure_service_area_businesses' => 'includePureServiceAreaBusinesses',
            'include_future_opening_businesses' => 'includeFutureOpeningBusinesses',
            'page_token' => 'pageToken',
        ];

        foreach ($map as $inputKey => $googleKey) {
            if (array_key_exists($inputKey, $options)) {
                $body[$googleKey] = $options[$inputKey];
            }
        }

        if (!isset($body['languageCode']) && $this->config->language() !== null) {
            $body['languageCode'] = $this->config->language();
        }

        if (!isset($body['regionCode']) && $this->config->regionCode() !== null) {
            $body['regionCode'] = $this->config->regionCode();
        }

        $fieldMask = $this->fieldMask(
            $options['field_mask'] ?? self::DEFAULT_SEARCH_FIELDS
        );

        $response = $this->client->post(
            'places:searchText',
            $body,
            $fieldMask
        );

        $places = array_map(
            fn(array $place) => $this->normalizePlace($place),
            is_array($response['places'] ?? null) ? $response['places'] : []
        );

        return ProviderResult::ok(
            'text_search',
            $places[0]['id'] ?? null,
            [
                'places' => $places,
                'next_page_token' => $response['nextPageToken'] ?? null,
                'raw' => $response,
            ],
            [
                'field_mask' => $fieldMask,
                'result_count' => count($places),
            ]
        );
    }

    public function searchNearby(
        float $latitude,
        float $longitude,
        float $radiusMeters,
        array $options = []
    ): ProviderResult {
        $this->validateCoordinates($latitude, $longitude);

        if ($radiusMeters <= 0 || $radiusMeters > 50000) {
            throw new ValidationException(
                'Nearby Search radius must be greater than 0 and no more than 50000 meters.'
            );
        }

        $body = [
            'locationRestriction' => [
                'circle' => [
                    'center' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ],
                    'radius' => $radiusMeters,
                ],
            ],
        ];

        $map = [
            'included_types' => 'includedTypes',
            'excluded_types' => 'excludedTypes',
            'included_primary_types' => 'includedPrimaryTypes',
            'excluded_primary_types' => 'excludedPrimaryTypes',
            'max_result_count' => 'maxResultCount',
            'rank_preference' => 'rankPreference',
            'language_code' => 'languageCode',
            'region_code' => 'regionCode',
            'include_future_opening_businesses' => 'includeFutureOpeningBusinesses',
        ];

        foreach ($map as $inputKey => $googleKey) {
            if (array_key_exists($inputKey, $options)) {
                $body[$googleKey] = $options[$inputKey];
            }
        }

        if (!isset($body['languageCode']) && $this->config->language() !== null) {
            $body['languageCode'] = $this->config->language();
        }

        if (!isset($body['regionCode']) && $this->config->regionCode() !== null) {
            $body['regionCode'] = $this->config->regionCode();
        }

        $fieldMask = $this->fieldMask(
            $options['field_mask'] ?? self::DEFAULT_SEARCH_FIELDS
        );

        $response = $this->client->post(
            'places:searchNearby',
            $body,
            $fieldMask
        );

        $places = array_map(
            fn(array $place) => $this->normalizePlace($place),
            is_array($response['places'] ?? null) ? $response['places'] : []
        );

        return ProviderResult::ok(
            'nearby_search',
            $places[0]['id'] ?? null,
            [
                'places' => $places,
                'raw' => $response,
            ],
            [
                'field_mask' => $fieldMask,
                'result_count' => count($places),
                'center' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ],
                'radius_meters' => $radiusMeters,
            ]
        );
    }

    public function getPhotoUri(
        string $photoName,
        int $maxWidthPx = 800,
        ?int $maxHeightPx = null
    ): ProviderResult {
        $photoName = trim($photoName);

        if ($photoName === '') {
            throw new ValidationException('Google Place photo resource name is required.');
        }

        if ($maxWidthPx < 1 || $maxWidthPx > 4800) {
            throw new ValidationException('maxWidthPx must be between 1 and 4800.');
        }

        if ($maxHeightPx !== null && ($maxHeightPx < 1 || $maxHeightPx > 4800)) {
            throw new ValidationException('maxHeightPx must be between 1 and 4800.');
        }

        $query = [
            'maxWidthPx' => $maxWidthPx,
            'skipHttpRedirect' => 'true',
        ];

        if ($maxHeightPx !== null) {
            $query['maxHeightPx'] = $maxHeightPx;
        }

        $response = $this->client->get(
            ltrim($photoName, '/') . '/media',
            $query
        );

        return ProviderResult::ok(
            'place_photo_uri',
            $photoName,
            [
                'photo_uri' => $response['photoUri'] ?? null,
                'raw' => $response,
            ]
        );
    }

    public function healthCheck(): ProviderResult
    {
        return ProviderResult::ok(
            'health_check',
            null,
            [
                'configured' => true,
                'remote_test_performed' => false,
                'curl_available' => extension_loaded('curl'),
                'base_url' => Config::BASE_URL,
                'language' => $this->config->language(),
                'region_code' => $this->config->regionCode(),
            ],
            [
                'billing_note' => 'No live Places request was made. Places API operations may be billable.',
            ]
        );
    }

    private function normalizePlace(array $place): array
    {
        $displayName = is_array($place['displayName'] ?? null)
            ? $place['displayName']
            : [];

        $location = is_array($place['location'] ?? null)
            ? $place['location']
            : [];

        $primaryTypeDisplayName = is_array($place['primaryTypeDisplayName'] ?? null)
            ? $place['primaryTypeDisplayName']
            : [];

        return [
            'id' => $place['id'] ?? null,
            'resource_name' => $place['name'] ?? null,
            'display_name' => $displayName['text'] ?? null,
            'display_name_language_code' => $displayName['languageCode'] ?? null,
            'formatted_address' => $place['formattedAddress'] ?? null,
            'short_formatted_address' => $place['shortFormattedAddress'] ?? null,
            'location' => [
                'latitude' => isset($location['latitude']) ? (float) $location['latitude'] : null,
                'longitude' => isset($location['longitude']) ? (float) $location['longitude'] : null,
            ],
            'types' => $place['types'] ?? [],
            'primary_type' => $place['primaryType'] ?? null,
            'primary_type_display_name' => $primaryTypeDisplayName['text'] ?? null,
            'business_status' => $place['businessStatus'] ?? null,
            'google_maps_uri' => $place['googleMapsUri'] ?? null,
            'website_uri' => $place['websiteUri'] ?? null,
            'national_phone_number' => $place['nationalPhoneNumber'] ?? null,
            'international_phone_number' => $place['internationalPhoneNumber'] ?? null,
            'rating' => $place['rating'] ?? null,
            'user_rating_count' => $place['userRatingCount'] ?? null,
            'price_level' => $place['priceLevel'] ?? null,
            'photos' => $place['photos'] ?? [],
            'regular_opening_hours' => $place['regularOpeningHours'] ?? null,
            'current_opening_hours' => $place['currentOpeningHours'] ?? null,
            'address_components' => $place['addressComponents'] ?? [],
            'raw' => $place,
        ];
    }

    private function fieldMask(array|string $fields): string
    {
        if (is_array($fields)) {
            $fields = implode(',', array_map('strval', $fields));
        }

        $fields = trim($fields);

        if ($fields === '') {
            throw new ValidationException('Google Places field mask is required.');
        }

        if (str_contains($fields, ' ')) {
            throw new ValidationException('Google Places field masks cannot contain spaces.');
        }

        return $fields;
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
