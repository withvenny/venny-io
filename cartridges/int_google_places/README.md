# int_google_places

## Purpose

`int_google_places` gives Venny I/O a reusable server-side adapter for Google Maps Platform Places API (New) without coupling application cartridges directly to Google's HTTP endpoints, field-mask rules, billing-sensitive response shapes, or session-token mechanics. It exposes Autocomplete, Place Details, Text Search, Nearby Search, and Place Photos, while preserving Google Place IDs as the canonical provider identity and normalizing common place fields for Venny consumers. The cartridge owns Google Places authentication, HTTPS request construction, required field masks, Autocomplete session-token generation, provider-status translation, and safe response normalization. It intentionally does not own Venny address records, CRM contacts, businesses, properties, or application-domain location truth, and it does not perform geocoding/address normalization; those concerns remain with consuming Venny cartridges and `int_google_locations`.

## Provider

Google Maps Platform

## Tool

Places API (New)

Overview:

https://developers.google.com/maps/documentation/places/web-service/op-overview

Google currently documents Places API (New) as including:

```text
Autocomplete (New)
Place Details (New)
Text Search (New)
Nearby Search (New)
Place Photos (New)
```

## Architectural Boundary

This cartridge answers:

```text
What place is the user looking for?
What places match this text?
What places are near these coordinates?
What details does Google know about this Place ID?
What photos are associated with this place?
```

`int_google_locations` answers:

```text
Where exactly is this?
What coordinates correspond to this address?
What normalized address corresponds to these coordinates?
```

Do not collapse these responsibilities into one integration.

## Installation

Vanilla PHP with cURL:

```bash
composer install
```

No Google-specific PHP SDK is required for these documented Places API (New) HTTPS endpoints.

## Configuration

### `v_GOOGLE_MAPS_SERVER_KEY`

Required.

Server-side Google Maps Platform key used for Places API (New).

The key should be dedicated to server-side Venny integrations and restricted to the Places API (New) service as appropriate.

### `v_GOOGLE_PLACES_LANGUAGE`

Optional.

Default language code supplied to supported Places requests when a caller does not provide one.

Example:

```text
en
```

### `v_GOOGLE_PLACES_REGION_CODE`

Optional.

Default two-character region code used where Places API supports region-code behavior.

Example:

```text
US
```

## Field Masks

Place Details (New), Text Search (New), and Nearby Search (New) require a field mask. Google documents that omitted field masks produce errors, while requesting unnecessary fields increases response size and can affect billing.

The cartridge therefore uses explicit field masks and does not default production requests to `*`.

Conservative defaults:

Place Details:

```text
id,displayName,formattedAddress,location,types,businessStatus
```

Text Search / Nearby Search:

```text
places.id,places.displayName,places.formattedAddress,places.location,places.types,places.businessStatus
```

Callers may provide a different documented field mask when they intentionally need fields such as phone numbers, ratings, opening hours, photos, entrances, navigation points, reviews, website URI, price level, or accessibility information.

## Capabilities

### Autocomplete

```php
autocomplete(string $input, array $options = [])
```

Supports normalized options including:

```text
session_token
language_code
region_code
included_primary_types
included_region_codes
location_bias
location_restriction
origin
include_query_predictions
include_pure_service_area_businesses
include_future_opening_businesses
```

If no `session_token` is supplied, the cartridge can generate one with:

```php
createSessionToken()
```

Venny UI/application flows should keep one token across a logical Autocomplete session and use the resulting Place ID with Place Details when appropriate.

### Place Details

```php
getPlace(string $placeId, array $options = [])
```

Options:

```text
field_mask
language_code
region_code
session_token
```

### Text Search

```php
searchText(string $query, array $options = [])
```

Supports documented body options such as:

```text
included_type
strict_type_filtering
location_bias
location_restriction
language_code
region_code
rank_preference
open_now
min_rating
max_result_count
price_levels
include_pure_service_area_businesses
include_future_opening_businesses
page_token
```

### Nearby Search

```php
searchNearby(
    float $latitude,
    float $longitude,
    float $radiusMeters,
    array $options = []
)
```

Supports:

```text
included_types
excluded_types
included_primary_types
excluded_primary_types
max_result_count
rank_preference
language_code
region_code
include_future_opening_businesses
```

### Place Photos

