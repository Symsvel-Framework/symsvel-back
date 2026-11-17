<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/** Keep email delivery synchronous without changing unrelated Messenger queues. */
final class SynchronousMailerRoutingTest extends TestCase
{
    public function testMailerIsSynchronousAndOtherMessengerMessagesRemainAsync(): void
    {
        $config = Yaml::parseFile(dirname(__DIR__).'/config/packages/messenger.yaml');
        $messenger = $config['framework']['messenger'];
        self::assertSame('sync://', $messenger['transports']['sync']);
        self::assertSame(
            'sync',
            $messenger['routing']['Symfony\\Component\\Mailer\\Messenger\\SendEmailMessage'],
        );
        self::assertSame(
            'async',
            $messenger['routing']['Symfony\\Component\\Notifier\\Message\\ChatMessage'],
        );
        self::assertSame(
            'async',
            $messenger['routing']['Symfony\\Component\\Notifier\\Message\\SmsMessage'],
        );
        self::assertSame('%env(MESSENGER_TRANSPORT_DSN)%', $messenger['transports']['async']['dsn']);
    }
}
