<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\StandaloneSetup;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SymSvel\Tooling\Process\PhpCliExecutableResolver;

final class StandaloneSetupPhpCliTest extends TestCase
{
    public function testNativeSetupRunsConsoleWithoutPathAndRejectsInvalidOverride(): void
    {
        self::assertTrue(class_exists(PhpCliExecutableResolver::class), 'The stable Tooling lock must provide the native PHP CLI resolver.');

        $backend = sys_get_temp_dir().'/symsvel-native-php-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($backend, 0700));
        self::assertTrue(mkdir($backend.'/bin', 0700));
        $previous = getenv('SYMSVEL_PHP_CLI');
        $path = getenv('PATH');
        try {
            file_put_contents($backend.'/bin/console', '<?php file_put_contents(dirname(__DIR__)."/result.json", json_encode([PHP_SAPI, PHP_BINARY, array_slice($argv, 1)], JSON_THROW_ON_ERROR));');
            putenv('PATH');
            putenv('SYMSVEL_PHP_CLI='.PHP_BINARY);
            $command = new ReflectionMethod(StandaloneSetup::class, 'command');
            $command->invoke(null, $backend, ['symsvel:setup:database', '--no-interaction'], null, 'Test');
            [$sapi, $binary, $arguments] = json_decode((string) file_get_contents($backend.'/result.json'), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('cli', $sapi);
            self::assertNotSame('', $binary);
            self::assertSame(['symsvel:setup:database', '--no-interaction'], $arguments);

            unlink($backend.'/result.json');
            putenv('SYMSVEL_PHP_CLI='.$backend.'/missing-php');
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('SYMSVEL_PHP_CLI must designate');
            try {
                $command->invoke(null, $backend, ['symsvel:setup:database'], null, 'Test');
            } finally {
                self::assertFileDoesNotExist($backend.'/result.json');
            }
        } finally {
            putenv($previous === false ? 'SYMSVEL_PHP_CLI' : 'SYMSVEL_PHP_CLI='.$previous);
            putenv($path === false ? 'PATH' : 'PATH='.$path);
            @unlink($backend.'/result.json');
            @unlink($backend.'/bin/console');
            @rmdir($backend.'/bin');
            @rmdir($backend);
        }
    }
}
