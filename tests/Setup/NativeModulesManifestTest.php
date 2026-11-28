<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use PHPUnit\Framework\TestCase;

final class NativeModulesManifestTest extends TestCase
{
    public function testAuthTwoFactorAndMailsAreRequiredForEveryProjectIncludingBoOnly(): void
    {
        $path = dirname(__DIR__, 2).'/config/symsvel-native-modules.json';
        $manifest = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $manifest['schema']);
        self::assertContains('symsvel-framework/module-auth', $manifest['modules']);
        self::assertContains('symsvel-framework/module-two-factor', $manifest['modules']);
        self::assertContains('symsvel-framework/module-admins', $manifest['modules']);
        self::assertContains('symsvel-framework/module-roles', $manifest['modules']);
        self::assertContains('symsvel-framework/module-acls', $manifest['modules']);
        self::assertContains('symsvel-framework/module-settings', $manifest['modules']);
        self::assertContains('symsvel-framework/module-mails', $manifest['modules']);
        self::assertContains('symsvel-framework/module-languages', $manifest['modules']);
        self::assertContains('symsvel-framework/module-countries', $manifest['modules']);
        self::assertContains('symsvel-framework/module-gdpr', $manifest['modules']);
        self::assertNotContains('symsvel-framework/module-tracking', $manifest['modules']);
        foreach ($manifest['components'] as $packages) {
            self::assertNotContains('symsvel-framework/module-tracking', $packages);
        }
        foreach (['settings', 'mails'] as $dependency) {
            self::assertLessThan(
                array_search('symsvel-framework/module-gdpr', $manifest['modules'], true),
                array_search('symsvel-framework/module-'.$dependency, $manifest['modules'], true),
            );
        }
        self::assertLessThan(
            array_search('symsvel-framework/module-countries', $manifest['modules'], true),
            array_search('symsvel-framework/module-languages', $manifest['modules'], true),
        );
        self::assertLessThan(
            array_search('symsvel-framework/module-two-factor', $manifest['modules'], true),
            array_search('symsvel-framework/module-auth', $manifest['modules'], true),
        );
        self::assertNotContains('symsvel-framework/module-users', $manifest['modules']);
        self::assertContains('symsvel-framework/module-users', $manifest['components']['app']);
        self::assertSame(count($manifest['modules']), count(array_unique($manifest['modules'])));
    }
}
