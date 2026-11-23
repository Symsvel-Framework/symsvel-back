<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\StandaloneSetup;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class StandaloneSetupTest extends TestCase
{
    public function testOnlyTheFourExplicitWizardPathsAreIntercepted(): void
    {
        foreach (['/setup', '/setup/admin', '/prod_settings', '/prod_settings/admin'] as $path) {
            self::assertTrue(StandaloneSetup::isSetupPath($path), $path);
        }
        foreach (['/', '/setup/extra', '/prod_settings.php', '/prod_settings/extra'] as $path) {
            self::assertFalse(StandaloneSetup::isSetupPath($path), $path);
        }
    }

    public function testLocalWizardGeneratesAndPreservesANonVersionedSecret(): void
    {
        $backend = sys_get_temp_dir().'/symsvel-native-bootstrap-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($backend, 0700));
        try {
            $origins = [
                'back' => 'http://back.test.localhost',
                'bo' => 'http://bo.test.localhost',
                'app' => '',
                'front' => '',
            ];
            $method = new ReflectionMethod(StandaloneSetup::class, 'writeLocalEnvironment');
            $method->invoke(null, $backend, 'sqlite:///%kernel.project_dir%/var/app.sqlite', 'test.localhost', $origins);
            $contents = (string) file_get_contents($backend.'/.env.local');
            self::assertStringContainsString('APP_ENV="dev"', $contents);
            self::assertMatchesRegularExpression('/^APP_SECRET="([a-f0-9]{64})"$/m', $contents);
            self::assertStringContainsString('SYMSVEL_BACK_ORIGIN="http://back.test.localhost"', $contents);
            self::assertStringContainsString('SYMSVEL_BO_ORIGIN="http://bo.test.localhost"', $contents);
            preg_match('/^APP_SECRET="([a-f0-9]{64})"$/m', $contents, $matches);
            $method->invoke(null, $backend, 'sqlite:///%kernel.project_dir%/var/new.sqlite', 'test.localhost', $origins);
            $newContents = (string) file_get_contents($backend.'/.env.local');
            self::assertStringContainsString('APP_SECRET="'.$matches[1].'"', $newContents);
            self::assertStringNotContainsString('var/app.sqlite', $newContents);
            self::assertStringContainsString('var/new.sqlite', $newContents);
            self::assertFalse(is_executable($backend.'/.env.local'));
        } finally {
            @unlink($backend.'/.env.local');
            @rmdir($backend);
        }
    }

    public function testProductionCloneRestoresDevOrStableChannelFromComposerManifest(): void
    {
        foreach (['dev-dev' => 'dev', '^1.2' => 'stable'] as $constraint => $expected) {
            $backend = sys_get_temp_dir().'/symsvel-channel-'.bin2hex(random_bytes(8));
            self::assertTrue(mkdir($backend, 0700));
            try {
                file_put_contents($backend.'/composer.json', json_encode([
                    'require' => ['symsvel-framework/core' => $constraint],
                ], JSON_THROW_ON_ERROR));
                $method = new ReflectionMethod(StandaloneSetup::class, 'prepareProductionChannel');
                $method->invoke(null, $backend);
                self::assertSame($expected, \SymSvel\Tooling\Update\ProjectChannel::read($backend));
                // Once the channel exists, a second Setup visit must preserve it.
                $method->invoke(null, $backend);
                self::assertSame($expected, \SymSvel\Tooling\Update\ProjectChannel::read($backend));
            } finally {
                @unlink($backend.'/.symsvel/project.json');
                @rmdir($backend.'/.symsvel');
                @unlink($backend.'/composer.json');
                @rmdir($backend);
            }
        }
    }

    public function testFrontControllerMustGateRuntimeBeforeSymfonyAutoload(): void
    {
        $front = (string) file_get_contents(dirname(__DIR__, 2).'/public/index.php');
        self::assertLessThan(
            strpos($front, "require_once $". "backend.'/vendor/autoload_runtime.php'"),
            strpos($front, "'completed'"),
        );
        self::assertStringContainsString('StandaloneSetup::handle', $front);
    }
}
