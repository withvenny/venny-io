# int_google_locations

## Purpose

`int_google_locations` gives Venny I/O a reusable server-side location normalization and geocoding adapter for Google Maps Platform without coupling application cartridges directly to Google request URLs or response formats. It converts human-entered addresses into Google geocoding results, resolves latitude/longitude coordinates back to formatted addresses, resolves Google Place IDs through the Geocoding API, extracts normalized address components, and exposes canonical coordinates and location metadata through a consistent vanilla-PHP interface. The cartridge owns Google Maps Platform authentication, request construction, HTTPS transport, response validation, provider-status translation, and address-component normalization. It does not provide place discovery, business search, autocomplete, nearby search, or place photos; those belong in `int_google_places`. It also does not own Venny address records or decide which returned address should become application truth. Those responsibilities remain with the consuming Venny domain cartridge.

## Provider

Google Maps Platform

## Tool

Geocoding API

Official documentation:

https://developers.google.com/maps/documentation/geocoding

Forward geocoding:

https://developers.google.com/maps/documentation/geocoding/requests-geocoding

Reverse geocoding:

https://developers.google.com/maps/documentation/geocoding/requests-reverse-geocoding

## Architectural Boundary

This cartridge answers:

```text
Where exactly is this?
What coordinates correspond to this address?
What normalized address did Google resolve?
What address corresponds to these coordinates?
What geocoded location corresponds to this Google Place ID?
```

A separate `int_google_places` cartridge should answer:

```text
What place/business is the user searching for?
What autocomplete suggestions match this text?
What businesses are nearby?
What place details or photos are available?
```

This separation prevents address normalization from becoming coupled to place discovery.

## Installation

This cartridge uses vanilla PHP and PHP cURL.

```bash
composer install
```

No Google-specific PHP SDK is required because the documented Geocoding API is an HTTPS web service.

## Configuration

### `v_GOOGLE_MAPS_SERVER_KEY`

Required.

Server-side Google Maps Platform API key used for Geocoding API requests.

This key must not be exposed to browsers or mobile clients.

Google recommends restricting API keys to the APIs that use them and, for server-side web-service keys, applying appropriate application restrictions such as server IP restrictions when feasible.

### `v_GOOGLE_GEOCODING_LANGUAGE`

Optional.

Default response language code sent to Google when the caller does not specify a language.

Example:

```text
en
```

### `v_GOOGLE_GEOCODING_REGION`

Optional.

Default region bias sent to Google when forward geocoding.

Example:

```text
us
```

This is a bias, not an absolute geographic restriction.

## Capabilities

### Forward geocoding

```php
geocode(string $address, array $options = [])
```

Supported Venny options:

```text
language
region
bounds
components
```

These map to documented Geocoding API request parameters.

### Reverse geocoding

```php
reverseGeocode(float $latitude, float $longitude, array $options = [])
```

Supported options include:

```text
language
result_type
location_type
```

### Place ID geocoding

```php
getAddressFromPlaceId(string $placeId, array $options = [])
```

Resolves a Google Place ID through the Geocoding API.

This is not a Places Details request and therefore remains appropriate to the location-normalization boundary.

### Normalize address

```php
normalizeAddress(string $address, array $options = [])
```

Returns the highest-ranked geocoding result in a normalized Venny shape including:

```text
formatted_address
place_id
latitude
longitude
location_type
partial_match
address_components
types
```

### Coordinates

```php
getCoordinates(string $address, array $options = [])
```

Returns the canonical coordinates from the highest-ranked geocoding result.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\GoogleLocations\Config;
use Venny\Cartridges\GoogleLocations\Provider;

$provider = new Provider(Config::fromEnvironment());

$result = $provider->normalizeAddress(
    '1600 Amphitheatre Parkway, Mountain View, CA'
);

$location = $result->data()['location'];

echo $location['formatted_address'];
echo $location['latitude'];
echo $location['longitude'];
```

Reverse geocoding:

```php
$result = $provider->reverseGeocode(
    37.422,
    -122.084
);
```

Place ID resolution:

```php
$result = $provider->getAddressFromPlaceId(
    'ChIJ...'
);
```

## Normalized Address Components

Google returns address components as typed arrays.

The cartridge preserves the raw component list and also provides a convenient normalized map where available:

```text
street_number
route
locality
sublocality
administrative_area_level_1
administrative_area_level_2
country
postal_code
postal_code_suffix
```

Consuming applications should not assume every locality returns every component type. Address formats vary by country and geography.

## Provider Result

Example:

```php
[
    'success' => true,
    'provider' => 'google',
    'tool' => 'locations',
    'operation' => 'normalize_address',
    'provider_id' => 'ChIJ...',
    'data' => [
        'location' => [
            'formatted_address' => '...',
            'place_id' => 'ChIJ...',
            'latitude' => 37.422,
            'longitude' => -122.084,
            'location_type' => 'ROOFTOP',
            'partial_match' => false,
            'address_components' => [...]
        ]
    ],
    'metadata' => []
]
```

## Error Handling

Exceptions:

```text
ConfigurationException
AuthenticationException
QuotaException
ValidationException
ProviderException
```

Google Geocoding response statuses such as request denial, quota exhaustion, invalid requests, and zero results are normalized into appropriate Venny outcomes.

`ZERO_RESULTS` is returned as a successful provider operation with an empty result set rather than treated as an infrastructure failure.

## Health Check

The default health check is local and non-billable.

It validates:

```text
required configuration
API-key presence
cURL availability
HTTPS endpoint construction
optional language/region configuration
```

It does not automatically call Google because Geocoding API requests are metered.

A remote diagnostic can be invoked explicitly:

```php
healthCheck(true)
```

That performs a real Geocoding API request and may incur Google Maps Platform usage charges.

Business Manager should use the local health check by default and present an explicit administrator action if a remote connectivity test is needed.

## Business Manager

Mandatory metadata:

```text
bm/metadata.php
```

Configuration:

```text
bm/configuration.php
```

Health:

```text
bm/health.php
```

Every configuration key includes provider-side setup instructions explaining how to create or select the Google Cloud project, enable billing, enable the Geocoding API, create and restrict the server key, and store the resulting Venny configuration value.

## Security

Google Maps Platform recommends restricting API keys.

For this server-side cartridge:

```text
Do not expose v_GOOGLE_MAPS_SERVER_KEY in browser JavaScript.
Restrict the key to Geocoding API.
Apply server-side application restrictions such as IP restrictions when feasible.
Use separate keys for server and browser/mobile use.
Never log the complete API key.
```

## Persistence

This cartridge owns no PostgreSQL tables.

The consuming Venny domain should decide what geocoded address data it is permitted to persist and how Google Maps Platform terms apply to that use.

The integration returns provider data; it does not declare all returned Google data to be permanent Venny system-of-record data.

## Documentation

Geocoding API:

https://developers.google.com/maps/documentation/geocoding

Geocoding requests:

https://developers.google.com/maps/documentation/geocoding/requests-geocoding

Reverse geocoding:

https://developers.google.com/maps/documentation/geocoding/requests-reverse-geocoding

Google Maps Platform API security:

https://developers.google.com/maps/api-security-best-practices
