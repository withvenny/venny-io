# int_imgix_images

## Purpose

`int_imgix_images` gives Venny I/O a reusable, provider-specific adapter for Imgix's Image URL API while keeping Imgix implementation details out of application cartridges. It generates transformation URLs, secure signed URLs, and responsive `srcset` values from image paths already available through an Imgix Source. Consuming cartridges can request resize, crop, format, quality, focal-point, watermark, text, and other documented Imgix URL transformations by passing Imgix parameters without importing the Imgix SDK directly. The cartridge does not upload, own, or persist original image assets, and it does not become Venny I/O's media system of record.

## Provider

Imgix

## Tool

Imgix Image URL API

Official tool/API reference:

https://www.imgix.com/api

Official PHP client:

https://github.com/imgix/imgix-php

## Installation

This cartridge uses vanilla PHP with Composer autoloading.

From the cartridge directory:

```bash
composer install
```

Or, when dependencies are managed by the parent Venny I/O project:

```bash
composer require imgix/imgix-php:^4.1
composer dump-autoload
```

The official Imgix PHP library is used for URL construction, URL signing, path and parameter encoding, and responsive `srcset` generation.

## Configuration

The cartridge reads configuration from Venny I/O environment variables.

### `v_IMGIX_DOMAIN`

Required.

The Imgix Source domain used to construct URLs.

Example format:

```text
assets.example.imgix.net
```

Do not include a protocol or trailing slash.

### `v_IMGIX_SECURE_URL_TOKEN`

Optional, but recommended when secure URLs are enabled for the Imgix Source.

The server-side Secure URL Token used to sign Imgix URLs. Never expose this value to client-side JavaScript or logs.

### `v_IMGIX_USE_HTTPS`

Optional.

Controls whether generated Imgix URLs use HTTPS.

Accepted values:

```text
1
true
yes
on
0
false
no
off
```

Default:

```text
true
```

### `v_IMGIX_INCLUDE_LIBRARY_PARAM`

Optional.

Controls whether Imgix's `ixlib` library-identification parameter is included when supported by the underlying client.

Default:

```text
true
```

## Capabilities

### `buildUrl(string $path, array $params = [])`

Builds an Imgix URL for a source image path using documented Imgix URL API parameters.

Because Imgix's transformation surface is parameter-driven, the cartridge intentionally accepts a parameter array rather than hard-coding the entire Imgix parameter catalog.

Example:

```php
$result = $provider->buildUrl(
    'properties/123/front.jpg',
    [
        'w' => 1200,
        'h' => 800,
        'fit' => 'crop',
        'auto' => 'format,compress'
    ]
);
```

### `buildSignedUrl(string $path, array $params = [])`

Builds an Imgix URL and requires `v_IMGIX_SECURE_URL_TOKEN` to be configured.

The Imgix PHP client performs the signature generation.

### `buildSrcSet(string $path, array $params = [], array $options = [])`

Generates a responsive `srcset` using the official Imgix PHP client's `createSrcSet()` functionality.

This supports both width-based responsive output and fixed-width DPR output as determined by Imgix's documented client behavior.

### `resize(string $path, int $width, ?int $height = null, array $params = [])`

Convenience wrapper around documented Imgix width and height parameters.

### `thumbnail(string $path, int $width, int $height, array $params = [])`

Convenience wrapper that applies:

```text
w
h
fit=crop
```

unless `fit` is explicitly supplied by the caller.

### `healthCheck()`

Performs a non-destructive local integration diagnostic.

It validates configuration, constructs the Imgix client, and generates a deterministic test URL. It does not upload an asset, alter an Imgix Source, or make a destructive provider request.

## Usage

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Venny\Cartridges\ImgixImages\Config;
use Venny\Cartridges\ImgixImages\Provider;

$config = Config::fromEnvironment();
$provider = new Provider($config);

$result = $provider->buildUrl(
    'images/example.jpg',
    [
        'w' => 800,
        'h' => 600,
        'fit' => 'crop',
        'auto' => 'format,compress'
    ]
);

if ($result->success()) {
    echo $result->data()['url'];
}
```

Responsive image example:

```php
$result = $provider->buildSrcSet(
    'images/example.jpg',
    ['w' => 600]
);

echo $result->data()['srcset'];
```

Signed URL example:

```php
$result = $provider->buildSignedUrl(
    'private/example.jpg',
    ['w' => 1000]
);

echo $result->data()['url'];
```

## Error Handling

The cartridge wraps configuration and provider failures in Venny-specific exceptions:

```text
ConfigurationException
ProviderException
```

Configuration is validated before a provider operation is attempted.

`ProviderResult` preserves normalized success/failure information while allowing the caller to inspect operation-specific data.

The Secure URL Token is never included in a result object or Business Manager metadata.

## Health Check

Business Manager can invoke:

```php
bm/health.php
```

The health diagnostic verifies:

```text
required configuration is present
the domain is structurally valid
the Imgix URL builder can be created
a deterministic URL can be generated
whether signed URL support is configured
```

Because Imgix image transformation is URL-driven, this cartridge does not perform a mutating provider call as part of health verification.

## Business Manager

Business Manager metadata is defined in:

```text
bm/metadata.php
```

It exposes cartridge identity, version, provider, tool, documentation, expected configuration, capabilities, dependencies, and health/configuration screen availability.

Configuration status is available through:

```text
bm/configuration.php
```

No secret value is returned by Business Manager.

## Persistence

This cartridge owns no persistent Venny I/O data and therefore contains no SQL installation scripts.

Original assets should remain owned by Venny I/O's storage/media domain and its configured storage provider. Imgix operates on assets exposed through the configured Imgix Source.

## Documentation

Imgix Image URL API:

https://www.imgix.com/api

Official Imgix PHP client:

https://github.com/imgix/imgix-php

Imgix library blueprint:

https://github.com/imgix/imgix-blueprint

The cartridge intentionally exposes Imgix transformation parameters through generic parameter arrays so newly documented Imgix URL API parameters can be used without requiring a new PHP method for every transformation.
