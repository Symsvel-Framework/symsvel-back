<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * Validates discrete PostgreSQL connection fields before producing a DBAL URL.
 * Passwords are never persisted outside the private backend .env.local.
 */
final class DatabaseConnectionConfig
{
    public function buildVerifiedUrl(string $host, string $port, string $database, string $user, string $password): string
    {
        $url = $this->buildUrl($host, $port, $database, $user, $password);
        try {
            $pdo = new \\PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s;connect_timeout=5', trim($host), (int) $port, trim($database)),
                trim($user),
                $password,
                [\\PDO::ATTR_ERRMODE => \\PDO::ERRMODE_EXCEPTION],
            );
            $versionNumber = $pdo->query('SHOW server_version_num')->fetchColumn();
            $version = self::normalizeServerVersion((string) $versionNumber);
        } catch (\\PDOException $exception) {
            throw new \\RuntimeException('Connexion PostgreSQL impossible. Vérifiez les paramètres et les droits de la base.', 0, $exception);
        }

        return $url.'?serverVersion='.rawurlencode($version);
    }

    public static function normalizeServerVersion(string $versionNumber): string
    {
        if (!preg_match('/^[0-9]{5,6}$/D', $versionNumber)) {
            throw new \\RuntimeException('Version PostgreSQL non reconnue.');
        }
        $number = (int) $versionNumber;
        $major = intdiv($number, 10000);
        $remainder = $number % 10000;
        if ($major >= 10) {
            return sprintf('%d.0.%d', $major, $remainder);
        }

        return sprintf('%d.%d.%d', $major, intdiv($remainder, 100), $remainder % 100);
    }

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
