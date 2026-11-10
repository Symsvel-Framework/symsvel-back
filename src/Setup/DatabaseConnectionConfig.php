<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * Validates discrete PostgreSQL connection fields before producing a DBAL URL.
 * Passwords are never persisted outside the private backend .env.local.
 */
final class DatabaseConnectionConfig
{
    /** Build a Doctrine DBAL URL for the selected engine. */
    public function buildSelectedUrl(string $driver, string $host, string $port, string $database, string $user, string $password, string $sqlitePath = ''): string
    {
        if ($driver === 'postgresql') {
            return $this->buildVerifiedUrl($host, $port, $database, $user, $password);
        }
        if ($driver === 'sqlite') {
            if ($sqlitePath === '' || str_contains($sqlitePath, "\0") || str_contains($sqlitePath, "\n") || str_contains($sqlitePath, "\r") || str_contains($sqlitePath, '..') || str_starts_with($sqlitePath, '/') || !preg_match('~^[a-zA-Z0-9_./-]+$~D', $sqlitePath)) {
                throw new \InvalidArgumentException('Chemin SQLite invalide.');
            }
            return 'sqlite:///%kernel.project_dir%/'.ltrim($sqlitePath, '/');
        }
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new \InvalidArgumentException('SGBD non pris en charge.');
        }
        $host = trim($host);
        $user = trim($user);
        $database = trim($database);
        if ($host === '' || strlen($host) > 253 || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.-]*$/D', $host) !== 1 || str_contains($host, '..')
            || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535
            || $user === '' || $database === '' || strlen($user) > 128 || strlen($database) > 128
            || preg_match('/[\x00-\x1F\x7F]/', $user.$database.$password) === 1) {
            throw new \InvalidArgumentException('Paramètres MySQL/MariaDB invalides.');
        }
        try {
            $pdo = new \PDO(sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, (int) $port), $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 5]);
            $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (\PDOException $exception) {
            throw new \RuntimeException('Connexion MySQL/MariaDB impossible.', 0, $exception);
        }
        $isMaria = stripos($version, 'mariadb') !== false;
        if (($driver === 'mariadb') !== $isMaria) {
            throw new \RuntimeException('Le serveur détecté ne correspond pas au SGBD sélectionné.');
        }
        if (!preg_match_all('/(\d+\.\d+\.\d+)/', $version, $matches) || $matches[1] === []) {
            throw new \RuntimeException('Version MySQL/MariaDB non reconnue.');
        }
        // MariaDB may report the compatibility prefix 5.5.5-10.x.y-MariaDB.
        $detectedVersion = $isMaria ? end($matches[1]) : $matches[1][0];
        $serverVersion = ($isMaria ? 'mariadb-' : '').$detectedVersion;
        $credentials = rawurlencode($user).($password === '' ? '' : ':'.rawurlencode($password));
        return sprintf('mysql://%s@%s:%d/%s?serverVersion=%s&charset=utf8mb4', $credentials, $host, (int) $port, rawurlencode($database), rawurlencode($serverVersion));
    }

    public function buildVerifiedUrl(string $host, string $port, string $database, string $user, string $password): string
    {
        $url = $this->buildUrl($host, $port, $database, $user, $password);
        try {
            $pdo = new \PDO(
                sprintf('pgsql:host=%s;port=%d;dbname=%s;connect_timeout=5', trim($host), (int) $port, 'postgres'),
                trim($user),
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
            );
            $versionNumber = $pdo->query('SHOW server_version_num')->fetchColumn();
            $version = self::normalizeServerVersion((string) $versionNumber);
        } catch (\PDOException $exception) {
            throw new \RuntimeException('Connexion PostgreSQL impossible. Vérifiez les paramètres et les droits de la base.', 0, $exception);
        }

        return $url.'?serverVersion='.rawurlencode($version);
    }

    public static function normalizeServerVersion(string $versionNumber): string
    {
        if (!preg_match('/^[0-9]{5,6}$/D', $versionNumber)) {
            throw new \RuntimeException('Version PostgreSQL non reconnue.');
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
