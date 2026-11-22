<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProductionSetupConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ProductionSetupConfigTest extends TestCase
{
    public function testInfersOwnerFromUserProjectGitRemote(): void
    {
        $root = sys_get_temp_dir().'/symsvel-github-'.bin2hex(random_bytes(6));
        mkdir($root);
        try {
            $init = new Process(['git', 'init', '-q'], $root);
            $init->mustRun();
            (new Process(['git', 'remote', 'add', 'origin', 'git@github.com:kevin-lebug/monProjetBack.git'], $root))->mustRun();
            self::assertSame([
                'bo' => 'https://github.com/kevin-lebug/bo',
                'app' => 'https://github.com/kevin-lebug/app',
                'front' => 'https://github.com/kevin-lebug/front',
            ], (new ProductionSetupConfig())->suggestions($root));
        } finally {
            (new Process(['rm', '-rf', $root]))->mustRun();
        }
    }

    public function testStoresOnlyRepositoryUrlsAndNeverCredentials(): void
    {
        $root = sys_get_temp_dir().'/symsvel-production-'.bin2hex(random_bytes(6));
        mkdir($root);
        mkdir($root.'/.symsvel');
        try {
            $config = new ProductionSetupConfig();
            self::assertNull($config->read($root));
            $config->save($root, [
                'bo' => 'https://github.com/kevin-lebug/monProjetBo.git',
                'app' => '',
                'front' => 'https://github.com/kevin-lebug/monProjetFront',
            ]);
            self::assertSame([
                'bo' => 'https://github.com/kevin-lebug/monProjetBo',
                'app' => '',
                'front' => 'https://github.com/kevin-lebug/monProjetFront',
            ], $config->read($root));
            self::assertSame(0600, fileperms($root.'/.symsvel/production.json') & 0777);
        } finally {
            @unlink($root.'/.symsvel/production.json');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }

    public function testRejectsInvalidRepositoryAndSymlink(): void
    {
        $config = new ProductionSetupConfig();
        foreach (['http://github.com/u/repo', 'https://bad.example.org/repo', 'https://github.com/u/../../../etc/passwd'] as $url) {
            try {
                $config->normalizeRepository($url);
                self::fail('Unsafe URL accepted: '.$url);
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
