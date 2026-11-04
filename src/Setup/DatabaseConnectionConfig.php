<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * Validates discrete PostgreSQL connection fields before producing a DBAL URL.
 * Passwords are never persisted outside the private backend .env.local.
 */
final class DatabaseConnectionConfig
{
    public function buildUrl(string $host, string $port, string $database, string $user, string $password): string
    {
        $host = trim($host);
        $port = trim($port);
        $database = trim($database);
        $user = trim($user);

        if ($host === '' || strlen($host) > 253
            || preg_match('/^(?=.{1,253}$)[a-zA-Z0-9](?:[a-zA-Z0-9.-]*[a-zA-Z0-9])?$/D', $host) !== 1
            || str_contains($host, '..')) {
            throw new \InvalidArgumentException('Hôte PostgreSQL invalide.');
        }
        if (!ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            throw new \InvalidArgumentException('Port PostgreSQL invalide.');
        }
        foreach (['nom de la base' => $database, 'utilisateur' => $user] as $label => $value) {
            if ($value === '' || strlen($value) > 128 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                throw new \InvalidArgumentException('Champ PostgreSQL invalide : '.$label.'.');
            }
        }
        if (str_contains($password, "\0") || str_contains($password, "\n") || str_contains($password, "\r")) {
            throw new \InvalidArgumentException('Mot de passe PostgreSQL invalide.');
        }

        $credentials = rawurlencode($user);
        if ($password !== '') {
            $credentials .= ':'.rawurlencode($password);
        }

        return sprintf('postgresql://%s@%s:%d/%s', $credentials, $host, (int) $port, rawurlencode($database));
    }
}