```php
getPhotoUri(
    string $photoName,
    int $maxWidthPx = 800,
    ?int $maxHeightPx = null
)
```

The cartridge asks Google's Place Photos endpoint for the photo URI and returns that URI rather than downloading or persisting the image bytes.

Photo resource names come from Places responses when the `photos` field is explicitly included in the relevant field mask.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\GooglePlaces\Config;
use Venny\Cartridges\GooglePlaces\Provider;

$provider = new Provider(Config::fromEnvironment());

$sessionToken = $provider->createSessionToken();

$suggestions = $provider->autocomplete(
    '1600 Amphitheatre',
    [
        'session_token' => $sessionToken
    ]
);
```

Place Details:

```php
$place = $provider->getPlace(
    'ChIJ...',
    [
        'session_token' => $sessionToken,
        'field_mask' => [
            'id',
            'displayName',
            'formattedAddress',
            'location',
            'addressComponents',
            'types'
        ]
    ]
);
```

Text Search:

```php
$result = $provider->searchText(
    'coffee near downtown Dallas',
    [
        'field_mask' => [
            'places.id',
            'places.displayName',
            'places.formattedAddress',
            'places.location'
        ]
    ]
);
```

Nearby Search:

```php
$result = $provider->searchNearby(
    32.7767,
    -96.7970,
    1500,
    [
        'included_types' => ['restaurant'],
        'max_result_count' => 10
    ]
);
```

## Normalized Place Shape

Where fields are available, the cartridge normalizes:

```text
id
resource_name
display_name
formatted_address
short_formatted_address
location.latitude
location.longitude
types
primary_type
primary_type_display_name
business_status
google_maps_uri
website_uri
national_phone_number
international_phone_number
rating
user_rating_count
price_level
photos
regular_opening_hours
current_opening_hours
address_components
```

The original Google object is also preserved in the normalized result under `raw` for provider-specific fields intentionally requested by the consuming cartridge.

## Session Tokens

Autocomplete (New) supports session tokens to group user keystrokes into a logical autocomplete session.

This cartridge generates opaque URL-safe tokens with:

```php
createSessionToken()
```

The consuming UI/application should retain the token across one autocomplete interaction and pass it onward when requesting Place Details for the chosen prediction when applicable.

The provider cartridge does not persist sessions.

## Provider Result

Example:

```php
[
    'success' => true,
    'provider' => 'google',
    'tool' => 'places',
    'operation' => 'place_details',
    'provider_id' => 'ChIJ...',
    'data' => [
        'place' => [...]
    ],
    'metadata' => [
        'field_mask' => [...]
    ]
]
```

## Health Check

The default health check is local and non-billable.

It validates:

```text
required configuration
API key shape
cURL availability
Places endpoint construction
default language and region settings
```

It does not execute a live Places request because those operations can be billable.

A future Business Manager provider test can call a remote method explicitly with administrator intent.

## Business Manager

Mandatory:

```text
bm/metadata.php
bm/configuration.php
bm/health.php
```

Every configuration key includes human-readable setup instructions covering:

```text
Google Cloud project selection
billing enablement
Places API (New) enablement
API key creation
API restriction
server-side application restriction
expected value format
secret classification
documentation links
```

## Security

Use a separate server-side key.

Do not place:

```text
v_GOOGLE_MAPS_SERVER_KEY
```

in browser JavaScript, public source, logs, or client applications.

Restrict keys according to Google Maps Platform security guidance.

## Persistence and Google Data

This cartridge returns Google Places data but does not define Venny persistence policy.

The consuming application must decide what data may be stored, refreshed, attributed, displayed, or cached under the applicable Google Maps Platform terms and Places policies.

Canonical provider identity should use Google Place ID when appropriate rather than relying on a mutable display name or formatted address.

## Documentation

Places API (New) overview:

https://developers.google.com/maps/documentation/places/web-service/op-overview

Autocomplete:

https://developers.google.com/maps/documentation/places/web-service/place-autocomplete

Place Details:

https://developers.google.com/maps/documentation/places/web-service/place-details

Text Search:

https://developers.google.com/maps/documentation/places/web-service/text-search

Nearby Search:

https://developers.google.com/maps/documentation/places/web-service/nearby-search

Place Photos:

https://developers.google.com/maps/documentation/places/web-service/place-photos

Field masks:

https://developers.google.com/maps/documentation/places/web-service/choose-fields
