<?php

declare(strict_types=1);

namespace App\Mailer;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

/** Native Symfony transport registration, with optional DSNs resolved only for delivery. */
final class ConfiguredTransportFactory extends AbstractTransportFactory
{
    public function __construct(
        #[AutowireServiceClosure('mailer.transport_factory')] private readonly \Closure $factory,
        #[Autowire('%env(default::MAILER_DSN)%')] #[\SensitiveParameter] private readonly ?string $smtpDsn,
        #[Autowire('%env(default::MAILTRAP_DSN)%')] #[\SensitiveParameter] private readonly ?string $mailtrapDsn,
    ) {
        parent::__construct();
    }

    public function create(Dsn $dsn): TransportInterface
    {
        if (!$this->supports($dsn)) {
            throw new \InvalidArgumentException('Unsupported configured mail transport.');
        }
        return match ($dsn->getHost()) {
            'smtp' => new DeferredConfiguredTransport('MAILER_DSN', $this->smtpDsn, $this->factory),
            'mailtrap' => new DeferredConfiguredTransport('MAILTRAP_DSN', $this->mailtrapDsn, $this->factory),
            default => throw new \InvalidArgumentException('Unknown configured mail transport.'),
        };
    }

    protected function getSupportedSchemes(): array
    {
        return ['symsvel-configured'];
    }
}
