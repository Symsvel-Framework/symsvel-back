<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ApiInfrastructureTest extends KernelTestCase
{
    public function testSwaggerRoutesAreRegisteredForBothApiAreas(): void
    {
        self::bootKernel();
        $router = static::getContainer()->get(RouterInterface::class);

        foreach ([
            '/api/doc' => 'app.swagger_ui',
            '/api/doc.json' => 'app.swagger',
            '/apibo/doc' => 'app.apibo_swagger_ui',
            '/apibo/doc.json' => 'app.apibo_swagger',
        ] as $path => $routeName) {
            $match = $router->match($path);
            self::assertSame($routeName, $match['_route'], $path);
        }
    }

    public function testBothNelmioBundlesAreEnabled(): void
    {
        self::bootKernel();

        self::assertTrue(static::$kernel->getBundle('NelmioApiDocBundle') instanceof \Nelmio\ApiDocBundle\NelmioApiDocBundle);
        self::assertTrue(static::$kernel->getBundle('NelmioCorsBundle') instanceof \Nelmio\CorsBundle\NelmioCorsBundle);
    }
}
