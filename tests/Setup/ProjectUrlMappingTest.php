<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\ProjectUrlMapping;
use PHPUnit\Framework\TestCase;

final class ProjectUrlMappingTest extends TestCase
{
    public function testMapsManagerChoralysToAppWithoutAssumingItsSubdomain(): void
    {
        $service = new ProjectUrlMapping();
        $urls = $service->normalize([
            'back' => 'https://api.choralys.com',
            'bo' => 'https://admin.choralys.com',
            'app' => 'https://manager.choralys.com',
            'front' => 'https://www.choralys.com',
        ], true);
        self::assertSame('https://manager.choralys.com', $urls['app']);
        $regex = '~'.$service->corsRegex($urls).'~D';
        foreach (['https://admin.choralys.com', 'https://manager.choralys.com', 'https://www.choralys.com'] as $origin) {
            self::assertSame(1, preg_match($regex, $origin), $origin);
        }
        foreach (['https://api.choralys.com', 'https://app.choralys.com', 'http://manager.choralys.com',
            'https://manager.choralys.com.evil.org', 'https://manager.choralys.com:443',
            'https://manager.choralys.com/path'] as $origin) {
            self::assertSame(0, preg_match($regex, $origin), $origin);
        }
        $env = $service->environment($urls, 'choralys.com');
        self::assertSame('https://manager.choralys.com', $env['SYMSVEL_APP_ORIGIN']);
        self::assertSame('https://admin.choralys.com', $env['SYMSVEL_BO_ORIGIN']);
        self::assertSame($service->corsRegex($urls), $env['SYMSVEL_CORS_ALLOW_ORIGIN']);
    }

    public function testOptionalScopesAreNotGrantedCorsAccessWhenAbsent(): void
    {
        $service = new ProjectUrlMapping();
        $urls = $service->normalize([
            'back' => 'https://api.example.fr', 'bo' => 'https://bo.example.fr',
            'app' => '', 'front' => '',
        ], true);
        $regex = '~'.$service->corsRegex($urls).'~D';
        self::assertSame(1, preg_match($regex, 'https://bo.example.fr'));
        self::assertSame(0, preg_match($regex, 'https://app.example.fr'));
    }

    public function testLocalExplicitOriginsAndPorts(): void
    {
        $service = new ProjectUrlMapping();
        $urls = $service->normalize([
            'back' => 'http://backend.xt.localhost:8000',
            'bo' => 'http://admin.xt.localhost:5173',
            'app' => 'http://manager.xt.localhost:5174',
            'front' => 'http://xt.localhost:5175',
        ], false);
        $regex = '~'.$service->corsRegex($urls).'~D';
        self::assertSame(1, preg_match($regex, 'http://manager.xt.localhost:5174'));
        self::assertSame(0, preg_match($regex, 'http://manager.xt.localhost:5175'));
        self::assertSame(0, preg_match($regex, 'https://manager.xt.localhost:5174'));
    }

    public function testRejectsNonOriginsAndInsecureProductionUrls(): void
    {
        $service = new ProjectUrlMapping();
        $base = ['back' => 'https://backend.example.fr', 'bo' => 'https://bo.example.fr', 'app' => '', 'front' => ''];
        foreach (['https://bo.example.fr/path', 'https://bo.example.fr/', 'https://user@bo.example.fr',
            'https://bo.example.fr?x=1', 'https://bo.example.fr:99999', 'http://bo.example.fr',
            'https://bad..example.fr', 'javascript:alert(1)'] as $invalid) {
            try {
                $service->normalize(array_replace($base, ['bo' => $invalid]), true);
                self::fail('Accepted invalid origin '.$invalid);
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }
}
