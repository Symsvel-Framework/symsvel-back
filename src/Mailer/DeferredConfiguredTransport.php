<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/** Do not parse or initialize an optional SMTP DSN until its named transport is used. */
#[Exclude]
final class DeferredConfiguredTransport implements TransportInterface
{
    private ?TransportInterface $transport = null;

    public function __construct(
        private readonly string $variable,
        #[\SensitiveParameter] private readonly ?string $dsn,
        private readonly \Closure $factory,
    ) {
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($this->transport === null) {
            if ($this->dsn === null || trim($this->dsn) === '') {
                throw new TransportException($this->variable.' is not configured.');
            }
            // A configured DSN must not recursively select the deferred wrapper.
            if (stripos($this->dsn, 'symsvel-configured://') !== false) {
                throw new TransportException($this->variable.' contains an invalid recursive transport.');
            }
            try {
                $this->transport = ($this->factory)()->fromString($this->dsn);
            } catch (\Exception) {
                // DSNs can contain credentials: expose only the configuration key.
                throw new TransportException($this->variable.' contains an invalid mail transport DSN.');
            }
        }

        return $this->transport->send($message, $envelope);
    }

    public function __toString(): string
    {
        return 'configured:'.$this->variable;
    }
}
