<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ComposerInterfaceSyncTest extends TestCase
{
    public function testFreshBackendCloneSkipsInterfaceSync(): void
    {
        $this->assertSkipped('dev', false, 'no interface sources');
    }

    public function testProductionSkipsEvenWhenSourcesExist(): void
    {
        $this->assertSkipped('prod', true, 'disabled in production');
    }

    private function assertSkipped(string $environment, bool $sources, string $message): void
    {
        if (!class_exists(\SymSvel\Tooling\Module\ComposerInterfaceSynchronizer::class)) {
            self::markTestSkipped('Requires the next Tooling release.');
        }
        $root = sys_get_temp_dir().'/symsvel-sync-'.bin2hex(random_bytes(8));
        mkdir($root.'/backend/scripts', 0777, true);
        mkdir($root.'/app');
        $backend = $root.'/backend';
        try {
            copy(dirname(__DIR__).'/scripts/symsvel-module-sync.php', $backend.'/scripts/symsvel-module-sync.php');
            symlink(dirname(__DIR__).'/vendor', $backend.'/vendor');
            file_put_contents($backend.'/.env', "APP_ENV=dev\n");
            file_put_contents($backend.'/.env.local', "APP_ENV=".$environment."\n");
            if ($sources) {
                file_put_contents($root.'/app/package.json', '{}');
            }
            $process = new Process([PHP_BINARY, 'scripts/symsvel-module-sync.php'], $backend, ['APP_ENV' => false, 'APP_DEBUG' => false, 'SYMFONY_DOTENV_VARS' => false]);
            $process->mustRun();
            self::assertStringContainsString($message, $process->getOutput());
        } finally {
            if ($sources) {
                unlink($root.'/app/package.json');
            }
            unlink($backend.'/.env.local');
            unlink($backend.'/.env');
            unlink($backend.'/vendor');
            unlink($backend.'/scripts/symsvel-module-sync.php');
            rmdir($backend.'/scripts');
            rmdir($backend);
            rmdir($root.'/app');
            rmdir($root);
        }
    }
}
