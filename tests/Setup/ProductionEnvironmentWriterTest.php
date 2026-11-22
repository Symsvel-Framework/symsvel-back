<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProductionEnvironmentWriter;
use PHPUnit\Framework\TestCase;

final class ProductionEnvironmentWriterTest extends TestCase
{
    public function testCreatesBothEnvironmentFilesAndPreservesSecretOnRetry(): void
    {
        $root = sys_get_temp_dir().'/symsvel-prod-env-'.bin2hex(random_bytes(6));
        mkdir($root);
        mkdir($root.'/backend');
        try {
            $origins = [
                'back' => 'https://backend.choralys.com',
                'bo' => 'https://bo.choralys.com',
                'app' => 'https://manager.choralys.com',
                'front' => 'https://www.choralys.com',
            ];
            $writer = new ProductionEnvironmentWriter();
            $writer->write($root.'/backend', 'postgresql://db:pass@localhost:5432/site', 'choralys.com', $origins);
            $envFile = $root.'/backend/.env.local';
            $secretsFile = $root.'/secrets/.env';
            $initial = (string) file_get_contents($envFile);
            self::assertSame($initial, file_get_contents($secretsFile));
            self::assertStringContainsString('APP_ENV="prod"', $initial);
            self::assertStringContainsString('SYMSVEL_APP_ORIGIN="https://manager.choralys.com"', $initial);
            self::assertStringContainsString('SYMSVEL_BO_ORIGIN="https://bo.choralys.com"', $initial);
            self::assertStringContainsString('SYMSVEL_BACK_ORIGIN="https://backend.choralys.com"', $initial);
            self::assertStringContainsString('SYMSVEL_FRONT_ORIGIN="https://www.choralys.com"', $initial);
            self::assertStringContainsString('DATABASE_URL="postgresql://db:pass@localhost:5432/site"', $initial);
            self::assertMatchesRegularExpression('/APP_SECRET="[0-9a-f]{64}"/', $initial);
            self::assertSame(0600, fileperms($envFile) & 0777);
            self::assertSame(0600, fileperms($secretsFile) & 0777);

            $writer->write($root.'/backend', 'postgresql://db:changed@localhost:5432/site', 'choralys.com', $origins);
            $updated = (string) file_get_contents($envFile);
            preg_match('/APP_SECRET="[0-9a-f]{64}"/', $initial, $one);
            preg_match('/APP_SECRET="[0-9a-f]{64}"/', $updated, $two);
            self::assertSame($one[0], $two[0]);
            self::assertSame(1, preg_match_all('/^DATABASE_URL=/m', $updated));
            self::assertSame($updated, file_get_contents($secretsFile));
        } finally {
            @unlink($root.'/backend/.env.local');
            @unlink($root.'/secrets/.env');
            @rmdir($root.'/secrets');
            @rmdir($root.'/backend');
            @rmdir($root);
        }
    }
}
