<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProjectMetadata;
use PHPUnit\Framework\TestCase;

final class ProjectMetadataTest extends TestCase
{
    public function testWritesPrivateMetadataAndSupportsRetry(): void
    {
        $root = sys_get_temp_dir().'/symsvel-project-'.bin2hex(random_bytes(8));
        mkdir($root);
        mkdir($root.'/.symsvel');
        try {
            $metadata = new ProjectMetadata();
            $metadata->save($root, '  Mon projet  ');
            $path = $root.'/.symsvel/project.json';
            self::assertSame(['schema' => 1, 'name' => 'Mon projet'], json_decode((string) file_get_contents($path), true));
            clearstatcache(true, $path);
            self::assertSame(0600, fileperms($path) & 0777);
            $metadata->save($root, 'Projet repris');
            self::assertSame('Projet repris', json_decode((string) file_get_contents($path), true)['name']);
            clearstatcache(true, $path);
            self::assertSame(0600, fileperms($path) & 0777);
        } finally {
            @unlink($root.'/.symsvel/project.json');
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
        $metadata = new ProjectMetadata();
        $metadata->validate(str_repeat('é', 120));
        self::assertTrue(true, 'A valid 120-character Unicode name is accepted.');
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
            (new ProjectMetadata())->save($root, 'Mon projet');
        } finally {
            @unlink($root.'/.symsvel/project.json');
            @unlink($root.'/target');
            @rmdir($root.'/.symsvel');
            @rmdir($root);
        }
    }
}
