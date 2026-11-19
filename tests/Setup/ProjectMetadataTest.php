<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProjectMetadata;
use PHPUnit\Framework\TestCase;

final class ProjectMetadataTest extends TestCase
{
    public function testPublicNameNeverExposesPrivateProjectMetadata(): void
    {
        $root = sys_get_temp_dir().'/symsvel-public-brand-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        try {
            $metadata = new ProjectMetadata();
            self::assertSame('SymSvel', $metadata->publicName($root));
            $metadata->save($root, '  Projet Démo ', 'private.localhost');
            self::assertSame('Projet Démo', $metadata->publicName($root));
            $path = $root.'/.symsvel/project.json';
            file_put_contents($path, json_encode([
                'schema' => 1, 'name' => ['invalid'], 'channel' => 'dev',
                'local_domain' => 'private.localhost',
            ], JSON_THROW_ON_ERROR));
            self::assertSame('SymSvel', $metadata->publicName($root));
        } finally {
            @unlink($root.'/.symsvel/project.json');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }

    public function testWritesPrivateMetadataAndSupportsRetry(): void
    {
        $root = sys_get_temp_dir().'/symsvel-project-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        try {
            $metadata = new ProjectMetadata();
            $metadata->save($root, '  Mon projet  ', 'XT.Localhost');
            $path = $root.'/.symsvel/project.json';
            self::assertSame(['schema' => 1, 'name' => 'Mon projet', 'local_domain' => 'xt.localhost'], json_decode((string) file_get_contents($path), true));
            clearstatcache(true, $path);
            self::assertSame(0600, fileperms($path) & 0777);
            $metadata->save($root, 'Projet repris', 'nouveau.localhost');
            self::assertSame('Projet repris', json_decode((string) file_get_contents($path), true)['name']);
            self::assertSame('nouveau.localhost', json_decode((string) file_get_contents($path), true)['local_domain']);
            clearstatcache(true, $path);
            self::assertSame(0600, fileperms($path) & 0777);
        } finally {
            @unlink($root.'/.symsvel/project.json');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }

    public function testPreservesCliChannelWhenSavingSetupMetadata(): void
    {
        $root = sys_get_temp_dir().'/symsvel-project-channel-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        $path = $root.'/.symsvel/project.json';
        try {
            file_put_contents($path, json_encode(['schema' => 1, 'channel' => 'dev'], JSON_THROW_ON_ERROR));
            (new ProjectMetadata())->save($root, 'Mon projet', 'xt.localhost');
            self::assertSame(
                ['schema' => 1, 'channel' => 'dev', 'name' => 'Mon projet', 'local_domain' => 'xt.localhost'],
                json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR),
            );
        } finally {
            @unlink($path);
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }

    public function testRejectsInvalidName(): void
    {
        $metadata = new ProjectMetadata();
        foreach (['', '   ', str_repeat('x', 121), "invalid\nname", "\xFF"] as $name) {
            try {
                $metadata->validate($name);
                self::fail('Invalid project name was accepted.');
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testAcceptsUnicodeNameWithoutMbstring(): void
    {
        $name = str_repeat('é', 120);
        (new ProjectMetadata())->validate($name);
        self::assertSame(120, preg_match_all('/./us', $name));
    }

    public function testValidatesLocalDomain(): void
    {
        $metadata = new ProjectMetadata();
        foreach (['localhost', 'xt.localhost', 'xt.local', 'app.dev.test'] as $domain) {
            $metadata->validateLocalDomain($domain);
        }
        foreach (['', 'https://xt.local', 'xt.local:8080', '-invalid.local', 'bad..local', 'bad/path', str_repeat('a', 254)] as $domain) {
            try {
                $metadata->validateLocalDomain($domain);
                self::fail('Invalid local domain accepted: '.$domain);
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRejectsSymlinkedMetadata(): void
    {
        $root = sys_get_temp_dir().'/symsvel-project-link-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        file_put_contents($root.'/target', 'untouched');
        symlink($root.'/target', $root.'/.symsvel/project.json');
        try {
            $this->expectException(\RuntimeException::class);
            (new ProjectMetadata())->save($root, 'Mon projet', 'xt.localhost');
        } finally {
            @unlink($root.'/.symsvel/project.json');
            @unlink($root.'/target');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }
}
