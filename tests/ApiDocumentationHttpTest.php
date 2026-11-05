<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ApiDocumentationHttpTest extends WebTestCase
{
    public function testBothOpenApiDocumentsAreServedAsJson(): void
    {
        $client = static::createClient();

        foreach (['/api/doc.json', '/apibo/doc.json'] as $path) {
            $client->request('GET', $path);

            self::assertResponseIsSuccessful($path);
            $document = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
            self::assertIsArray($document);
            self::assertArrayHasKey('openapi', $document);
            self::assertSame('SymSvel API', $document['info']['title'] ?? null);
            self::assertArrayHasKey('paths', $document);
        }
    }

    public function testCorsPreflightAllowsConfiguredLocalOrigin(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/api/example', [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'content-type,authorization',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('http://localhost:5173', $client->getResponse()->headers->get('Access-Control-Allow-Origin'));
        self::assertStringContainsString('POST', (string) $client->getResponse()->headers->get('Access-Control-Allow-Methods'));
    }

    public function testSwaggerUiPagesAreServedForBothAreas(): void
    {
        $client = static::createClient();

        foreach (['/api/doc', '/apibo/doc'] as $path) {
            $client->request('GET', $path);
            self::assertResponseIsSuccessful($path);
            self::assertStringContainsString('swagger', strtolower((string) $client->getResponse()->getContent()));
        }
    }

    public function testCorsPreflightWorksForBackOfficeApi(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/apibo/example', [], [], [
            'HTTP_ORIGIN' => 'http://127.0.0.1:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSame('http://127.0.0.1:5173', $client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    public function testCorsDoesNotAllowUnconfiguredOrigin(): void
    {
        $client = static::createClient();
        $client->request('OPTIONS', '/apibo/example', [], [], [
            'HTTP_ORIGIN' => 'https://untrusted.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        self::assertNotSame('https://untrusted.example', $client->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }
}
