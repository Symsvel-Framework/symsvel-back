<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class BoBrowserCorsConfigurationTest extends TestCase
{
    public function testOnlyBrowserAuthPathsPermitCredentials(): void
    {
        $config = Yaml::parseFile(dirname(__DIR__, 2).'/config/packages/nelmio_cors.yaml');
        $cors = $config['nelmio_cors'];
        self::assertFalse($cors['defaults']['allow_credentials']);
        self::assertSame(['Content-Type'], $cors['paths']['^/apibo/auth/browser(?:/|$)']['allow_headers']);
        self::assertTrue($cors['paths']['^/apibo/auth/browser(?:/|$)']['allow_credentials']);
        self::assertSame(['POST', 'OPTIONS'], $cors['paths']['^/apibo/auth/browser(?:/|$)']['allow_methods']);
        self::assertNull($cors['paths']['^/api(?:/|$)']);
        self::assertNull($cors['paths']['^/apibo(?:/|$)']);
        self::assertArrayHasKey('^/apiopen(?:/|$)', $cors['paths']);
        self::assertNull($cors['paths']['^/apiopen(?:/|$)']);
        self::assertSame(['^/apibo/auth/browser(?:/|$)'], array_keys(array_filter($cors['paths'], static fn ($path) => ($path['allow_credentials'] ?? false) === true)));
        self::assertSame('^/apibo/auth/browser(?:/|$)', array_key_first($cors['paths']) === '^/api(?:/|$)'
            ? array_keys($cors['paths'])[1] : '');
    }
}
