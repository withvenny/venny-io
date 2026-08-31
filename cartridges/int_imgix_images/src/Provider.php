<?php

declare(strict_types=1);

namespace Venny\Cartridges\ImgixImages;

use Throwable;
use Venny\Cartridges\ImgixImages\Exceptions\ConfigurationException;
use Venny\Cartridges\ImgixImages\Exceptions\ProviderException;

final class Provider
{
    private readonly Client $client;

    public function __construct(
        private readonly Config $config,
        ?Client $client = null,
    ) {
        $this->client = $client ?? new Client($config);
    }

    public function buildUrl(string $path, array $params = []): ProviderResult
    {
        $path = $this->validatePath($path);

        try {
            $url = $this->client->builder()->createURL($path, $params);

            return ProviderResult::ok('build_url', [
                'url' => $url,
            ], [
                'signed' => $this->config->hasSecureUrlToken(),
            ]);
        } catch (Throwable $exception) {
            throw ProviderException::fromThrowable('Unable to build Imgix URL.', $exception);
        }
    }

    public function buildSignedUrl(string $path, array $params = []): ProviderResult
    {
        if (!$this->config->hasSecureUrlToken()) {
            throw new ConfigurationException(
                'v_IMGIX_SECURE_URL_TOKEN is required to explicitly build a signed URL.'
            );
        }

        return $this->buildUrl($path, $params);
    }

    public function buildSrcSet(
        string $path,
        array $params = [],
        array $options = []
    ): ProviderResult {
        $path = $this->validatePath($path);

        try {
            $srcset = $this->client->builder()->createSrcSet($path, $params, $options);

            return ProviderResult::ok('build_srcset', [
                'srcset' => $srcset,
            ], [
                'signed' => $this->config->hasSecureUrlToken(),
            ]);
        } catch (Throwable $exception) {
            throw ProviderException::fromThrowable('Unable to build Imgix srcset.', $exception);
        }
    }

    public function resize(
        string $path,
        int $width,
        ?int $height = null,
        array $params = []
    ): ProviderResult {
        if ($width <= 0) {
            throw new ProviderException('Width must be greater than zero.');
        }

        if ($height !== null && $height <= 0) {
            throw new ProviderException('Height must be greater than zero when provided.');
        }

        $params['w'] = $width;

        if ($height !== null) {
            $params['h'] = $height;
        }

        return $this->buildUrl($path, $params);
    }

    public function thumbnail(
        string $path,
        int $width,
        int $height,
        array $params = []
    ): ProviderResult {
        if ($width <= 0 || $height <= 0) {
            throw new ProviderException('Thumbnail width and height must be greater than zero.');
        }

        $params['w'] = $width;
        $params['h'] = $height;
        $params['fit'] ??= 'crop';

        return $this->buildUrl($path, $params);
    }

    public function healthCheck(): ProviderResult
    {
        try {
            $url = $this->client->builder()->createURL(
                '__venny_healthcheck__.jpg',
                ['w' => 1, 'h' => 1]
            );

            return ProviderResult::ok('health_check', [
                'configured' => true,
                'domain' => $this->config->domain(),
                'https' => $this->config->useHttps(),
                'signed_urls_configured' => $this->config->hasSecureUrlToken(),
                'sample_url_generated' => $url !== '',
            ]);
        } catch (Throwable $exception) {
            throw ProviderException::fromThrowable('Imgix cartridge health check failed.', $exception);
        }
    }

    private function validatePath(string $path): string
    {
        $path = trim($path);

        if ($path === '') {
            throw new ProviderException('Imgix image path is required.');
        }

        return $path;
    }
}
