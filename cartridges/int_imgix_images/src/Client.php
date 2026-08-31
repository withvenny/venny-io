<?php

declare(strict_types=1);

namespace Venny\Cartridges\ImgixImages;

use Imgix\UrlBuilder;

final class Client
{
    private readonly UrlBuilder $builder;

    public function __construct(private readonly Config $config)
    {
        $this->builder = new UrlBuilder(
            $config->domain(),
            $config->useHttps(),
            $config->secureUrlToken() ?? '',
            $config->includeLibraryParam()
        );
    }

    public function builder(): UrlBuilder
    {
        return $this->builder;
    }
}
