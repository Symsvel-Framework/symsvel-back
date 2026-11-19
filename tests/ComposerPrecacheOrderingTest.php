<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class ComposerPrecacheOrderingTest extends TestCase
{
    public function testOwnershipInventoryIsRefreshedBeforeSymfonyCacheClear(): void
    {
        $root = dirname(__DIR__);
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (['post-install-cmd', 'post-update-cmd'] as $hook) {
            $scripts = $composer['scripts'][$hook];
            self::assertSame('@php scripts/symsvel-module-precache.php', $scripts[0]);
            self::assertSame('@auto-scripts', $scripts[1]);
            self::assertContains('@php scripts/symsvel-module-sync.php', $scripts);
        }
        self::assertFileExists($root.'/scripts/symsvel-module-precache.php');
        $precache = (string) file_get_contents($root.'/scripts/symsvel-module-precache.php');
        self::assertStringContainsString('ComposerModuleRouteSynchronizer', $precache);
        self::assertLessThan(
            strpos($precache, 'ComposerModuleBundleMapWriter'),
            strpos($precache, 'ComposerModuleRouteSynchronizer'),
            'Local routes must be checked before Symfony cache compilation and bundle publication.',
        );
    }
}
