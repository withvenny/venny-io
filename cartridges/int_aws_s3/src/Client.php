<?php

declare(strict_types=1);

namespace Venny\Cartridges\AwsS3;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

final class Client
{
    private readonly S3Client $s3;

    public function __construct(private readonly Config $config)
    {
        $options = [
            'version' => 'latest',
            'region' => $config->region(),
        ];

        if ($config->hasStaticCredentials()) {
            $options['credentials'] = new Credentials(
                $config->accessKeyId(),
                $config->secretAccessKey(),
                $config->sessionToken()
            );
        }

        if ($config->endpoint() !== null) {
            $options['endpoint'] = $config->endpoint();
        }

        if ($config->pathStyle()) {
            $options['use_path_style_endpoint'] = true;
        }

        $this->s3 = new S3Client($options);
    }

    public function s3(): S3Client
    {
        return $this->s3;
    }
}
