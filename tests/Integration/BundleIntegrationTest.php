<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use SymSvel\Core\SymSvelCoreBundle;
use SymSvel\Tooling\SymSvelToolingBundle;

final class BundleIntegrationTest extends TestCase
{
    public function testCoreAndToolingAreRegisteredAndInstallCommandIsAvailable(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        try {
            $bundles = $kernel->getBundles();

            self::assertInstanceOf(SymSvelCoreBundle::class, $bundles['SymSvelCoreBundle'] ?? null);
            self::assertInstanceOf(SymSvelToolingBundle::class, $bundles['SymSvelToolingBundle'] ?? null);

            $application = new Application($kernel);
            self::assertTrue($application->has('symsvel:install'));
        } finally {
            $kernel->shutdown();
        }
    }
}
