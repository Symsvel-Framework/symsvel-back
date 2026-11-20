<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\LocalCorsOrigins;
use PHPUnit\Framework\TestCase;

final class LocalCorsOriginsTest extends TestCase
{
    public function testAllowsOnlyLocalSvelteOrigins(): void
    {
        $regex = '~'.(new LocalCorsOrigins())->fromDomain('xt.local').'~D';
        foreach (['http://bo.xt.local', 'http://app.xt.local', 'http://xt.local', 'http://www.xt.local', 'https://www.xt.local', 'https://bo.xt.local', 'http://bo.xt.local:5173'] as $origin) {
            self::assertSame(1, preg_match($regex, $origin), $origin);
        }
        foreach (['http://front.xt.local', 'http://backend.xt.local', 'http://evil.xt.local', 'http://bo.xt.local.evil.org', 'https://bo-xt.local', 'http://boXxt.local'] as $origin) {
            self::assertSame(0, preg_match($regex, $origin), $origin);
        }
    }

    public function testEscapesRegexCharactersInDomain(): void
    {
        $regex = '~'.(new LocalCorsOrigins())->fromDomain('example.local').'~D';
        self::assertSame(0, preg_match($regex, 'http://bo.exampleXlocal'));
    }

    public function testRejectsInvalidDomain(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new LocalCorsOrigins())->fromDomain('bad/domain');
    }
}
