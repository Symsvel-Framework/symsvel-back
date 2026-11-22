<?php

declare(strict_types=1);

namespace App\Tests\Setup;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ProductionSetupRoutesTest extends KernelTestCase
{
    public function testBothSetupModesAreRoutedToTheSameController(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        self::assertNotNull($routes->get('symsvel_setup'));
        self::assertNotNull($routes->get('symsvel_prod_settings'));
        self::assertNotNull($routes->get('symsvel_setup_admin'));
        self::assertNotNull($routes->get('symsvel_prod_settings_admin'));

        self::assertSame('/prod_settings', $routes->get('symsvel_prod_settings')->getPath());
        self::assertSame('/prod_settings/admin', $routes->get('symsvel_prod_settings_admin')->getPath());
        self::assertSame($routes->get('symsvel_setup')->getDefault('_controller'), $routes->get('symsvel_prod_settings')->getDefault('_controller'));
        self::assertSame($routes->get('symsvel_setup_admin')->getDefault('_controller'), $routes->get('symsvel_prod_settings_admin')->getDefault('_controller'));
    }
}
