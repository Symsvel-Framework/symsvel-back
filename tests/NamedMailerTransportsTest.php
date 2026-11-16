<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/** Protect the native names expected by module Mails without probing servers. */
final class NamedMailerTransportsTest extends TestCase
{
    public function testLocalMailCatchersAreDeclaredWithoutInstallingThem(): void
    {
        $config = Yaml::parseFile(dirname(__DIR__).'/config/packages/mailer.yaml');
        $transports = $config['framework']['mailer']['transports'] ?? [];
        self::assertSame([
            'smtp', 'mailpit', 'mailhog', 'smtp4dev', 'mailtrap',
        ], array_keys($transports));
        self::assertSame('%env(MAILER_DSN)%', $transports['smtp']);
        self::assertSame('smtp://127.0.0.1:1025', $transports['mailpit']);
        self::assertSame('smtp://127.0.0.1:1025', $transports['mailhog']);
        self::assertSame('smtp://127.0.0.1:25', $transports['smtp4dev']);
        self::assertSame('%env(MAILTRAP_DSN)%', $transports['mailtrap']);
        self::assertArrayNotHasKey('dsn', $config['framework']['mailer']);
    }
}
