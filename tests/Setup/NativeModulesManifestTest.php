<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use PHPUnit\Framework\TestCase;

final class NativeModulesManifestTest extends TestCase
{
    public function testAuthAndMailsAreRequiredForEveryProjectIncludingBoOnly(): void
    {
        $path = dirname(__DIR__, 2).'/config/symsvel-native-modules.json';
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $manifest['schema']);
        self::assertContains('symsvel-framework/module-auth', $manifest['modules']);
        self::assertContains('symsvel-framework/module-admins', $manifest['modules']);
        self::assertContains('symsvel-framework/module-roles', $manifest['modules']);
        self::assertContains('symsvel-framework/module-acls', $manifest['modules']);
        self::assertContains('symsvel-framework/module-settings', $manifest['modules']);
        self::assertContains('symsvel-framework/module-mails', $manifest['modules']);
        self::assertContains('symsvel-framework/module-languages', $manifest['modules']);
        self::assertNotContains('symsvel-framework/module-users', $manifest['modules']);
        self::assertContains('symsvel-framework/module-users', $manifest['components']['app']);
        self::assertSame(count($manifest['modules']), count(array_unique($manifest['modules'])));
    }
}
