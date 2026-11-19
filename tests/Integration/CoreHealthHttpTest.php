<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use SymSvel\Core\Controller\HealthController;

final class CoreHealthHttpTest extends WebTestCase
{
    public function testNativeCoreLivenessIsAccessibleThroughBack(): void
    {
        if (!class_exists(HealthController::class)) {
            self::markTestSkipped('This test runs against Core dev in the dedicated Back integration CI step.');
        }

        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseStatusCodeSame(200);
        self::assertSame(
            ['data' => ['status' => 'ok']],
            json_decode((string) $client->getResponse()->getContent(), true, 16, JSON_THROW_ON_ERROR),
        );
        self::assertStringContainsString(
            'no-store',
            (string) $client->getResponse()->headers->get('Cache-Control'),
        );
    }
}
