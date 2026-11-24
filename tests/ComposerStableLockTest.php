<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;

final class ComposerStableLockTest extends TestCase
{
    public function testNativeStableDependenciesAreCurrentAndLockIsFresh(): void
    {
        $root = dirname(__DIR__);
        $composer = json_decode(
            (string) file_get_contents($root.'/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $lock = json_decode(
            (string) file_get_contents($root.'/composer.lock'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertSame('^1.2.1', $composer['require']['symsvel-framework/core']);
        self::assertSame('^1.4', $composer['require-dev']['symsvel-framework/tooling']);

        // Equivalent to Composer\Package\Locker::getContentHash(), using
        // Composer's JsonFile::encode($relevantContent, 0) representation.
        $keys = array_fill_keys([
            'name', 'version', 'require', 'require-dev', 'conflict',
            'replace', 'provide', 'minimum-stability', 'prefer-stable',
            'repositories', 'extra',
        ], true);
        $relevant = array_intersect_key($composer, $keys);
        if (isset($composer['config']['platform'])) {
            $relevant['config']['platform'] = $composer['config']['platform'];
        }
        ksort($relevant);
        self::assertSame(md5(json_encode($relevant, JSON_THROW_ON_ERROR)), $lock['content-hash']);

        $packages = array_merge($lock['packages'], $lock['packages-dev']);
        foreach (['symsvel-framework/core', 'symsvel-framework/tooling'] as $name) {
            $matches = array_values(array_filter(
                $packages,
                static fn (array $package): bool => ($package['name'] ?? null) === $name,
            ));
            self::assertCount(1, $matches, $name.' absent du lock');
            $package = $matches[0];
            $version = $package['version'];
            self::assertMatchesRegularExpression('/^v\d+\.\d+\.\d+$/D', $version);
            $minimum = $name === 'symsvel-framework/tooling' ? '1.4.0' : '1.2.1';
            self::assertTrue(version_compare(ltrim($version, 'v'), $minimum, '>='), $name.' trop ancien : '.$version);
            self::assertSame($package['source']['reference'], $package['dist']['reference']);
            $slug = substr($name, strlen('symsvel-framework/'));
            self::assertSame(
                'https://api.github.com/repos/Symsvel-Framework/symsvel-'.$slug.'/zipball/'.$package['source']['reference'],
                $package['dist']['url'],
            );
        }
    }
}
