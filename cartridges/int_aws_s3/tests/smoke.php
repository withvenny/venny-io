<?php

declare(strict_types=1);

use Venny\Cartridges\AwsS3\Config;

require_once dirname(__DIR__, 3) . '/config/bootstrap.php';

$config = new Config(
    region: 'us-east-1',
    bucket: 'example-bucket',
    prefix: 'uploads/'
);

assert($config->key('images/test.jpg') === 'uploads/images/test.jpg');
assert($config->bucket() === 'example-bucket');
assert($config->region() === 'us-east-1');

echo "int_aws_s3 local configuration smoke tests passed.\n";
