<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\DatabaseConnectionConfig;
use PHPUnit\Framework\TestCase;

final class DatabaseConnectionConfigTest extends TestCase
{
    public function testBuildsSQLiteUrlWithoutServerVersion(): void
    {
        self::assertSame('sqlite:///%kernel.project_dir%/var/app.sqlite',
            (new DatabaseConnectionConfig())->buildSelectedUrl('sqlite', '', '', '', '', '', 'var/app.sqlite'));
    }

    public function testRejectsUnsafeSQLitePath(): void
    {
        $this->expectException(\\InvalidArgumentException::class);
        (new DatabaseConnectionConfig())->buildSelectedUrl('sqlite', '', '', '', '', '', '../secrets/config.sqlite');
    }

    public function testRejectsUnknownDatabaseEngine(): void
    {
        $this->expectException(\\InvalidArgumentException::class);
        (new DatabaseConnectionConfig())->buildSelectedUrl('oracle', '', '', '', '', '');
    }

    public function testBuildsPostgresqlUrlAndEncodesCredentials(): void
    {
        $url = (new DatabaseConnectionConfig())->buildUrl(' localhost ', '5432', 'my/database', ' user@name ', 'p@ss:/?#%');
        self::assertSame('postgresql://user%40name:p%40ss%3A%2F%3F%23%25@localhost:5432/my%2Fdatabase', $url);
    }

    public function testAllowsEmptyPassword(): void
    {
        self::assertSame(
            'postgresql://postgres@127.0.0.1:5432/project',
            (new DatabaseConnectionConfig())->buildUrl('127.0.0.1', '5432', 'project', 'postgres', ''),
        );
    }

    public function testRejectsInvalidFields(): void
    {
        $invalid = [
            ['', '5432', 'project', 'postgres', ''],
            ['example.com/evil', '5432', 'project', 'postgres', ''],
            ['localhost', '0', 'project', 'postgres', ''],
            ['localhost', '65536', 'project', 'postgres', ''],
            ['localhost', '5432', '', 'postgres', ''],
            ['localhost', '5432', 'project', '', ''],
            ['localhost', '5432', 'project', 'postgres', "bad\npassword"],
        ];
        foreach ($invalid as $fields) {
            try {
                (new DatabaseConnectionConfig())->buildUrl(...$fields);
                self::fail('Invalid connection parameters were accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
    public function testNormalizesPostgresqlServerVersions(): void
    {
        self::assertSame('9.6.24', DatabaseConnectionConfig::normalizeServerVersion('90624'));
        self::assertSame('12.0.19', DatabaseConnectionConfig::normalizeServerVersion('120019'));
        self::assertSame('16.0.4', DatabaseConnectionConfig::normalizeServerVersion('160004'));
        self::assertSame('17.0.0', DatabaseConnectionConfig::normalizeServerVersion('170000'));
    }

    public function testRejectsMissingServerVersion(): void
    {
        $this->expectException(\RuntimeException::class);
        DatabaseConnectionConfig::normalizeServerVersion('');
    }

}
