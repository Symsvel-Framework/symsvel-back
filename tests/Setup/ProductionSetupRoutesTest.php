<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use App\Setup\StandaloneSetup;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ProductionSetupRoutesTest extends KernelTestCase
{
    public function testNativeSetupPathsAreNotExposedAsSymfonyRoutes(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        foreach ([
            'symsvel_setup' => '/setup',
            'symsvel_prod_settings' => '/prod_settings',
            'symsvel_setup_admin' => '/setup/admin',
            'symsvel_prod_settings_admin' => '/prod_settings/admin',
        ] as $name => $path) {
            self::assertNull($routes->get($name), $name.' must not boot Symfony for installation.');
            self::assertTrue(StandaloneSetup::isSetupPath($path), $path);
        }
    }
}
