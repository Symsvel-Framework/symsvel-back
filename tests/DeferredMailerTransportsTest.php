<?php

declare(strict_types=1);

namespace App\Tests;

use App\Mailer\ConfiguredTransportFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

final class DeferredMailerTransportsTest extends KernelTestCase
{
    public function testRealContainerBuildsNamedTransportsWithoutOptionalCredentials(): void
    {
        $previous = [];
        foreach (['MAILER_DSN', 'MAILTRAP_DSN'] as $key) {
            $previous[$key] = [$_ENV[$key] ?? null, $_SERVER[$key] ?? null, getenv($key)];
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
        }
        try {
            self::bootKernel();
            $transports = self::getContainer()->get('mailer.transports');
            self::assertInstanceOf(TransportInterface::class, $transports);
            self::assertSame('[smtp,mailpit,mailhog,smtp4dev,mailtrap]', (string) $transports);
            $this->expectException(TransportException::class);
            $this->expectExceptionMessage('MAILTRAP_DSN is not configured.');
            $transports->send($this->message('mailtrap'));
        } finally {
            self::ensureKernelShutdown();
            foreach ($previous as $key => [$env, $server, $process]) {
                if ($env !== null) {
                    $_ENV[$key] = $env;
                }
                if ($server !== null) {
                    $_SERVER[$key] = $server;
                }
                putenv($process === false ? $key : $key.'='.$process);
            }
        }
    }

    public function testUnusedMissingOrInvalidDsnDoesNotPreventDeliveryOnAnotherTransport(): void
    {
        $transports = $this->named(null, 'invalid-dsn');
        // The native null transport stands in for Mailpit, avoiding network I/O.
        $message = $this->message('mailpit');
        $transports->send($message);
        self::assertFalse($message->getHeaders()->has('X-Transport'));
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('MAILTRAP_DSN contains an invalid mail transport DSN.');
        $transports->send($this->message('mailtrap'));
    }

    public function testSelectingMissingSmtpFailsExplicitly(): void
    {
        $transports = $this->named(null, null);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('MAILER_DSN is not configured.');
        $transports->send($this->message('smtp'));
    }

    public function testConfiguredTransportDelegatesToNativeSymfonyFactory(): void
    {
        $transports = $this->named('null://null', null);
        $message = $this->message('smtp');
        $transports->send($message);
        self::assertFalse($message->getHeaders()->has('X-Transport'));
    }

    private function named(?string $smtp, ?string $mailtrap): TransportInterface
    {
        $factory = null;
        $deferred = new ConfiguredTransportFactory(static function () use (&$factory): Transport {
            return $factory;
        }, $smtp, $mailtrap);
        $factory = new Transport([$deferred, ...Transport::getDefaultFactories()]);
        return $factory->fromStrings([
            'smtp' => 'symsvel-configured://smtp',
            'mailpit' => 'null://null',
            'mailtrap' => 'symsvel-configured://mailtrap',
        ]);
    }

    private function message(string $name): Email
    {
        $message = (new Email())->from('sender@example.test')->to('recipient@example.test')->subject('Test')->text('Test');
        $message->getHeaders()->addTextHeader('X-Transport', $name);
        return $message;
    }
}
