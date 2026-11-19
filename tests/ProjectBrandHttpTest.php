<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProjectBrandHttpTest extends WebTestCase
{
    public function testPublicBrandReturnsOnlyTheProjectName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/apiopen/project');
        self::assertResponseIsSuccessful();
        $body = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(['data'], array_keys($body));
        self::assertSame(['name'], array_keys($body['data']));
        self::assertNotSame('', trim($body['data']['name']));
        self::assertStringContainsString('no-store', (string) $client->getResponse()->headers->get('Cache-Control'));
    }
}
